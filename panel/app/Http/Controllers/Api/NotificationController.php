<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Models\DeploymentRun;
use App\Models\InstallerRun;
use App\Models\SystemAlert;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NotificationController extends Controller
{
    private const DISMISS_LIMIT = 500;

    public function feed(Request $request): JsonResponse
    {
        $user = $request->user();
        $items = $this->buildFeedItems($user);
        $dismissed = $this->dismissedIdsForUser((int) $user->id);
        if ($dismissed !== []) {
            $dismissedSet = array_flip($dismissed);
            $items = array_values(array_filter($items, fn (array $row) => ! isset($dismissedSet[$row['id'] ?? ''])));
        }

        return response()->json(['items' => $items]);
    }

    public function dismiss(Request $request): JsonResponse
    {
        $user = $request->user();
        $userId = (int) $user->id;

        $validated = $request->validate([
            'ids' => 'sometimes|array|max:120',
            'ids.*' => 'string|max:80',
            'clear_all' => 'sometimes|boolean',
        ]);

        $toDismiss = $validated['ids'] ?? [];
        if ($request->boolean('clear_all')) {
            $toDismiss = array_merge($toDismiss, array_column($this->buildFeedItems($user), 'id'));
        }

        $merged = $this->mergeDismissedIds($userId, $toDismiss);

        return response()->json([
            'ok' => true,
            'dismissed_count' => count($merged),
        ]);
    }

    /**
     * @return list<array{id: string, level: string, title: string, message: ?string, path: ?string, created_at: ?string}>
     */
    private function buildFeedItems(User $user): array
    {
        $userId = (int) $user->id;
        $cacheKey = 'notifications:feed:'.$userId.':'.($user->isAdmin() || $user->isVendorOperator() ? 'ops' : 'user');

        return Cache::remember($cacheKey, 45, function () use ($user, $userId): array {
            $items = [];

            foreach (InstallerRun::query()->where('user_id', $userId)->latest('id')->limit(10)->get(['id', 'status', 'app', 'message', 'created_at']) as $r) {
                $items[] = [
                    'id' => 'installer-'.$r->id,
                    'level' => $r->status === 'failed' ? 'error' : ($r->status === 'success' ? 'success' : 'info'),
                    'title' => 'Installer: '.strtoupper((string) $r->app),
                    'message' => $r->message,
                    'path' => '/installer',
                    'created_at' => optional($r->created_at)->toIso8601String(),
                ];
            }

            foreach (DeploymentRun::query()->where('user_id', $userId)->latest('id')->limit(10)->get(['id', 'status', 'trigger', 'commit_hash', 'created_at']) as $r) {
                $items[] = [
                    'id' => 'deploy-'.$r->id,
                    'level' => $r->status === 'failed' ? 'error' : ($r->status === 'success' ? 'success' : 'info'),
                    'title' => 'Deploy: '.$r->trigger,
                    'message' => $r->commit_hash ? ('commit: '.$r->commit_hash) : $r->status,
                    'path' => '/deploy',
                    'created_at' => optional($r->created_at)->toIso8601String(),
                ];
            }

            foreach (Backup::query()->where('user_id', $userId)->latest('id')->limit(10)->get(['id', 'status', 'type', 'created_at']) as $r) {
                $items[] = [
                    'id' => 'backup-'.$r->id,
                    'level' => $r->status === 'failed' ? 'error' : ($r->status === 'completed' ? 'success' : 'info'),
                    'title' => 'Backup: '.($r->type ?: 'full'),
                    'message' => $r->status,
                    'path' => '/backups',
                    'created_at' => optional($r->created_at)->toIso8601String(),
                ];
            }

            if ($user->isAdmin() || $user->isVendorOperator()) {
                foreach (SystemAlert::query()->latest('id')->limit(12)->get(['id', 'level', 'title', 'message', 'path', 'created_at']) as $a) {
                    $items[] = [
                        'id' => 'sysalert-'.$a->id,
                        'level' => $a->level ?: 'info',
                        'title' => $a->title,
                        'message' => $a->message,
                        'path' => $a->path ?: '/system',
                        'created_at' => optional($a->created_at)->toIso8601String(),
                    ];
                }
            }

            usort($items, fn ($a, $b) => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));

            return array_slice($items, 0, 40);
        });
    }

    /**
     * @return list<string>
     */
    private function dismissedIdsForUser(int $userId): array
    {
        $raw = Cache::get($this->dismissCacheKey($userId), []);

        return is_array($raw) ? array_values(array_filter($raw, 'is_string')) : [];
    }

    /**
     * @param  list<string>  $ids
     * @return list<string>
     */
    private function mergeDismissedIds(int $userId, array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids, fn ($id) => is_string($id) && $id !== '')));
        if ($ids === []) {
            return $this->dismissedIdsForUser($userId);
        }

        $merged = array_values(array_unique(array_merge($this->dismissedIdsForUser($userId), $ids)));
        if (count($merged) > self::DISMISS_LIMIT) {
            $merged = array_slice($merged, -self::DISMISS_LIMIT);
        }

        Cache::forever($this->dismissCacheKey($userId), $merged);

        return $merged;
    }

    private function dismissCacheKey(int $userId): string
    {
        return 'notifications:dismissed:'.$userId;
    }
}
