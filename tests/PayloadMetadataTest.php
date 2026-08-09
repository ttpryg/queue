<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Tests;

use Ttpryg\Queue\Entities\PayloadMetadata;
use PHPUnit\Framework\TestCase;

class PayloadMetadataTest extends TestCase
{
    public function testMetadataSerialization(): void
    {
        $metadata = new PayloadMetadata(
            userId: 'user_123',
            ipAddress: '127.0.0.1',
            userAgent: 'PHPUnit',
            requestId: 'req_abc',
            createdAt: '2026-08-09 20:00:00',
            custom: ['tag' => 'urgent']
        );

        $array = $metadata->toArray();

        $this->assertEquals('user_123', $array['user_id']);
        $this->assertEquals('127.0.0.1', $array['ip_address']);
        $this->assertEquals('PHPUnit', $array['user_agent']);
        $this->assertEquals('req_abc', $array['request_id']);
        $this->assertEquals(['tag' => 'urgent'], $array['custom']);

        $restored = PayloadMetadata::fromArray($array);
        $this->assertEquals('user_123', $restored->userId);
        $this->assertEquals('127.0.0.1', $restored->ipAddress);
        $this->assertEquals(['tag' => 'urgent'], $restored->custom);
    }
}
