<?php

namespace App\Enums;

enum InterviewStatus: string
{
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => '予定',
            self::Completed => '実施済み',
            self::Cancelled => 'キャンセル',
        };
    }
}
