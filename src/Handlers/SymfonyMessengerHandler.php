<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Handlers;

use Ttpryg\Queue\Config\QueueConfig;
use Ttpryg\Queue\Contracts\QueueInterface;
use Ttpryg\Queue\Entities\QueueJob as QueueJobEntity;
use Ttpryg\Queue\Entities\PayloadMetadata;
use Ttpryg\Queue\Entities\QueuePushResult;
use Ttpryg\Queue\Message\MessageHandler;
use Ttpryg\Queue\Message\SymfonyQueueMessage;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Throwable;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;

class SymfonyMessengerHandler implements QueueInterface
{
    protected ?int $delay = null;
    protected ?int $priority = null;

    private ?MessageBusInterface $messageBus = null;
    private ?MessageHandler $messageHandler = null;
    private array $failedJobs = [];

    public function __construct(
        protected QueueConfig $config,
        protected ?LoggerInterface $logger = null
    ) {
    }

    public function name(): string
    {
        return 'symfony-messenger';
    }

    public function push(string $queue, string $job, array $data, ?PayloadMetadata $metadata = null): QueuePushResult
    {
        $this->validateJobAndPriority($queue, $job);

        $message = new SymfonyQueueMessage($job, $data, [
            'queue' => $queue,
            'priority' => $this->priority ?? 0,
            'metadata' => $metadata?->toArray() ?? [],
        ]);

        $stamps = [];

        if ($this->delay !== null) {
            $stamps[] = new DelayStamp($this->delay * 1000);
        }

        if (class_exists(TransportNamesStamp::class)) {
            $stamps[] = new TransportNamesStamp([$queue]);
        }

        try {
            $envelope = new Envelope($message, $stamps);
            $this->getMessageBus()->dispatch($envelope);

            $this->delay = null;
            $this->priority = null;

            return QueuePushResult::success('message-dispatched');
        } catch (Throwable $e) {
            return QueuePushResult::failure($e->getMessage());
        }
    }

    public function pop(string $queue, array $priorities = []): ?QueueJobEntity
    {
        return null;
    }

    public function later(QueueJobEntity $queueJob, int $seconds): bool
    {
        return true;
    }

    public function failed(QueueJobEntity $queueJob, Throwable $err, bool $keepJob = true): bool
    {
        $jobId = $queueJob->id !== null
            ? (str_starts_with((string) $queueJob->id, 'failed_') ? (string) $queueJob->id : 'failed_' . $queueJob->id)
            : uniqid('failed_');

        $failedJob = [
            'id' => $jobId,
            'connection' => $this->name(),
            'queue' => $queueJob->queue,
            'payload' => $queueJob->payload?->toArray() ?? [],
            'failed_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'exception' => $err->getMessage(),
        ];

        $this->failedJobs[] = $failedJob;

        return true;
    }

    public function done(QueueJobEntity $queueJob): bool
    {
        return true;
    }

    public function clear(?string $queue = null): bool
    {
        return true;
    }

    public function getQueues(): array
    {
        $queues = [];
        foreach ($this->config->symfonyMessenger['transports'] ?? ['default' => 'sync://'] as $name => $dsn) {
            $queues[] = $name;
        }
        return $queues;
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
        $results = [];

        foreach ($this->failedJobs as $failedJob) {
            if ($queue === null || $failedJob['queue'] === $queue) {
                $results[] = QueueJobEntity::fromArray($failedJob);
            }
        }

        return $results;
    }

    public function retry(?int $id, ?string $queue = null): int
    {
        $count = 0;
        $remaining = [];

        foreach ($this->failedJobs as $failedJob) {
            if ($queue !== null && $failedJob['queue'] !== $queue) {
                $remaining[] = $failedJob;
                continue;
            }

            if ($id === null || $failedJob['id'] === 'failed_' . $id || $failedJob['id'] === (string) $id) {
                $payload = $failedJob['payload'];
                if (!empty($payload)) {
                    $this->push(
                        $failedJob['queue'],
                        $payload['job'],
                        $payload['data'] ?? [],
                        isset($payload['metadata']) ? PayloadMetadata::fromArray($payload['metadata']) : null
                    );
                    $count++;
                }
            } else {
                $remaining[] = $failedJob;
            }
        }

        $this->failedJobs = $remaining;

        return $count;
    }

    public function forget(int $id): bool
    {
        $remaining = [];
        $found = false;

        foreach ($this->failedJobs as $failedJob) {
            if ($failedJob['id'] === 'failed_' . $id || $failedJob['id'] === (string) $id) {
                $found = true;
            } else {
                $remaining[] = $failedJob;
            }
        }

        $this->failedJobs = $remaining;

        return $found;
    }

    public function flush(?int $hours = null, ?string $queue = null): void
    {
        $remaining = [];

        foreach ($this->failedJobs as $failedJob) {
            if ($queue !== null && $failedJob['queue'] !== $queue) {
                $remaining[] = $failedJob;
                continue;
            }

            if ($hours !== null) {
                $failedAt = new \DateTimeImmutable($failedJob['failed_at']);
                $diff = (new \DateTimeImmutable())->diff($failedAt);

                if ($diff->h < $hours) {
                    $remaining[] = $failedJob;
                }
            }
        }

        $this->failedJobs = $remaining;
    }

    private function getMessageBus(): MessageBusInterface
    {
        if ($this->messageBus === null) {
            $this->messageBus = $this->createMessageBus();
        }
        return $this->messageBus;
    }

    private function createMessageBus(): MessageBusInterface
    {
        $this->messageHandler = new MessageHandler($this->config, $this->logger);

        $handlersLocator = new HandlersLocator([
            SymfonyQueueMessage::class => [$this->messageHandler],
        ]);

        return new MessageBus([
            new HandleMessageMiddleware($handlersLocator),
        ]);
    }

    private function validateJobAndPriority(string $queue, string $job): void
    {
        if ($this->priority !== null && $this->priority < 0) {
            throw new InvalidArgumentException('Priority must be a positive integer.');
        }
    }
}
