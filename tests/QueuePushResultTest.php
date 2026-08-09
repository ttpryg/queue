<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Tests;

use Ttpryg\Queue\Entities\QueuePushResult;
use PHPUnit\Framework\TestCase;

class QueuePushResultTest extends TestCase
{
    public function testSuccessResult(): void
    {
        $result = QueuePushResult::success('100');

        $this->assertTrue($result->isSuccess);
        $this->assertEquals('100', $result->jobId);
        $this->assertNull($result->errorMessage);
    }

    public function testFailureResult(): void
    {
        $result = QueuePushResult::failure('Connection failed');

        $this->assertFalse($result->isSuccess);
        $this->assertNull($result->jobId);
        $this->assertEquals('Connection failed', $result->errorMessage);
    }
}
