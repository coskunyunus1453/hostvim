<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PanelRelease extends Model
{
    protected $fillable = [
        'version',
        'channel',
        'profile',
        'title',
        'changelog',
        'artifact_url',
        'artifact_sha256',
        'git_tag',
        'min_panel_version',
        'requires_engine_restart',
        'is_published',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'requires_engine_restart' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function isVisibleNow(): bool
    {
        if (! $this->is_published || $this->published_at === null) {
            return false;
        }

        return $this->published_at->lte(now());
    }

    public function matchesProfile(string $profile): bool
    {
        $p = strtolower(trim($this->profile ?: 'all'));
        $req = strtolower(trim($profile ?: 'customer'));

        return $p === 'all' || $p === $req;
    }
}
