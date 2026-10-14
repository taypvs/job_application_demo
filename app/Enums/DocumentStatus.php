<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case Pending = 'pending';
    case Indexed = 'indexed';
    case Stored = 'stored';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => '処理中',
            self::Indexed => 'Pinecone登録済み',
            self::Stored => '本文のみ保存',
            self::Failed => '失敗',
        };
    }
}
