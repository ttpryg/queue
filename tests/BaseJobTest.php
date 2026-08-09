<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Tests;

use Ttpryg\Queue\BaseJob;
use PHPUnit\Framework\TestCase;

class BaseJobTest extends TestCase
{
    public function testGetRetryAfter(): void
    {
        $job = new class (['data' => 'value']) extends BaseJob {
            public function process(): void
            {
            }
        };

        $this->assertEquals(60, $job->getRetryAfter());
    }

    public function testGetTries(): void
    {
        $job = new class (['data' => 'value']) extends BaseJob {
            public function process(): void
            {
            }
        };

        $this->assertEquals(1, $job->getTries());
    }

    public function testGetDataAndSetData(): void
    {
        $data = ['key' => 'value'];
        $job = new class ($data) extends BaseJob {
            public function process(): void
            {
            }
        };

        $this->assertEquals($data, $job->getData());

        $job->setData(['new' => 'data']);
        $this->assertEquals(['new' => 'data'], $job->getData());
    }

    public function testExecuteCallsProcess(): void
    {
        $job = new class (['test' => 'data']) extends BaseJob {
            public bool $processed = false;

            public function process(): void
            {
                $this->processed = true;
            }
        };

        $this->assertFalse($job->processed);
        $job->execute();
        $this->assertTrue($job->processed);
    }
}
