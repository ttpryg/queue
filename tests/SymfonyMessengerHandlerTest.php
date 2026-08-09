<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Tests;

use Ttpryg\Queue\BaseJob;
use Ttpryg\Queue\Config\QueueConfig;
use Ttpryg\Queue\Entities\QueueJob;
use Ttpryg\Queue\Handlers\SymfonyMessengerHandler;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class TestDummyJob extends BaseJob
{
    public static bool $executed = false;

    public function process(): void
    {
        self::$executed = true;
    }
}

class SymfonyMessengerHandlerTest extends TestCase
{
    public function testPush(): void
    {
        $config = new QueueConfig(jobHandlers: [
            'TestDummyJob' => TestDummyJob::class,
        ]);

        $handler = new SymfonyMessengerHandler($config);

        $result = $handler->push('default', 'TestDummyJob', ['key' => 'val']);

        $this->assertTrue($result->isSuccess);
        $this->assertEquals('message-dispatched', $result->jobId);
        $this->assertTrue(TestDummyJob::$executed);
    }

    public function testFailedAndRetry(): void
    {
        $config = new QueueConfig();
        $handler = new SymfonyMessengerHandler($config);

        $job = new QueueJob(
            id: '1',
            queue: 'default',
            job: 'FailingJob'
        );

        $handler->failed($job, new RuntimeException('Failed messenger job'));

        $failed = $handler->listFailed('default');
        $this->assertCount(1, $failed);

        $handler->forget(1);
        $this->assertCount(0, $handler->listFailed('default'));
    }
}
