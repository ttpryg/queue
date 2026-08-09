<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Tests;

use PHPUnit\Framework\TestCase;
use Ttpryg\Queue\BaseJob;

class BaseJobTest extends TestCase
{
    public function test_get_retry_after(): void
    {
        $job = new class(['data' => 'value']) extends BaseJob
        {
            public function process(): void {}
        };

        $this->assertEquals(60, $job->getRetryAfter());
    }

    public function test_get_tries(): void
    {
        $job = new class(['data' => 'value']) extends BaseJob
        {
            public function process(): void {}
        };

        $this->assertEquals(1, $job->getTries());
    }

    public function test_get_data_and_set_data(): void
    {
        $data = ['key' => 'value'];
        $job = new class($data) extends BaseJob
        {
            public function process(): void {}
        };

        $this->assertEquals($data, $job->getData());

        $job->setData(['new' => 'data']);
        $this->assertEquals(['new' => 'data'], $job->getData());
    }

    public function test_execute_calls_process(): void
    {
        $job = new class(['test' => 'data']) extends BaseJob
        {
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
