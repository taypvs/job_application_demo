<?php

namespace App\Enums;

enum RemotePolicy: string
{
    case Onsite = 'onsite';
    case Hybrid = 'hybrid';
    case Remote = 'remote';

    public function label(): string
    {
        return match ($this) {
            self::Onsite => '出社',
            self::Hybrid => 'ハイブリッド',
            self::Remote => 'フルリモート',
        };
    }
}
