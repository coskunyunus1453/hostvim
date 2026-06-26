<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class DnsRecordValidator
{
    /**
     * @param  array{type: string, name: string, value: string, priority?: int|null}  $data
     */
    public function validateForStore(array $data): array
    {
        $type = strtoupper(trim((string) ($data['type'] ?? '')));
        $name = strtolower(trim((string) ($data['name'] ?? '')));
        $value = trim((string) ($data['value'] ?? ''));

        if ($type === 'NS' && ($name === '' || $name === '@')) {
            throw ValidationException::withMessages([
                'type' => [__('dns.apex_ns_managed')],
            ]);
        }

        $ttl = isset($data['ttl']) ? (int) $data['ttl'] : 3600;
        if ($ttl < 60 || $ttl > 604800) {
            throw ValidationException::withMessages([
                'ttl' => [__('dns.ttl_range')],
            ]);
        }
        $data['ttl'] = $ttl;

        $valueMax = $type === 'TXT' ? 4096 : 255;
        if (strlen($value) > $valueMax) {
            throw ValidationException::withMessages([
                'value' => [__('dns.value_too_long', ['max' => $valueMax])],
            ]);
        }

        if (in_array($type, ['MX', 'SRV'], true)) {
            if (! isset($data['priority']) || $data['priority'] === '' || $data['priority'] === null) {
                throw ValidationException::withMessages([
                    'priority' => [__('dns.priority_required')],
                ]);
            }
            $priority = (int) $data['priority'];
            if ($priority < 0 || $priority > 65535) {
                throw ValidationException::withMessages([
                    'priority' => [__('dns.priority_range')],
                ]);
            }
            $data['priority'] = $priority;
        }

        if ($type === 'A' && ! filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            throw ValidationException::withMessages([
                'value' => [__('dns.a_must_be_ipv4')],
            ]);
        }

        if ($type === 'AAAA' && ! filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            throw ValidationException::withMessages([
                'value' => [__('dns.aaaa_must_be_ipv6')],
            ]);
        }

        if ($type === 'CNAME' && ! $this->isValidCnameTarget($value)) {
            throw ValidationException::withMessages([
                'value' => [__('dns.cname_invalid')],
            ]);
        }

        $data['type'] = $type;
        $data['name'] = $name;
        $data['value'] = $value;

        return $data;
    }

    private function isValidCnameTarget(string $value): bool
    {
        $v = rtrim(strtolower($value), '.');

        return (bool) preg_match(
            '/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i',
            $v,
        );
    }

    public function isValidAValue(string $value): bool
    {
        return (bool) filter_var(trim($value), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }

    public function isApexNsRecord(string $type, string $name): bool
    {
        $type = strtoupper(trim($type));
        $name = strtolower(trim($name));

        return $type === 'NS' && ($name === '' || $name === '@');
    }
}
