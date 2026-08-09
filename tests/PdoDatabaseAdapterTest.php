<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Tests;

use Ttpryg\Queue\Database\PdoDatabaseAdapter;
use Ttpryg\Queue\Enums\Status;
use PDO;
use PHPUnit\Framework\TestCase;

class PdoDatabaseAdapterTest extends TestCase
{
    private PDO $pdo;
    private PdoDatabaseAdapter $adapter;

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

        $this->adapter = new PdoDatabaseAdapter($this->pdo);
    }

    public function testInsertAndFetchWaitingJob(): void
    {
        $id = $this->adapter->insert('queue_jobs', [
            'queue' => 'default',
            'job' => 'TestJob',
            'payload' => '{}',
            'status' => Status::Waiting->value,
            'priority' => 0,
            'attempts' => 0,
            'available_at' => date('Y-m-d H:i:s', time() - 10),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->assertNotEmpty($id);

        $row = $this->adapter->fetchWaitingJob('queue_jobs', 'default');
        $this->assertNotNull($row);
        $this->assertEquals('TestJob', $row->job);
    }

    public function testUpdateAndDelete(): void
    {
        $id = $this->adapter->insert('queue_jobs', [
            'queue' => 'default',
            'job' => 'TestJob',
            'payload' => '{}',
            'status' => Status::Waiting->value,
            'priority' => 0,
            'attempts' => 0,
            'available_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $updated = $this->adapter->update('queue_jobs', ['status' => Status::Done->value], ['id' => $id]);
        $this->assertTrue($updated);

        $deletedCount = $this->adapter->delete('queue_jobs', ['id' => $id]);
        $this->assertEquals(1, $deletedCount);
    }
}
