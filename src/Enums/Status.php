<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Enums;

enum Status: string
{
    case Waiting = 'waiting';
    case Reserved = 'reserved';
    case Done = 'done';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Waiting => 'Waiting',
            self::Reserved => 'Reserved',
            self::Done => 'Done',
            self::Failed => 'Failed',
        };
    }
}
