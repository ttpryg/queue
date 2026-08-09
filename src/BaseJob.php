<?php

declare(strict_types=1);

namespace Ttpryg\Queue;

use Ttpryg\Queue\Contracts\JobInterface;

abstract class BaseJob implements JobInterface
{
    protected int $retryAfter = 60;

    protected int $tries = 1;

    public function __construct(
        protected array $data = []
    ) {}

    abstract public function process(): void;

    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }

    public function getTries(): int
    {
        return $this->tries;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function execute(): void
    {
        $this->process();
    }
}
