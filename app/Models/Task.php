<?php

namespace App\Models;

use App\Traits\HasBuildingScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'building_id', 'created_by', 'assigned_to',
        'title', 'description', 'priority', 'status',
        'due_date', 'completed_at',
    ];

    protected $casts = [
        'due_date'     => 'date',
        'completed_at' => 'datetime',
    ];

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(TaskSubtask::class);
    }

    public function isOverdue(): bool
    {
        return $this->due_date
            && $this->due_date->isPast()
            && !in_array($this->status, ['completed', 'cancelled']);
    }

    public function completionPercent(): int
    {
        $total = $this->subtasks->count();
        if ($total === 0) return $this->status === 'completed' ? 100 : 0;
        return (int) round(($this->subtasks->where('completed', true)->count() / $total) * 100);
    }
}
