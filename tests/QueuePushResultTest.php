<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Tests;

use PHPUnit\Framework\TestCase;
use Ttpryg\Queue\Entities\QueuePushResult;

class QueuePushResultTest extends TestCase
{
    public function test_success_result(): void
    {
        $result = QueuePushResult::success('100');

        $this->assertTrue($result->isSuccess);
        $this->assertEquals('100', $result->jobId);
        $this->assertNull($result->errorMessage);
    }

    public function test_failure_result(): void
    {
        $result = QueuePushResult::failure('Connection failed');

        $this->assertFalse($result->isSuccess);
        $this->assertNull($result->jobId);
        $this->assertEquals('Connection failed', $result->errorMessage);
    }
}
