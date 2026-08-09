<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Contracts;

interface EventDispatcherInterface
{
    /**
     * Dispatch an event object or event by name with metadata.
     */
    public function dispatch(object|string $event, array $metadata = []): void;
}
