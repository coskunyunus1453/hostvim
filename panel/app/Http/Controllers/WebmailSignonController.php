<?php

namespace App\Http\Controllers;

use App\Services\WebmailSignonService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Geriye donuk uyumluluk: eski panel /webmail-signon baglantilari webmail host panelze-signon'a yonlendirilir.
 */
class WebmailSignonController extends Controller
{
    public function __invoke(Request $request, WebmailSignonService $signon): RedirectResponse|Response
    {
        $token = trim((string) $request->query('token', ''));
        $webmailUrl = $signon->peekWebmailUrlForToken($token);
        if ($webmailUrl === null) {
            return response(__('email.webmail_sso_token_invalid'), 403);
        }

        $target = rtrim($webmailUrl, '/').'/panelze-signon?token='.rawurlencode($token);

        return redirect()->away($target);
    }
}
