<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class AppearanceAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_site_settings_partial_renders_form_fields(): void
    {
        view()->share('errors', new ViewErrorBag);

        $html = view('admin.site-settings.edit', [
            'embedded' => true,
            'siteName' => 'Test Site',
            'siteTagline' => '',
            'logoUrl' => null,
            'faviconUrl' => null,
            'contactEmail' => '',
            'socialTwitter' => '',
            'socialGithub' => '',
            'socialLinkedin' => '',
            'analyticsGa4' => '',
            'analyticsHeadCode' => '',
            'analyticsBodyCode' => '',
            'footerExtraNote' => '',
            'logoMaxHeightPx' => '',
            'logoMaxWidthPx' => '',
            'logoFooterMaxHeightPx' => '',
            'logoFooterMaxWidthPx' => '',
        ])->render();

        $this->assertStringContainsString('id="site_name"', $html);
    }

    public function test_appearance_page_renders_site_settings_tab_by_default(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.appearance.index'));

        $response->assertOk();
        $response->assertSee('id="hv-appearance-tab-site"', false);
        $response->assertSee('id="site_name"', false);
        $response->assertSee('name="site_name"', false);
        $response->assertSee('hv-tab-panel-site', false);
        $response->assertSee('hv-appearance-tabs', false);
    }

    public function test_appearance_page_renders_theme_tab_content(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.appearance.index', ['tab' => 'theme']));

        $response->assertOk();
        $response->assertSee('id="hv-appearance-tab-theme"', false);
        $response->assertSee('name="active_theme"', false);
        $response->assertSee('hv-tab-panel-theme', false);
        $response->assertSee('hv-theme-tab-general', false);
    }

    public function test_appearance_page_renders_all_tab_panels_without_alpine(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.appearance.index', ['tab' => 'home']));

        $response->assertOk();
        $response->assertSee('hv-tab-panel-home', false);
        $response->assertSee('hv-tab-panel-install', false);
        $response->assertSee('name="hero_image"', false);
    }

    public function test_appearance_invalid_tab_falls_back_to_site(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.appearance.index', ['tab' => 'invalid']));

        $response->assertOk();
        $response->assertSee('id="hv-appearance-tab-site"', false);
        $response->assertSee('checked', false);
    }
}
