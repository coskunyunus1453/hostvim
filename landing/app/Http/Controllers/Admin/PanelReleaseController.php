<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PanelRelease;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PanelReleaseController extends Controller
{
    public function index(): View
    {
        $releases = PanelRelease::query()
            ->orderByDesc('published_at')
            ->orderByDesc('version')
            ->paginate(25);

        return view('admin.panel-releases.index', compact('releases'));
    }

    public function create(): View
    {
        return view('admin.panel-releases.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $release = PanelRelease::query()->create($this->validated($request));

        return redirect()
            ->route('admin.panel-releases.edit', $release)
            ->with('status', 'Sürüm kaydı oluşturuldu. Yayınlamak için düzenleme sayfasından «Yayınla» kullanın.');
    }

    public function edit(PanelRelease $panel_release): View
    {
        return view('admin.panel-releases.edit', ['release' => $panel_release]);
    }

    public function update(Request $request, PanelRelease $panel_release): RedirectResponse
    {
        $panel_release->update($this->validated($request, $panel_release->id));

        return redirect()
            ->route('admin.panel-releases.index')
            ->with('status', 'Sürüm güncellendi.');
    }

    public function destroy(PanelRelease $panel_release): RedirectResponse
    {
        if ($panel_release->is_published) {
            return redirect()
                ->route('admin.panel-releases.index')
                ->with('error', 'Yayınlanmış sürüm silinemez. Önce yayından kaldırın.');
        }

        $panel_release->delete();

        return redirect()
            ->route('admin.panel-releases.index')
            ->with('status', 'Sürüm silindi.');
    }

    public function publish(PanelRelease $panel_release): RedirectResponse
    {
        if ($panel_release->artifact_url === null && $panel_release->git_tag === null) {
            return redirect()
                ->route('admin.panel-releases.edit', $panel_release)
                ->with('error', 'Yayınlamadan önce artifact URL veya git etiketi girin ve «Kaydet» ile kaydedin.');
        }

        $panel_release->update([
            'is_published' => true,
            'published_at' => $panel_release->published_at?->isFuture()
                ? $panel_release->published_at
                : ($panel_release->published_at ?? now()),
        ]);

        return redirect()
            ->route('admin.panel-releases.index')
            ->with('status', "Sürüm {$panel_release->version} yayınlandı. Müşteri panelleri kontrol sırasında bildirim alacaktır.");
    }

    public function unpublish(PanelRelease $panel_release): RedirectResponse
    {
        $panel_release->update([
            'is_published' => false,
        ]);

        return redirect()
            ->route('admin.panel-releases.index')
            ->with('status', 'Sürüm yayından kaldırıldı.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'version' => [
                'required', 'string', 'max:32', 'regex:/^\d+\.\d+\.\d+([\-+][0-9A-Za-z\.\-]+)?$/',
                Rule::unique('panel_releases', 'version')->ignore($ignoreId),
            ],
            'channel' => ['required', 'string', 'max:32', 'in:stable,beta'],
            'profile' => ['required', 'string', 'max:32', 'in:customer,pro,all'],
            'title' => ['required', 'string', 'max:255'],
            'changelog' => ['required', 'string', 'max:50000'],
            'artifact_url' => ['nullable', 'string', 'max:2048', 'url'],
            'artifact_sha256' => ['nullable', 'string', 'size:64', 'regex:/^[a-f0-9]{64}$/i'],
            'git_tag' => ['nullable', 'string', 'max:64', 'regex:/^v?\d+\.\d+\.\d+/'],
            'min_panel_version' => ['nullable', 'string', 'max:32', 'regex:/^\d+\.\d+\.\d+([\-+][0-9A-Za-z\.\-]+)?$/'],
            'requires_engine_restart' => ['nullable', 'in:0,1'],
            'is_published' => ['nullable', 'in:0,1'],
            'published_at' => ['nullable', 'date'],
        ]);

        $artifactUrl = trim((string) ($data['artifact_url'] ?? ''));
        $gitTag = trim((string) ($data['git_tag'] ?? ''));
        if ($artifactUrl === '' && $gitTag === '' && $ignoreId === null) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'artifact_url' => 'Artifact URL veya git etiketi zorunludur.',
            ]);
        }
        if ($artifactUrl === '' && $gitTag === '' && $ignoreId !== null) {
            $existing = PanelRelease::query()->find($ignoreId);
            if ($existing && $existing->artifact_url === null && $existing->git_tag === null) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'artifact_url' => 'Artifact URL veya git etiketi zorunludur.',
                ]);
            }
        }

        $isPublished = (bool) ($data['is_published'] ?? false);
        $publishedAt = ! empty($data['published_at']) ? $data['published_at'] : null;
        if ($isPublished && $publishedAt === null) {
            $publishedAt = now();
        }
        if (! $isPublished) {
            $publishedAt = ! empty($data['published_at']) ? $data['published_at'] : null;
        }

        return [
            'version' => $data['version'],
            'channel' => $data['channel'],
            'profile' => $data['profile'],
            'title' => $data['title'],
            'changelog' => $data['changelog'],
            'artifact_url' => $artifactUrl !== '' ? $artifactUrl : null,
            'artifact_sha256' => isset($data['artifact_sha256']) && $data['artifact_sha256'] !== ''
                ? strtolower($data['artifact_sha256'])
                : null,
            'git_tag' => $gitTag !== '' ? $gitTag : null,
            'min_panel_version' => trim((string) ($data['min_panel_version'] ?? '')) ?: null,
            'requires_engine_restart' => (bool) ($data['requires_engine_restart'] ?? 1),
            'is_published' => $isPublished,
            'published_at' => $publishedAt,
        ];
    }
}
