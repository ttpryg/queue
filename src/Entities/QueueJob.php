<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Entities;

use Ttpryg\Queue\Payloads\Payload;

class QueueJob
{
    public int $attempts = 0;

    public ?string $reserved_at = null;

    public ?string $reserved_by = null;

    public ?string $finished_at = null;

    public ?string $exception = null;

    public ?string $trace = null;

    public ?string $status = null;

    public function __construct(
        public ?string $id = null,
        public ?string $connection = null,
        public ?string $queue = null,
        public ?string $job = null,
        public ?Payload $payload = null,
        public ?\DateTimeInterface $failed_at = null,
        public ?int $priority = null,
        public ?string $available_at = null,
    ) {}

    public static function fromArray(array $data): self
    {
        $job = new self;
        $job->id = isset($data['id']) ? (string) $data['id'] : null;
        $job->connection = $data['connection'] ?? null;
        $job->queue = $data['queue'] ?? null;
        $job->job = $data['job'] ?? null;
        $job->failed_at = isset($data['failed_at']) ? new \DateTimeImmutable($data['failed_at']) : null;
        $job->status = $data['status'] ?? null;
        $job->exception = $data['exception'] ?? null;
        $job->trace = $data['trace'] ?? null;

        if (isset($data['payload'])) {
            $payloadData = is_string($data['payload']) ? json_decode($data['payload'], true) : (array) $data['payload'];
            if (is_array($payloadData)) {
                $job->payload = Payload::fromArray($payloadData);
            }
        }

        return $job;
    }

    public static function fromObject(object $row): self
    {
        $job = new self;
        $job->id = (string) ($row->id ?? null);
        $job->connection = 'database';
        $job->queue = $row->queue ?? null;
        $job->job = $row->job ?? null;
        $job->attempts = (int) ($row->attempts ?? 0);
        $job->priority = (int) ($row->priority ?? 0);
        $job->status = $row->status ?? null;
        $job->reserved_at = $row->reserved_at ?? null;
        $job->reserved_by = $row->reserved_by ?? null;
        $job->finished_at = $row->finished_at ?? null;
        $job->exception = $row->exception ?? null;
        $job->trace = $row->trace ?? null;
        $job->available_at = $row->available_at ?? null;
        $job->failed_at = isset($row->updated_at) ? new \DateTimeImmutable($row->updated_at) : null;

        if (isset($row->payload)) {
            $data = is_string($row->payload) ? json_decode($row->payload, true) : (array) $row->payload;
            if (is_array($data)) {
                $job->payload = Payload::fromArray($data);
            }
        }

        return $job;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function incrementAttempts(): void
    {
        $this->attempts++;
    }
}
