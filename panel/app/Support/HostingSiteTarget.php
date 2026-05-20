<?php

namespace App\Support;

use App\Models\Domain;
use App\Models\SiteSubdomain;

/**
 * Panelde yönetilen barındırma hedefi: ana domain veya alt alan adı.
 */
final class HostingSiteTarget
{
    public function __construct(
        public Domain $domain,
        public ?SiteSubdomain $subdomain,
        public string $hostname,
        public string $documentRoot,
        public string $engineSiteName,
    ) {}

    public function isSubdomain(): bool
    {
        return $this->subdomain !== null;
    }

    public function targetKey(): string
    {
        return $this->isSubdomain()
            ? 's-'.$this->subdomain->id
            : 'd-'.$this->domain->id;
    }

    public function label(): string
    {
        return $this->hostname;
    }

    public function phpVersion(): string
    {
        if ($this->subdomain?->php_version) {
            return (string) $this->subdomain->php_version;
        }

        return (string) ($this->domain->php_version ?? '8.2');
    }

    public function serverType(): string
    {
        if ($this->subdomain?->server_type) {
            return (string) $this->subdomain->server_type;
        }

        return (string) ($this->domain->server_type ?? 'nginx');
    }
}
