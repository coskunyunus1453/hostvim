<?php

namespace App\Services;

use App\Models\PanelRelease;
use Illuminate\Support\Collection;

class PanelReleaseQueryService
{
    /**
     * @return Collection<int, PanelRelease>
     */
    public function publishedFor(string $channel, string $profile): Collection
    {
        return PanelRelease::query()
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where('channel', $channel)
            ->orderByDesc('published_at')
            ->get()
            ->filter(fn (PanelRelease $r): bool => $r->matchesProfile($profile))
            ->values();
    }

    public function latestFor(string $channel, string $profile): ?PanelRelease
    {
        $rows = $this->publishedFor($channel, $profile);

        $best = null;
        foreach ($rows as $row) {
            if ($best === null || version_compare($row->version, $best->version, '>')) {
                $best = $row;
            }
        }

        return $best;
    }

    public function canUpgradeFrom(string $currentVersion, PanelRelease $target): bool
    {
        if (version_compare($currentVersion, $target->version, '>=')) {
            return false;
        }

        $min = trim((string) ($target->min_panel_version ?? ''));
        if ($min !== '' && version_compare($currentVersion, $min, '<')) {
            return false;
        }

        if ($target->artifact_url === null && $target->git_tag === null) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeRelease(PanelRelease $release): array
    {
        return [
            'version' => $release->version,
            'title' => $release->title,
            'changelog' => $release->changelog,
            'channel' => $release->channel,
            'profile' => $release->profile,
            'published_at' => $release->published_at?->toIso8601String(),
            'artifact_url' => $release->artifact_url,
            'artifact_sha256' => $release->artifact_sha256,
            'git_tag' => $release->git_tag,
            'min_panel_version' => $release->min_panel_version,
            'requires_engine_restart' => (bool) $release->requires_engine_restart,
        ];
    }
}
