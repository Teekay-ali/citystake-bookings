<?php

namespace App\Support;

use App\Models\Building;
use Spatie\Permission\Models\Role;

/**
 * "View as role" preview state.
 *
 * Lets a super-admin browse the app exactly as another role sees it, without
 * creating throwaway accounts. Preview is always an INTERSECTION with the
 * viewer's real permissions, so it can only ever subtract — never escalate.
 * Writes are blocked while previewing (see BlockWritesWhilePreviewing).
 */
class RolePreview
{
    public const ROLE_KEY     = 'preview_role';
    public const BUILDING_KEY = 'preview_building_id';

    /** Only these roles may start a preview. */
    public static function canPreview(?\App\Models\User $user): bool
    {
        return $user !== null && $user->hasRole('super-admin');
    }

    public static function active(): bool
    {
        return self::role() !== null;
    }

    /** The previewed role name, or null. Guards against a stale/deleted role. */
    public static function role(): ?string
    {
        if (! app()->bound('session') || ! session()->isStarted()) {
            return null;
        }

        $name = session(self::ROLE_KEY);

        return $name && Role::where('name', $name)->exists() ? $name : null;
    }

    public static function buildingId(): ?int
    {
        return self::active() ? (session(self::BUILDING_KEY) ?: null) : null;
    }

    public static function start(string $role, ?int $buildingId = null): void
    {
        session([self::ROLE_KEY => $role, self::BUILDING_KEY => $buildingId]);
    }

    public static function stop(): void
    {
        session()->forget([self::ROLE_KEY, self::BUILDING_KEY]);
    }

    /** Permission names held by the previewed role. */
    public static function permissions(): array
    {
        $role = self::role();

        if (! $role) {
            return [];
        }

        return Role::where('name', $role)->first()?->permissions->pluck('name')->all() ?? [];
    }

    /** Does the previewed role treat the app as global (all buildings)? */
    public static function roleHasGlobalAccess(): bool
    {
        return in_array(self::role(), ['super-admin', 'ceo'], true);
    }

    /** Payload for the banner. */
    public static function share(): ?array
    {
        if (! self::active()) {
            return null;
        }

        $buildingId = self::buildingId();

        return [
            'role'     => self::role(),
            'building' => $buildingId ? Building::find($buildingId)?->name : null,
        ];
    }
}
