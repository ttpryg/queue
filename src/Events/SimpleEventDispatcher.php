<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Events;

use Ttpryg\Queue\Contracts\EventDispatcherInterface;
use Psr\EventDispatcher\EventDispatcherInterface as PsrEventDispatcherInterface;

class SimpleEventDispatcher implements EventDispatcherInterface, PsrEventDispatcherInterface
{
    /** @var array<string, array<callable>> */
    protected array $listeners = [];

    public function listen(string $eventName, callable $listener): void
    {
        $this->listeners[$eventName][] = $listener;
    }

    public function dispatch(object|string $event, array $metadata = []): object
    {
        $eventName = is_string($event) ? $event : get_class($event);
        $eventObj = is_string($event) ? new QueueEvent($event, 'core', null, $metadata) : $event;

        if (isset($this->listeners[$eventName])) {
            foreach ($this->listeners[$eventName] as $listener) {
                $listener($eventObj, $metadata);
            }
        }

        return $eventObj;
    }
}
