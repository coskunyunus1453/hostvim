<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Backup extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'domain_id',
        'destination_id',
        'type',
        'level',
        'parent_backup_id',
        'base_backup_id',
        'file_path',
        'snapshot_path',
        'remote_path',
        'remote_file_id',
        'engine_backup_id',
        'size_mb',
        'status',
        'completed_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
            'level' => 'integer',
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

    /** Zincirdeki bir önceki yedek (arttırımlı için). */
    public function parent()
    {
        return $this->belongsTo(Backup::class, 'parent_backup_id');
    }

    /** Zinciri başlatan level-0 tam (base) yedek. */
    public function base()
    {
        return $this->belongsTo(Backup::class, 'base_backup_id');
    }

    /** Bu yedeği base alan arttırımlı yedekler. */
    public function increments()
    {
        return $this->hasMany(Backup::class, 'base_backup_id');
    }

    public function isIncremental(): bool
    {
        return (int) $this->level > 0;
    }

    /**
     * Bu yedeği geri yüklemek için gereken zincir: base → ... → bu yedek (sıralı).
     *
     * @return array<int, Backup>
     */
    public function restoreChain(): array
    {
        $chain = [$this];
        $node = $this;
        $guard = 0;
        while ($node->parent_backup_id && $guard++ < 1000) {
            $parent = Backup::query()->find($node->parent_backup_id);
            if (! $parent) {
                break;
            }
            array_unshift($chain, $parent);
            $node = $parent;
        }

        return $chain;
    }
}
