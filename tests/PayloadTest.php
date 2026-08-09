<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Ttpryg\Queue\Payloads\Payload;
use Ttpryg\Queue\Payloads\PayloadCollection;

class PayloadTest extends TestCase
{
    public function test_payload_getters_and_to_array(): void
    {
        $payload = new Payload(
            job: 'SendEmail',
            data: ['email' => 'test@example.com'],
            metadata: ['ip' => '127.0.0.1'],
            queue: 'emails',
            priority: 5,
            delay: 30
        );

        $this->assertEquals('SendEmail', $payload->getJob());
        $this->assertEquals(['email' => 'test@example.com'], $payload->getData());
        $this->assertEquals(['ip' => '127.0.0.1'], $payload->getMetadata());
        $this->assertEquals('emails', $payload->getQueue());
        $this->assertEquals(5, $payload->getPriority());
        $this->assertEquals(30, $payload->getDelay());

        $array = $payload->toArray();
        $this->assertEquals('SendEmail', $array['job']);
        $this->assertEquals('emails', $array['queue']);
        $this->assertEquals(5, $array['priority']);
    }

    public function test_from_array(): void
    {
        $data = [
            'job' => 'ProcessOrder',
            'data' => ['order_id' => 123],
            'queue' => 'orders',
        ];

        $payload = Payload::fromArray($data);
        $this->assertEquals('ProcessOrder', $payload->job);
        $this->assertEquals(['order_id' => 123], $payload->data);
        $this->assertEquals('orders', $payload->queue);
    }

    public function test_chained_jobs(): void
    {
        $payload = new Payload('JobA');
        $collection = new PayloadCollection;
        $collection->add(new Payload('JobB'));

        $payload->setChainedJobs($collection);
        $this->assertSame($collection, $payload->getChainedJobs());
    }

    public function test_array_access(): void
    {
        $payload = new Payload('JobA', ['foo' => 'bar']);

        $this->assertTrue(isset($payload['job']));
        $this->assertEquals('JobA', $payload['job']);
        $this->assertEquals(['foo' => 'bar'], $payload['data']);

        $this->expectException(RuntimeException::class);
        $payload['job'] = 'JobB';
    }
}
