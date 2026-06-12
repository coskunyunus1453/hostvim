<?php

namespace App\Http\Controllers;

use App\Services\PhpMyAdminSignonService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * phpMyAdmin «signon» oturumu — tek kullanımlık panel jetonu ile.
 */
class PhpMyAdminSignonController extends Controller
{
    public function __invoke(Request $request, PhpMyAdminSignonService $signon): RedirectResponse|Response
    {
        $token = (string) $request->query('token', '');
        $payload = $signon->consumeToken($token);
        if ($payload === null) {
            return response(__('databases.phpmyadmin_sso_token_invalid'), 403);
        }

        $sessionName = (string) config('panelze.phpmyadmin_signon.session_name', 'SignonSession');
        if ($sessionName !== '') {
            session_name($sessionName);
        }
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $_SESSION['PMA_single_signon_user'] = $payload['username'];
        $_SESSION['PMA_single_signon_password'] = $payload['password'];
        $_SESSION['PMA_single_signon_host'] = $payload['host'];
        if ($payload['port'] > 0) {
            $_SESSION['PMA_single_signon_port'] = $payload['port'];
        }

        return redirect()->away($signon->redirectUrlAfterSignon($payload['db']));
    }
}
