<?php

namespace Database\Seeders;

use App\Models\LandingSiteSetting;
use App\Services\LandingAppearance;
use Illuminate\Database\Seeder;

class LandingSettingsSeeder extends Seeder
{
    public function run(): void
    {
        LandingSiteSetting::put('landing.default_locale', 'en');
        LandingSiteSetting::put('landing.enabled_locales', json_encode(['en', 'tr']));

        // Boş varsayılanlar — mevcut logo/favicon/iletişim değerlerini silme
        $defaults = [
            'landing.site_name' => '',
            'landing.site_tagline' => '',
            'landing.site_logo_path' => '',
            'landing.site_logo_max_height_px' => '',
            'landing.site_logo_max_width_px' => '',
            'landing.site_logo_footer_max_height_px' => '',
            'landing.site_logo_footer_max_width_px' => '',
            'landing.favicon_path' => '',
            'landing.contact_email' => '',
            'landing.social_twitter_url' => '',
            'landing.social_github_url' => '',
            'landing.social_linkedin_url' => '',
            'landing.analytics_ga4_id' => '',
            'landing.analytics_head_code' => '',
            'landing.analytics_body_code' => '',
            'landing.footer_extra_note' => '',
            'landing.header_brand_mode' => LandingAppearance::HEADER_BRAND_MODE_BOTH,
            'landing.active_theme' => 'orange',
            'landing.graphic_motif' => 'grid',
            'landing.theme_primary_hex' => '',
            'landing.hero_image_path' => '',
            'landing.hero_image_alt' => '',
            'landing.hero_image_caption' => '',
            'landing.page_overrides' => '{}',
            'landing.home_feature_cards' => '[]',
        ];

        foreach ($defaults as $key => $value) {
            if (LandingSiteSetting::getValue($key, null) === null) {
                LandingSiteSetting::put($key, $value);
            }
        }

        // Diskte logo varsa ve DB boşsa bağla
        $logoPath = (string) (LandingSiteSetting::getValue('landing.site_logo_path', '') ?? '');
        if ($logoPath === '') {
            foreach (['landing/logo-1775426807.png', 'landing/logo-1775255636.png'] as $candidate) {
                if (LandingAppearance::landingUploadExists($candidate)) {
                    LandingSiteSetting::put('landing.site_logo_path', $candidate);
                    break;
                }
            }
        }

        $faviconPath = (string) (LandingSiteSetting::getValue('landing.favicon_path', '') ?? '');
        if ($faviconPath === '' && LandingAppearance::landingUploadExists('landing/favicon-1775255636.png')) {
            LandingSiteSetting::put('landing.favicon_path', 'landing/favicon-1775255636.png');
        }
    }
}
