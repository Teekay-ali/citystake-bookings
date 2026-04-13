<?php

namespace App\Traits;

use App\Models\Building;
use Illuminate\Database\Eloquent\Builder;

trait ScopedByBuilding
{
    protected function scopeToBuildings(Builder $query, string $column = 'building_id'): Builder
    {
        $user = auth()->user();

        if ($user->hasGlobalAccess()) {
            return $query;
        }

        return $query->whereIn($column, $user->accessibleBuildingIds() ?? []);
    }

    protected function accessibleBuildings()
    {
        $user = auth()->user();

        $query = Building::where('is_active', true);

        if (!$user->hasGlobalAccess()) {
            $query->whereIn('id', $user->accessibleBuildingIds() ?? []);
        }

        return $query;
    }
}
