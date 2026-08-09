# Esoftdream Queue Core

`esoftdream/queue-core` is a lightweight, high-performance, **framework-agnostic** queue management library for PHP 8.1+.

It provides a unified queue interface with support for Database (via PDO or custom adapters) and Symfony Messenger backends.

---

## Features

- **Framework Agnostic**: Zero dependency on any full-stack framework. Works seamlessly in Native PHP, Slim Framework, CodeIgniter 4, Laravel, or custom CLI scripts.
- **Database Handler**: Out-of-the-box `PdoDatabaseAdapter` for MySQL/MariaDB, PostgreSQL, and SQLite.
- **Symfony Messenger Support**: Native integration for Symfony Messenger transports (Sync, AMQP, Redis, etc.).
- **PSR Standard Compliant**:
  - **PSR-3**: Logger interface for structured logging.
  - **PSR-14**: Event dispatcher interface for queue lifecycle hooks (`job.pushed`, `job.processing`, `job.failed`, `worker.started`, etc.).
- **CLI Management**: Includes `symfony/console` commands for running workers, clearing queues, retrying failed jobs, and flushing history.
- **Advanced Job Control**: Supports job priorities, delayed execution, retry attempts, backoff, and memory limits.

---

## Installation

Add `esoftdream/queue-core` to your `composer.json`:

```bash
composer require esoftdream/queue-core
```

---

## Quick Start

### 1. Database Setup

Create the jobs table in your database (e.g. MySQL/MariaDB):

```sql
CREATE TABLE IF NOT EXISTS `queue_jobs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `queue` VARCHAR(255) NOT NULL DEFAULT 'default',
    `job` VARCHAR(255) NOT NULL,
    `payload` LONGTEXT NOT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'waiting',
    `priority` INT NOT NULL DEFAULT 0,
    `attempts` INT NOT NULL DEFAULT 0,
    `available_at` DATETIME NOT NULL,
    `reserved_at` DATETIME NULL,
    `reserved_by` VARCHAR(255) NULL,
    `finished_at` DATETIME NULL,
    `exception` TEXT NULL,
    `trace` LONGTEXT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    INDEX `idx_queue_status_available` (`queue`, `status`, `available_at`, `priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

### 2. Creating a Job Class

Jobs extend `Ttpryg\Queue\BaseJob`:

```php
namespace App\Jobs;

use Ttpryg\Queue\BaseJob;

class SendEmailJob extends BaseJob
{
    protected int $tries = 3;
    protected int $retryAfter = 60;

    public function process(): void
    {
        $recipient = $this->data['email'] ?? null;
        $subject   = $this->data['subject'] ?? 'Notification';

        // Perform job logic here...
        echo "Sending email to {$recipient} with subject '{$subject}'\n";
    }
}
```

---

### 3. Dispatching Jobs (Native PHP / Slim Framework)

Initialize the queue manager using the `PdoDatabaseAdapter`:

```php
use PDO;
use Ttpryg\Queue\QueueManager;
use Ttpryg\Queue\Config\QueueConfig;
use Ttpryg\Queue\Database\PdoDatabaseAdapter;
use App\Jobs\SendEmailJob;

// 1. Setup PDO connection
$pdo = new PDO('mysql:host=127.0.0.1;dbname=app_db', 'dbuser', 'dbpass', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

// 2. Setup Configuration
$config = QueueConfig::fromArray([
    'defaultHandler' => 'database',
    'database' => [
        'table' => 'queue_jobs',
    ],
    'jobHandlers' => [
        SendEmailJob::class => SendEmailJob::class,
    ],
]);

// 3. Instantiate Queue Manager
$dbAdapter = new PdoDatabaseAdapter($pdo);
$queueManager = new QueueManager($config, $dbAdapter);

$queue = $queueManager->init();

// 4. Push Job to Queue
$result = $queue->push('emails', SendEmailJob::class, [
    'email'   => 'user@example.com',
    'subject' => 'Welcome to our platform!',
]);

if ($result->isSuccess) {
    echo "Job pushed successfully with ID: {$result->jobId}\n";
}
```

#### Delayed Jobs & Priorities

```php
// Dispatch job with 5-minute delay and priority level 10
$queue->delay(300)
      ->priority(10)
      ->push('emails', SendEmailJob::class, ['email' => 'user@example.com']);
```

---

### 4. Running Queue Workers (CLI Script)

Create a CLI entry script `bin/queue` in your project:

```php
#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use PDO;
use Symfony\Component\Console\Application;
use Ttpryg\Queue\QueueManager;
use Ttpryg\Queue\Config\QueueConfig;
use Ttpryg\Queue\Database\PdoDatabaseAdapter;
use Ttpryg\Queue\Console\Commands\WorkCommand;
use Ttpryg\Queue\Console\Commands\ClearCommand;
use Ttpryg\Queue\Console\Commands\FailedCommand;
use Ttpryg\Queue\Console\Commands\RetryCommand;

$pdo = new PDO('mysql:host=127.0.0.1;dbname=app_db', 'dbuser', 'dbpass');
$config = QueueConfig::fromArray(['defaultHandler' => 'database']);
$queue = (new QueueManager($config, new PdoDatabaseAdapter($pdo)))->init();

$app = new Application('Esoftdream Queue CLI', '1.0.0');
$app->add(new WorkCommand($queue));
$app->add(new ClearCommand($queue));
$app->add(new FailedCommand($queue));
$app->add(new RetryCommand($queue));

$app->run();
```

Run worker from shell:

```bash
php bin/queue queue:work --queue=emails --memory=128 --tries=3
```

---

## Custom Architecture Adapters

To integrate `esoftdream/queue-core` with custom database connections or framework event loops, implement the contracts:

- **`Ttpryg\Queue\Contracts\DatabaseAdapterInterface`**: Implement to use custom query builders (e.g. CodeIgniter 4 Query Builder, Doctrine DBAL, Eloquent).
- **`Ttpryg\Queue\Contracts\EventDispatcherInterface`**: Implement to bridge events to custom event engines (e.g. CodeIgniter Events, Symfony EventDispatcher, Laravel Events).

---

## License

This project is open-source software licensed under the [MIT License](LICENSE).
