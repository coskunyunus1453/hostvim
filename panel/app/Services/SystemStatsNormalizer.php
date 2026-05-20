<?php

namespace App\Services;

/**
 * Engine GET /api/v1/system/stats yanıtını panel genelinde tek şemaya çevirir.
 */
class SystemStatsNormalizer
{
    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    public static function normalize(array $raw): array
    {
        if (! empty($raw['error'])) {
            return ['available' => false, 'error' => (string) $raw['error']];
        }

        if ($raw === []) {
            return ['available' => false, 'error' => 'engine_unreachable'];
        }

        $load1 = (float) ($raw['load1'] ?? 0);
        $load5 = (float) ($raw['load5'] ?? 0);
        $load15 = (float) ($raw['load15'] ?? 0);

        return [
            'available' => true,
            'hostname' => $raw['hostname'] ?? null,
            'os' => $raw['os'] ?? null,
            'cpu_usage' => round((float) ($raw['cpu_usage'] ?? 0), 1),
            'cpu_model' => $raw['cpu_model'] ?? null,
            'cpu_cores_logical' => (int) ($raw['cpu_cores_logical'] ?? 0),
            'memory_usage_percent' => round((float) ($raw['memory_percent'] ?? $raw['memory_usage_percent'] ?? 0), 1),
            'memory_total_bytes' => (int) ($raw['memory_total'] ?? 0),
            'memory_used_bytes' => (int) ($raw['memory_used'] ?? 0),
            'memory_available_bytes' => (int) ($raw['memory_available'] ?? 0),
            'swap_percent' => round((float) ($raw['swap_percent'] ?? 0), 1),
            'disk_usage_percent' => round((float) ($raw['disk_percent'] ?? $raw['disk_usage_percent'] ?? 0), 1),
            'disk_total_bytes' => (int) ($raw['disk_total'] ?? 0),
            'disk_used_bytes' => (int) ($raw['disk_used'] ?? 0),
            'load' => sprintf('%.2f %.2f %.2f', $load1, $load5, $load15),
            'load1' => $load1,
            'load5' => $load5,
            'load15' => $load15,
            'uptime' => (int) ($raw['uptime'] ?? 0),
            'uptime_human' => self::formatUptime((int) ($raw['uptime'] ?? 0)),
            'net_rx_bytes_per_sec' => (float) ($raw['net_rx_bytes_per_sec'] ?? 0),
            'net_tx_bytes_per_sec' => (float) ($raw['net_tx_bytes_per_sec'] ?? 0),
            'top_cpu_processes' => array_slice($raw['top_cpu_processes'] ?? [], 0, 8),
            'top_memory_processes' => array_slice($raw['top_memory_processes'] ?? [], 0, 8),
            'top_disk_mounts' => array_slice($raw['top_disk_mounts'] ?? [], 0, 6),
        ];
    }

    public static function formatUptime(int $seconds): string
    {
        if ($seconds <= 0) {
            return '0s';
        }
        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $mins = intdiv($seconds % 3600, 60);

        $parts = [];
        if ($days > 0) {
            $parts[] = $days.'g';
        }
        if ($hours > 0) {
            $parts[] = $hours.'s';
        }
        if ($mins > 0 || $parts === []) {
            $parts[] = $mins.'dk';
        }

        return implode(' ', $parts);
    }
}
