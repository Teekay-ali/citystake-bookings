<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockedDate extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_id',
        'blocked_from',
        'blocked_to',
        'reason',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'blocked_from' => 'date',
        'blocked_to' => 'date',
    ];

    /**
     * Get the unit that owns the blocked date
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the admin who created the block
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if a date range overlaps with this blocked period
     */
    public function overlaps($from, $to): bool
    {
        return $this->blocked_from <= $to && $this->blocked_to >= $from;
    }
}
