<?php

namespace App\Jobs;

use App\Models\PanelUpdateRun;
use App\Services\PanelUpdateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class RunPanelUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    public function __construct(
        private readonly int $runId,
    ) {}

    public function handle(PanelUpdateService $updates): void
    {
        $run = PanelUpdateRun::query()->find($this->runId);
        if (! $run || ! $run->isActive()) {
            return;
        }

        $release = is_array($run->release_payload) ? $run->release_payload : [];
        $logPath = storage_path('logs/panel-update-'.$run->id.'.log');
        File::ensureDirectoryExists(dirname($logPath));

        $run->status = 'running';
        $run->progress = 5;
        $run->message = 'Güncelleme başlatılıyor…';
        $run->started_at = now();
        $run->save();

        File::put($updates->maintenanceFlagPath(), (string) now()->toIso8601String());

        try {
            $this->runScript($updates, $run, $release, $logPath);
        } catch (\Throwable $e) {
            // Log yazımı veya süreç yönetimi hatası güncellemeyi kilitlemesin.
            report($e);
            @unlink($updates->maintenanceFlagPath());

            $run->refresh();
            if ($run->isActive()) {
                $run->status = 'failed';
                $run->progress = 100;
                $run->message = 'Güncelleme hatası: '.mb_substr($e->getMessage(), 0, 300);
                $run->finished_at = now();
                $run->save();
            }
        }
    }

    private function runScript(PanelUpdateService $updates, PanelUpdateRun $run, array $release, string $logPath): void
    {
        $home = dirname(base_path());
        $script = '/usr/local/sbin/panelze-panel-update';
        if (! is_executable($script)) {
            $repoScript = $home.'/deploy/host/panelze-panel-update';
            if (is_file($repoScript)) {
                $script = $repoScript;
            }
        }

        $args = [
            $script,
            '--version='.(string) ($release['version'] ?? $run->to_version),
            '--home='.$home,
            '--log-file='.$logPath,
        ];
        if (! empty($release['artifact_url'])) {
            $args[] = '--artifact-url='.(string) $release['artifact_url'];
        }
        if (! empty($release['artifact_sha256'])) {
            $args[] = '--artifact-sha256='.(string) $release['artifact_sha256'];
        }
        if (! empty($release['git_tag'])) {
            $args[] = '--git-tag='.(string) $release['git_tag'];
        }
        if (! empty($release['requires_engine_restart'])) {
            $args[] = '--rebuild-engine=1';
        }

        $useSudo = is_executable('/usr/local/sbin/panelze-panel-update');
        if ($useSudo) {
            array_unshift($args, 'sudo', '-n');
        }

        $process = new Process($args, null, null, null, 3600);
        $process->run(function (string $type, string $buffer) use ($run, $logPath): void {
            // Betik root olarak log dosyasına yazabildiği için burada izin
            // hatası olabilir; güncellemeyi asla düşürmemeli.
            try {
                File::append($logPath, $buffer);
            } catch (\Throwable) {
                // yoksay
            }

            if ($type === 'err' || ! str_contains($buffer, '==>')) {
                return;
            }

            try {
                $run->message = trim(substr(trim($buffer), 0, 480));
                $run->progress = min(95, (int) $run->progress + 8);
                $run->save();
            } catch (\Throwable) {
                // yoksay
            }
        });

        @unlink($updates->maintenanceFlagPath());

        $output = File::exists($logPath) ? (string) File::get($logPath) : $process->getOutput().$process->getErrorOutput();

        if (! $process->isSuccessful()) {
            $run->status = 'failed';
            $run->progress = 100;
            $run->message = 'Güncelleme başarısız (çıkış '.$process->getExitCode().')';
            $run->output = mb_substr($output, 0, 65000);
            $run->finished_at = now();
            $run->save();

            return;
        }

        $run->status = 'success';
        $run->progress = 100;
        $run->message = 'Panel '.$run->to_version.' sürümüne güncellendi. Sayfayı yenileyin.';
        $run->output = mb_substr($output, 0, 65000);
        $run->finished_at = now();
        $run->save();
    }
}
