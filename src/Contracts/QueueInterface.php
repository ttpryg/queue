<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Contracts;

use Ttpryg\Queue\Entities\QueueJob;
use Ttpryg\Queue\Entities\PayloadMetadata;
use Ttpryg\Queue\Entities\QueuePushResult;

interface QueueInterface
{
    public function name(): string;

    public function push(string $queue, string $job, array $data, ?PayloadMetadata $metadata = null): QueuePushResult;

    public function pop(string $queue, array $priorities = []): ?QueueJob;

    public function later(QueueJob $queueJob, int $seconds): bool;

    public function failed(QueueJob $queueJob, \Throwable $err, bool $keepJob = true): bool;

    public function done(QueueJob $queueJob): bool;

    public function clear(?string $queue = null): bool;

    public function getQueues(): array;

    public function priority(int $priority): self;

    public function delay(int $delay): self;

    public function setPriority(int $priority): self;

    public function setDelay(int $delay): self;

    public function listFailed(?string $queue = null): array;

    public function retry(?int $id, ?string $queue = null): int;

    public function forget(int $id): bool;

    public function flush(?int $hours = null, ?string $queue = null): void;
}
