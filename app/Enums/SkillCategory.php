<?php

namespace App\Enums;

enum SkillCategory: string
{
    case Required = 'required';
    case NiceToHave = 'nice_to_have';

    public function label(): string
    {
        return match ($this) {
            self::Required => '必須スキル',
            self::NiceToHave => '歓迎スキル',
        };
    }
}
