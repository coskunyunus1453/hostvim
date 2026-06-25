<?php

namespace App\Services\Domain\Registrar;

use App\Models\DomainRegistrar;

/**
 * Gercek domain yonetim yetenekleri (DNS/NS/yenileme/gizlilik/transfer).
 * Yalnizca bu islemleri destekleyen registrar surucu(ler)i (orn. Spaceship) implement eder.
 * Diger suruculer (sadece fiyat/uygunluk) bunu implement ETMEZ; resolver instanceof ile kontrol eder.
 */
interface DomainManagementInterface
{
    /**
     * Saglayicidaki tum domainleri listeler.
     *
     * @return list<array{domain: string, expires_at: ?string, registered_at: ?string, auto_renew: bool, privacy: ?string, locked: bool, status: ?string, ns_provider: ?string, nameservers: list<string>}>
     */
    public function listDomains(DomainRegistrar $account): array;

    /**
     * Tek bir domainin guncel bilgisini doner.
     *
     * @return array{domain: string, expires_at: ?string, registered_at: ?string, auto_renew: bool, privacy: ?string, locked: bool, status: ?string, ns_provider: ?string, nameservers: list<string>}|null
     */
    public function getDomainInfo(DomainRegistrar $account, string $domain): ?array;

    /**
     * Nameserver'lari gunceller. provider 'basic' (varsayilan saglayici NS) veya 'custom'.
     *
     * @param  list<string>  $hosts
     * @return array{ok: bool, message: string}
     */
    public function setNameservers(DomainRegistrar $account, string $domain, string $provider, array $hosts = []): array;

    /**
     * WHOIS gizlilik seviyesini gunceller: 'public' | 'high'.
     *
     * @return array{ok: bool, message: string}
     */
    public function setPrivacy(DomainRegistrar $account, string $domain, string $level): array;

    /**
     * Otomatik yenilemeyi ac/kapat.
     *
     * @return array{ok: bool, message: string}
     */
    public function setAutoRenew(DomainRegistrar $account, string $domain, bool $enabled): array;

    /**
     * Domaini belirtilen yil kadar yeniler (suresini uzatir).
     *
     * @return array{ok: bool, message: string, expires_at?: ?string}
     */
    public function renewDomain(DomainRegistrar $account, string $domain, int $years): array;

    /**
     * DNS kayitlarini normalize edilmis halde listeler.
     *
     * @return list<array{type: string, name: string, value: string, ttl: int, priority: ?int}>
     */
    public function getDnsRecords(DomainRegistrar $account, string $domain): array;

    /**
     * DNS kayit kumesini hedef duruma senkronlar (ekle/guncelle + silinen kayitlari kaldir).
     *
     * @param  list<array{type: string, name: string, value: string, ttl?: int, priority?: ?int}>  $records
     * @return array{ok: bool, message: string}
     */
    public function syncDnsRecords(DomainRegistrar $account, string $domain, array $records): array;

    /**
     * Giden transfer icin EPP/auth (transfer) kodunu doner.
     *
     * @return array{ok: bool, code: ?string, message: string}
     */
    public function getAuthCode(DomainRegistrar $account, string $domain): array;

    /**
     * Domaini saglayicida gercek olarak register eder (otomatik satin alma).
     *
     * $registrant verilirse domain musteri adina (musteri bilgileriyle olusturulan
     * contact ile) kaydedilir; verilmezse/basarisiz olursa hesabin varsayilan
     * contact'ina dusulur. Beklenen anahtarlar: name, email, phone, address, city,
     * country, postal_code, company.
     *
     * @param  array<string, mixed>|null  $registrant
     * @return array{ok: bool, message: string, expires_at?: ?string, status?: ?string}
     */
    public function registerDomain(DomainRegistrar $account, string $domain, int $years, bool $autoRenew, bool $privacyHigh, ?array $registrant = null): array;
}
