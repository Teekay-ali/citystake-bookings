<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    /**
     * Dispatch a notification to recipients without letting a failed channel
     * (e.g. SMTP timing out) bubble up and 500 the triggering action.
     * The in-app database notifications are best-effort; mail failures are logged.
     */
    public static function send($notifiables, $notification): void
    {
        try {
            Notification::send($notifiables, $notification);
        } catch (\Throwable $e) {
            Log::error('Notification dispatch failed', [
                'notification' => get_class($notification),
                'error'        => $e->getMessage(),
            ]);
        }
    }

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
