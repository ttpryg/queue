<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Exceptions;

use RuntimeException;

class QueueException extends RuntimeException
{
    public static function forIncorrectHandler(): self
    {
        return new self('Selected queue handler is incorrect or missing from configuration.');
    }
}
