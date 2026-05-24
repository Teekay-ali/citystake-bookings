<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    protected $fillable = [
        'documentable_id',
        'documentable_type',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
        'uploaded_by',
        'sort_order',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    protected $appends = ['url', 'is_image', 'formatted_size'];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getUrlAttribute(): string
    {
        if (str_starts_with($this->file_path, 'http')) {
            return $this->file_path;
        }
        return Storage::disk('public')->url($this->file_path);
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size ?? 0;
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024)    return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
