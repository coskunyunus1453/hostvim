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

        return $data;
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
