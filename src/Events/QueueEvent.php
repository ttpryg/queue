<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Events;

class QueueEvent
{
    public function __construct(
        public readonly string $type,
        public readonly string $handler,
        public readonly ?string $queue = null,
        public readonly array $metadata = []
    ) {
    }
}
