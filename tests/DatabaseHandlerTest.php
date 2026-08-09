<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Tests;

use Ttpryg\Queue\Config\QueueConfig;
use Ttpryg\Queue\Database\PdoDatabaseAdapter;
use Ttpryg\Queue\Handlers\DatabaseHandler;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DatabaseHandlerTest extends TestCase
{
    private PDO $pdo;
    private DatabaseHandler $handler;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec('
            CREATE TABLE queue_jobs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                queue TEXT NOT NULL,
                job TEXT NOT NULL,
                payload TEXT NOT NULL,
                status TEXT NOT NULL,
                priority INTEGER NOT NULL DEFAULT 0,
                attempts INTEGER NOT NULL DEFAULT 0,
                available_at TEXT NOT NULL,
                reserved_at TEXT NULL,
                reserved_by TEXT NULL,
                finished_at TEXT NULL,
                exception TEXT NULL,
                trace TEXT NULL,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            );
        ');

        $adapter = new PdoDatabaseAdapter($this->pdo);
        $config = new QueueConfig();
        $this->handler = new DatabaseHandler($config, $adapter);
    }

    public function testPushAndPop(): void
    {
        $result = $this->handler->push('emails', 'SendWelcomeEmail', ['user_id' => 42]);

        $this->assertTrue($result->isSuccess);
        $this->assertNotNull($result->jobId);

        $job = $this->handler->pop('emails');

        $this->assertNotNull($job);
        $this->assertEquals('SendWelcomeEmail', $job->job);
        $this->assertEquals(1, $job->attempts);
        $this->assertEquals('reserved', $job->status);
    }

    public function testDoneAndClear(): void
    {
        $this->handler->push('default', 'ProcessTask', ['foo' => 'bar']);
        $job = $this->handler->pop('default');

        $done = $this->handler->done($job);
        $this->assertTrue($done);

        $this->handler->push('default', 'UnprocessedTask', []);
        $cleared = $this->handler->clear('default');
        $this->assertTrue($cleared);
    }

    public function testFailedAndRetry(): void
    {
        $this->handler->push('default', 'FailingJob', []);
        $job = $this->handler->pop('default');

        $this->handler->failed($job, new RuntimeException('Job crashed'));

        $failedList = $this->handler->listFailed('default');
        $this->assertCount(1, $failedList);
        $this->assertEquals('Job crashed', $failedList[0]->exception);

        $retriedCount = $this->handler->retry((int) $failedList[0]->id);
        $this->assertEquals(1, $retriedCount);
    }
}
