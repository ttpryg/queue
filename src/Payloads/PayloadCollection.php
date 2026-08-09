<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Payloads;

class PayloadCollection
{
    protected array $items = [];

    public function add(Payload $payload): void
    {
        $this->items[] = $payload;
    }

    public function shift(): ?Payload
    {
        return array_shift($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function all(): array
    {
        return $this->items;
    }
}
