<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'original_name',
    's3_key',
    'mime_type',
    'size_bytes',
    'extracted_text',
    'parsed_profile',
    'parse_engine',
    'is_active',
])]
class Cv extends Model
{
    protected function casts(): array
    {
        return [
            'parsed_profile' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
