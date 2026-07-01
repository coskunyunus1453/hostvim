<?php

/**
 * Blog kapak görselleri — marka renkleriyle SVG (1200×630 OG boyutu).
 * Kullanım: php scripts/generate-blog-covers.php
 */

$outDir = __DIR__.'/../public/images/blog';
if (! is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}

$covers = [
    'vps-hosting-paneli-nasil-secilir' => ['VPS Panel Seçimi', 'server', '#f97316', '#0f172a'],
    'lets-encrypt-ssl-hosting-panelinde' => ['Let\'s Encrypt SSL', 'lock', '#22c55e', '#1e3a5f'],
    'cpanel-alternatifi-acik-kaynak-hosting-paneli' => ['cPanel Alternatifi', 'grid', '#8b5cf6', '#1e293b'],
    'wordpress-hosting-nasil-secilir-kurulum' => ['WordPress Hosting', 'wp', '#21759b', '#0f172a'],
    'sunucu-guvenligi-fail2ban-ufw-rehberi' => ['Sunucu Güvenliği', 'shield', '#ef4444', '#1e293b'],
    'git-deploy-ile-canli-site-guncelleme' => ['Git Deploy', 'git', '#f97316', '#134e4a'],
    'google-drive-sunucu-yedekleme-rehberi' => ['Google Drive Yedek', 'cloud', '#4285f4', '#1e293b'],
    'web-ajansi-coklu-site-yonetimi' => ['Ajans Yönetimi', 'layers', '#f59e0b', '#312e81'],
    'ubuntu-22-04-web-sunucu-kurulum-checklist' => ['Ubuntu 22.04', 'ubuntu', '#e95420', '#0f172a'],
    'php-surumu-degistirme-performans' => ['PHP Sürüm Yönetimi', 'php', '#777bb4', '#1e293b'],
];

function iconSvg(string $type): string
{
    return match ($type) {
        'server' => '<rect x="420" y="200" width="360" height="80" rx="8" fill="white" fill-opacity="0.15"/><rect x="440" y="220" width="12" height="12" rx="2" fill="#4ade80"/><rect x="460" y="220" width="12" height="12" rx="2" fill="#4ade80"/><rect x="420" y="300" width="360" height="80" rx="8" fill="white" fill-opacity="0.12"/><rect x="440" y="320" width="12" height="12" rx="2" fill="#fbbf24"/><rect x="420" y="400" width="360" height="80" rx="8" fill="white" fill-opacity="0.1"/>',
        'lock' => '<rect x="470" y="280" width="260" height="200" rx="16" fill="white" fill-opacity="0.2"/><path d="M520 280v-40a80 80 0 0 1 160 0v40" stroke="white" stroke-width="16" fill="none" stroke-linecap="round"/><circle cx="600" cy="380" r="24" fill="white" fill-opacity="0.9"/>',
        'grid' => '<rect x="400" y="200" width="120" height="120" rx="12" fill="white" fill-opacity="0.2"/><rect x="540" y="200" width="120" height="120" rx="12" fill="white" fill-opacity="0.15"/><rect x="680" y="200" width="120" height="120" rx="12" fill="white" fill-opacity="0.1"/><rect x="400" y="340" width="120" height="120" rx="12" fill="white" fill-opacity="0.15"/><rect x="540" y="340" width="120" height="120" rx="12" fill="white" fill-opacity="0.2"/><rect x="680" y="340" width="120" height="120" rx="12" fill="white" fill-opacity="0.12"/>',
        'wp' => '<circle cx="600" cy="350" r="120" fill="white" fill-opacity="0.15"/><text x="600" y="375" text-anchor="middle" font-family="Georgia,serif" font-size="96" font-weight="bold" fill="white" fill-opacity="0.9">W</text>',
        'shield' => '<path d="M600 180 L780 260 V400 C780 480 600 520 600 520 C600 520 420 480 420 400 V260 Z" fill="white" fill-opacity="0.18"/><path d="M560 360 L590 390 L660 310" stroke="white" stroke-width="14" fill="none" stroke-linecap="round" stroke-linejoin="round"/>',
        'git' => '<circle cx="520" cy="320" r="40" fill="white" fill-opacity="0.2"/><circle cx="680" cy="260" r="40" fill="white" fill-opacity="0.2"/><circle cx="680" cy="420" r="40" fill="white" fill-opacity="0.2"/><line x1="552" y1="300" x2="648" y2="272" stroke="white" stroke-width="8" opacity="0.5"/><line x1="552" y1="340" x2="648" y2="408" stroke="white" stroke-width="8" opacity="0.5"/>',
        'cloud' => '<ellipse cx="520" cy="360" rx="100" ry="60" fill="white" fill-opacity="0.15"/><ellipse cx="620" cy="340" rx="120" ry="70" fill="white" fill-opacity="0.2"/><ellipse cx="720" cy="365" rx="90" ry="55" fill="white" fill-opacity="0.15"/><path d="M600 400 L600 460 M560 430 L600 460 L640 430" stroke="white" stroke-width="10" fill="none" stroke-linecap="round" stroke-linejoin="round" opacity="0.7"/>',
        'layers' => '<rect x="420" y="380" width="360" height="50" rx="8" fill="white" fill-opacity="0.12"/><rect x="440" y="320" width="320" height="50" rx="8" fill="white" fill-opacity="0.18"/><rect x="460" y="260" width="280" height="50" rx="8" fill="white" fill-opacity="0.24"/>',
        'ubuntu' => '<circle cx="600" cy="350" r="110" fill="white" fill-opacity="0.12"/><circle cx="600" cy="350" r="70" fill="none" stroke="white" stroke-width="12" opacity="0.5"/><circle cx="600" cy="280" r="18" fill="white" fill-opacity="0.7"/><circle cx="660" cy="390" r="18" fill="white" fill-opacity="0.7"/><circle cx="540" cy="390" r="18" fill="white" fill-opacity="0.7"/>',
        'php' => '<ellipse cx="600" cy="350" rx="160" ry="90" fill="white" fill-opacity="0.15"/><text x="600" y="375" text-anchor="middle" font-family="Arial,sans-serif" font-size="72" font-weight="bold" fill="white" fill-opacity="0.9">PHP</text>',
        default => '<circle cx="600" cy="350" r="100" fill="white" fill-opacity="0.15"/>',
    };
}

foreach ($covers as $slug => [$label, $icon, $accent, $dark]) {
    $iconMarkup = iconSvg($icon);
    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630" viewBox="0 0 1200 630" role="img" aria-label="{$label}">
  <defs>
    <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="{$accent}"/>
      <stop offset="100%" stop-color="{$dark}"/>
    </linearGradient>
    <pattern id="dots" width="24" height="24" patternUnits="userSpaceOnUse">
      <circle cx="2" cy="2" r="1.5" fill="white" opacity="0.06"/>
    </pattern>
  </defs>
  <rect width="1200" height="630" fill="url(#bg)"/>
  <rect width="1200" height="630" fill="url(#dots)"/>
  {$iconMarkup}
  <text x="80" y="560" font-family="system-ui,-apple-system,sans-serif" font-size="36" font-weight="600" fill="white" fill-opacity="0.92">{$label}</text>
  <text x="80" y="598" font-family="system-ui,-apple-system,sans-serif" font-size="20" fill="white" fill-opacity="0.55">Panelze Blog</text>
</svg>
SVG;

    $path = $outDir.'/'.$slug.'.svg';
    file_put_contents($path, $svg);
    echo "Wrote {$path}\n";
}

echo "Done: ".count($covers)." covers.\n";
