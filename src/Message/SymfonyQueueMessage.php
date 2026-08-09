<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Message;

class SymfonyQueueMessage
{
    public function __construct(
        public readonly string $job,
        public readonly array $data = [],
        public readonly array $options = []
    ) {
    }

    public function getQueue(): string
    {
        return $this->options['queue'] ?? 'default';
    }

    public function getPriority(): int
    {
        return $this->options['priority'] ?? 0;
    }
}
