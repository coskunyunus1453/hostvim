<?php

namespace App\Support;

/**
 * 11 ön yüz tema paketi — ana tema (hostvim-main) mevcut varsayılanlarla birebir.
 *
 * @phpstan-type Preset array{
 *   id: string,
 *   label: string,
 *   description: string,
 *   shell: string,
 *   font_family: string,
 *   font_url: string,
 *   settings: array<string, string>
 * }
 */
final class ThemePresets
{
    /** @return array<string, Preset> */
    public static function all(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $presets = [
            self::hostvimMain(),
            self::corporateOrange(),
            self::corporateBlue(),
            self::corporateGreen(),
            self::corporateRed(),
            self::gamerNeon(),
            self::gamerCyber(),
            self::dynamicPulse(),
            self::dynamicEdge(),
            self::friendlySoft(),
            self::friendlyCozy(),
        ];

        $cache = [];
        foreach ($presets as $preset) {
            $cache[$preset['id']] = $preset;
        }

        return $cache;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $out = [];
        foreach (self::all() as $preset) {
            $out[$preset['id']] = $preset['label'];
        }
        $out['custom'] = 'Özel (manuel renkler)';

        return $out;
    }

    public static function has(string $id): bool
    {
        return $id === 'custom' || isset(self::all()[$id]);
    }

    /** @return Preset|null */
    public static function get(string $id): ?array
    {
        return self::all()[$id] ?? null;
    }

    /** @return array<string, string> */
    public static function settingsFor(string $id): array
    {
        $preset = self::get($id);

        return $preset['settings'] ?? [];
    }

    /** @param array<string, string> $light @param array<string, string> $dark */
    private static function pack(
        string $id,
        string $label,
        string $description,
        string $shell,
        string $fontFamily,
        string $fontUrl,
        array $light,
        array $dark,
        string $headerStyle = 'glass',
        string $footerStyle = 'default',
        bool $headerSticky = true,
        bool $headerBlur = true,
        bool $headerBorder = true,
        bool $footerStats = true,
        string $headerBgLight = '',
        string $headerBgDark = '',
        string $footerBgLight = '',
        string $footerBgDark = '',
    ): array {
        $headerBgLight = $headerBgLight ?: $light['bg'];
        $headerBgDark = $headerBgDark ?: $dark['bg'];
        $footerBgLight = $footerBgLight ?: $light['surface'];
        $footerBgDark = $footerBgDark ?: $dark['surface'];

        return [
            'id' => $id,
            'label' => $label,
            'description' => $description,
            'shell' => $shell,
            'font_family' => $fontFamily,
            'font_url' => $fontUrl,
            'settings' => [
                'design_theme_preset' => $id,
                'design_font_family' => $fontFamily,
                'design_header_style' => $headerStyle,
                'design_header_sticky' => $headerSticky ? '1' : '0',
                'design_header_blur' => $headerBlur ? '1' : '0',
                'design_header_border' => $headerBorder ? '1' : '0',
                'design_footer_style' => $footerStyle,
                'design_footer_show_stats' => $footerStats ? '1' : '0',
                'design_header_bg_light' => $headerBgLight,
                'design_header_bg_dark' => $headerBgDark,
                'design_footer_bg_light' => $footerBgLight,
                'design_footer_bg_dark' => $footerBgDark,
                'design_light_primary' => $light['primary'],
                'design_light_primary_hover' => $light['primary_hover'],
                'design_light_secondary' => $light['secondary'],
                'design_light_bg' => $light['bg'],
                'design_light_surface' => $light['surface'],
                'design_light_surface_elevated' => $light['surface_elevated'],
                'design_light_text' => $light['text'],
                'design_light_text_muted' => $light['text_muted'],
                'design_light_link' => $light['link'],
                'design_light_border' => $light['border'],
                'design_dark_primary' => $dark['primary'],
                'design_dark_primary_hover' => $dark['primary_hover'],
                'design_dark_secondary' => $dark['secondary'],
                'design_dark_bg' => $dark['bg'],
                'design_dark_surface' => $dark['surface'],
                'design_dark_surface_elevated' => $dark['surface_elevated'],
                'design_dark_text' => $dark['text'],
                'design_dark_text_muted' => $dark['text_muted'],
                'design_dark_link' => $dark['link'],
                'design_dark_border' => $dark['border'],
            ],
        ];
    }

    /** @return Preset */
    private static function hostvimMain(): array
    {
        return self::pack(
            id: 'hostvim-main',
            label: 'Ana Tema (varsayılan)',
            description: 'Mevcut HostVim turuncu/yeşil cam header — bozulmaz.',
            shell: 'classic',
            fontFamily: 'Plus Jakarta Sans',
            fontUrl: 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap',
            light: [
                'primary' => '#C2410C', 'primary_hover' => '#9A3412', 'secondary' => '#166534',
                'bg' => '#FFFFFF', 'surface' => '#FAFAF9', 'surface_elevated' => '#FFFFFF',
                'text' => '#1C1917', 'text_muted' => '#57534E', 'link' => '#C2410C', 'border' => '#E7E5E4',
            ],
            dark: [
                'primary' => '#EA580C', 'primary_hover' => '#FB923C', 'secondary' => '#22C55E',
                'bg' => '#0C0A09', 'surface' => '#1C1917', 'surface_elevated' => '#292524',
                'text' => '#FAFAF9', 'text_muted' => '#A8A29E', 'link' => '#FB923C', 'border' => '#292524',
            ],
            headerStyle: 'glass',
            footerStyle: 'default',
            headerBgLight: '#FFFFFF',
            headerBgDark: '#0C0A09',
            footerBgLight: '#FAFAF9',
            footerBgDark: '#1C1917',
        );
    }

    /** @return Preset */
    private static function corporateOrange(): array
    {
        return self::pack(
            id: 'corporate-orange',
            label: 'Kurumsal Turuncu',
            description: 'Üst vurgu çubuğu, keskin köşeler, kurumsal hosting.',
            shell: 'corporate-bar',
            fontFamily: 'IBM Plex Sans',
            fontUrl: 'https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;600;700&display=swap',
            light: [
                'primary' => '#EA580C', 'primary_hover' => '#C2410C', 'secondary' => '#0F766E',
                'bg' => '#FFFBF7', 'surface' => '#FFF7ED', 'surface_elevated' => '#FFFFFF',
                'text' => '#1C1917', 'text_muted' => '#78716C', 'link' => '#EA580C', 'border' => '#FED7AA',
            ],
            dark: [
                'primary' => '#FB923C', 'primary_hover' => '#FDBA74', 'secondary' => '#2DD4BF',
                'bg' => '#1C1410', 'surface' => '#292018', 'surface_elevated' => '#352818',
                'text' => '#FAFAF9', 'text_muted' => '#A8A29E', 'link' => '#FB923C', 'border' => '#44403C',
            ],
            headerStyle: 'default',
            footerStyle: 'default',
            headerBorder: true,
        );
    }

    /** @return Preset */
    private static function corporateBlue(): array
    {
        return self::pack(
            id: 'corporate-blue',
            label: 'Kurumsal Mavi',
            description: 'Güven veren mavi, gradient footer, solid header.',
            shell: 'corporate-blue',
            fontFamily: 'Inter',
            fontUrl: 'https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap',
            light: [
                'primary' => '#2563EB', 'primary_hover' => '#1D4ED8', 'secondary' => '#0891B2',
                'bg' => '#F8FAFC', 'surface' => '#F1F5F9', 'surface_elevated' => '#FFFFFF',
                'text' => '#0F172A', 'text_muted' => '#64748B', 'link' => '#2563EB', 'border' => '#E2E8F0',
            ],
            dark: [
                'primary' => '#3B82F6', 'primary_hover' => '#60A5FA', 'secondary' => '#22D3EE',
                'bg' => '#0B1120', 'surface' => '#111827', 'surface_elevated' => '#1E293B',
                'text' => '#F8FAFC', 'text_muted' => '#94A3B8', 'link' => '#60A5FA', 'border' => '#334155',
            ],
            headerStyle: 'solid',
            footerStyle: 'gradient',
        );
    }

    /** @return Preset */
    private static function corporateGreen(): array
    {
        return self::pack(
            id: 'corporate-green',
            label: 'Kurumsal Yeşil',
            description: 'Sürdürülebilir yeşil, alt çizgili header, sade footer.',
            shell: 'corporate-green',
            fontFamily: 'Source Sans 3',
            fontUrl: 'https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&display=swap',
            light: [
                'primary' => '#059669', 'primary_hover' => '#047857', 'secondary' => '#0D9488',
                'bg' => '#F7FEF9', 'surface' => '#ECFDF5', 'surface_elevated' => '#FFFFFF',
                'text' => '#14532D', 'text_muted' => '#4B5563', 'link' => '#059669', 'border' => '#D1FAE5',
            ],
            dark: [
                'primary' => '#34D399', 'primary_hover' => '#6EE7B7', 'secondary' => '#2DD4BF',
                'bg' => '#0A1612', 'surface' => '#0F1F18', 'surface_elevated' => '#152820',
                'text' => '#ECFDF5', 'text_muted' => '#9CA3AF', 'link' => '#34D399', 'border' => '#1F2937',
            ],
            headerStyle: 'glass',
            footerStyle: 'default',
        );
    }

    /** @return Preset */
    private static function corporateRed(): array
    {
        return self::pack(
            id: 'corporate-red',
            label: 'Kurumsal Kırmızı',
            description: 'Güçlü kırmızı vurgu, minimal footer, kalın header.',
            shell: 'corporate-red',
            fontFamily: 'Roboto',
            fontUrl: 'https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap',
            light: [
                'primary' => '#DC2626', 'primary_hover' => '#B91C1C', 'secondary' => '#991B1B',
                'bg' => '#FFFBFB', 'surface' => '#FEF2F2', 'surface_elevated' => '#FFFFFF',
                'text' => '#1F2937', 'text_muted' => '#6B7280', 'link' => '#DC2626', 'border' => '#FECACA',
            ],
            dark: [
                'primary' => '#F87171', 'primary_hover' => '#FCA5A5', 'secondary' => '#EF4444',
                'bg' => '#180A0A', 'surface' => '#1F1010', 'surface_elevated' => '#2A1515',
                'text' => '#F9FAFB', 'text_muted' => '#9CA3AF', 'link' => '#F87171', 'border' => '#374151',
            ],
            headerStyle: 'solid',
            footerStyle: 'minimal',
            footerStats: false,
        );
    }

    /** @return Preset */
    private static function gamerNeon(): array
    {
        return self::pack(
            id: 'gamer-neon',
            label: 'Gamer Tema 1',
            description: 'Neon mor, koyu arka plan, keskin HUD header.',
            shell: 'gamer-neon',
            fontFamily: 'Rajdhani',
            fontUrl: 'https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;600;700&display=swap',
            light: [
                'primary' => '#A855F7', 'primary_hover' => '#9333EA', 'secondary' => '#06B6D4',
                'bg' => '#0F0A14', 'surface' => '#1A1225', 'surface_elevated' => '#221830',
                'text' => '#F5F3FF', 'text_muted' => '#C4B5FD', 'link' => '#C084FC', 'border' => '#4C1D95',
            ],
            dark: [
                'primary' => '#C084FC', 'primary_hover' => '#E879F9', 'secondary' => '#22D3EE',
                'bg' => '#050308', 'surface' => '#0F0A14', 'surface_elevated' => '#1A1225',
                'text' => '#FAF5FF', 'text_muted' => '#A78BFA', 'link' => '#E879F9', 'border' => '#581C87',
            ],
            headerStyle: 'default',
            footerStyle: 'gradient',
            headerBlur: false,
            headerBgLight: '#1A1225',
            headerBgDark: '#0F0A14',
        );
    }

    /** @return Preset */
    private static function gamerCyber(): array
    {
        return self::pack(
            id: 'gamer-cyber',
            label: 'Gamer Tema 2',
            description: 'Siber cyan, açılı panel header, minimal footer.',
            shell: 'gamer-cyber',
            fontFamily: 'Orbitron',
            fontUrl: 'https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;700&display=swap',
            light: [
                'primary' => '#06B6D4', 'primary_hover' => '#0891B2', 'secondary' => '#8B5CF6',
                'bg' => '#030712', 'surface' => '#0B1220', 'surface_elevated' => '#111827',
                'text' => '#E0F2FE', 'text_muted' => '#67E8F9', 'link' => '#22D3EE', 'border' => '#164E63',
            ],
            dark: [
                'primary' => '#22D3EE', 'primary_hover' => '#67E8F9', 'secondary' => '#A78BFA',
                'bg' => '#000000', 'surface' => '#030712', 'surface_elevated' => '#0B1220',
                'text' => '#F0FDFA', 'text_muted' => '#5EEAD4', 'link' => '#2DD4BF', 'border' => '#115E59',
            ],
            headerStyle: 'solid',
            footerStyle: 'minimal',
            headerBlur: false,
        );
    }

    /** @return Preset */
    private static function dynamicPulse(): array
    {
        return self::pack(
            id: 'dynamic-pulse',
            label: 'Dinamik Tema 1',
            description: 'Canlı turuncu-mor gradyan, dalga header.',
            shell: 'dynamic-pulse',
            fontFamily: 'Space Grotesk',
            fontUrl: 'https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;600;700&display=swap',
            light: [
                'primary' => '#F97316', 'primary_hover' => '#EA580C', 'secondary' => '#8B5CF6',
                'bg' => '#FFFBF5', 'surface' => '#FFF7ED', 'surface_elevated' => '#FFFFFF',
                'text' => '#1E1B4B', 'text_muted' => '#6B7280', 'link' => '#F97316', 'border' => '#FED7AA',
            ],
            dark: [
                'primary' => '#FB923C', 'primary_hover' => '#FDBA74', 'secondary' => '#A78BFA',
                'bg' => '#0C0A1A', 'surface' => '#151228', 'surface_elevated' => '#1E1835',
                'text' => '#FAF5FF', 'text_muted' => '#A5B4FC', 'link' => '#FDBA74', 'border' => '#312E81',
            ],
            headerStyle: 'glass',
            footerStyle: 'gradient',
        );
    }

    /** @return Preset */
    private static function dynamicEdge(): array
    {
        return self::pack(
            id: 'dynamic-edge',
            label: 'Dinamik Tema 2',
            description: 'Mor-indigo split header, enerjik kartlar.',
            shell: 'dynamic-edge',
            fontFamily: 'Manrope',
            fontUrl: 'https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700&display=swap',
            light: [
                'primary' => '#7C3AED', 'primary_hover' => '#6D28D9', 'secondary' => '#EC4899',
                'bg' => '#FAFAFF', 'surface' => '#F5F3FF', 'surface_elevated' => '#FFFFFF',
                'text' => '#1E1B4B', 'text_muted' => '#6366F1', 'link' => '#7C3AED', 'border' => '#E9D5FF',
            ],
            dark: [
                'primary' => '#A78BFA', 'primary_hover' => '#C4B5FD', 'secondary' => '#F472B6',
                'bg' => '#0F0A1F', 'surface' => '#1A1030', 'surface_elevated' => '#251845',
                'text' => '#F5F3FF', 'text_muted' => '#C4B5FD', 'link' => '#C084FC', 'border' => '#4C1D95',
            ],
            headerStyle: 'solid',
            footerStyle: 'default',
        );
    }

    /** @return Preset */
    private static function friendlySoft(): array
    {
        return self::pack(
            id: 'friendly-soft',
            label: 'Samimi Tema 1',
            description: 'Yumuşak amber, yuvarlak köşeler, sıcak tonlar.',
            shell: 'friendly-soft',
            fontFamily: 'Nunito',
            fontUrl: 'https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap',
            light: [
                'primary' => '#F59E0B', 'primary_hover' => '#D97706', 'secondary' => '#10B981',
                'bg' => '#FFFBEB', 'surface' => '#FEF3C7', 'surface_elevated' => '#FFFFFF',
                'text' => '#422006', 'text_muted' => '#92400E', 'link' => '#D97706', 'border' => '#FDE68A',
            ],
            dark: [
                'primary' => '#FBBF24', 'primary_hover' => '#FCD34D', 'secondary' => '#34D399',
                'bg' => '#1A1408', 'surface' => '#292010', 'surface_elevated' => '#352818',
                'text' => '#FEF3C7', 'text_muted' => '#D6D3D1', 'link' => '#FCD34D', 'border' => '#44403C',
            ],
            headerStyle: 'glass',
            footerStyle: 'minimal',
        );
    }

    /** @return Preset */
    private static function friendlyCozy(): array
    {
        return self::pack(
            id: 'friendly-cozy',
            label: 'Samimi Tema 2',
            description: 'Pembe-krem palet, davetkar kart footer.',
            shell: 'friendly-cozy',
            fontFamily: 'Quicksand',
            fontUrl: 'https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600;700&display=swap',
            light: [
                'primary' => '#EC4899', 'primary_hover' => '#DB2777', 'secondary' => '#F472B6',
                'bg' => '#FFF5F7', 'surface' => '#FCE7F3', 'surface_elevated' => '#FFFFFF',
                'text' => '#500724', 'text_muted' => '#9D174D', 'link' => '#DB2777', 'border' => '#FBCFE8',
            ],
            dark: [
                'primary' => '#F472B6', 'primary_hover' => '#F9A8D4', 'secondary' => '#FB7185',
                'bg' => '#1A0812', 'surface' => '#2A1020', 'surface_elevated' => '#351828',
                'text' => '#FDF2F8', 'text_muted' => '#F9A8D4', 'link' => '#F9A8D4', 'border' => '#831843',
            ],
            headerStyle: 'default',
            footerStyle: 'gradient',
        );
    }
}
