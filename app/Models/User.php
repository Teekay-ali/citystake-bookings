<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'is_admin',
        'email_marketing',      // Promos & offers
        'email_reminders',      // Check-in reminders
        'email_newsletters',    // Monthly updates
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'email_marketing' => 'boolean',
            'email_reminders' => 'boolean',
            'email_newsletters' => 'boolean',
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Check if user should receive marketing emails
     */
    public static function shouldReceiveMarketing(string $email): bool
    {
        $user = static::where('email', $email)->first();
        return !$user || $user->email_marketing;
    }

    /**
     * Check if user should receive reminders
     */
    public static function shouldReceiveReminders(string $email): bool
    {
        $user = static::where('email', $email)->first();
        return !$user || $user->email_reminders;
    }

    /**
     * Check if user should receive newsletters
     */
    public static function shouldReceiveNewsletters(string $email): bool
    {
        $user = static::where('email', $email)->first();
        return !$user || $user->email_newsletters;
    }
}
