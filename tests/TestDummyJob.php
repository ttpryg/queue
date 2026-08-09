<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Tests;

use Ttpryg\Queue\BaseJob;

class TestDummyJob extends BaseJob
{
    public static bool $executed = false;

    public function process(): void
    {
        self::$executed = true;
    }
}
