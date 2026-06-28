<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\DomainName;
use App\Models\OwnershipTransferRequest;
use App\Services\Domain\DomainManagementService;
use Illuminate\Http\Request;
use Throwable;

class DomainController extends Controller
{
    public function __construct(private DomainManagementService $domains) {}

    public function index(Request $request)
    {
        $list = DomainName::query()
            ->where('customer_email', $request->user()->email)
            ->orderBy('domain')
            ->get();

        return view('account.domains', [
            'domains' => $list,
        ]);
    }

    public function show(Request $request, int $id)
    {
        $domain = $this->ownedDomain($request, $id);

        $records = [];
        $dnsError = null;
        if ($this->isManageable($domain)) {
            try {
                $records = $this->domains->dnsRecords($domain);
            } catch (Throwable $e) {
                $dnsError = $e->getMessage();
            }
        }

        $pendingTransfer = OwnershipTransferRequest::query()
            ->where('domain_name_id', $domain->id)
            ->where('status', OwnershipTransferRequest::STATUS_PENDING)
            ->latest()
            ->first();

        return view('account.domain-detail', [
            'domain' => $domain,
            'records' => $records,
            'dnsError' => $dnsError,
            'pendingTransfer' => $pendingTransfer,
        ]);
    }

    public function saveDns(Request $request, int $id)
    {
        $domain = $this->ownedDomain($request, $id);

        $validated = $request->validate([
            'records' => ['array'],
            'records.*.type' => ['required', 'string', 'in:A,AAAA,CNAME,MX,TXT,NS,CAA,SRV,ALIAS'],
            'records.*.name' => ['nullable', 'string', 'max:253'],
            'records.*.value' => ['required', 'string', 'max:2000'],
            'records.*.ttl' => ['nullable', 'integer', 'min:60', 'max:604800'],
            'records.*.priority' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);

        $records = array_map(function (array $r): array {
            return [
                'type' => $r['type'],
                'name' => trim((string) ($r['name'] ?? '')) ?: '@',
                'value' => trim((string) $r['value']),
                'ttl' => (int) ($r['ttl'] ?? 3600),
                'priority' => isset($r['priority']) && $r['priority'] !== null ? (int) $r['priority'] : null,
            ];
        }, $validated['records'] ?? []);

        return $this->run($domain, fn () => $this->domains->saveDnsRecords($domain, $records), 'DNS kayıtları güncellendi.');
    }

    public function nameservers(Request $request, int $id)
    {
        $domain = $this->ownedDomain($request, $id);

        $validated = $request->validate([
            'provider' => ['required', 'string', 'in:basic,custom'],
            'hosts' => ['nullable', 'string'],
        ]);

        $hosts = [];
        if ($validated['provider'] === 'custom') {
            $hosts = array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', (string) ($validated['hosts'] ?? '')))));
            if (count($hosts) < 2) {
                return back()->with('error', 'Özel nameserver için en az 2 sunucu girmelisiniz.');
            }
        }

        return $this->run($domain, fn () => $this->domains->setNameservers($domain, $validated['provider'], $hosts), 'Nameserver ayarları güncellendi.');
    }

    public function renew(Request $request, int $id)
    {
        $domain = $this->ownedDomain($request, $id);
        $validated = $request->validate([
            'years' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        return $this->run($domain, fn () => $this->domains->renew($domain, (int) $validated['years']), 'Alan adı yenilendi.');
    }

    public function privacy(Request $request, int $id)
    {
        $domain = $this->ownedDomain($request, $id);

        return $this->run($domain, fn () => $this->domains->setPrivacy($domain, ! $domain->privacyEnabled()), 'Gizlilik ayarı güncellendi.');
    }

    public function autoRenew(Request $request, int $id)
    {
        $domain = $this->ownedDomain($request, $id);

        return $this->run($domain, fn () => $this->domains->setAutoRenew($domain, ! $domain->auto_renew), 'Otomatik yenileme ayarı güncellendi.');
    }

    public function authCode(Request $request, int $id)
    {
        $domain = $this->ownedDomain($request, $id);

        try {
            $result = $this->domains->authCode($domain);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        if (! ($result['ok'] ?? false) || empty($result['code'])) {
            return back()->with('error', $result['message'] ?? 'Transfer kodu alınamadı.');
        }

        return back()->with('auth_code', $result['code']);
    }

    /**
     * @param  callable():array{ok: bool, message: string}  $callback
     */
    private function run(DomainName $domain, callable $callback, string $successMessage)
    {
        if (! $this->isManageable($domain)) {
            return back()->with('error', 'Bu alan adı henüz yönetime hazır değil.');
        }

        try {
            $result = $callback();
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        if (! ($result['ok'] ?? false)) {
            return back()->with('error', $result['message'] ?? 'İşlem başarısız.');
        }

        return back()->with('success', $successMessage);
    }

    private function ownedDomain(Request $request, int $id): DomainName
    {
        $domain = DomainName::query()->findOrFail($id);
        $email = (string) $request->user()->email;
        abort_unless($domain->customer_email !== null && strcasecmp($domain->customer_email, $email) === 0, 403);

        return $domain;
    }

    private function isManageable(DomainName $domain): bool
    {
        return in_array($domain->status, ['registered', 'active'], true);
    }
}
