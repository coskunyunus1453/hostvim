<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatabaseImportRun extends Model
{
    protected $fillable = [
        'user_id',
        'database_id',
        'status',
        'progress',
        'phase',
        'message',
        'error_message',
        'file_path',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'progress' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function database(): BelongsTo
    {
        return $this->belongsTo(Database::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function touchProgress(int $progress, string $phase, ?string $message = null): void
    {
        $this->progress = max(0, min(100, $progress));
        $this->phase = $phase;
        if ($message !== null) {
            $this->message = $message;
        }
        $this->save();
    }
}
