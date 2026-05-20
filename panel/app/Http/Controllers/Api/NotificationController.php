<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Models\CronJobRun;
use App\Models\DeploymentRun;
use App\Models\InstallerRun;
use App\Models\SiteStackAlert;
use App\Models\SystemAlert;
use App\Services\SiteStackTranslator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NotificationController extends Controller
{
    public function feed(Request $request, SiteStackTranslator $stackTranslator): JsonResponse
    {
        $user = $request->user();
        $userId = (int) $user->id;
        $cacheKey = 'notifications:feed:'.$userId.':'.($user->isAdmin() || $user->isVendorOperator() ? 'ops' : 'user');

        $items = Cache::remember($cacheKey, 45, function () use ($user, $userId, $stackTranslator): array {
            $items = [];

            foreach (SiteStackAlert::query()->where('user_id', $userId)->where('status', 'open')->latest('id')->limit(8)->get() as $a) {
                $items[] = [
                    'id' => 'stack-'.$a->id,
                    'level' => $a->severity === 'critical' ? 'error' : 'warning',
                    'title' => $stackTranslator->alertTitle($a->domain_name, $a->profile, (int) $a->issue_count),
                    'message' => __('domains.stack_alert_open_hint'),
                    'path' => '/domains',
                    'meta' => ['stack_alert_id' => $a->id, 'domain_id' => $a->domain_id],
                    'created_at' => optional($a->created_at)->toIso8601String(),
                ];
            }

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

            foreach (CronJobRun::query()->where('user_id', $userId)->latest('id')->limit(10)->get(['id', 'status', 'exit_code', 'created_at']) as $r) {
                $items[] = [
                    'id' => 'cron-'.$r->id,
                    'level' => $r->status === 'failed' ? 'error' : 'info',
                    'title' => 'Cron run #'.$r->id,
                    'message' => $r->status.($r->exit_code !== null ? (' (exit '.$r->exit_code.')') : ''),
                    'path' => '/cron',
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

        return response()->json(['items' => $items]);
    }
}
