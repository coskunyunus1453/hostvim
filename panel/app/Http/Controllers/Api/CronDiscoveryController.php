<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesUserDomain;
use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Services\Cron\CronCommandDiscoveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CronDiscoveryController extends Controller
{
    use AuthorizesUserDomain;

    public function __construct(
        private CronCommandDiscoveryService $discovery,
    ) {}

    public function discover(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }

        $deep = $request->boolean('deep', true);

        return response()->json(
            $this->discovery->discover($domain, $request->user(), $deep)
        );
    }
}
