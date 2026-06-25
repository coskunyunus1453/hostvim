<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Controller;
use App\Services\WebmailSignonService;
use App\Support\Http\TrustsLoopbackOnly;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Roundcube panelze-signon.php → yerel token tüketimi (yalnızca localhost).
 */
class WebmailSignonConsumeController extends Controller
{
    use TrustsLoopbackOnly;

    public function consume(Request $request, WebmailSignonService $signon): JsonResponse
    {
        if (! $this->isLoopbackRequest($request)) {
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
}
