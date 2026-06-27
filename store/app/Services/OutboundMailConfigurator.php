<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

class OutboundMailConfigurator
{
    /**
     * site_settings içindeki outbound_mail.* değerlerini Laravel mail yapılandırmasına uygular.
     */
    public static function apply(bool $force = false): void
    {
        static $applied = false;

        // Uzun ömürlü queue worker'larında ayarlar bir kez uygulanıp "stale" kalabiliyor.
        // $force=true ile (her job öncesi) güncel DB ayarları yeniden uygulanır.
        if ($applied && ! $force) {
            return;
        }

        $applied = true;

        try {
            $settings = self::mailSettings();

            if ($settings->isEmpty()) {
                return;
            }

            $driver = $settings->get('outbound_mail.driver');
            if (! is_string($driver) || ! in_array($driver, ['smtp', 'sendmail', 'log'], true)) {
                return;
            }

            Config::set('mail.default', $driver);

            if ($driver === 'smtp') {
                $host = $settings->get('outbound_mail.smtp_host');
                Config::set('mail.mailers.smtp.host', is_string($host) && $host !== '' ? $host : '127.0.0.1');

                $port = $settings->get('outbound_mail.smtp_port');
                $port = is_numeric($port) ? (int) $port : 587;
                Config::set('mail.mailers.smtp.port', $port);

                $user = $settings->get('outbound_mail.smtp_username');
                Config::set('mail.mailers.smtp.username', is_string($user) && $user !== '' ? $user : null);

                $encRaw = $settings->get('outbound_mail.smtp_encryption');
                $enc = is_string($encRaw) ? strtolower(trim($encRaw)) : '';
                if (! in_array($enc, ['', 'tls', 'ssl'], true)) {
                    $enc = '';
                }

                Config::set('mail.mailers.smtp.scheme', ($enc === 'ssl' || $port === 465) ? 'smtps' : 'smtp');
                Config::set('mail.mailers.smtp.url', null);

                $rawPass = $settings->get('outbound_mail.smtp_password');
                $plain = null;
                if (is_string($rawPass) && $rawPass !== '') {
                    try {
                        $plain = decrypt($rawPass);
                    } catch (\Throwable) {
                        $plain = $rawPass;
                    }
                }
                Config::set('mail.mailers.smtp.password', $plain);

                $verifyPeer = $settings->get('outbound_mail.smtp_verify_peer');
                $skipVerify = in_array($verifyPeer, ['0', 'false', 'no'], true);
                Config::set('mail.mailers.smtp.verify_peer', $skipVerify ? false : true);
            }

            $addr = $settings->get('outbound_mail.from_address');
            if (is_string($addr) && $addr !== '') {
                Config::set('mail.from.address', $addr);
            }
            $name = $settings->get('outbound_mail.from_name');
            if (is_string($name) && $name !== '') {
                Config::set('mail.from.name', $name);
            }
        } catch (\Throwable) {
            // .env tabanlı yapılandırma geçerli kalır
        } finally {
            if (app()->bound('mail.manager')) {
                app('mail.manager')->forgetMailers();
            }
        }
    }

    /**
     * @return Collection<string, mixed>
     */
    protected static function mailSettings(): Collection
    {
        // SettingsService bir ServiceProvider'da explicit bind/singleton ile kaydedilmedigi
        // icin app()->bound(...) HER ZAMAN false donerdi; bu da mail ayarlarinin hic
        // uygulanmamasina (mail 'log' surucusunde kalip musteriye gitmemesine) yol aciyordu.
        // Concrete sinif oldugu icin dogrudan resolve edip hatayi yakaliyoruz.
        try {
            $service = app(SettingsService::class);
        } catch (\Throwable) {
            return collect();
        }

        return collect($service->all())
            ->filter(fn (mixed $value, string $key): bool => str_starts_with($key, 'outbound_mail.'));
    }
}
