<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Tests;

use PHPUnit\Framework\TestCase;
use Ttpryg\Queue\Events\SimpleEventDispatcher;

class SimpleEventDispatcherTest extends TestCase
{
    public function test_listen_and_dispatch(): void
    {
        $dispatcher = new SimpleEventDispatcher;
        $called = false;

        $dispatcher->listen('user.registered', function ($event) use (&$called) {
            $called = true;
        });

        $dispatcher->dispatch('user.registered');

        $this->assertTrue($called);
    }
}
