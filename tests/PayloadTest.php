<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Tests;

use Ttpryg\Queue\Payloads\Payload;
use Ttpryg\Queue\Payloads\PayloadCollection;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PayloadTest extends TestCase
{
    public function testPayloadGettersAndToArray(): void
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

    public function testFromArray(): void
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

    public function testChainedJobs(): void
    {
        $payload = new Payload('JobA');
        $collection = new PayloadCollection();
        $collection->add(new Payload('JobB'));

        $payload->setChainedJobs($collection);
        $this->assertSame($collection, $payload->getChainedJobs());
    }

    public function testArrayAccess(): void
    {
        $payload = new Payload('JobA', ['foo' => 'bar']);

        $this->assertTrue(isset($payload['job']));
        $this->assertEquals('JobA', $payload['job']);
        $this->assertEquals(['foo' => 'bar'], $payload['data']);

        $this->expectException(RuntimeException::class);
        $payload['job'] = 'JobB';
    }
}
