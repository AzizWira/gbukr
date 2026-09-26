<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'admin_enabled',
        'google_id',
        'active',
        'email_verified_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
            'admin_enabled' => 'boolean',
        ];
    }

    public function customerProfile()
    {
        return $this->hasOne(CustomerProfile::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'customer_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'customer_id');
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function canAccessAdmin(): bool
    {
        // Fallback role=admin menjaga sesi lama tetap aman sebelum migration dijalankan.
        return $this->isOwner() || $this->admin_enabled || $this->role === 'admin';
    }

    public function currentMode(): string
    {
        if ($this->isOwner()) {
            return 'owner';
        }

        if ($this->canAccessAdmin()) {
            $mode = session()->get('acting_as');
            if (in_array($mode, ['admin', 'customer'], true)) {
                return $mode;
            }

            return 'unselected';
        }

        return 'customer';
    }

    public function isStaff(): bool
    {
        return in_array($this->currentMode(), ['owner', 'admin'], true);
    }

    public function isCustomerMode(): bool
    {
        return $this->currentMode() === 'customer';
    }
}
