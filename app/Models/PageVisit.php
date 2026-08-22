<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One recorded page view by a staff/admin user inside the /manage area. Written
 * by the TrackPageVisit middleware; aggregated by the usage-analytics screen.
 */
class PageVisit extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'route_name', 'path', 'user_agent', 'visited_at'];

    protected $casts = ['visited_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
