<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Applied = 'applied';
    case Interview = 'interview';
    case Rejected = 'rejected';
    case Passed = 'passed';

    public function label(): string
    {
        return match ($this) {
            self::Applied => '応募済み',
            self::Interview => '面接予定',
            self::Rejected => '不合格',
            self::Passed => '合格',
        };
    }
}
