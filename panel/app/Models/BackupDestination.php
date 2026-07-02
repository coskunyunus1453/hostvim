<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BackupDestination extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'driver',
        'config',
        'is_default',
        'is_active',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'encrypted:array',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'is_system' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Müşteri hedefleri (şirket havuzu hariç). */
    public function scopeCustomer($query)
    {
        return $query->where('is_system', false);
    }

    /** Şirket (merkezi) yedekleme havuzu hedefleri. */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }
}
