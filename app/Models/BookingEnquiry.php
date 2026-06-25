<?php

namespace App\Models;

use App\Traits\HasBuildingScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingEnquiry extends Model
{
    use HasFactory, HasBuildingScope;

    protected $fillable = [
        'building_id', 'unit_type_id',
        'check_in', 'check_out', 'guests',
        'guest_name', 'guest_email', 'guest_phone', 'special_requests',
        'status', 'converted_booking_id', 'handled_by', 'staff_notes',
    ];

    protected $casts = [
        'check_in'  => 'date',
        'check_out' => 'date',
        'guests'    => 'integer',
    ];

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function unitType(): BelongsTo
    {
        return $this->belongsTo(UnitType::class);
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function convertedBooking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'converted_booking_id');
    }

    public function nights(): int
    {
        return $this->check_in->diffInDays($this->check_out);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'new'       => 'New',
            'contacted' => 'Contacted',
            'converted' => 'Converted',
            'closed'    => 'Closed',
            default     => ucfirst($this->status),
        };
    }
}
