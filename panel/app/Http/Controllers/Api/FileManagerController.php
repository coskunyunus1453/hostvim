<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesUserDomain;
use App\Http\Controllers\Concerns\ResolvesHostingSiteTarget;
use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Support\HostingSiteTarget;
use App\Models\PanelSetting;
use App\Services\AutoWebConfigurator;
use App\Services\EngineApiService;
use App\Services\HostingQuotaService;
use App\Services\SafeAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FileManagerController extends Controller
{
    use AuthorizesUserDomain;
    use ResolvesHostingSiteTarget;

    private const TRASH_DIR = '.panelze-trash';

    private const TRASH_ITEMS_DIR = '.panelze-trash/items';

    private const TRASH_META_DIR = '.panelze-trash/meta';

    public function __construct(
        private EngineApiService $engine,
        private HostingQuotaService $quota,
        private AutoWebConfigurator $autoWebConfigurator,
    ) {}

    /**
     * Panelin “site document_root altına göreli path” gönderdiği varsayımıyla,
     * engine'in “engine web_root/domain altına göreli path” beklediği çeviriyi yapar.
     */
    private function panelRelToEngineRel(HostingSiteTarget $target, string $panelRel): string
    {
        $hostingRoot = rtrim((string) config('hostvim.hosting_web_root'), '/\\');
        $engineRoot = $hostingRoot.DIRECTORY_SEPARATOR.$target->engineSiteName;

        $docRoot = $this->fileManagerBasePath($target);

        $panelRelNorm = str_replace('\\', '/', trim($panelRel));
        $panelRelNorm = ltrim($panelRelNorm, '/'); // engine tarafı leading slash'ları temizliyor ama biz net olsun diye

        $engineRootNorm = str_replace('\\', '/', $engineRoot);
        $docRootNorm = str_replace('\\', '/', $docRoot);

        $baseRel = '';
        if ($docRootNorm === $engineRootNorm) {
            $baseRel = '';
        } elseif (str_starts_with($docRootNorm, $engineRootNorm.'/')) {
            $baseRel = substr($docRootNorm, strlen($engineRootNorm) + 1);
        } else {
            // Fallback: document_root hostingRoot/domain altında mı?
            $expectedPrefix = $hostingRoot.'/'.$target->engineSiteName.'/';
            if ($hostingRoot !== '' && str_starts_with($docRootNorm, $expectedPrefix)) {
                $baseRel = substr($docRootNorm, strlen($expectedPrefix));
            }
        }

        if ($baseRel === '') {
            return $panelRelNorm;
        }

        if ($panelRelNorm === '') {
            return $baseRel;
        }

        return $baseRel.'/'.$panelRelNorm;
    }

    /**
     * Plesk benzeri dosya yöneticisi kapsamı: site public_html (veya alt alan public_html).
     * Belge kökü out/ veya public/ olsa bile üst klasörlere gezinilebilir; jail dışına çıkılmaz.
     */
    private function fileManagerSiteHomePath(HostingSiteTarget $target): string
    {
        $hostingRoot = rtrim((string) config('hostvim.hosting_web_root'), '/\\');
        if ($target->isSubdomain()) {
            $docRoot = str_replace('\\', '/', rtrim($target->documentRoot, '/'));
            if ($docRoot === '') {
                return $target->documentRoot;
            }
            if (str_ends_with(strtolower($docRoot), '/public_html')) {
                return str_replace('/', DIRECTORY_SEPARATOR, $docRoot);
            }
            if (str_ends_with(strtolower($docRoot), '/public')) {
                $parent = dirname($docRoot);
                if (is_file($parent.'/artisan') || is_file($parent.'/composer.json') || is_file($parent.'/spark')) {
                    return str_replace('/', DIRECTORY_SEPARATOR, $parent);
                }
            }

            return str_replace('/', DIRECTORY_SEPARATOR, $docRoot);
        }

        return $hostingRoot.DIRECTORY_SEPARATOR.$target->engineSiteName.DIRECTORY_SEPARATOR.'public_html';
    }

    private function fileManagerBasePath(HostingSiteTarget $target): string
    {
        return $this->fileManagerSiteHomePath($target);
    }

    private function documentRootRelFromSiteHome(HostingSiteTarget $target): string
    {
        $home = str_replace('\\', '/', rtrim($this->fileManagerSiteHomePath($target), '/'));
        $doc = str_replace('\\', '/', rtrim($target->documentRoot, '/'));
        if ($doc === '' || $doc === $home) {
            return '';
        }
        if (str_starts_with($doc, $home.'/')) {
            return substr($doc, strlen($home) + 1);
        }

        return '';
    }

    /**
     * @return list<array{name: string, path: string, children: list<mixed>}>
     */
    private function buildFolderTree(HostingSiteTarget $target, string $rel, int $depthLeft): array
    {
        if ($depthLeft <= 0) {
            return [];
        }
        $rel = trim(str_replace('\\', '/', $rel), '/');
        $engineRel = $this->panelRelToEngineRel($target, $rel);
        $list = $this->engine->listFilesResult($target->engineSiteName, $engineRel, 500, 0, 'name', 'asc');
        if ($list['error'] !== null) {
            return [];
        }
        $nodes = [];
        foreach ((array) ($list['entries'] ?? []) as $entry) {
            if (! is_array($entry) || empty($entry['is_dir'])) {
                continue;
            }
            $name = (string) ($entry['name'] ?? '');
            if ($name === '' || $name === '.' || $name === '..' || str_contains($name, '/')) {
                continue;
            }
            if ($name === self::TRASH_DIR || $name === '.panelze-trash') {
                continue;
            }
            $childRel = $rel === '' ? $name : $rel.'/'.$name;
            $nodes[] = [
                'name' => $name,
                'path' => $childRel,
                'children' => $this->buildFolderTree($target, $childRel, $depthLeft - 1),
            ];
        }

        return $nodes;
    }

    public function index(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }
        $hostingTarget = $this->resolveHostingTarget($request, $domain);
        $path = $this->resolveFileManagerPath($request);
        $engineRelPath = $this->panelRelToEngineRel($hostingTarget, $path);

        $limit = (int) $request->query('limit', 200);
        $offset = (int) $request->query('offset', 0);
        $sort = (string) $request->query('sort', 'name');
        $order = (string) $request->query('order', 'asc');

        $limit = max(1, min(5000, $limit));
        $offset = max(0, $offset);
        $sort = in_array($sort, ['name', 'size', 'mtime'], true) ? $sort : 'name';
        $order = strtolower($order) === 'desc' ? 'desc' : 'asc';

        $list = $this->engine->listFilesResult($hostingTarget->engineSiteName, $engineRelPath, $limit, $offset, $sort, $order);
        if ($list['error'] !== null) {
            return response()->json([
                'message' => $list['error'],
                'entries' => [],
                'document_root_hint' => $hostingTarget->documentRoot,
                'hostname' => $hostingTarget->hostname,
                'subdomain_id' => $hostingTarget->subdomain?->id,
                'total' => 0,
                'offset' => $offset,
                'limit' => $limit,
            ], 503);
        }

        return response()->json([
            'entries' => $list['entries'],
            'document_root_hint' => $hostingTarget->documentRoot,
            'file_manager_root' => $this->fileManagerBasePath($hostingTarget),
            'document_root_rel' => $this->documentRootRelFromSiteHome($hostingTarget),
            'hostname' => $hostingTarget->hostname,
            'subdomain_id' => $hostingTarget->subdomain?->id,
            'total' => $list['total'] ?? 0,
            'offset' => $list['offset'] ?? $offset,
            'limit' => $list['limit'] ?? $limit,
        ]);
    }

    public function tree(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }
        $hostingTarget = $this->resolveHostingTarget($request, $domain);
        $depth = max(1, min(5, (int) $request->query('depth', 3)));

        return response()->json([
            'tree' => $this->buildFolderTree($hostingTarget, '', $depth),
            'file_manager_root' => $this->fileManagerBasePath($hostingTarget),
            'document_root_hint' => $hostingTarget->documentRoot,
            'document_root_rel' => $this->documentRootRelFromSiteHome($hostingTarget),
            'hostname' => $hostingTarget->hostname,
            'subdomain_id' => $hostingTarget->subdomain?->id,
        ]);
    }

    public function search(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }
        $hostingTarget = $this->resolveHostingTarget($request, $domain);
        $validated = $request->validate([
            'path' => 'nullable|string|max:2048',
            'q' => 'required|string|min:2|max:256',
        ]);

        return response()->json([
            'hits' => $this->engine->searchFiles(
                $hostingTarget->engineSiteName,
                $this->panelRelToEngineRel($hostingTarget, (string) ($validated['path'] ?? '')),
                $validated['q']
            ),
        ]);
    }

    public function mkdir(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }
        $hostingTarget = $this->resolveHostingTarget($request, $domain);
        $validated = $request->validate(['path' => 'required|string']);

        $enginePath = $this->panelRelToEngineRel($hostingTarget, $validated['path']);
        $templateMode = $this->inferSiblingMode($hostingTarget, $enginePath, true);
        $result = $this->engine->mkdirFile($hostingTarget->engineSiteName, $enginePath);
        if (! empty($result['error'])) {
            return response()->json(['message' => $result['error']], 422);
        }
        if ($templateMode !== null) {
            $this->engine->chmodFile($hostingTarget->engineSiteName, $enginePath, $templateMode);
        }

        return response()->json($result);
    }

    public function destroy(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }
        $hostingTarget = $this->resolveHostingTarget($request, $domain);
        $from = $this->resolveFileManagerPath($request);
        if ($from === '') {
            return response()->json(['message' => 'The path field is required.'], 422);
        }
        $engineFrom = $this->panelRelToEngineRel($hostingTarget, $from);
        try {
            $result = $this->engine->deleteFile($hostingTarget->engineSiteName, $engineFrom);
            if (! empty($result['error'])) {
                $this->logFileAction($request, $domain, 'delete', $from, null, false, $result['error']);

                return response()->json(['message' => $result['error']], 422);
            }
            $this->logFileAction($request, $domain, 'delete', $from, null, true, null);
        } catch (\Throwable $e) {
            $this->logFileAction($request, $domain, 'delete', $from, null, false, $e->getMessage());

            return response()->json(['message' => $e->getMessage()], 500);
        }

        return response()->json($result);
    }

    public function trashIndex(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }
        $hostingTarget = $this->resolveHostingTarget($request, $domain);

        $limit = (int) $request->query('limit', 200);
        $limit = max(1, min(200, $limit));

        $metaList = $this->engine->listFilesResult($hostingTarget->engineSiteName, self::TRASH_META_DIR, $limit, 0, 'mtime', 'desc');
        if ($metaList['error'] !== null) {
            // Trash hiç yoksa boş dön.
            return response()->json(['items' => []]);
        }

        $items = [];
        foreach (($metaList['entries'] ?? []) as $e) {
            $name = (string) ($e['name'] ?? '');
            $isDir = (bool) ($e['is_dir'] ?? false);
            if ($name === '' || $isDir) {
                continue;
            }
            if (! str_ends_with($name, '.json')) {
                continue;
            }

            $id = substr($name, 0, -5);
            if ($id === '') {
                continue;
            }

            try {
                $raw = $this->engine->readFile($hostingTarget->engineSiteName, self::TRASH_META_DIR.'/'.$name);
                $meta = json_decode($raw, true);
                if (! is_array($meta)) {
                    continue;
                }
                $items[] = [
                    'id' => $id,
                    'original_path' => (string) ($meta['original_path'] ?? ''),
                    'deleted_at' => (string) ($meta['deleted_at'] ?? ''),
                    'name' => (string) ($meta['name'] ?? ''),
                    'is_dir' => (bool) ($meta['is_dir'] ?? false),
                    'size' => (int) ($meta['size'] ?? 0),
                ];
            } catch (\Throwable $ignored) {
                continue;
            }
        }

        return response()->json(['items' => $items]);
    }

    public function trashMove(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }
        $validated = $request->validate([
            'path' => 'required|string|max:2048',
        ]);

        $from = trim((string) $validated['path']);
        if ($from === '') {
            return response()->json(['message' => 'The path field is required.'], 422);
        }

        $result = $this->trashMovePath($request, $domain, $from);
        if (! $result['ok']) {
            return response()->json(['message' => $result['message'] ?? 'trash_move_failed'], 422);
        }

        return response()->json(['id' => $result['id'], 'message' => 'moved_to_trash']);
    }

    public function trashMoveBulk(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }
        $validated = $request->validate([
            'paths' => 'required|array|min:1|max:500',
            'paths.*' => 'required|string|max:2048',
        ]);

        $paths = array_values(array_filter(array_map(
            static fn ($p) => trim((string) $p),
            $validated['paths']
        ), static fn ($p) => $p !== ''));

        if ($paths === []) {
            return response()->json(['message' => 'The paths field is required.'], 422);
        }

        $ok = 0;
        $failed = [];
        foreach ($paths as $from) {
            $result = $this->trashMovePath($request, $domain, $from);
            if ($result['ok']) {
                $ok++;
            } else {
                $failed[] = [
                    'path' => $from,
                    'message' => (string) ($result['message'] ?? 'trash_move_failed'),
                ];
            }
        }

        return response()->json([
            'ok' => $ok,
            'failed' => $failed,
            'total' => count($paths),
        ]);
    }

    /**
     * @return array{ok: bool, id?: string, message?: string}
     */
    private function trashMovePath(Request $request, Domain $domain, string $from): array
    {
        $hostingTarget = $this->resolveHostingTarget($request, $domain);
        $id = now()->format('YmdHis').'-'.Str::lower(Str::random(10));
        $engineFrom = $this->panelRelToEngineRel($hostingTarget, $from);
        $engineItem = self::TRASH_ITEMS_DIR.'/'.$id;
        $engineMeta = self::TRASH_META_DIR.'/'.$id.'.json';

        try {
            $this->engine->mkdirFile($hostingTarget->engineSiteName, self::TRASH_DIR);
            $this->engine->mkdirFile($hostingTarget->engineSiteName, self::TRASH_ITEMS_DIR);
            $this->engine->mkdirFile($hostingTarget->engineSiteName, self::TRASH_META_DIR);

            $mv = $this->engine->moveFile($hostingTarget->engineSiteName, $engineFrom, $engineItem);
            if (! empty($mv['error'])) {
                $this->logFileAction($request, $domain, 'trash_move', $from, null, false, $mv['error']);

                return ['ok' => false, 'message' => $mv['error']];
            }

            $metaPayload = [
                'id' => $id,
                'original_path' => $from,
                'deleted_at' => now()->toIso8601String(),
                'name' => basename($from),
            ];
            $wr = $this->engine->writeFile($hostingTarget->engineSiteName, $engineMeta, json_encode($metaPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            if (! empty($wr['error'])) {
                $this->logFileAction($request, $domain, 'trash_meta_write', $from, null, false, $wr['error']);
            }

            $this->logFileAction($request, $domain, 'trash_move', $from, null, true, null);

            return ['ok' => true, 'id' => $id];
        } catch (\Throwable $e) {
            $this->logFileAction($request, $domain, 'trash_move', $from, null, false, $e->getMessage());

            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    public function trashRestore(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }
        $hostingTarget = $this->resolveHostingTarget($request, $domain);

        $validated = $request->validate([
            'id' => 'required|string|max:64',
        ]);
        $id = trim((string) $validated['id']);
        if ($id === '') {
            return response()->json(['message' => 'The id field is required.'], 422);
        }

        $engineMeta = self::TRASH_META_DIR.'/'.$id.'.json';
        $engineItem = self::TRASH_ITEMS_DIR.'/'.$id;

        try {
            $raw = $this->engine->readFile($hostingTarget->engineSiteName, $engineMeta);
            $meta = json_decode($raw, true);
            $origPanel = is_array($meta) ? (string) ($meta['original_path'] ?? '') : '';
            if (trim($origPanel) === '') {
                return response()->json(['message' => 'Restore bilgisi bulunamadı.'], 404);
            }

            $engineTo = $this->panelRelToEngineRel($hostingTarget, $origPanel);
            $mv = $this->engine->moveFile($hostingTarget->engineSiteName, $engineItem, $engineTo);
            if (! empty($mv['error'])) {
                // Çakışma varsa alternatif isme restore et.
                $altPanel = $origPanel.'.restored-'.now()->format('YmdHis');
                $altEngine = $this->panelRelToEngineRel($hostingTarget, $altPanel);
                $mv2 = $this->engine->moveFile($hostingTarget->engineSiteName, $engineItem, $altEngine);
                if (! empty($mv2['error'])) {
                    $this->logFileAction($request, $domain, 'trash_restore', $origPanel, null, false, $mv2['error']);

                    return response()->json(['message' => $mv2['error']], 422);
                }
            }

            // Meta'yı sil.
            $this->engine->deleteFile($hostingTarget->engineSiteName, $engineMeta);

            $this->logFileAction($request, $domain, 'trash_restore', $origPanel, null, true, null);

            return response()->json(['message' => 'restored']);
        } catch (\Throwable $e) {
            $this->logFileAction($request, $domain, 'trash_restore', $id, null, false, $e->getMessage());
            throw $e;
        }
    }

    public function trashDestroy(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }
        $hostingTarget = $this->resolveHostingTarget($request, $domain);

        $id = (string) $request->query('id', '');
        $id = trim($id);
        if ($id === '') {
            return response()->json(['message' => 'The id field is required.'], 422);
        }

        $engineMeta = self::TRASH_META_DIR.'/'.$id.'.json';
        $engineItem = self::TRASH_ITEMS_DIR.'/'.$id;

        try {
            $r1 = $this->engine->deleteFile($hostingTarget->engineSiteName, $engineItem);
            $this->engine->deleteFile($hostingTarget->engineSiteName, $engineMeta);
            if (! empty($r1['error'])) {
                $this->logFileAction($request, $domain, 'trash_delete', $id, null, false, $r1['error']);

                return response()->json(['message' => $r1['error']], 422);
            }
            $this->logFileAction($request, $domain, 'trash_delete', $id, null, true, null);

            return response()->json(['message' => 'deleted']);
        } catch (\Throwable $e) {
            $this->logFileAction($request, $domain, 'trash_delete', $id, null, false, $e->getMessage());
            throw $e;
        }
    }

    public function trashEmpty(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }
        $hostingTarget = $this->resolveHostingTarget($request, $domain);

        try {
            $r = $this->engine->deleteFile($hostingTarget->engineSiteName, self::TRASH_DIR);
            if (! empty($r['error'])) {
                $this->logFileAction($request, $domain, 'trash_empty', null, null, false, $r['error']);

                return response()->json(['message' => $r['error']], 422);
            }
            $this->logFileAction($request, $domain, 'trash_empty', null, null, true, null);

            return response()->json(['message' => 'emptied']);
        } catch (\Throwable $e) {
            $this->logFileAction($request, $domain, 'trash_empty', null, null, false, $e->getMessage());
            throw $e;
        }
    }

    public function read(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }
        $hostingTarget = $this->resolveHostingTarget($request, $domain);
        $path = $this->resolveFileManagerPath($request);
        if ($path === '') {
            return response()->json(['message' => 'The path field is required.'], 422);
        }
        $enginePath = $this->panelRelToEngineRel($hostingTarget, $path);

        return response()->json([
            'content' => $this->engine->readFile($hostingTarget->engineSiteName, $enginePath),
        ]);
    }

    public function write(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }
        $hostingTarget = $this->resolveHostingTarget($request, $domain);
        $validated = $request->validate([
            'path' => 'required|string',
            'content' => 'nullable|string',
        ]);

        $from = $validated['path'];
        $engineFrom = $this->panelRelToEngineRel($hostingTarget, $from);
        $this->quota->ensureDiskHeadroom($request->user(), strlen((string) ($validated['content'] ?? '')));
        try {
            $result = $this->engine->writeFile($hostingTarget->engineSiteName, $engineFrom, $validated['content'] ?? '');
            if (! empty($result['error'])) {
                $this->logFileAction($request, $domain, 'edit', $from, null, false, $result['error']);

                return response()->json(['message' => $result['error']], 422);
            }
            $this->logFileAction($request, $domain, 'edit', $from, null, true, null);
        } catch (\Throwable $e) {
            $this->logFileAction($request, $domain, 'edit', $from, null, false, $e->getMessage());
            throw $e;
        }

        return response()->json($result);
    }

    public function create(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }
        $hostingTarget = $this->resolveHostingTarget($request, $domain);

        $validated = $request->validate([
            'path' => 'required|string',
            'content' => 'nullable|string',
        ]);

        $from = $validated['path'];
        $engineFrom = $this->panelRelToEngineRel($hostingTarget, $from);
        $this->quota->ensureDiskHeadroom($request->user(), strlen((string) ($validated['content'] ?? '')));
        try {
            $result = $this->engine->createFile($hostingTarget->engineSiteName, $engineFrom, $validated['content'] ?? '');
            if (! empty($result['error'])) {
                $this->logFileAction($request, $domain, 'create', $from, null, false, $result['error']);

                return response()->json(['message' => $result['error']], 422);
            }

            $this->logFileAction($request, $domain, 'create', $from, null, true, null);

            return response()->json($result);
        } catch (\Throwable $e) {
            $this->logFileAction($request, $domain, 'create', $from, null, false, $e->getMessage());
            throw $e;
        }
    }

    public function upload(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }
        $hostingTarget = $this->resolveHostingTarget($request, $domain);
        $maxKb = $this->fileManagerMaxUploadKb();
        $validated = $request->validate([
            'path' => 'nullable|string',
            'file' => 'required|file|max:'.$maxKb,
        ]);
        $relPath = (string) ($validated['path'] ?? '');
        $engineRelPath = $this->panelRelToEngineRel($hostingTarget, $relPath);
        // Klasör sürükle-bırakta derin/yeni dizinler gelebilir; upload öncesi dizini server tarafında garanti et.
        if (trim($engineRelPath) !== '') {
            $mk = $this->engine->mkdirFile($hostingTarget->engineSiteName, $engineRelPath);
            if (! empty($mk['error']) && ! str_contains(strtolower((string) $mk['error']), 'exist')) {
                return response()->json(['message' => $mk['error']], 422);
            }
        }
        $up = $request->file('file');
        $baseName = basename((string) $up->getClientOriginalName());
        $engineTargetPath = trim($engineRelPath !== '' ? $engineRelPath.'/'.$baseName : $baseName, '/');
        $templateMode = $this->inferSiblingMode($hostingTarget, $engineTargetPath, false);
        $this->quota->ensureDiskHeadroom($request->user(), (int) $up->getSize());
        try {
            $result = $this->engine->uploadFile($hostingTarget->engineSiteName, $engineRelPath, $up);
            $ok = empty($result['error']);
            $auto = null;
            if ($ok && $templateMode !== null) {
                $this->engine->chmodFile($hostingTarget->engineSiteName, $engineTargetPath, $templateMode);
            }
            if ($ok && $this->shouldAutoConfigureAfterUpload($baseName)) {
                $auto = $this->autoWebConfigurator->detectAndApply($domain->fresh());
                if (! ($auto['applied'] ?? false)) {
                    SafeAuditLogger::warning('hostvim.file_audit', [
                        'domain' => $hostingTarget->engineSiteName,
                        'action' => 'auto_web_config_after_upload_failed',
                        'error' => (string) ($auto['error'] ?? 'unknown'),
                        'profile' => (string) ($auto['profile'] ?? ''),
                        'variant' => (string) ($auto['variant'] ?? ''),
                    ], $request);
                }
            }
            $this->logFileAction($request, $domain, 'upload', $relPath, null, $ok, $result['error'] ?? null);
            if ($auto !== null) {
                $result['auto_web'] = $auto;
            }

            $status = $ok ? 200 : 502;

            return response()->json($result, $status);
        } catch (\Throwable $e) {
            $this->logFileAction($request, $domain, 'upload', $relPath, null, false, $e->getMessage());
            throw $e;
        }
    }

    private function fileManagerMaxUploadKb(): int
    {
        $configured = (int) config('hostvim.limits.max_file_manager_size_mb', 50);
        $panelOverride = (int) (PanelSetting::query()->where('key', 'limits.max_file_manager_size_mb')->value('value') ?? 0);
        $mb = max(1, $panelOverride > 0 ? $panelOverride : $configured);

        return $mb * 1024;
    }

    /**
     * Hedef dizindeki kardeş dosya/klasörlerden mode şablonu bulur (örn 644/755).
     */
    private function inferSiblingMode(HostingSiteTarget $hostingTarget, string $engineTargetPath, bool $wantDir): ?string
    {
        $target = trim(str_replace('\\', '/', $engineTargetPath), '/');
        if ($target === '') {
            return null;
        }
        $base = basename($target);
        $targetExt = $wantDir ? '' : $this->fileExt($base);
        $pos = strrpos($target, '/');
        $parent = $pos === false ? '' : substr($target, 0, $pos);

        $list = $this->engine->listFilesResult($hostingTarget->engineSiteName, $parent, 500, 0, 'name', 'asc');
        if (($list['error'] ?? null) !== null) {
            return null;
        }

        $sameExtMode = null;
        $anyMode = null;
        foreach (($list['entries'] ?? []) as $e) {
            $name = (string) ($e['name'] ?? '');
            if ($name === '' || $name === $base) {
                continue;
            }
            if ((bool) ($e['is_dir'] ?? false) !== $wantDir) {
                continue;
            }
            $mode = $this->normalizeMode((string) ($e['mode'] ?? ''));
            if ($mode === null) {
                continue;
            }
            if ($wantDir) {
                return $mode;
            }
            if ($anyMode === null) {
                $anyMode = $mode;
            }
            if ($targetExt !== '' && $this->fileExt($name) === $targetExt) {
                $sameExtMode = $mode;
                break;
            }
        }

        return $sameExtMode ?? $anyMode;
    }

    private function normalizeMode(string $raw): ?string
    {
        $m = trim($raw);
        if ($m === '') {
            return null;
        }
        if (preg_match('/^[0-7]{3,4}$/', $m) === 1) {
            return strlen($m) === 4 ? substr($m, 1) : $m;
        }

        return null;
    }

    private function fileExt(string $name): string
    {
        $dot = strrpos($name, '.');
        if ($dot === false || $dot === 0 || $dot === strlen($name) - 1) {
            return '';
        }

        return strtolower(substr($name, $dot + 1));
    }

    private function shouldAutoConfigureAfterUpload(string $filename): bool
    {
        $f = strtolower(trim($filename));
        if ($f === '') {
            return false;
        }
        $markers = [
            'artisan',
            'composer.json',
            'package.json',
            'wp-config.php',
            'next.config.js',
            'next.config.mjs',
            'next.config.ts',
            'nuxt.config.js',
            'nuxt.config.ts',
            '.env',
        ];
        if (in_array($f, $markers, true)) {
            return true;
        }

        return str_ends_with($f, '.zip')
            || str_ends_with($f, '.tar')
            || str_ends_with($f, '.tar.gz')
            || str_ends_with($f, '.tgz');
    }

    public function rename(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }
        $hostingTarget = $this->resolveHostingTarget($request, $domain);

        $validated = $request->validate([
            'from' => 'required|string',
            'to' => 'required|string',
        ]);

        $from = $validated['from'];
        $to = $validated['to'];
        $engineFrom = $this->panelRelToEngineRel($hostingTarget, $from);
        $engineTo = $this->panelRelToEngineRel($hostingTarget, $to);
        try {
            $result = $this->engine->renameFile($hostingTarget->engineSiteName, $engineFrom, $engineTo);
            if (! empty($result['error'])) {
                $this->logFileAction($request, $domain, 'rename', $from, $to, false, $result['error']);

                return response()->json(['message' => $result['error']], 422);
            }
            $this->logFileAction($request, $domain, 'rename', $from, $to, true, null);

            return response()->json($result);
        } catch (\Throwable $e) {
            $this->logFileAction($request, $domain, 'rename', $from, $to, false, $e->getMessage());
            throw $e;
        }
    }

    public function move(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }
        $hostingTarget = $this->resolveHostingTarget($request, $domain);

        $validated = $request->validate([
            'from' => 'required|string',
            'to' => 'required|string',
        ]);

        $from = $validated['from'];
        $to = $validated['to'];
        $engineFrom = $this->panelRelToEngineRel($hostingTarget, $from);
        $engineTo = $this->panelRelToEngineRel($hostingTarget, $to);
        try {
            $result = $this->engine->moveFile($hostingTarget->engineSiteName, $engineFrom, $engineTo);
            if (! empty($result['error'])) {
                $this->logFileAction($request, $domain, 'move', $from, $to, false, $result['error']);

                return response()->json(['message' => $result['error']], 422);
            }
            $this->logFileAction($request, $domain, 'move', $from, $to, true, null);

            return response()->json($result);
        } catch (\Throwable $e) {
            $this->logFileAction($request, $domain, 'move', $from, $to, false, $e->getMessage());
            throw $e;
        }
    }

    public function copy(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }
        $hostingTarget = $this->resolveHostingTarget($request, $domain);
        $validated = $request->validate([
            'from' => 'required|string',
            'to' => 'required|string',
        ]);
        $from = $validated['from'];
        $to = $validated['to'];
        $engineFrom = $this->panelRelToEngineRel($hostingTarget, $from);
        $engineTo = $this->panelRelToEngineRel($hostingTarget, $to);
        $this->quota->ensureDiskHeadroom($request->user(), $this->quota->engineFileSizeBytes($hostingTarget->engineSiteName, $engineFrom));
        $result = $this->engine->copyFile($hostingTarget->engineSiteName, $engineFrom, $engineTo);
        if (! empty($result['error'])) {
            $this->logFileAction($request, $domain, 'copy', $from, $to, false, $result['error']);

            return response()->json(['message' => $result['error']], 422);
        }
        $this->logFileAction($request, $domain, 'copy', $from, $to, true, null);

        return response()->json($result);
    }

    public function chmod(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }
        $hostingTarget = $this->resolveHostingTarget($request, $domain);
        $validated = $request->validate([
            'path' => 'required|string',
            'mode' => 'required|string|regex:/^[0-7]{3,4}$/',
        ]);
        $path = $validated['path'];
        $mode = $validated['mode'];
        $enginePath = $this->panelRelToEngineRel($hostingTarget, $path);
        $result = $this->engine->chmodFile($hostingTarget->engineSiteName, $enginePath, $mode);
        if (! empty($result['error'])) {
            $this->logFileAction($request, $domain, 'chmod', $path, null, false, $result['error']);

            return response()->json(['message' => $result['error']], 422);
        }
        $this->logFileAction($request, $domain, 'chmod', $path, null, true, null);

        return response()->json($result);
    }

    public function zip(Request $request, Domain $domain): JsonResponse
    {
        set_time_limit(1900);

        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }
        $hostingTarget = $this->resolveHostingTarget($request, $domain);
        $validated = $request->validate([
            'source' => 'required|string',
            'target' => 'required|string',
        ]);
        $source = $validated['source'];
        $zipDest = $validated['target'];
        $result = $this->engine->zipPath(
            $hostingTarget->engineSiteName,
            $this->panelRelToEngineRel($hostingTarget, $source),
            $this->panelRelToEngineRel($hostingTarget, $zipDest)
        );
        if (! empty($result['error'])) {
            $this->logFileAction($request, $domain, 'zip', $source, $zipDest, false, $result['error']);

            return response()->json(['message' => $result['error']], 422);
        }
        $this->logFileAction($request, $domain, 'zip', $source, $zipDest, true, null);

        return response()->json($result);
    }

    public function zipBulk(Request $request, Domain $domain): JsonResponse
    {
        set_time_limit(1900);

        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }
        $hostingTarget = $this->resolveHostingTarget($request, $domain);

        $validated = $request->validate([
            'sources' => 'required|array|min:1',
            'sources.*' => 'required|string',
            'target' => 'required|string',
        ]);

        $sources = $validated['sources'];
        $zipDest = $validated['target'];

        $engineSources = array_map(
            fn (string $s): string => $this->panelRelToEngineRel($hostingTarget, $s),
            $sources
        );
        $engineTarget = $this->panelRelToEngineRel($hostingTarget, $zipDest);

        $result = $this->engine->zipSources($hostingTarget->engineSiteName, $engineSources, $engineTarget);

        if (! empty($result['error'])) {
            $this->logFileAction($request, $domain, 'zip', (string) implode(',', $sources), $zipDest, false, $result['error']);

            return response()->json(['message' => $result['error']], 422);
        }

        $this->logFileAction($request, $domain, 'zip', (string) implode(',', $sources), $zipDest, true, null);

        return response()->json($result);
    }

    public function unzip(Request $request, Domain $domain): JsonResponse
    {
        ignore_user_abort(true);
        set_time_limit(1900);

        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }
        $hostingTarget = $this->resolveHostingTarget($request, $domain);
        $validated = $request->validate([
            'archive' => 'required|string',
            'target_dir' => 'nullable|string',
            'targetDir' => 'nullable|string',
            'if_exists' => 'nullable|string|in:fail,overwrite,skip',
        ]);
        $archive = $validated['archive'];
        $targetDir = (string) ($validated['target_dir'] ?? $validated['targetDir'] ?? '');
        $ifExists = (string) ($validated['if_exists'] ?? 'fail');
        $engineArchive = $this->panelRelToEngineRel($hostingTarget, $archive);
        $this->quota->ensureDiskHeadroom($request->user(), $this->quota->estimatedUnzipHeadroomBytes($hostingTarget->engineSiteName, $engineArchive));
        $result = $this->engine->unzipPath(
            $hostingTarget->engineSiteName,
            $engineArchive,
            $this->panelRelToEngineRel($hostingTarget, $targetDir),
            $ifExists
        );
        if (! empty($result['error'])) {
            $this->logFileAction($request, $domain, 'unzip', $archive, $targetDir, false, $result['error']);

            return response()->json(['message' => $result['error']], 422);
        }

        $domainId = (int) $domain->id;
        $engineSite = $hostingTarget->engineSiteName;
        dispatch(function () use ($domainId, $engineSite): void {
            $fresh = Domain::query()->find($domainId);
            if ($fresh === null) {
                return;
            }
            $auto = app(AutoWebConfigurator::class)->detectAndApply($fresh);
            if (! ($auto['applied'] ?? false)) {
                SafeAuditLogger::warning('hostvim.file_audit', [
                    'domain' => $engineSite,
                    'action' => 'auto_web_config_after_unzip_failed',
                    'error' => (string) ($auto['error'] ?? 'unknown'),
                    'profile' => (string) ($auto['profile'] ?? ''),
                    'variant' => (string) ($auto['variant'] ?? ''),
                ]);
            }
        })->afterResponse();

        $result['auto_web'] = ['queued' => true];
        $this->logFileAction($request, $domain, 'unzip', $archive, $targetDir, true, null);

        return response()->json($result);
    }

    public function download(Request $request, Domain $domain)
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }
        $hostingTarget = $this->resolveHostingTarget($request, $domain);

        $path = $this->resolveFileManagerPath($request);
        if ($path === '') {
            return response()->json(['message' => 'The path field is required.'], 422);
        }
        $enginePath = $this->panelRelToEngineRel($hostingTarget, $path);

        $result = $this->engine->openDownloadStream($hostingTarget->engineSiteName, $enginePath);
        if (! empty($result['error'])) {
            $this->logFileAction($request, $domain, 'download', $path, null, false, $result['error']);

            return response()->json(['message' => $result['error']], 422);
        }

        $stream = $result['stream'] ?? null;
        if ($stream === null) {
            $this->logFileAction($request, $domain, 'download', $path, null, false, 'download stream unavailable');

            return response()->json(['message' => 'download stream unavailable'], 502);
        }

        $mime = (string) ($result['mime'] ?? 'application/octet-stream');
        $filename = basename((string) ($result['filename'] ?? basename($path)));

        $this->logFileAction($request, $domain, 'download', $path, null, true, null);

        return response()->stream(function () use ($stream): void {
            while (! $stream->eof()) {
                echo $stream->read(262144);
                if (ob_get_level() > 0) {
                    @ob_flush();
                }
                flush();
            }
        }, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * read / delete / download: path bazen yalnızca query stringde, bazen gövdede gelir;
     * DELETE + axios + bazı proxy'lerde validate() query'yi görmeyebiliyor — QUERY_STRING ile yedeklenir.
     */
    private function resolveFileManagerPath(Request $request): string
    {
        $rawPath = $request->query('path');
        if (is_array($rawPath)) {
            $rawPath = $rawPath[0] ?? null;
        }
        if (! is_string($rawPath) || trim($rawPath) === '') {
            $rawPath = $request->input('path');
            if (is_array($rawPath)) {
                $rawPath = $rawPath[0] ?? null;
            }
        }
        if (! is_string($rawPath) || trim($rawPath) === '') {
            $jp = $request->json('path');
            if (is_string($jp) && trim($jp) !== '') {
                $rawPath = $jp;
            }
        }
        if (! is_string($rawPath) || trim($rawPath) === '') {
            $qs = (string) $request->server('QUERY_STRING', '');
            if ($qs !== '') {
                parse_str($qs, $parsed);
                $fromQs = $parsed['path'] ?? null;
                if (is_array($fromQs)) {
                    $fromQs = $fromQs[0] ?? null;
                }
                if (is_string($fromQs)) {
                    $rawPath = $fromQs;
                }
            }
        }

        return is_string($rawPath) ? trim($rawPath) : '';
    }

    private function logFileAction(
        Request $request,
        Domain $domain,
        string $action,
        ?string $from,
        ?string $to,
        bool $success,
        ?string $error,
    ): void {
        SafeAuditLogger::info('hostvim.file_audit', [
            'domain' => $domain->name,
            'action' => $action,
            'from_fp' => SafeAuditLogger::pathFingerprint($domain->name, $from),
            'from_base' => SafeAuditLogger::pathBasename($from),
            'to_fp' => SafeAuditLogger::pathFingerprint($domain->name, $to),
            'to_base' => SafeAuditLogger::pathBasename($to),
            'success' => $success,
            'error' => $error,
        ], $request);
    }
}
