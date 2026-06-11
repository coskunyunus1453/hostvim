<?php
/**
 * Panelze → Roundcube tek tık giriş (webmail.* ile aynı origin).
 * Token panel iç API ile doğrulanır; oturum sunucu tarafında açılır.
 */

declare(strict_types=1);

$configFile = '/etc/roundcube/panelze-signon.inc.php';
if (! is_file($configFile)) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Webmail SSO yapılandırması eksik. Sunucuda configure-roundcube-signon.sh çalıştırın.\n";
    exit;
}

/** @var array{panel_internal_url: string, internal_key: string} $panelzeSignon */
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
    echo "Webmail SSO yapılandırması eksik.\n";
    exit;
}

$consumeUrl = $panelUrl.'/api/internal/webmail-signon/consume';
$body = http_build_query(['token' => $token]);

$ch = curl_init($consumeUrl);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $body,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/json',
        'X-Panelze-Webmail-Signon-Key: '.$internalKey,
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
    echo "Webmail oturum bağlantısı geçersiz veya süresi dolmuş.\n";
    exit;
}

/** @var array{email?: string, password?: string}|null $payload */
$payload = json_decode((string) $responseBody, true);
$email = is_array($payload) ? trim((string) ($payload['email'] ?? '')) : '';
$password = is_array($payload) ? (string) ($payload['password'] ?? '') : '';

if ($email === '' || $password === '') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Webmail oturum bağlantısı geçersiz veya süresi dolmuş.\n";
    exit;
}

chdir(__DIR__);
require_once __DIR__.'/program/include/iniset.php';

$RCMAIL = rcmail::get_instance(0);
if (! $RCMAIL->login($email, $password, null, false)) {
    http_response_code(401);
    header('Content-Type: text/plain; charset=UTF-8');
    $err = (string) $RCMAIL->login_error();
    echo $err !== '' ? $err : "Webmail girişi başarısız.\n";
    exit;
}

header('Location: /?_task=mail', true, 302);
exit;
