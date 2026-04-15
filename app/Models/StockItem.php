<?php

namespace App\Models;

use App\Traits\HasBuildingScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockItem extends Model
{
    use HasFactory, HasBuildingScope;

    protected $fillable = [
        'building_id', 'created_by', 'name', 'category',
        'unit', 'quantity', 'low_stock_threshold', 'notes', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(StockLog::class);
    }

    public function isLowStock(): bool
    {
        return $this->quantity <= $this->low_stock_threshold;
    }
}
