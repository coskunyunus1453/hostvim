<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, LogsActivity, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'force_password_change',
        'password_set_at',
        'locale',
        'status',
        'parent_id',
        'hosting_package_id',
        'hosting_package_manual_override',
        'two_factor_secret',
        'two_factor_enabled',
        'onboarding_completed_at',
        'resellerclub_customer_id',
        'resellerclub_contact_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'force_password_change' => 'boolean',
            'password_set_at' => 'datetime',
            'two_factor_enabled' => 'boolean',
            'hosting_package_manual_override' => 'boolean',
            'onboarding_completed_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'status'])
            ->logOnlyDirty();
    }

    public function domains()
    {
        return $this->hasMany(Domain::class);
    }

    public function hostingPackage()
    {
        return $this->belongsTo(HostingPackage::class);
    }

    public function databases()
    {
        return $this->hasMany(Database::class);
    }

    public function emailAccounts()
    {
        return $this->hasMany(EmailAccount::class);
    }

    public function cronJobs()
    {
        return $this->hasMany(CronJob::class);
    }

    public function subUsers()
    {
        return $this->hasMany(User::class, 'parent_id');
    }

    public function parentUser()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function resellerWhiteLabel()
    {
        return $this->hasOne(ResellerWhiteLabel::class, 'user_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function supportTickets()
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function backups()
    {
        return $this->hasMany(Backup::class);
    }

    public function ftpAccounts()
    {
        return $this->hasMany(FtpAccount::class);
    }

    public function pluginModules()
    {
        return $this->belongsToMany(PluginModule::class, 'user_plugin_modules')
            ->withPivot(['status', 'is_active', 'installed_at', 'activated_at'])
            ->withTimestamps();
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isVendorAdmin(): bool
    {
        return $this->hasRole('vendor_admin');
    }

    public function isVendorOperator(): bool
    {
        return $this->hasAnyRole(['admin', 'vendor_admin', 'vendor_support', 'vendor_finance', 'vendor_devops']);
    }

    public function isReseller(): bool
    {
        return $this->hasRole('reseller');
    }

    /**
     * Sanctum token yetenekleri (Spatie permission adlarıyla aynı stringler).
     *
     * @return list<string>
     */
    public function sanctumAbilities(): array
    {
        if ($this->isAdmin()) {
            return ['*'];
        }

        $perms = $this->getAllPermissions()->pluck('name')->filter()->values()->all();

        return array_values(array_unique(array_merge(['access:customer-panel'], $perms)));
    }
}
