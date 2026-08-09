<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Tests;

use Ttpryg\Queue\Payloads\Payload;
use Ttpryg\Queue\Payloads\PayloadCollection;
use PHPUnit\Framework\TestCase;

class PayloadCollectionTest extends TestCase
{
    public function testPayloadCollectionOperations(): void
    {
        $collection = new PayloadCollection();
        $this->assertTrue($collection->isEmpty());
        $this->assertEquals(0, $collection->count());

        $payload1 = new Payload('Job1');
        $payload2 = new Payload('Job2');

        $collection->add($payload1);
        $collection->add($payload2);

        $this->assertFalse($collection->isEmpty());
        $this->assertEquals(2, $collection->count());
        $this->assertEquals([$payload1, $payload2], $collection->all());

        $shifted = $collection->shift();
        $this->assertSame($payload1, $shifted);
        $this->assertEquals(1, $collection->count());
    }
}
