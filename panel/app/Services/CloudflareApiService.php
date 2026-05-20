<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudflareApiService
{
    private const BASE = 'https://api.cloudflare.com/client/v4';

    /**
     * @return array{ok: bool, error?: string, data?: mixed}
     */
    public function verifyToken(string $token): array
    {
        return $this->request('GET', '/user/tokens/verify', $token);
    }

    /**
     * @return array{ok: bool, error?: string, zones?: array<int, array<string, mixed>>}
     */
    public function listZones(string $token, ?string $name = null): array
    {
        $query = ['per_page' => 50];
        if ($name !== null && trim($name) !== '') {
            $query['name'] = strtolower(trim($name));
        }
        $resp = $this->request('GET', '/zones', $token, null, $query);
        if (! ($resp['ok'] ?? false)) {
            return $resp;
        }

        return [
            'ok' => true,
            'zones' => (array) ($resp['data']['result'] ?? []),
        ];
    }

    /**
     * @return array{ok: bool, error?: string, zone?: array<string, mixed>}
     */
    public function getZone(string $token, string $zoneId): array
    {
        $resp = $this->request('GET', '/zones/'.rawurlencode($zoneId), $token);
        if (! ($resp['ok'] ?? false)) {
            return $resp;
        }

        return [
            'ok' => true,
            'zone' => (array) ($resp['data']['result'] ?? []),
        ];
    }

    /**
     * @return array{ok: bool, error?: string, records?: array<int, array<string, mixed>>}
     */
    public function listDnsRecords(string $token, string $zoneId): array
    {
        $resp = $this->request('GET', '/zones/'.rawurlencode($zoneId).'/dns_records', $token, null, ['per_page' => 100]);
        if (! ($resp['ok'] ?? false)) {
            return $resp;
        }

        return [
            'ok' => true,
            'records' => (array) ($resp['data']['result'] ?? []),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{ok: bool, error?: string, record?: array<string, mixed>}
     */
    public function createDnsRecord(string $token, string $zoneId, array $payload): array
    {
        $resp = $this->request('POST', '/zones/'.rawurlencode($zoneId).'/dns_records', $token, $payload);
        if (! ($resp['ok'] ?? false)) {
            return $resp;
        }

        return [
            'ok' => true,
            'record' => (array) ($resp['data']['result'] ?? []),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{ok: bool, error?: string, record?: array<string, mixed>}
     */
    public function updateDnsRecord(string $token, string $zoneId, string $recordId, array $payload): array
    {
        $resp = $this->request('PATCH', '/zones/'.rawurlencode($zoneId).'/dns_records/'.rawurlencode($recordId), $token, $payload);
        if (! ($resp['ok'] ?? false)) {
            return $resp;
        }

        return [
            'ok' => true,
            'record' => (array) ($resp['data']['result'] ?? []),
        ];
    }

    public function deleteDnsRecord(string $token, string $zoneId, string $recordId): array
    {
        return $this->request('DELETE', '/zones/'.rawurlencode($zoneId).'/dns_records/'.rawurlencode($recordId), $token);
    }

    /**
     * @return array{ok: bool, error?: string, value?: string}
     */
    public function getSslMode(string $token, string $zoneId): array
    {
        $resp = $this->request('GET', '/zones/'.rawurlencode($zoneId).'/settings/ssl', $token);
        if (! ($resp['ok'] ?? false)) {
            return $resp;
        }

        return [
            'ok' => true,
            'value' => (string) ($resp['data']['result']['value'] ?? 'full'),
        ];
    }

    /**
     * @return array{ok: bool, error?: string, value?: string}
     */
    public function setSslMode(string $token, string $zoneId, string $mode): array
    {
        $resp = $this->request('PATCH', '/zones/'.rawurlencode($zoneId).'/settings/ssl', $token, ['value' => $mode]);
        if (! ($resp['ok'] ?? false)) {
            return $resp;
        }

        return [
            'ok' => true,
            'value' => (string) ($resp['data']['result']['value'] ?? $mode),
        ];
    }

    public function purgeEverything(string $token, string $zoneId): array
    {
        return $this->request('POST', '/zones/'.rawurlencode($zoneId).'/purge_cache', $token, [
            'purge_everything' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $body
     * @param  array<string, mixed>  $query
     * @return array{ok: bool, error?: string, data?: mixed}
     */
    private function request(string $method, string $path, string $token, ?array $body = null, array $query = []): array
    {
        $token = trim($token);
        if ($token === '') {
            return ['ok' => false, 'error' => 'token_required'];
        }
        try {
            $req = Http::timeout(45)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$token,
                    'Content-Type' => 'application/json',
                ]);
            $url = self::BASE.$path;
            $response = match (strtoupper($method)) {
                'GET' => $req->get($url, $query),
                'POST' => $req->post($url, $body ?? []),
                'PATCH' => $req->patch($url, $body ?? []),
                'DELETE' => $req->delete($url, $body ?? []),
                default => $req->get($url, $query),
            };
            $json = $response->json();
            if ($response->successful() && ($json['success'] ?? false) === true) {
                return ['ok' => true, 'data' => $json];
            }
            $msg = $this->firstErrorMessage($json);

            return ['ok' => false, 'error' => $msg, 'data' => $json];
        } catch (\Throwable $e) {
            Log::warning('Cloudflare API error', ['path' => $path, 'message' => $e->getMessage()]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * @param  mixed  $json
     */
    private function firstErrorMessage($json): string
    {
        if (! is_array($json)) {
            return 'cloudflare_error';
        }
        $err = $json['errors'][0] ?? null;
        if (is_array($err) && ! empty($err['message'])) {
            return (string) $err['message'];
        }

        return 'cloudflare_error';
    }
}
