<?php

namespace App\Console\Commands;

use App\Mail\ManagedBackupFailureDigestMail;
use App\Services\ManagedBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Son 24 saatte başarısız olan yedeklerin günlük özet bildirimini HostVim ekibine yollar.
 * Başarısızlık yoksa mail atılmaz.
 */
class BackupsNotifyFailuresCommand extends Command
{
    protected $signature = 'backups:notify-failures {--hours=24}';

    protected $description = 'Başarısız yedeklerin günlük özet bildirimini gönderir';

    public function handle(ManagedBackupService $svc): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $failures = $svc->recentFailures($hours);

        if ($failures->isEmpty()) {
            $this->info('Başarısız yedek yok, bildirim gönderilmedi.');

            return self::SUCCESS;
        }

        $recipients = $svc->notifyRecipients();
        if ($recipients === []) {
            $this->warn('Bildirim alıcısı bulunamadı (managed_backup.notify_email veya admin e-postası).');

            return self::SUCCESS;
        }

        $rows = $failures->take(100)->map(fn ($b) => [
            'domain' => (string) ($b->domain?->name ?? ('#'.$b->domain_id)),
            'type' => (string) $b->type,
            'when' => optional($b->updated_at)->format('d.m.Y H:i') ?? '',
        ])->all();

        $panelName = (string) config('app.name', 'HostVim');

        try {
            Mail::to($recipients)->queue(new ManagedBackupFailureDigestMail(
                failures: $rows,
                totalCount: $failures->count(),
                panelName: $panelName,
            ));
            $this->info(sprintf('Bildirim kuyruğa alındı: %d başarısız, %d alıcı.', $failures->count(), count($recipients)));
        } catch (\Throwable $e) {
            Log::error('backups:notify-failures mail failed: '.$e->getMessage());
            $this->error('Mail gönderilemedi: '.$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
