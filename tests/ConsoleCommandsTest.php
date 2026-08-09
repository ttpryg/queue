<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Tests;

use Ttpryg\Queue\Config\QueueConfig;
use Ttpryg\Queue\Console\Commands\ClearCommand;
use Ttpryg\Queue\Console\Commands\FailedCommand;
use Ttpryg\Queue\Console\Commands\FlushCommand;
use Ttpryg\Queue\Console\Commands\ForgetCommand;
use Ttpryg\Queue\Console\Commands\RetryCommand;
use Ttpryg\Queue\Console\Commands\WorkCommand;
use Ttpryg\Queue\Database\PdoDatabaseAdapter;
use Ttpryg\Queue\Handlers\DatabaseHandler;
use PDO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ConsoleCommandsTest extends TestCase
{
    private DatabaseHandler $handler;

    protected function setUp(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdo->exec('
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

        $adapter = new PdoDatabaseAdapter($pdo);
        $config = new QueueConfig();
        $this->handler = new DatabaseHandler($config, $adapter);
    }

    public function testClearCommand(): void
    {
        $this->handler->push('default', 'SomeJob', []);

        $command = new ClearCommand($this->handler);
        $tester = new CommandTester($command);

        $tester->execute(['--queue' => 'default']);

        $this->assertStringContainsString('Queue cleared successfully', $tester->getDisplay());
    }

    public function testFailedCommand(): void
    {
        $command = new FailedCommand($this->handler);
        $tester = new CommandTester($command);

        $tester->execute([]);

        $this->assertStringContainsString('No failed jobs found', $tester->getDisplay());
    }

    public function testRetryCommand(): void
    {
        $command = new RetryCommand($this->handler);
        $tester = new CommandTester($command);

        $tester->execute([]);

        $this->assertStringContainsString('Retried 0 failed job(s)', $tester->getDisplay());
    }

    public function testForgetCommand(): void
    {
        $command = new ForgetCommand($this->handler);
        $tester = new CommandTester($command);

        $tester->execute(['--id' => 999]);

        $this->assertStringContainsString('not found', $tester->getDisplay());
    }

    public function testFlushCommand(): void
    {
        $command = new FlushCommand($this->handler);
        $tester = new CommandTester($command);

        $tester->execute([]);

        $this->assertStringContainsString('Failed jobs flushed successfully', $tester->getDisplay());
    }
}
