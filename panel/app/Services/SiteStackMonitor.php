<?php

namespace App\Services;

use App\Models\Domain;
use App\Models\SiteStackAlert;
use Illuminate\Support\Facades\Log;

class SiteStackMonitor
{
    public function __construct(
        private SiteStackAdvisor $advisor,
        private SiteStackTranslator $translator,
    ) {}

    /**
     * Tüm aktif alan adlarını tarar; yeni sorunlarda bildirim kaydı oluşturur.
     *
     * @return array{scanned:int, alerted:int, cleared:int, errors:int}
     */
    public function runHourly(): array
    {
        $stats = ['scanned' => 0, 'alerted' => 0, 'cleared' => 0, 'errors' => 0];

        Domain::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->chunkById(40, function ($domains) use (&$stats): void {
                foreach ($domains as $domain) {
                    $stats['scanned']++;
                    try {
                        $this->processDomain($domain, $stats);
                    } catch (\Throwable $e) {
                        $stats['errors']++;
                        Log::warning('Site stack hourly scan failed', [
                            'domain' => $domain->name,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        return $stats;
    }

    /**
     * @param  array{scanned:int, alerted:int, cleared:int, errors:int}  $stats
     */
    private function processDomain(Domain $domain, array &$stats): void
    {
        $report = $this->advisor->scan($domain);
        if (! empty($report['error'])) {
            $stats['errors']++;

            return;
        }

        $issues = (array) ($report['issues'] ?? []);
        $critical = array_filter($issues, fn ($i) => is_array($i) && ($i['severity'] ?? '') === 'critical');

        if ($issues === []) {
            $closed = SiteStackAlert::query()
                ->where('domain_id', $domain->id)
                ->where('status', 'open')
                ->update(['status' => 'resolved', 'dismissed_at' => now()]);
            $stats['cleared'] += $closed;

            return;
        }

        $codes = [];
        foreach ($issues as $issue) {
            if (is_array($issue) && ! empty($issue['code'])) {
                $codes[] = (string) $issue['code'];
            }
        }
        sort($codes);
        $fingerprint = hash('sha256', $domain->id.'|'.implode(',', $codes));
        $severity = $critical !== [] ? 'critical' : 'warning';
        $profile = (string) (($report['scan'] ?? [])['profile'] ?? 'standard');

        $existing = SiteStackAlert::query()
            ->where('domain_id', $domain->id)
            ->where('status', 'open')
            ->first();

        if ($existing && $existing->fingerprint === $fingerprint) {
            return;
        }

        if ($existing) {
            $existing->update(['status' => 'superseded', 'dismissed_at' => now()]);
        }

        SiteStackAlert::query()->create([
            'user_id' => $domain->user_id,
            'domain_id' => $domain->id,
            'domain_name' => $domain->name,
            'profile' => $profile,
            'severity' => $severity,
            'fingerprint' => $fingerprint,
            'status' => 'open',
            'issue_codes' => $codes,
            'issue_count' => count($issues),
            'notified_at' => now(),
        ]);
        $stats['alerted']++;
    }
}
