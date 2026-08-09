<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Tests;

use Ttpryg\Queue\Events\QueueEvent;
use Ttpryg\Queue\Events\SimpleEventDispatcher;
use PHPUnit\Framework\TestCase;

class SimpleEventDispatcherTest extends TestCase
{
    public function testListenAndDispatch(): void
    {
        $dispatcher = new SimpleEventDispatcher();
        $called = false;

        $dispatcher->listen('user.registered', function ($event) use (&$called) {
            $called = true;
        });

        $dispatcher->dispatch('user.registered');

        $this->assertTrue($called);
    }
}
