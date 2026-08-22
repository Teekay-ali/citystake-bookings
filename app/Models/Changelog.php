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

    /** Roles allowed to see platform updates. Configurable; super-admin + ceo by default. */
    public const AUDIENCE_SETTING = 'changelog_audience_roles';
    public const AUDIENCE_DEFAULT = ['super-admin', 'ceo'];

    public static function audienceRoles(): array
    {
        return self::audienceRolesFrom(Setting::get(self::AUDIENCE_SETTING, self::AUDIENCE_DEFAULT));
    }

    /** Normalize an audience list: super-admin always included, no duplicates. */
    public static function audienceRolesFrom($roles): array
    {
        return array_values(array_unique(array_merge(['super-admin'], (array) $roles)));
    }

    public static function canBeSeenBy(User $user): bool
    {
        return $user->hasAnyRole(self::audienceRoles());
    }
}
