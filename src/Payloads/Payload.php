<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Payloads;

class Payload implements \ArrayAccess
{
    protected ?PayloadCollection $chainedJobs = null;

    public function __construct(
        public readonly string $job,
        public readonly array $data = [],
        public readonly ?array $metadata = null,
        public readonly ?string $queue = null,
        public readonly ?int $priority = null,
        public readonly ?int $delay = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['job'] ?? '',
            $data['data'] ?? [],
            $data['metadata'] ?? null,
            $data['queue'] ?? null,
            $data['priority'] ?? null,
            $data['delay'] ?? null
        );
    }

    public function toArray(): array
    {
        $data = [
            'job' => $this->job,
            'data' => $this->data,
            'metadata' => $this->metadata,
        ];

        if ($this->queue !== null) {
            $data['queue'] = $this->queue;
        }

        if ($this->priority !== null) {
            $data['priority'] = $this->priority;
        }

        if ($this->delay !== null) {
            $data['delay'] = $this->delay;
        }

        return $data;
    }

    public function getJob(): string
    {
        return $this->job;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function getQueue(): ?string
    {
        return $this->queue;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function getDelay(): ?int
    {
        return $this->delay;
    }

    public function setChainedJobs(?PayloadCollection $payloads): self
    {
        $this->chainedJobs = $payloads;

        return $this;
    }

    public function getChainedJobs(): ?PayloadCollection
    {
        return $this->chainedJobs;
    }

    public function offsetExists(mixed $offset): bool
    {
        return in_array($offset, ['job', 'data', 'metadata', 'queue', 'priority', 'delay'], true);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return match ($offset) {
            'job' => $this->job,
            'data' => $this->data,
            'metadata' => $this->metadata,
            'queue' => $this->queue,
            'priority' => $this->priority,
            'delay' => $this->delay,
            default => null,
        };
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \RuntimeException('Payload properties are read-only.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \RuntimeException('Payload properties are read-only.');
    }
}
