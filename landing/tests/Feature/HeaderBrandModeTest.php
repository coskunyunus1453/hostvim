<?php

namespace Tests\Feature;

use App\Models\LandingSiteSetting;
use App\Models\User;
use App\Services\LandingAppearance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class HeaderBrandModeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_header_brand_mode_defaults_to_both(): void
    {
        $this->assertSame(LandingAppearance::HEADER_BRAND_MODE_BOTH, LandingAppearance::headerBrandMode());
        $this->assertTrue(LandingAppearance::showHeaderLogo());
        $this->assertTrue(LandingAppearance::showHeaderBrandText());
    }

    public function test_name_only_hides_logo_in_header_markup(): void
    {
        LandingSiteSetting::put('landing.header_brand_mode', LandingAppearance::HEADER_BRAND_MODE_NAME_ONLY);

        $response = $this->get(route('landing.home'));

        $response->assertOk();
        $response->assertSee('data-hv-show-logo="0"', false);
        $response->assertSee('data-hv-show-text="1"', false);
    }

    public function test_logo_only_hides_brand_text_in_header_markup(): void
    {
        LandingSiteSetting::put('landing.header_brand_mode', LandingAppearance::HEADER_BRAND_MODE_LOGO_ONLY);

        $response = $this->get(route('landing.home'));

        $response->assertOk();
        $response->assertSee('data-hv-show-logo="1"', false);
        $response->assertSee('data-hv-show-text="0"', false);
    }

    public function test_admin_can_save_header_brand_mode(): void
    {
        $response = $this->actingAs($this->admin())->put(route('admin.site-settings.update'), [
            'header_brand_mode' => LandingAppearance::HEADER_BRAND_MODE_LOGO_ONLY,
            'return_to' => 'appearance',
            'tab' => 'site',
        ]);

        $response->assertRedirect(route('admin.appearance.index', ['tab' => 'site']));
        $this->assertSame(
            LandingAppearance::HEADER_BRAND_MODE_LOGO_ONLY,
            LandingAppearance::headerBrandMode()
        );
    }

    public function test_site_settings_form_renders_header_brand_mode_field(): void
    {
        view()->share('errors', new ViewErrorBag);

        $html = view('admin.site-settings.edit', [
            'embedded' => true,
            'siteName' => '',
            'siteTagline' => '',
            'headerBrandMode' => LandingAppearance::HEADER_BRAND_MODE_BOTH,
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

        $this->assertStringContainsString('name="header_brand_mode"', $html);
        $this->assertStringContainsString('value="logo_only"', $html);
    }
}
