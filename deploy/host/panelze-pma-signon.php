<?php
/**
 * Panelze → phpMyAdmin tek tık giriş (/phpmyadmin ile aynı origin).
 * Token panel iç API ile doğrulanır; SignonSession oturumu açılır.
 */

declare(strict_types=1);

$configFile = '/etc/phpmyadmin/panelze-signon.inc.php';
if (! is_file($configFile)) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "phpMyAdmin SSO yapılandırması eksik. Sunucuda configure-phpmyadmin-signon.sh çalıştırın.\n";
    exit;
}

/** @var array{panel_internal_url: string, internal_key: string, session_name?: string} $panelzeSignon */
$panelzeSignon = require $configFile;

$token = trim((string) ($_GET['token'] ?? ''));
if ($token === '' || strlen($token) > 128) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Geçersiz veya eksik oturum bağlantısı.\n";
    exit;
}

$panelUrl = rtrim((string) ($panelzeSignon['panel_internal_url'] ?? ''), '/');
$internalKey = (string) ($panelzeSignon['internal_key'] ?? '');
if ($panelUrl === '' || $internalKey === '') {
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "phpMyAdmin SSO yapılandırması eksik.\n";
    exit;
}

$consumeUrl = $panelUrl.'/api/internal/phpmyadmin-signon/consume';
$body = http_build_query(['token' => $token]);

$ch = curl_init($consumeUrl);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $body,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/json',
        'X-Panelze-Pma-Signon-Key: '.$internalKey,
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_CONNECTTIMEOUT => 5,
]);
$responseBody = curl_exec($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($responseBody === false || $httpCode !== 200) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "phpMyAdmin oturum bağlantısı geçersiz veya süresi dolmuş.\n";
    exit;
}

/** @var array{username?: string, password?: string, host?: string, port?: int, db?: string}|null $payload */
$payload = json_decode((string) $responseBody, true);
$username = is_array($payload) ? trim((string) ($payload['username'] ?? '')) : '';
$password = is_array($payload) ? (string) ($payload['password'] ?? '') : '';
$host = is_array($payload) ? trim((string) ($payload['host'] ?? '127.0.0.1')) : '127.0.0.1';
$port = is_array($payload) ? (int) ($payload['port'] ?? 0) : 0;
$db = is_array($payload) ? trim((string) ($payload['db'] ?? '')) : '';

if ($username === '' || $password === '') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "phpMyAdmin oturum bağlantısı geçersiz veya süresi dolmuş.\n";
    exit;
}

$sessionName = trim((string) ($panelzeSignon['session_name'] ?? 'SignonSession'));
if ($sessionName === '') {
    $sessionName = 'SignonSession';
}
session_name($sessionName);
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$_SESSION['PMA_single_signon_user'] = $username;
$_SESSION['PMA_single_signon_password'] = $password;
$_SESSION['PMA_single_signon_host'] = $host !== '' ? $host : '127.0.0.1';
if ($port > 0) {
    $_SESSION['PMA_single_signon_port'] = $port;
}
session_write_close();

$redirect = 'index.php';
if ($db !== '') {
    $redirect .= '?db='.rawurlencode($db);
}
header('Location: '.$redirect, true, 302);
exit;
