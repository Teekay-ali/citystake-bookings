<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Changelog extends Model
{
    protected $fillable = [
        'title', 'body', 'version', 'type', 'send_email', 'published_at', 'created_by',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'send_email'   => 'boolean',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function readers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'changelog_reads')
            ->withPivot('read_at');
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at');
    }

    public static function unreadForUser(User $user)
    {
        return static::published()
            ->whereNotIn('id', $user->changelogReads()->pluck('changelog_id'))
            ->latest('published_at')
            ->get();
    }
}
