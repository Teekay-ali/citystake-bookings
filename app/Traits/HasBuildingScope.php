<?php

namespace App\Traits;

trait HasBuildingScope
{
    public function scopeScopedToUser($query, $user)
    {
        if ($user && !$user->hasGlobalAccess()) {
            $query->whereIn('building_id', $user->accessibleBuildingIds());
        }

        return $query;
    }
}
