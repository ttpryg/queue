<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Handlers;

use Ttpryg\Queue\Config\QueueConfig;
use Ttpryg\Queue\Contracts\DatabaseAdapterInterface;
use Ttpryg\Queue\Contracts\QueueInterface;
use Ttpryg\Queue\Entities\QueueJob;
use Ttpryg\Queue\Entities\PayloadMetadata;
use Ttpryg\Queue\Entities\QueuePushResult;
use Ttpryg\Queue\Enums\Status;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Throwable;

class DatabaseHandler implements QueueInterface
{
    protected ?int $delay = null;
    protected ?int $priority = null;
    protected string $table = 'queue_jobs';

    public function __construct(
        protected QueueConfig $config,
        protected DatabaseAdapterInterface $db,
        protected ?LoggerInterface $logger = null
    ) {
        $this->table = $config->database['table'] ?? 'queue_jobs';
    }

    public function name(): string
    {
        return 'database';
    }

    public function push(string $queue, string $job, array $data, ?PayloadMetadata $metadata = null): QueuePushResult
    {
        $this->validateJobAndPriority($queue, $job);

        $insertData = [
            'queue' => $queue,
            'job' => $job,
            'payload' => json_encode([
                'job' => $job,
                'data' => $data,
                'metadata' => $metadata?->toArray(),
            ]),
            'status' => Status::Waiting->value,
            'priority' => $this->priority ?? 0,
            'attempts' => 0,
            'available_at' => $this->delay !== null
                ? date('Y-m-d H:i:s', time() + $this->delay)
                : date('Y-m-d H:i:s'),
            'reserved_at' => null,
            'reserved_by' => null,
            'finished_at' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        try {
            $insertId = $this->db->insert($this->table, $insertData);

            $this->delay = null;
            $this->priority = null;

            return QueuePushResult::success((string) $insertId);
        } catch (Throwable $e) {
            $this->logger?->error('Failed to push job: ' . $e->getMessage());
            return QueuePushResult::failure($e->getMessage());
        }
    }

    public function pop(string $queue, array $priorities = []): ?QueueJob
    {
        $now = date('Y-m-d H:i:s');
        $row = $this->db->fetchWaitingJob($this->table, $queue, $priorities);

        if ($row === null) {
            return null;
        }

        $updateData = [
            'status' => Status::Reserved->value,
            'reserved_at' => $now,
            'reserved_by' => gethostname(),
            'attempts' => ((int) ($row->attempts ?? 0)) + 1,
            'updated_at' => $now,
        ];

        $this->db->update($this->table, $updateData, ['id' => $row->id]);

        return QueueJob::fromObject($row);
    }

    public function later(QueueJob $queueJob, int $seconds): bool
    {
        $availableAt = date('Y-m-d H:i:s', time() + $seconds);

        return $this->db->update(
            $this->table,
            [
                'available_at' => $availableAt,
                'status' => Status::Waiting->value,
                'reserved_at' => null,
                'reserved_by' => null,
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            ['id' => $queueJob->id]
        );
    }

    public function failed(QueueJob $queueJob, Throwable $err, bool $keepJob = true): bool
    {
        $updateData = [
            'status' => Status::Failed->value,
            'finished_at' => date('Y-m-d H:i:s'),
            'exception' => $err->getMessage(),
            'trace' => $err->getTraceAsString(),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $result = $this->db->update($this->table, $updateData, ['id' => $queueJob->id]);

        if (!$keepJob) {
            $this->db->delete($this->table, ['id' => $queueJob->id]);
        }

        return $result;
    }

    public function done(QueueJob $queueJob): bool
    {
        return $this->db->update(
            $this->table,
            [
                'status' => Status::Done->value,
                'finished_at' => date('Y-m-d H:i:s'),
                'reserved_at' => null,
                'reserved_by' => null,
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            ['id' => $queueJob->id]
        );
    }

    public function clear(?string $queue = null): bool
    {
        $where = [
            'status' => [Status::Waiting->value, Status::Reserved->value],
        ];

        if ($queue !== null) {
            $where['queue'] = $queue;
        }

        return $this->db->delete($this->table, $where) > 0;
    }

    public function getQueues(): array
    {
        return array_keys($this->config->handlers);
    }

    public function priority(int $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    public function delay(int $delay): self
    {
        $this->delay = $delay;
        return $this;
    }

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    public function setDelay(int $delay): self
    {
        $this->delay = $delay;
        return $this;
    }

    public function listFailed(?string $queue = null): array
    {
        $rows = $this->db->fetchFailed($this->table, $queue);

        $results = [];
        foreach ($rows as $row) {
            $results[] = QueueJob::fromObject($row);
        }

        return $results;
    }

    public function retry(?int $id, ?string $queue = null): int
    {
        $jobs = $this->db->fetchFailed($this->table, $queue, $id);

        $count = 0;
        foreach ($jobs as $job) {
            $payload = json_decode($job->payload, true);

            $this->push(
                $job->queue,
                $job->job,
                $payload['data'] ?? [],
                isset($payload['metadata'])
                    ? PayloadMetadata::fromArray($payload['metadata'])
                    : null
            );

            $this->db->delete($this->table, ['id' => $job->id]);
            $count++;
        }

        return $count;
    }

    public function forget(int $id): bool
    {
        return $this->db->delete($this->table, [
            'id' => $id,
            'status' => Status::Failed->value,
        ]) > 0;
    }

    public function flush(?int $hours = null, ?string $queue = null): void
    {
        $where = ['status' => Status::Failed->value];

        if ($queue !== null) {
            $where['queue'] = $queue;
        }

        $this->db->delete($this->table, $where);
    }

    private function validateJobAndPriority(string $queue, string $job): void
    {
        if ($this->priority !== null && $this->priority < 0) {
            throw new InvalidArgumentException('Priority must be a positive integer.');
        }
    }
}
