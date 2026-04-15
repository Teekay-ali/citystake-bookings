<?php

namespace App\Models;

use App\Traits\HasBuildingScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffQuery extends Model
{
    use HasFactory, HasBuildingScope;

    protected $fillable = [
        'building_id',
        'staff_id',
        'issued_by',
        'subject',
        'description',
        'type',
        'status',
        'staff_response',
        'resolution',
        'responded_at',
        'closed_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
        'closed_at'    => 'datetime',
    ];

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public static function types(): array
    {
        return [
            'misconduct'       => 'Misconduct',
            'attendance'       => 'Attendance',
            'performance'      => 'Performance',
            'insubordination'  => 'Insubordination',
            'policy_violation' => 'Policy Violation',
            'other'            => 'Other',
        ];
    }
}
