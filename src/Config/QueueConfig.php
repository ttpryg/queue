<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Config;

use RuntimeException;

class QueueConfig
{
    public function __construct(
        public string $defaultHandler = 'database',
        public array $handlers = [
            'database' => \Ttpryg\Queue\Handlers\DatabaseHandler::class,
            'symfony' => \Ttpryg\Queue\Handlers\SymfonyMessengerHandler::class,
        ],
        public array $sync = [],
        public array $database = [
            'table' => 'queue_jobs',
        ],
        public array $symfonyMessenger = [
            'dsn' => 'sync://',
            'auto_setup' => true,
            'transports' => [
                'default' => 'sync://',
                'failed' => 'sync://',
            ],
            'failure_transport' => 'failed',
        ],
        public array $jobHandlers = [],
        public array $queueDefaultPriority = [],
        public array $queuePriorities = [],
        public bool $keepFailedJobs = true,
        public int $maxRetries = 3,
        public int $defaultRetryAfter = 60,
        public int $sleepOnFail = 3,
    ) {}

    public static function fromArray(array $config): self
    {
        return new self(
            defaultHandler: $config['defaultHandler'] ?? 'database',
            handlers: $config['handlers'] ?? [
                'database' => \Ttpryg\Queue\Handlers\DatabaseHandler::class,
                'symfony' => \Ttpryg\Queue\Handlers\SymfonyMessengerHandler::class,
            ],
            sync: $config['sync'] ?? [],
            database: $config['database'] ?? ['table' => 'queue_jobs'],
            symfonyMessenger: $config['symfonyMessenger'] ?? [
                'dsn' => 'sync://',
                'auto_setup' => true,
                'transports' => [
                    'default' => 'sync://',
                    'failed' => 'sync://',
                ],
                'failure_transport' => 'failed',
            ],
            jobHandlers: $config['jobHandlers'] ?? [],
            queueDefaultPriority: $config['queueDefaultPriority'] ?? [],
            queuePriorities: $config['queuePriorities'] ?? [],
            keepFailedJobs: $config['keepFailedJobs'] ?? true,
            maxRetries: $config['maxRetries'] ?? 3,
            defaultRetryAfter: $config['defaultRetryAfter'] ?? 60,
            sleepOnFail: $config['sleepOnFail'] ?? 3,
        );
    }

    public function resolveJobClass(string $name): string
    {
        if (! isset($this->jobHandlers[$name])) {
            throw new RuntimeException("Job handler '{$name}' not found.");
        }

        return $this->jobHandlers[$name];
    }

    public function getQueuePriorities(string $name): ?string
    {
        if (! isset($this->queuePriorities[$name])) {
            return null;
        }

        return implode(',', $this->queuePriorities[$name]);
    }
}
