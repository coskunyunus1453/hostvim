<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingSiteSetting;
use App\Services\InstallGuide;
use App\Services\LandingI18n;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InstallSettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.install-settings.edit', [
            'installSettings' => InstallGuide::settings(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'install_get_url' => ['nullable', 'url', 'max:500'],
            'install_community_script' => ['nullable', 'url', 'max:500'],
            'install_pro_script' => ['nullable', 'url', 'max:500'],
            'install_remote_url' => ['nullable', 'url', 'max:500'],
            'install_motor_script' => ['nullable', 'url', 'max:500'],
            'install_repo_url' => ['nullable', 'url', 'max:500'],
            'install_repo_branch' => ['nullable', 'string', 'max:64', 'regex:/^[a-zA-Z0-9._\/-]+$/'],
            'install_home' => ['nullable', 'string', 'max:255', 'regex:/^\/[a-zA-Z0-9._\/-]*$/'],
            'install_admin_login_file' => ['nullable', 'string', 'max:255', 'regex:/^\/[a-zA-Z0-9._\/-]+$/'],
        ]);

        $map = [
            'install_get_url' => 'landing.install_get_url',
            'install_community_script' => 'landing.install_community_script',
            'install_pro_script' => 'landing.install_pro_script',
            'install_remote_url' => 'landing.install_remote_url',
            'install_motor_script' => 'landing.install_motor_script',
            'install_repo_url' => 'landing.install_repo_url',
            'install_repo_branch' => 'landing.install_repo_branch',
            'install_home' => 'landing.install_home',
            'install_admin_login_file' => 'landing.install_admin_login_file',
        ];

        foreach ($map as $input => $storageKey) {
            $val = trim((string) ($validated[$input] ?? ''));
            LandingSiteSetting::put($storageKey, $val);
        }

        LandingI18n::clearRuntimeCache();

        if ($request->input('return_to') === 'appearance') {
            return redirect()->route('admin.appearance.index', ['tab' => $request->input('tab', 'install')])
                ->with('status', 'Kurulum komutları güncellendi.');
        }

        return redirect()->route('admin.install-settings.edit')->with('status', 'Kurulum komutları güncellendi.');
    }
}
