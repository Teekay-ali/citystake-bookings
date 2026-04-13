<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'is_admin',
        'is_staff',
        'is_active',
        'email_marketing',
        'email_reminders',
        'email_newsletters',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_admin'          => 'boolean',
            'is_staff'          => 'boolean',
            'is_active'         => 'boolean',
            'email_marketing'   => 'boolean',
            'email_reminders'   => 'boolean',
            'email_newsletters' => 'boolean',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function buildings(): BelongsToMany
    {
        return $this->belongsToMany(Building::class, 'user_buildings')
            ->withTimestamps()
            ->select('buildings.*'); // always qualify — prevents ambiguous column on any join
    }

    // ─── Helpers ──────────────────────────────────────────────────

    /**
     * Super Admin and CEO see all buildings.
     * All other staff are scoped to their assigned buildings.
     */
    public function hasGlobalAccess(): bool
    {
        return $this->hasRole(['super-admin', 'ceo']);
    }

    /**
     * Returns building IDs this user can access.
     * Returns null if user has global access (no restriction).
     */
    public function accessibleBuildingIds(): ?array
    {
        if ($this->hasGlobalAccess()) {
            return null;
        }

        return $this->buildings()->pluck('buildings.id')->toArray();
    }

    // ─── Email preference helpers (unchanged) ─────────────────────

    public static function shouldReceiveMarketing(string $email): bool
    {
        $user = static::where('email', $email)->first();
        return !$user || $user->email_marketing;
    }

    public static function shouldReceiveReminders(string $email): bool
    {
        $user = static::where('email', $email)->first();
        return !$user || $user->email_reminders;
    }

    public static function shouldReceiveNewsletters(string $email): bool
    {
        $user = static::where('email', $email)->first();
        return !$user || $user->email_newsletters;
    }
}
