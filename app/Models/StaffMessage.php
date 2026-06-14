<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class StaffMessage extends Model
{
    protected $fillable = [
        'sender_id', 'subject', 'body', 'parent_id', 'broadcast_role',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(StaffMessage::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(StaffMessage::class, 'parent_id')->with('sender:id,name')->latest();
    }

    public function recipients(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'staff_message_recipients')
            ->withPivot('read_at', 'deleted_at');
    }

    public function isReadBy(User $user): bool
    {
        return $this->recipients()
            ->where('user_id', $user->id)
            ->whereNotNull('staff_message_recipients.read_at')
            ->exists();
    }
}
