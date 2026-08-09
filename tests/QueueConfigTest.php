<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Tests;

use Ttpryg\Queue\Config\QueueConfig;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class QueueConfigTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $config = new QueueConfig();

        $this->assertEquals('database', $config->defaultHandler);
        $this->assertEquals('queue_jobs', $config->database['table']);
        $this->assertEquals(3, $config->maxRetries);
    }

    public function testFromArray(): void
    {
        $config = QueueConfig::fromArray([
            'defaultHandler' => 'symfony',
            'maxRetries' => 5,
            'jobHandlers' => [
                'testJob' => 'App\Jobs\TestJob',
            ],
            'queuePriorities' => [
                'default' => [10, 5, 1],
            ],
        ]);

        $this->assertEquals('symfony', $config->defaultHandler);
        $this->assertEquals(5, $config->maxRetries);
        $this->assertEquals('App\Jobs\TestJob', $config->resolveJobClass('testJob'));
        $this->assertEquals('10,5,1', $config->getQueuePriorities('default'));
    }

    public function testResolveJobClassThrowsExceptionWhenNotFound(): void
    {
        $config = new QueueConfig();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Job handler 'nonExistent' not found.");

        $config->resolveJobClass('nonExistent');
    }
}
