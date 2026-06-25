<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingSiteSetting;
use App\Services\InstallGuide;
use App\Services\LandingAppearance;
use Illuminate\Contracts\View\View;

class AppearanceController extends Controller
{
    public function index(): View
    {
        $groups = config('landing_content_keys.groups', []);
        $allowedKeys = [];
        foreach ($groups as $labels) {
            $allowedKeys = array_merge($allowedKeys, array_keys($labels));
        }

        $rawCards = LandingSiteSetting::getValue('landing.home_feature_cards', '[]');
        $cards = json_decode((string) $rawCards, true);
        if (! is_array($cards) || $cards === []) {
            $cards = app()->getLocale() === 'tr'
                ? LandingAppearance::DEFAULT_FEATURE_CARDS_TR
                : LandingAppearance::DEFAULT_FEATURE_CARDS_EN;
        }

        return view('admin.appearance.index', [
            'activeTab' => $this->resolveAppearanceTab(request('tab', 'site')),
            // site settings data
            'siteName' => trim((string) (LandingSiteSetting::getValue('landing.site_name', '') ?? '')),
            'siteTagline' => trim((string) (LandingSiteSetting::getValue('landing.site_tagline', '') ?? '')),
            'logoUrl' => LandingAppearance::siteLogoUrl(),
            'faviconUrl' => LandingAppearance::faviconUrl(),
            'contactEmail' => trim((string) (LandingSiteSetting::getValue('landing.contact_email', '') ?? '')),
            'socialTwitter' => trim((string) (LandingSiteSetting::getValue('landing.social_twitter_url', '') ?? '')),
            'socialGithub' => trim((string) (LandingSiteSetting::getValue('landing.social_github_url', '') ?? '')),
            'socialLinkedin' => trim((string) (LandingSiteSetting::getValue('landing.social_linkedin_url', '') ?? '')),
            'analyticsGa4' => trim((string) (LandingSiteSetting::getValue('landing.analytics_ga4_id', '') ?? '')),
            'analyticsHeadCode' => trim((string) (LandingSiteSetting::getValue('landing.analytics_head_code', '') ?? '')),
            'analyticsBodyCode' => trim((string) (LandingSiteSetting::getValue('landing.analytics_body_code', '') ?? '')),
            'footerExtraNote' => trim((string) (LandingSiteSetting::getValue('landing.footer_extra_note', '') ?? '')),
            'logoMaxHeightPx' => (string) (LandingSiteSetting::getValue('landing.site_logo_max_height_px', '') ?? ''),
            'logoMaxWidthPx' => (string) (LandingSiteSetting::getValue('landing.site_logo_max_width_px', '') ?? ''),
            'logoFooterMaxHeightPx' => (string) (LandingSiteSetting::getValue('landing.site_logo_footer_max_height_px', '') ?? ''),
            'logoFooterMaxWidthPx' => (string) (LandingSiteSetting::getValue('landing.site_logo_footer_max_width_px', '') ?? ''),
            'headerBrandMode' => LandingAppearance::headerBrandMode(),
            // theme settings data
            'activeTheme' => LandingAppearance::activeTheme(),
            'graphicMotif' => LandingAppearance::graphicMotif(),
            'primaryHex' => LandingSiteSetting::getValue('landing.theme_primary_hex', '') ?? '',
            'themes' => config('landing_theme.themes', []),
            'motifs' => config('landing_theme.graphic_motifs', []),
            'featureIcons' => config('landing_theme.feature_icons', []),
            'neonTop' => LandingAppearance::neonTop(),
            'neonStackSection' => LandingAppearance::neonStackSection(),
            'neonStackItems' => LandingAppearance::neonStackItems(),
            'neonGridSection' => LandingAppearance::neonGridSection(),
            'neonGridItems' => LandingAppearance::neonGridItems(),
            // home content data
            'groups' => $groups,
            'allowedKeys' => $allowedKeys,
            'overrides' => LandingAppearance::pageOverrides(),
            'featureCards' => $cards,
            'heroImageUrl' => LandingAppearance::heroImageUrl(),
            'heroImageAlt' => LandingAppearance::heroImageAlt(),
            'heroImageCaption' => LandingAppearance::heroImageCaption(),
            'icons' => config('landing_theme.feature_icons', []),
            'embedded' => true,
            'installSettings' => InstallGuide::settings(),
        ]);
    }

    private function resolveAppearanceTab(string $tab): string
    {
        return in_array($tab, ['site', 'theme', 'home', 'install'], true) ? $tab : 'site';
    }
}
