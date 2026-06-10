<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Sunucu MySQL bakım hesabı (.env MYSQL_PROVISION_*); phpMyAdmin / manuel import için.
 * Debian/MariaDB kök kullanıcısı çoğunlukla unix_socket ile giriş yapar; şifre gerekmeyebilir.
 */
class ServerMysqlSettingsController extends Controller
{
    public function show(): JsonResponse
    {
        $mp = config('panelze.mysql_provision', []);
        $enabled = filter_var($mp['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $provisionPass = '';
        if ($enabled) {
            $provisionPass = trim((string) ($mp['password'] ?? ''));
        }

        return response()->json([
            'provision' => [
                'enabled' => $enabled,
                'host' => (string) ($mp['host'] ?? '127.0.0.1'),
                'port' => (int) ($mp['port'] ?? 3306),
                'username' => (string) ($mp['username'] ?? ''),
                'password' => $provisionPass,
            ],
            'panel_app_database' => [
                'host' => (string) config('database.connections.mysql.host', '127.0.0.1'),
                'port' => (int) config('database.connections.mysql.port', 3306),
                'database' => (string) config('database.connections.mysql.database', ''),
                'username' => (string) config('database.connections.mysql.username', ''),
                'password' => (string) config('database.connections.mysql.password', ''),
            ],
            'phpmyadmin_url' => (string) config('panelze.ui.phpmyadmin_url', ''),
            'hints' => [
                'provision_use' => __('databases.server_mysql_provision_use'),
                'root_socket' => __('databases.server_mysql_root_socket'),
                'ssh_secret_file' => __('databases.server_mysql_ssh_secret'),
            ],
        ]);
    }
}
