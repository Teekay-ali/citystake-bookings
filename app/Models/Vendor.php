<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'company',
        'category',
        'phone',
        'email',
        'address',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'rating',
        'notes',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'rating'    => 'decimal:1',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function categories(): array
    {
        return [
            'plumbing'    => 'Plumbing',
            'electrical'  => 'Electrical',
            'ac_hvac'     => 'AC / HVAC',
            'carpentry'   => 'Carpentry',
            'cleaning'    => 'Cleaning',
            'security'    => 'Security',
            'it_telecoms' => 'IT & Telecoms',
            'landscaping' => 'Landscaping',
            'painting'    => 'Painting',
            'general'     => 'General Contractor',
            'other'       => 'Other',
        ];
    }
}
