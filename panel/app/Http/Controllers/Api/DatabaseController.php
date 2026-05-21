<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ImportDatabaseJob;
use App\Models\Database;
use App\Models\DatabaseImportRun;
use App\Models\Domain;
use App\Services\DatabaseService;
use App\Services\HostingQuotaService;
use App\Services\MysqlProvisioner;
use App\Services\PanelLicenseService;
use App\Services\PhpMyAdminSignonService;
use App\Services\PostgresProvisioner;
use App\Support\DatabaseImportConfirmation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PDOException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class DatabaseController extends Controller
{
    public function __construct(
        private DatabaseService $databaseService,
        private HostingQuotaService $quota,
        private MysqlProvisioner $mysqlProvisioner,
        private PostgresProvisioner $postgresProvisioner,
        private PanelLicenseService $panelLicense,
        private PhpMyAdminSignonService $phpMyAdminSignon,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $databases = $request->user()->databases()->latest()->paginate(20);
        $this->databaseService->hydrateDatabaseSizesOnPaginator($databases);

        return response()->json($databases);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:64',
            'type' => 'nullable|string|in:mysql,postgresql',
            'domain_id' => 'nullable|exists:domains,id',
            'grant_host' => 'nullable|string|max:64',
        ]);

        if (! empty($validated['domain_id'])) {
            $ownsDomain = Domain::query()
                ->where('id', (int) $validated['domain_id'])
                ->where('user_id', $request->user()->id)
                ->exists();
            if (! $ownsDomain) {
                abort(403);
            }
        }

        $this->quota->ensureCanCreateDatabase($request->user());

        try {
            $result = $this->databaseService->create(
                $request->user(),
                $validated['name'],
                $validated['type'] ?? 'mysql',
                $validated['domain_id'] ?? null,
                $validated['grant_host'] ?? null,
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (PDOException $e) {
            report($e);

            return response()->json([
                'message' => __('databases.provision_failed').': '.$e->getMessage(),
            ], 503);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => $e->getMessage() ?: __('databases.provision_failed')], 500);
        }

        return response()->json([
            'message' => __('databases.created'),
            'database' => $result['database'],
            'password_plain' => $result['password_plain'],
        ], 201);
    }

    public function update(Request $request, Database $database): JsonResponse
    {
        $this->authorize('update', $database);
        $validated = $request->validate([
            'grant_host' => 'nullable|string|max:64',
            'password' => 'nullable|string|min:8|max:255',
        ]);

        $grantHost = isset($validated['grant_host']) ? trim((string) $validated['grant_host']) : null;
        $password = isset($validated['password']) ? trim((string) $validated['password']) : null;

        $hasPassword = $password !== null && $password !== '';
        $grantProvided = $grantHost !== null && $grantHost !== '';
        $grantChanged = $database->type === 'mysql'
            && $grantProvided
            && $grantHost !== $database->mysqlGrantHost();

        if (! $hasPassword && ! $grantChanged) {
            return response()->json(['message' => __('databases.update_no_changes')], 422);
        }

        if ($grantProvided && $database->type !== 'mysql') {
            return response()->json(['message' => __('databases.grant_host_mysql_only')], 422);
        }

        try {
            if ($hasPassword) {
                $result = $this->databaseService->updateCredentials(
                    $database,
                    $password,
                    $database->type === 'mysql' ? $grantHost : null,
                );
            } else {
                $this->databaseService->updateGrantHost($database, (string) $grantHost);
                $result = [];
            }
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (PDOException $e) {
            report($e);

            return response()->json([
                'message' => __('databases.provision_failed').': '.$e->getMessage(),
            ], 503);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => $e->getMessage() ?: __('databases.provision_failed'),
            ], 500);
        }

        $payload = [
            'message' => __('databases.updated'),
            'database' => $database->fresh(),
            'password_plain' => $result['password_plain'] ?? null,
        ];
        if (! empty($result['app_config_reminder'])) {
            $payload['sync_reminder'] = (string) __('databases.credentials_sync_reminder');
        }

        return response()->json($payload);
    }

    public function rotatePassword(Request $request, Database $database): JsonResponse
    {
        $this->authorize('rotatePassword', $database);

        if (! in_array($database->type, ['mysql', 'postgresql'], true)) {
            return response()->json(['message' => __('databases.rotate_password_unsupported')], 422);
        }

        try {
            $result = $this->databaseService->rotatePassword($database);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (PDOException $e) {
            report($e);

            return response()->json([
                'message' => __('databases.provision_failed').': '.$e->getMessage(),
            ], 503);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => $e->getMessage() ?: __('databases.provision_failed'),
            ], 500);
        }

        return response()->json([
            'message' => __('databases.password_rotated'),
            'database' => $database->fresh(),
            'password_plain' => $result['password_plain'],
        ]);
    }

    /**
     * Pro lisans: tek tık phpMyAdmin signon (otomatik kullanıcı/şifre).
     */
    public function phpmyadminLogin(Request $request, Database $database): JsonResponse
    {
        $this->authorize('update', $database);

        if (! $this->panelLicense->hasPhpMyAdminAutoLogin()) {
            return response()->json([
                'message' => __('databases.phpmyadmin_sso_pro_required'),
                'code' => 'pro_license_required',
            ], 403);
        }

        $pmaUrl = trim((string) config('hostvim.ui.phpmyadmin_url', ''));
        if ($pmaUrl === '') {
            return response()->json([
                'message' => __('databases.phpmyadmin_sso_not_configured'),
            ], 422);
        }

        try {
            $session = $this->phpMyAdminSignon->mintForDatabase($database);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => __('databases.phpmyadmin_sso_failed')], 500);
        }

        return response()->json([
            'message' => __('databases.phpmyadmin_sso_ready'),
            'signon_url' => $session['signon_url'],
            'expires_in' => $session['expires_in'],
            'database' => $database->name,
        ]);
    }

    public function destroy(Request $request, Database $database): JsonResponse
    {
        $this->authorize('delete', $database);

        try {
            $this->databaseService->delete($database);
        } catch (PDOException $e) {
            report($e);

            return response()->json([
                'message' => __('databases.provision_failed').': '.$e->getMessage(),
            ], 503);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => $e->getMessage() ?: __('databases.provision_failed'),
            ], 500);
        }

        return response()->json(['message' => __('databases.deleted')]);
    }

    public function export(Request $request, Database $database): JsonResponse|StreamedResponse
    {
        $this->authorize('export', $database);

        if (! in_array($database->type, ['mysql', 'postgresql'], true)) {
            return response()->json(['message' => __('databases.export_unsupported_type')], 422);
        }

        $safeName = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $database->name) ?: 'database';
        $filename = $safeName.'_'.date('Y-m-d_His').'.sql';

        try {
            return response()->streamDownload(function () use ($database): void {
                if ($database->type === 'mysql') {
                    $this->databaseService->streamMysqlDump($database, function (string $chunk): void {
                        echo $chunk;
                        if (ob_get_level() > 0) {
                            @ob_flush();
                        }
                        flush();
                    });
                } else {
                    $this->databaseService->streamPostgresDump($database, function (string $chunk): void {
                        echo $chunk;
                        if (ob_get_level() > 0) {
                            @ob_flush();
                        }
                        flush();
                    });
                }
            }, $filename, [
                'Content-Type' => 'application/sql; charset=UTF-8',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => $e->getMessage() ?: __('databases.export_failed'),
            ], 500);
        }
    }

    public function importMeta(Request $request): JsonResponse
    {
        return response()->json([
            'confirm_phrase' => DatabaseImportConfirmation::expectedPhrase(),
            'max_import_mb' => max(1, (int) config('hostvim.limits.max_db_import_mb', 512)),
            'mysql_tools_enabled' => $this->mysqlProvisioner->enabled(),
            'postgres_tools_enabled' => $this->postgresProvisioner->enabled(),
        ]);
    }

    public function importStatus(Request $request, Database $database, int $import): JsonResponse
    {
        $this->authorize('import', $database);

        $run = DatabaseImportRun::query()
            ->whereKey($import)
            ->where('database_id', $database->id)
            ->when(! $request->user()->isAdmin(), fn ($q) => $q->where('user_id', $request->user()->id))
            ->firstOrFail();

        return response()->json([
            'import_id' => $run->id,
            'status' => $run->status,
            'progress' => $run->progress,
            'phase' => $run->phase,
            'message' => $run->message,
            'error_message' => $run->error_message,
            'finished_at' => $run->finished_at,
        ]);
    }

    public function import(Request $request, Database $database): JsonResponse
    {
        $this->authorize('import', $database);

        if (! in_array($database->type, ['mysql', 'postgresql'], true)) {
            return response()->json(['message' => __('databases.import_unsupported_type')], 422);
        }

        if ($database->type === 'mysql' && ! $this->mysqlProvisioner->enabled()) {
            return response()->json(['message' => __('databases.provision_disabled_import')], 422);
        }
        if ($database->type === 'postgresql' && ! $this->postgresProvisioner->enabled()) {
            return response()->json(['message' => __('databases.provision_disabled_import')], 422);
        }

        $active = DatabaseImportRun::query()
            ->where('database_id', $database->id)
            ->whereIn('status', ['queued', 'running'])
            ->exists();
        if ($active) {
            return response()->json(['message' => __('databases.import_already_running')], 409);
        }

        $maxMb = max(1, (int) config('hostvim.limits.max_db_import_mb', 512));
        $maxKb = $maxMb * 1024;

        $validated = $request->validate([
            'sql_file' => ['required', 'file', 'max:'.$maxKb],
            'confirmation' => ['required', 'string', 'max:128'],
        ]);

        if (! DatabaseImportConfirmation::matches((string) $validated['confirmation'])) {
            return response()->json(['message' => __('databases.import_confirm_mismatch')], 422);
        }

        $upload = $request->file('sql_file');
        if (! DatabaseImportConfirmation::isSqlUpload($upload)) {
            return response()->json(['message' => __('databases.import_sql_only')], 422);
        }

        $run = DatabaseImportRun::query()->create([
            'user_id' => $request->user()->id,
            'database_id' => $database->id,
            'status' => 'queued',
            'progress' => 0,
            'phase' => 'queued',
            'message' => __('databases.import_started'),
        ]);

        $stored = $upload->storeAs('db-imports', (string) $run->id.'.sql', 'local');
        if ($stored === false) {
            $run->delete();

            return response()->json(['message' => __('databases.import_file_unreadable')], 500);
        }

        $run->file_path = $stored;
        $run->save();

        ImportDatabaseJob::dispatch($run->id)->afterResponse();

        return response()->json([
            'message' => __('databases.import_started'),
            'import_id' => $run->id,
            'status' => 'queued',
        ], 202);
    }
}
