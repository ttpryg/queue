<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Entities;

class QueuePushResult
{
    public function __construct(
        public readonly bool $isSuccess,
        public readonly ?string $jobId = null,
        public readonly ?string $errorMessage = null
    ) {
    }

    public static function success(string $jobId): self
    {
        return new self(true, $jobId);
    }

    public static function failure(string $errorMessage): self
    {
        return new self(false, null, $errorMessage);
    }
}
