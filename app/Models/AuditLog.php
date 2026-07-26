<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Route;

class AuditLog extends Model
{
    // Audited model class => the admin route that shows/edits that resource.
    // Route keys come from each model's getRouteKey(), so reference-keyed
    // models (bookings, groups) resolve correctly alongside id-keyed ones.
    private const RESOURCE_ROUTES = [
        \App\Models\Booking::class            => 'manage.bookings.show',
        \App\Models\BookingGroup::class       => 'manage.booking-groups.show',
        \App\Models\ProcurementRequest::class => 'manage.procurement.show',
        \App\Models\MaintenanceReport::class  => 'manage.maintenance.show',
        \App\Models\Complaint::class          => 'manage.complaints.show',
        \App\Models\Task::class               => 'manage.tasks.show',
    ];

    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo('subject', 'model_type', 'model_id');
    }

    /**
     * A link to the audited resource, or null when it isn't linkable
     * (unknown type, no route, or the record no longer exists).
     */
    public function resourceUrl(): ?string
    {
        $routeName = self::RESOURCE_ROUTES[$this->model_type] ?? null;
        if (! $routeName || ! $this->subject || ! Route::has($routeName)) {
            return null;
        }

        try {
            return route($routeName, $this->subject->getRouteKey());
        } catch (\Throwable) {
            return null;
        }
    }

    public static function log(string $action, $model = null, ?array $oldValues = null, ?array $newValues = null): void
    {
        // Don't record the audit owner's own actions
        $ownerEmail = config('audit.owner_email');
        if ($ownerEmail && auth()->check() && auth()->user()->email === $ownerEmail) {
            return;
        }

        static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->header('CF-Connecting-IP') ?? request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
