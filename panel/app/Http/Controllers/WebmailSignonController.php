<?php

namespace App\Http\Controllers;

use App\Services\WebmailSignonService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WebmailSignonController extends Controller
{
    public function __invoke(Request $request, WebmailSignonService $signon): Response
    {
        $payload = $signon->consumeToken((string) $request->query('token', ''));
        if ($payload === null) {
            return response(__('email.webmail_sso_token_invalid'), 403);
        }

        $action = rtrim($payload['webmail_url'], '/').'/';
        $email = $payload['email'];
        $password = $payload['password'];

        $html = <<<HTML
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Webmail</title>
  <style>body{font-family:system-ui,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f8fafc;color:#334155}</style>
</head>
<body>
  <p>Webmail açılıyor…</p>
  <form id="rc-login" method="post" action="{$this->e($action)}">
    <input type="hidden" name="_task" value="login">
    <input type="hidden" name="_action" value="login">
    <input type="hidden" name="_timezone" value="Europe/Istanbul">
    <input type="hidden" name="_user" value="{$this->e($email)}">
    <input type="hidden" name="_pass" value="{$this->e($password)}">
  </form>
  <script>document.getElementById('rc-login').submit();</script>
</body>
</html>
HTML;

        return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
