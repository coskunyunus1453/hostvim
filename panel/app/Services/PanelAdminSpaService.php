<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class PanelAdminSpaService
{
    public function publicRoot(): string
    {
        return public_path();
    }

    public function adminDir(): string
    {
        return $this->publicRoot().DIRECTORY_SEPARATOR.'admin';
    }

    public function assetTarget(): string
    {
        return $this->publicRoot().DIRECTORY_SEPARATOR.'assets';
    }

    public function indexTarget(): string
    {
        return $this->publicRoot().DIRECTORY_SEPARATOR.'index.html';
    }

    public function adminAssetLink(): string
    {
        return $this->adminDir().DIRECTORY_SEPARATOR.'assets';
    }

    public function adminIndexLink(): string
    {
        return $this->adminDir().DIRECTORY_SEPARATOR.'index.html';
    }

    public function spaRootReady(): bool
    {
        return is_dir($this->assetTarget()) && is_file($this->indexTarget());
    }

    /**
     * @return array<int, array{id:string, ok:bool, message:string}>
     */
    public function healthChecks(): array
    {
        $assetCheck = $this->symlinkCheck('admin_assets_symlink', $this->adminAssetLink(), $this->assetTarget());
        $indexCheck = $this->symlinkCheck('admin_index_symlink', $this->adminIndexLink(), $this->indexTarget());

        if ($this->spaRootReady()) {
            if (! $assetCheck['ok'] && is_dir($this->assetTarget())) {
                $assetCheck = [
                    'id' => 'admin_assets_symlink',
                    'ok' => true,
                    'message' => 'SPA assets at /assets (symlink recommended for /admin fallback)',
                ];
            }
            if (! $indexCheck['ok'] && is_file($this->indexTarget())) {
                $indexCheck = [
                    'id' => 'admin_index_symlink',
                    'ok' => true,
                    'message' => 'SPA index at root (symlink recommended for /admin routes)',
                ];
            }
        }

        return [$assetCheck, $indexCheck];
    }

    /**
     * @param  array<int, array{id:string, ok:bool, message:string}>  $steps
     */
    public function repair(array &$steps): void
    {
        if (! $this->spaRootReady()) {
            $steps[] = [
                'id' => 'admin_spa_build',
                'ok' => false,
                'message' => 'Frontend build missing (public/assets or public/index.html)',
            ];

            return;
        }

        try {
            File::ensureDirectoryExists($this->adminDir(), 0755, true);
            $steps[] = ['id' => 'mkdir:admin', 'ok' => true, 'message' => 'Admin directory ready'];
        } catch (\Throwable $e) {
            $steps[] = ['id' => 'mkdir:admin', 'ok' => false, 'message' => 'Admin directory: '.$e->getMessage()];
        }

        $this->repairSymlink($this->adminAssetLink(), $this->assetTarget(), $steps, 'admin_assets_symlink');
        $this->repairSymlink($this->adminIndexLink(), $this->indexTarget(), $steps, 'admin_index_symlink');

        $needsSudo = ! $this->symlinkCheck('admin_assets_symlink', $this->adminAssetLink(), $this->assetTarget())['ok']
            || ! $this->symlinkCheck('admin_index_symlink', $this->adminIndexLink(), $this->indexTarget())['ok'];

        if ($needsSudo) {
            $this->repairViaSudo($steps);
        }
    }

    /**
     * @param  array<int, array{id:string, ok:bool, message:string}>  $steps
     */
    private function repairViaSudo(array &$steps): void
    {
        $script = $this->resolveSudoHelperScript();
        if ($script === null) {
            $steps[] = [
                'id' => 'admin_spa_sudo',
                'ok' => false,
                'message' => 'Root helper missing (deploy panelze-fix-admin-spa or run fix-panel-permissions as root)',
            ];

            return;
        }

        $proc = new Process(['sudo', '-n', $script, base_path()]);
        $proc->setTimeout(30);
        $proc->run();

        $output = trim($proc->getOutput()."\n".$proc->getErrorOutput());
        $ok = $proc->isSuccessful()
            && $this->symlinkCheck('admin_assets_symlink', $this->adminAssetLink(), $this->assetTarget())['ok']
            && $this->symlinkCheck('admin_index_symlink', $this->adminIndexLink(), $this->indexTarget())['ok'];

        $steps[] = [
            'id' => 'admin_spa_sudo',
            'ok' => $ok,
            'message' => $ok
                ? ($output !== '' ? substr($output, 0, 300) : 'Admin SPA symlinks repaired via sudo')
                : ($output !== '' ? substr($output, 0, 300) : 'Sudo repair failed (check sudoers for panelze-fix-admin-spa)'),
        ];
    }

    private function resolveSudoHelperScript(): ?string
    {
        $candidates = [
            '/usr/local/sbin/panelze-fix-admin-spa',
            dirname(base_path()).DIRECTORY_SEPARATOR.'deploy'.DIRECTORY_SEPARATOR.'host'.DIRECTORY_SEPARATOR.'panelze-fix-admin-spa',
        ];

        foreach ($candidates as $path) {
            if (is_file($path) && is_executable($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @param  array<int, array{id:string, ok:bool, message:string}>  $steps
     */
    private function repairSymlink(string $linkPath, string $targetPath, array &$steps, string $id): void
    {
        if (! file_exists($targetPath)) {
            $steps[] = ['id' => $id, 'ok' => false, 'message' => 'Target missing: '.$targetPath];

            return;
        }

        if (is_link($linkPath)) {
            $current = @readlink($linkPath);
            if ($current !== false) {
                $resolved = $this->isAbsolutePath($current)
                    ? $current
                    : dirname($linkPath).DIRECTORY_SEPARATOR.$current;
                if (realpath($resolved) === realpath($targetPath)) {
                    $steps[] = ['id' => $id, 'ok' => true, 'message' => 'Symlink already valid'];

                    return;
                }
            }
            @unlink($linkPath);
        } elseif (file_exists($linkPath)) {
            $backup = $linkPath.'.bak.'.date('YmdHis');
            try {
                @rename($linkPath, $backup);
                if (file_exists($linkPath)) {
                    $steps[] = ['id' => $id, 'ok' => false, 'message' => 'Path exists and cannot be moved for symlink repair'];

                    return;
                }
                $steps[] = ['id' => $id.'_backup', 'ok' => true, 'message' => 'Existing path moved to '.$backup];
            } catch (\Throwable) {
                $steps[] = ['id' => $id, 'ok' => false, 'message' => 'Path exists and backup move failed'];

                return;
            }
        }

        $relative = $this->relativePath(dirname($linkPath), $targetPath);
        $created = @symlink($relative, $linkPath);
        $ok = is_link($linkPath);
        $err = $created ? '' : (error_get_last()['message'] ?? 'permission denied');
        $steps[] = [
            'id' => $id,
            'ok' => $ok,
            'message' => $ok ? 'Symlink repaired' : 'Symlink could not be created'.($err !== '' ? ': '.$err : ''),
        ];
    }

    /**
     * @return array{id:string, ok:bool, message:string}
     */
    private function symlinkCheck(string $id, string $linkPath, string $targetPath): array
    {
        if (! file_exists($targetPath)) {
            return ['id' => $id, 'ok' => false, 'message' => 'SPA build target missing'];
        }

        if (! is_link($linkPath)) {
            if (file_exists($linkPath)) {
                return ['id' => $id, 'ok' => false, 'message' => 'Path exists but is not a symlink'];
            }

            return ['id' => $id, 'ok' => false, 'message' => 'Symlink missing'];
        }

        $current = @readlink($linkPath);
        if ($current === false) {
            return ['id' => $id, 'ok' => false, 'message' => 'Symlink target unreadable'];
        }

        $resolved = $this->isAbsolutePath($current)
            ? $current
            : dirname($linkPath).DIRECTORY_SEPARATOR.$current;
        if (realpath($resolved) !== realpath($targetPath)) {
            return ['id' => $id, 'ok' => false, 'message' => 'Symlink points to unexpected target'];
        }

        return ['id' => $id, 'ok' => true, 'message' => 'Symlink valid'];
    }

    private function relativePath(string $fromDir, string $toPath): string
    {
        $from = explode(DIRECTORY_SEPARATOR, trim(realpath($fromDir) ?: $fromDir, DIRECTORY_SEPARATOR));
        $to = explode(DIRECTORY_SEPARATOR, trim(realpath($toPath) ?: $toPath, DIRECTORY_SEPARATOR));

        while (count($from) > 0 && count($to) > 0 && $from[0] === $to[0]) {
            array_shift($from);
            array_shift($to);
        }

        return str_repeat('..'.DIRECTORY_SEPARATOR, count($from)).implode(DIRECTORY_SEPARATOR, $to);
    }

    private function isAbsolutePath(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        if ($path[0] === '/' || $path[0] === '\\') {
            return true;
        }

        return (bool) preg_match('/^[A-Za-z]:[\\\\\\/]/', $path);
    }
}
