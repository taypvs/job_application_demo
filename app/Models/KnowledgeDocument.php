<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'uploaded_by',
    'title',
    'source_type',
    'original_name',
    's3_key',
    'source_url',
    'extracted_text',
    'status',
    'note',
    'chunk_count',
])]
class KnowledgeDocument extends Model
{
    protected function casts(): array
    {
        return [
            'status' => DocumentStatus::class,
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(DocumentChunk::class)->orderBy('chunk_index');
    }
}
