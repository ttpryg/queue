<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Ttpryg\Queue\Config\QueueConfig;

class QueueConfigTest extends TestCase
{
    public function test_default_values(): void
    {
        $config = new QueueConfig;

        $this->assertEquals('database', $config->defaultHandler);
        $this->assertEquals('queue_jobs', $config->database['table']);
        $this->assertEquals(3, $config->maxRetries);
    }

    public function test_from_array(): void
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

    public function test_resolve_job_class_throws_exception_when_not_found(): void
    {
        $config = new QueueConfig;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Job handler 'nonExistent' not found.");

        $config->resolveJobClass('nonExistent');
    }
}
