<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Controller;
use App\Services\PhpMyAdminSignonService;
use App\Support\Http\TrustsLoopbackOnly;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * phpMyAdmin panelze-pma-signon.php → yerel token tüketimi (yalnızca localhost).
 */
class PhpMyAdminSignonConsumeController extends Controller
{
    use TrustsLoopbackOnly;

    public function consume(Request $request, PhpMyAdminSignonService $signon): JsonResponse
    {
        if (! $this->isLoopbackRequest($request)) {
            abort(403);
        }

        $expected = (string) config('panelze.phpmyadmin_signon.internal_key', '');
        $provided = (string) $request->header('X-Panelze-Pma-Signon-Key', '');
        if ($expected === '' || $provided === '' || ! hash_equals($expected, $provided)) {
            abort(403);
        }

        $payload = $signon->consumeToken((string) $request->input('token', ''));
        if ($payload === null) {
            return response()->json(['message' => __('databases.phpmyadmin_sso_token_invalid')], 403);
        }

        return response()->json([
            'username' => $payload['username'],
            'password' => $payload['password'],
            'host' => $payload['host'],
            'port' => $payload['port'],
            'db' => $payload['db'],
        ]);
    }
}
