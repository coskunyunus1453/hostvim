<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Controller;
use App\Services\WebmailSignonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Roundcube panelze-signon.php → yerel token tüketimi (yalnızca localhost).
 */
class WebmailSignonConsumeController extends Controller
{
    public function consume(Request $request, WebmailSignonService $signon): JsonResponse
    {
        if (! $this->isLocalRequest($request)) {
            abort(403);
        }

        $expected = (string) config('panelze.webmail_signon.internal_key', '');
        $provided = (string) $request->header('X-Panelze-Webmail-Signon-Key', '');
        if ($expected === '' || $provided === '' || ! hash_equals($expected, $provided)) {
            abort(403);
        }

        $payload = $signon->consumeToken((string) $request->input('token', ''));
        if ($payload === null) {
            return response()->json(['message' => __('email.webmail_sso_token_invalid')], 403);
        }

        return response()->json([
            'email' => $payload['email'],
            'password' => $payload['password'],
        ]);
    }

    private function isLocalRequest(Request $request): bool
    {
        $ip = (string) $request->ip();

        return in_array($ip, ['127.0.0.1', '::1'], true);
    }
}
