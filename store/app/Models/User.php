<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'panel_user_id',
        'name',
        'email',
        'password',
        'phone',
        'company',
        'address',
        'city',
        'district',
        'postal_code',
        'country',
        'tax_office',
        'tax_number',
        'billing_company',
        'billing_address',
        'billing_city',
        'billing_district',
        'billing_postal_code',
        'billing_country',
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
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin;
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function blogPosts(): HasMany
    {
        return $this->hasMany(BlogPost::class);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notifyNow(new ResetPasswordNotification($token));
    }
}
