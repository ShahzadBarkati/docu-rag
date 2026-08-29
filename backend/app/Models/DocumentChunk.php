<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['document_id', 'chunk_index', 'content', 'char_start', 'char_end', 'parent_chunk_id', 'token_count', 'embedding'])]
class DocumentChunk extends Model
{
    public function parentChunk(): BelongsTo
    {
        return $this->belongsTo(DocumentChunk::class, 'parent_chunk_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(DocumentChunk::class, 'parent_chunk_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    protected function embedding(): Attribute
    {
        return Attribute::make(
            get: fn ($v) => is_string($v) ? json_decode($v, true) : $v,
            set: fn ($v) => is_array($v) ? '['.implode(',', $v).']' : $v,
        );
    }
}
