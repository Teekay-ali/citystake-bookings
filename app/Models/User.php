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
    // Alias the Spatie implementations so the role-preview overrides below can
    // still reach the real checks (HasRoles is a trait, so there's no parent::).
    use HasFactory, Notifiable, HasRoles {
        hasPermissionTo   as protected realHasPermissionTo;
        getAllPermissions as protected realGetAllPermissions;
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'is_admin',
        'is_staff',
        'is_active',
        'welcome_sent_at',
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

    // ─── Relationships
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function buildings(): BelongsToMany
    {
        return $this->belongsToMany(Building::class, 'user_buildings')
            ->withTimestamps()
            ->select('buildings.*'); // always qualify - prevents ambiguous column on any join
    }

    public function changelogReads(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Changelog::class, 'changelog_reads')
            ->withPivot('read_at');
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(StaffMessage::class, 'sender_id');
    }

    public function receivedMessages(): BelongsToMany
    {
        return $this->belongsToMany(StaffMessage::class, 'staff_message_recipients')
            ->withPivot('read_at', 'deleted_at');
    }

    // ─── Helpers ──────

    /**
     * Super Admin and CEO see all buildings.
     * All other staff are scoped to their assigned buildings.
     */
    /**
     * True when this user is previewing the app as another role.
     * Only applies to the currently authenticated user — never to other records.
     */
    protected function isPreviewing(): bool
    {
        return \App\Support\RolePreview::active()
            && auth()->id() === $this->id;
    }

    /**
     * Audit-log access is granted by identity (a configured owner email), not by
     * a permission — so it must be suppressed during a role preview, otherwise
     * every previewed role would still see the audit logs.
     */
    public function isAuditOwner(): bool
    {
        $ownerEmail = config('audit.owner_email');

        return (bool) $ownerEmail
            && $this->email === $ownerEmail
            && ! $this->isPreviewing();
    }

    public function hasGlobalAccess(): bool
    {
        $real = $this->hasRole(['super-admin', 'ceo']);

        // While previewing, global access follows the previewed role — and a
        // simulated building always means scoped, never global.
        if ($this->isPreviewing()) {
            return $real
                && \App\Support\RolePreview::roleHasGlobalAccess()
                && ! \App\Support\RolePreview::buildingId();
        }

        return $real;
    }

    /**
     * Permission checks are intersected with the previewed role's permissions,
     * so preview can only ever remove access, never grant it.
     */
    public function hasPermissionTo($permission, $guardName = null): bool
    {
        $real = $this->realHasPermissionTo($permission, $guardName);

        if (! $real || ! $this->isPreviewing()) {
            return $real;
        }

        $name = is_string($permission) ? $permission : ($permission->name ?? null);

        return $name !== null
            && in_array($name, \App\Support\RolePreview::permissions(), true);
    }

    /** Same intersection, for the list shared to the front-end nav. */
    public function getAllPermissions(): \Illuminate\Support\Collection
    {
        $all = $this->realGetAllPermissions();

        if (! $this->isPreviewing()) {
            return $all;
        }

        $allowed = \App\Support\RolePreview::permissions();

        return $all->filter(fn ($p) => in_array($p->name, $allowed, true))->values();
    }

    /**
     * Returns building IDs this user can access.
     * Returns null if user has global access (no restriction).
     */
    private ?array $_accessibleBuildingIds = null;
    private bool $_accessibleBuildingIdsLoaded = false;

    public function accessibleBuildingIds(): ?array
    {
        if ($this->hasGlobalAccess()) {
            return null;
        }

        // Previewing: a chosen building scopes to it; otherwise a non-global
        // previewed role still sees every building (we're not impersonating a
        // specific person, so there are no real building assignments to use).
        if ($this->isPreviewing()) {
            $buildingId = \App\Support\RolePreview::buildingId();

            return $buildingId
                ? [$buildingId]
                : Building::pluck('id')->toArray();
        }

        if (! $this->_accessibleBuildingIdsLoaded) {
            $this->_accessibleBuildingIds       = $this->buildings()->pluck('buildings.id')->toArray();
            $this->_accessibleBuildingIdsLoaded = true;
        }

        return $this->_accessibleBuildingIds;
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
