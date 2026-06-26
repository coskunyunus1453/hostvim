<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesUserDomain;
use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\FtpAccount;
use App\Services\EngineApiService;
use App\Services\HostingQuotaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FtpController extends Controller
{
    use AuthorizesUserDomain;

    public function __construct(
        private EngineApiService $engine,
        private HostingQuotaService $quota,
    ) {}

    public function index(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }

        $payload = [
            'local' => $request->user()->ftpAccounts()->where('domain_id', $domain->id)->get(),
        ];

        if ($request->user()->isAdmin()) {
            $payload['engine'] = $this->sanitizeEngineAccounts($this->engine->ftpList($domain->name));
        }

        return response()->json($payload);
    }

    public function store(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:32', 'regex:/^[a-zA-Z0-9][a-zA-Z0-9._-]{0,30}$/'],
            'home_directory' => ['required', 'string', 'max:255'],
            'quota_mb' => 'nullable|integer|min:-1',
        ]);

        $home = trim($validated['home_directory']);
        if (! $this->isSafeHomeDirectory($home)) {
            throw ValidationException::withMessages([
                'home_directory' => [__('ftp.home_invalid')],
            ]);
        }
        $validated['home_directory'] = $home;

        $this->quota->ensureCanCreateFtpAccount($request->user());

        $password = Str::random(16);

        $account = FtpAccount::create([
            'user_id' => $request->user()->id,
            'domain_id' => $domain->id,
            'username' => $validated['username'],
            'password' => $password,
            'home_directory' => $validated['home_directory'],
            'quota_mb' => $validated['quota_mb'] ?? -1,
            'status' => 'active',
        ]);

        $engine = $this->engine->ftpProvision($domain->name, array_merge($validated, ['password' => $password]));
        if (! empty($engine['error'])) {
            $account->delete();
            Log::warning('ftpProvision failed', [
                'domain' => $domain->name,
                'username' => $validated['username'],
                'error' => $engine['error'],
            ]);

            return response()->json(['message' => (string) $engine['error']], 422);
        }

        return response()->json([
            'message' => __('ftp.created'),
            'account' => $account,
            'password_plain' => $password,
            'engine' => $engine,
        ], 201);
    }

    public function destroy(Request $request, FtpAccount $ftpAccount): JsonResponse
    {
        if ($ftpAccount->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403);
        }

        $ftpAccount->loadMissing('domain');
        if ($ftpAccount->domain !== null && ! $this->userOwnsDomain($request, $ftpAccount->domain)) {
            abort(403);
        }

        $domainName = $ftpAccount->domain?->name;
        if ($domainName !== null) {
            $res = $this->engine->ftpDeleteAccount($domainName, $ftpAccount->username);
            if (! empty($res['error'])) {
                return response()->json(['message' => (string) $res['error']], 422);
            }
        }
        $ftpAccount->delete();

        return response()->json(['message' => __('ftp.deleted')]);
    }

    /**
     * @param  list<mixed>  $accounts
     * @return list<array<string, mixed>>
     */
    private function sanitizeEngineAccounts(array $accounts): array
    {
        return array_map(function ($row) {
            if (! is_array($row)) {
                return $row;
            }
            unset($row['password']);

            return $row;
        }, $accounts);
    }

    private function isSafeHomeDirectory(string $path): bool
    {
        $path = trim(str_replace('\\', '/', $path), '/');
        if ($path === '') {
            return false;
        }
        if (! preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._\\-\\/]*$/', $path)) {
            return false;
        }
        foreach (explode('/', $path) as $seg) {
            if ($seg === '' || $seg === '.' || $seg === '..' || strlen($seg) > 255) {
                return false;
            }
        }

        return true;
    }
}
