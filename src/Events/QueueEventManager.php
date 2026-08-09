<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Events;

use Throwable;
use Ttpryg\Queue\Contracts\EventDispatcherInterface;
use Ttpryg\Queue\Entities\QueueJob;

class QueueEventManager
{
    public const JOB_PUSHED = 'queue.job.pushed';

    public const JOB_PUSH_FAILED = 'queue.job.push.failed';

    public const JOB_PROCESSING_STARTED = 'queue.job.processing.started';

    public const JOB_PROCESSING_COMPLETED = 'queue.job.processing.completed';

    public const JOB_FAILED = 'queue.job.failed';

    public const QUEUE_CLEARED = 'queue.cleared';

    public const WORKER_STARTED = 'queue.worker.started';

    public const WORKER_STOPPED = 'queue.worker.stopped';

    public const HANDLER_CONNECTION_FAILED = 'queue.handler.connection.failed';

    public const HANDLER_CONNECTION_ESTABLISHED = 'queue.handler.connection.established';

    protected static ?EventDispatcherInterface $dispatcher = null;

    public static function setDispatcher(?EventDispatcherInterface $dispatcher): void
    {
        self::$dispatcher = $dispatcher;
    }

    public static function jobPushed(
        string $handler,
        string $queue,
        QueueJob $job,
        array $metadata = [],
    ): void {
        $event = new QueueEvent(
            type: self::JOB_PUSHED,
            handler: $handler,
            queue: $queue,
            metadata: array_merge([
                'job_class' => $job->payload?->job,
                'job' => $job,
            ], $metadata),
        );

        self::$dispatcher?->dispatch(self::JOB_PUSHED, ['event' => $event]);
    }

    public static function jobPushFailed(
        string $handler,
        string $queue,
        string $jobClass,
        Throwable $exception,
        array $metadata = [],
    ): void {
        $event = new QueueEvent(
            type: self::JOB_PUSH_FAILED,
            handler: $handler,
            queue: $queue,
            metadata: array_merge([
                'job_class' => $jobClass,
                'exception' => $exception,
            ], $metadata),
        );

        self::$dispatcher?->dispatch(self::JOB_PUSH_FAILED, ['event' => $event]);
    }

    public static function jobProcessingStarted(
        string $handler,
        string $queue,
        QueueJob $job,
        array $metadata = [],
    ): void {
        $event = new QueueEvent(
            type: self::JOB_PROCESSING_STARTED,
            handler: $handler,
            queue: $queue,
            metadata: array_merge([
                'job_class' => $job->payload?->job,
                'job' => $job,
            ], $metadata),
        );

        self::$dispatcher?->dispatch(self::JOB_PROCESSING_STARTED, ['event' => $event]);
    }

    public static function jobProcessingCompleted(
        string $handler,
        string $queue,
        QueueJob $job,
        float $processingTime,
        array $metadata = [],
    ): void {
        $event = new QueueEvent(
            type: self::JOB_PROCESSING_COMPLETED,
            handler: $handler,
            queue: $queue,
            metadata: array_merge([
                'job_class' => $job->payload?->job,
                'job' => $job,
                'processing_time' => $processingTime,
            ], $metadata),
        );

        self::$dispatcher?->dispatch(self::JOB_PROCESSING_COMPLETED, ['event' => $event]);
    }

    public static function jobFailed(
        string $handler,
        string $queue,
        QueueJob $job,
        Throwable $exception,
        float $processingTime = 0.0,
        array $metadata = [],
    ): void {
        $event = new QueueEvent(
            type: self::JOB_FAILED,
            handler: $handler,
            queue: $queue,
            metadata: array_merge([
                'job_class' => $job->payload?->job,
                'job' => $job,
                'exception' => $exception,
                'processing_time' => $processingTime,
            ], $metadata),
        );

        self::$dispatcher?->dispatch(self::JOB_FAILED, ['event' => $event]);
    }

    public static function queueCleared(
        string $handler,
        ?string $queue = null,
    ): void {
        $event = new QueueEvent(
            type: self::QUEUE_CLEARED,
            handler: $handler,
            queue: $queue,
        );

        self::$dispatcher?->dispatch(self::QUEUE_CLEARED, ['event' => $event]);
    }

    public static function workerStarted(
        string $handler,
        string $queue,
        array $priorities,
        array $config = [],
        array $metadata = [],
    ): void {
        $event = new QueueEvent(
            type: self::WORKER_STARTED,
            handler: $handler,
            queue: $queue,
            metadata: array_merge([
                'priorities' => $priorities,
                'config' => $config,
            ], $metadata),
        );

        self::$dispatcher?->dispatch(self::WORKER_STARTED, ['event' => $event]);
    }

    public static function workerStopped(
        string $handler,
        string $queue,
        array $priorities,
        float $uptime,
        int $jobsProcessed,
        array $metadata = [],
    ): void {
        $event = new QueueEvent(
            type: self::WORKER_STOPPED,
            handler: $handler,
            queue: $queue,
            metadata: array_merge([
                'priorities' => $priorities,
                'uptime_seconds' => $uptime,
                'jobs_processed' => $jobsProcessed,
            ], $metadata),
        );

        self::$dispatcher?->dispatch(self::WORKER_STOPPED, ['event' => $event]);
    }

    public static function handlerConnectionEstablished(
        string $handler,
        array $config = [],
    ): void {
        $event = new QueueEvent(
            type: self::HANDLER_CONNECTION_ESTABLISHED,
            handler: $handler,
            metadata: ['config' => $config],
        );

        self::$dispatcher?->dispatch(self::HANDLER_CONNECTION_ESTABLISHED, ['event' => $event]);
    }

    public static function handlerConnectionFailed(
        string $handler,
        Throwable $exception,
        array $config = [],
    ): void {
        $event = new QueueEvent(
            type: self::HANDLER_CONNECTION_FAILED,
            handler: $handler,
            metadata: [
                'exception' => $exception,
                'config' => $config,
            ],
        );

        self::$dispatcher?->dispatch(self::HANDLER_CONNECTION_FAILED, ['event' => $event]);
    }
}
