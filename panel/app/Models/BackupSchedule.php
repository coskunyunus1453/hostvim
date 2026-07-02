<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BackupSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'domain_id',
        'destination_id',
        'type',
        'full_interval_days',
        'retention_count',
        'schedule',
        'enabled',
        'is_managed',
        'last_run_at',
        'next_run_at',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'is_managed' => 'boolean',
            'last_run_at' => 'datetime',
            'next_run_at' => 'datetime',
            'full_interval_days' => 'integer',
            'retention_count' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }

    public function destination()
    {
        return $this->belongsTo(BackupDestination::class, 'destination_id');
    }
}
