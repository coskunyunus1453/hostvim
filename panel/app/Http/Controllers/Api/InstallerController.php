<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesUserDomain;
use App\Http\Controllers\Controller;
use App\Jobs\RunInstallerJob;
use App\Models\Database;
use App\Models\Domain;
use App\Models\InstallerRun;
use App\Services\EngineApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class InstallerController extends Controller
{
    use AuthorizesUserDomain;

    public function __construct(
        private EngineApiService $engine,
    ) {}

    public function apps(): JsonResponse
    {
        return response()->json(['apps' => $this->engine->installerApps()]);
    }

    public function install(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }

        try {
            $allowedApps = [
                'wordpress', 'opencart',
                'joomla', 'laravel', 'drupal', 'prestashop',
                'nodejs', 'docker', 'git_deploy', 'nextjs', 'strapi', 'n8n',
            ];
            $validated = $request->validate([
                'app' => ['required', 'string', Rule::in($allowedApps)],
                'database_id' => 'nullable|integer|exists:databases,id',
                'table_prefix' => 'nullable|string|regex:/^[a-zA-Z0-9_]{1,16}$/',
                'install_woocommerce' => 'sometimes|boolean',
            ]);

            $automated = in_array($validated['app'], ['wordpress', 'opencart'], true);
            if (! $automated) {
                return response()->json(['message' => __('installer.automated_apps_only')], 422);
            }

            $installWoo = ! empty($validated['install_woocommerce']);
            if ($validated['app'] === 'wordpress' && empty($validated['database_id'])) {
                return response()->json(['message' => __('installer.wordpress_requires_db')], 422);
            }
            if ($validated['app'] === 'opencart' && empty($validated['database_id'])) {
                return response()->json(['message' => __('installer.opencart_requires_db')], 422);
            }

            $db = Database::query()
                ->where('user_id', $request->user()->id)
                ->where('type', 'mysql')
                ->find($validated['database_id']);

            if (! $db) {
                return response()->json(['message' => __('installer.wordpress_mysql_db')], 422);
            }

            $prefix = trim((string) ($validated['table_prefix'] ?? ''));
            if ($prefix === '') {
                $prefix = 'wp_';
            }

            try {
                $dbPassword = $db->password;
            } catch (Throwable $e) {
                Log::warning('installer: veritabanı şifresi çözülemedi', [
                    'database_id' => $db->id,
                    'error' => $e->getMessage(),
                ]);

                return response()->json(['message' => __('installer.db_password_decrypt')], 422);
            }

            $dbHost = trim((string) ($db->host));
            if ($dbHost === '') {
                $dbHost = (string) config('panelze.mysql_provision.host', config('database.connections.mysql.host', '127.0.0.1'));
            }

            $dbPort = (int) ($db->port ?? 3306);
            if ($dbPort < 1 || $dbPort > 65535) {
                $dbPort = 3306;
            }

            $payload = [
                'db_host' => $dbHost,
                'db_port' => $dbPort,
                'db_name' => $db->name,
                'db_user' => $db->username,
                'db_password' => $dbPassword,
                'table_prefix' => $prefix,
            ];
            if ($validated['app'] === 'wordpress') {
                $payload['install_woocommerce'] = $installWoo;
            }

            $engineApp = $validated['app'];
            $runAppLabel = ($validated['app'] === 'wordpress' && $installWoo)
                ? 'wordpress_woocommerce'
                : $validated['app'];

            // Yeni installer_runs tablosu canlıda henüz migrate edilmemiş olabilir.
            // Bu durumda kurulum akışını düşürmeyip doğrudan senkron kurulum yap.
            if (! Schema::hasTable('installer_runs')) {
                return $this->runInstallerDirect($request, $domain, $engineApp, $payload);
            }

            $run = InstallerRun::query()->create([
                'user_id' => $request->user()->id,
                'domain_id' => $domain->id,
                'app' => $runAppLabel,
                'status' => 'queued',
                'message' => __('installer.started'),
            ]);

            $isSyncQueue = (string) config('queue.default', 'sync') === 'sync';
            if ($isSyncQueue) {
                // Queue worker yoksa kullanıcıyı yanıltmamak için aynı requestte çalıştır.
                (new RunInstallerJob($run->id, $domain->name, $engineApp, $payload))->handle($this->engine);

                $run->refresh();
                if ($run->status === 'failed') {
                    if (EngineApiService::isLikelyConnectionFailure($run->message)) {
                        return response()->json([
                            'message' => __('installer.engine_unreachable', [
                                'url' => config('panelze.engine_url'),
                            ]),
                            'hint' => __('installer.engine_start_hint'),
                            'run_id' => $run->id,
                            'background' => false,
                        ], 503);
                    }

                    return response()->json([
                        'message' => $run->message ?: __('installer.unexpected_error'),
                        'run_id' => $run->id,
                        'background' => false,
                    ], 502);
                }

                return response()->json([
                    'message' => __('installer.completed_sync'),
                    'run_id' => $run->id,
                    'status' => $run->status,
                    'background' => false,
                ]);
            }

            RunInstallerJob::dispatch($run->id, $domain->name, $engineApp, $payload)->afterResponse();

            return response()->json([
                'message' => __('installer.started_background'),
                'run_id' => $run->id,
                'status' => 'queued',
                'background' => true,
            ], 202);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('installer: beklenmeyen hata', array_filter([
                'domain_id' => $domain->id,
                'exception' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ]));

            $message = config('app.debug')
                ? $e->getMessage()
                : __('installer.unexpected_error');

            return response()->json(['message' => $message], 500);
        }
    }

    public function runs(Request $request): JsonResponse
    {
        $runs = InstallerRun::query()
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->limit(20)
            ->get(['id', 'domain_id', 'app', 'status', 'message', 'started_at', 'finished_at', 'created_at']);

        return response()->json(['runs' => $runs]);
    }

    public function diagnostics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'domain_id' => 'nullable|integer|exists:domains,id',
            'database_id' => 'nullable|integer|exists:databases,id',
        ]);

        $checks = [];

        $apps = $this->engine->installerApps();
        $engineOk = count($apps) > 0;
        $checks[] = [
            'key' => 'engine',
            'ok' => $engineOk,
            'message' => $engineOk
                ? __('installer.diag_engine_ok')
                : __('installer.diag_engine_fail'),
        ];

        $runsTableOk = Schema::hasTable('installer_runs');
        $checks[] = [
            'key' => 'installer_runs_table',
            'ok' => $runsTableOk,
            'message' => $runsTableOk
                ? __('installer.diag_runs_table_ok')
                : __('installer.diag_runs_table_fail'),
        ];

        if (! empty($validated['domain_id'])) {
            $domain = Domain::query()
                ->where('id', $validated['domain_id'])
                ->where('user_id', $request->user()->id)
                ->first();
            $domainOk = $domain !== null;
            $checks[] = [
                'key' => 'domain_access',
                'ok' => $domainOk,
                'message' => $domainOk
                    ? __('installer.diag_domain_access_ok')
                    : __('installer.diag_domain_access_fail'),
            ];

            if ($domainOk) {
                $docroot = (string) $domain->document_root;
                $docrootOk = is_dir($docroot) && is_writable($docroot);
                $message = $docrootOk
                    ? __('installer.diag_docroot_write_ok', ['path' => $docroot])
                    : __('installer.diag_docroot_write_fail', ['path' => $docroot]);

                if ($docrootOk) {
                    $tmp = rtrim($docroot, '/').'/.__panelze_installer_diag';
                    try {
                        file_put_contents($tmp, 'ok');
                        @unlink($tmp);
                    } catch (Throwable) {
                        $docrootOk = false;
                        $message = __('installer.diag_docroot_probe_fail', ['path' => $docroot]);
                    }
                }

                $checks[] = [
                    'key' => 'docroot_write',
                    'ok' => $docrootOk,
                    'message' => $message,
                ];
            }
        }

        if (! empty($validated['database_id'])) {
            $db = Database::query()
                ->where('id', $validated['database_id'])
                ->where('user_id', $request->user()->id)
                ->where('type', 'mysql')
                ->first();
            $dbRecordOk = $db !== null;
            $checks[] = [
                'key' => 'database_access',
                'ok' => $dbRecordOk,
                'message' => $dbRecordOk
                    ? __('installer.diag_db_access_ok')
                    : __('installer.diag_db_access_fail'),
            ];

            if ($dbRecordOk) {
                $dbConnectionOk = $this->testDatabaseConnection($db);
                $checks[] = [
                    'key' => 'database_connection',
                    'ok' => $dbConnectionOk['ok'],
                    'message' => $dbConnectionOk['message'],
                ];
            }
        }

        $allOk = collect($checks)->every(fn (array $c) => (bool) ($c['ok'] ?? false));

        return response()->json([
            'ok' => $allOk,
            'checks' => $checks,
        ], $allOk ? 200 : 422);
    }

    public function runShow(Request $request, InstallerRun $installerRun): JsonResponse
    {
        if ((int) $installerRun->user_id !== (int) $request->user()->id) {
            abort(403);
        }

        return response()->json([
            'run' => [
                'id' => $installerRun->id,
                'domain_id' => $installerRun->domain_id,
                'app' => $installerRun->app,
                'status' => $installerRun->status,
                'message' => $installerRun->message,
                'output' => $installerRun->output,
                'started_at' => optional($installerRun->started_at)->toIso8601String(),
                'finished_at' => optional($installerRun->finished_at)->toIso8601String(),
                'created_at' => optional($installerRun->created_at)->toIso8601String(),
            ],
        ]);
    }

    /**
     * installer_runs yoksa veya run kaydı kullanılamıyorsa fallback senkron kurulum.
     *
     * @param  array<string, mixed>  $payload
     */
    private function runInstallerDirect(Request $request, Domain $domain, string $engineApp, array $payload): JsonResponse
    {
        $engine = $this->engine->installerRun($engineApp, $domain->name, $payload);
        if (! empty($engine['error'])) {
            if (EngineApiService::isLikelyConnectionFailure($engine['error'])) {
                return response()->json([
                    'message' => __('installer.engine_unreachable', [
                        'url' => config('panelze.engine_url'),
                    ]),
                    'hint' => __('installer.engine_start_hint'),
                    'background' => false,
                ], 503);
            }

            $response = [
                'message' => (string) $engine['error'],
                'hint' => __('installer.diag_runs_table_fail'),
                'background' => false,
            ];
            if ($this->canExposeEnginePayload($request)) {
                $response['engine'] = $engine;
            }

            return response()->json($response, 502);
        }

        $response = [
            'message' => __('installer.completed_sync'),
            'status' => 'success',
            'background' => false,
        ];
        if ($this->canExposeEnginePayload($request)) {
            $response['engine'] = $engine;
        }

        return response()->json($response, 200);
    }

    /**
     * @return array{ok: bool, message: string}
     */
    private function testDatabaseConnection(Database $db): array
    {
        try {
            $password = $db->password;
        } catch (Throwable) {
            return [
                'ok' => false,
                'message' => __('installer.diag_db_password_decrypt_fail'),
            ];
        }

        $host = trim((string) $db->host);
        if ($host === '') {
            $host = (string) config('panelze.mysql_provision.host', config('database.connections.mysql.host', '127.0.0.1'));
        }
        $port = (int) ($db->port ?? 3306);
        if ($port < 1 || $port > 65535) {
            $port = 3306;
        }

        try {
            $pdo = DB::connection('mysql')->getPdo();
            if (! $pdo instanceof \PDO) {
                return ['ok' => false, 'message' => __('installer.diag_db_pdo_fail')];
            }

            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, (string) $db->name);
            $probe = new \PDO($dsn, (string) $db->username, (string) $password, [
                \PDO::ATTR_TIMEOUT => 4,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
            $probe->query('SELECT 1');

            return ['ok' => true, 'message' => __('installer.diag_db_connection_ok')];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => __('installer.diag_db_connection_fail', ['error' => $e->getMessage()])];
        }
    }

    private function canExposeEnginePayload(Request $request): bool
    {
        return (bool) optional($request->user())->isAdmin();
    }
}
