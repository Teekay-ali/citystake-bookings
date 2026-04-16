<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * Get all users with any of the given roles,
     * optionally scoped to a building.
     */
    public static function getUsersByRoles(array $roles, ?int $buildingId = null): Collection
    {
        return User::role($roles)
            ->when($buildingId, function ($query) use ($buildingId) {
                $query->where(function ($q) use ($buildingId) {
                    // Global access users always included
                    $q->whereHas('buildings', fn($b) => $b->where('buildings.id', $buildingId))
                        ->orWhereDoesntHave('buildings'); // global access users have no building restrictions
                });
            })
            ->where('is_active', true)
            ->get();
    }
}
