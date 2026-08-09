<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Entities;

class PayloadMetadata
{
    public function __construct(
        public readonly ?string $userId = null,
        public readonly ?string $ipAddress = null,
        public readonly ?string $userAgent = null,
        public readonly ?string $requestId = null,
        public readonly ?string $createdAt = null,
        public readonly array $custom = []
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['user_id'] ?? null,
            $data['ip_address'] ?? null,
            $data['user_agent'] ?? null,
            $data['request_id'] ?? null,
            $data['created_at'] ?? null,
            $data['custom'] ?? []
        );
    }

    public function toArray(): array
    {
        $result = [];

        if ($this->userId !== null) {
            $result['user_id'] = $this->userId;
        }

        if ($this->ipAddress !== null) {
            $result['ip_address'] = $this->ipAddress;
        }

        if ($this->userAgent !== null) {
            $result['user_agent'] = $this->userAgent;
        }

        if ($this->requestId !== null) {
            $result['request_id'] = $this->requestId;
        }

        if ($this->createdAt !== null) {
            $result['created_at'] = $this->createdAt;
        }

        if (!empty($this->custom)) {
            $result['custom'] = $this->custom;
        }

        return $result;
    }
}
