<?php

$files = [
    'app/Models/MenuItem.php' => [
        "case 'page':\n                    // Sayfa route'u eklendiğinde buraya eklenecek\n                    return '#';",
        "case 'page':\n                    \$page = \\App\\Models\\Page::find(\$this->type_id);\n                    return \$page ? route('page.show', \$page->slug) : '#';",
    ],
];

foreach ($files as $file => [$search, $replace]) {
    $path = __DIR__.'/../../'.$file;
    if (! is_file($path)) {
        fwrite(STDERR, "Missing: $path\n");
        exit(1);
    }
    $content = file_get_contents($path);
    if (str_contains($content, "route('page.show'")) {
        echo "$file already patched\n";
        continue;
    }
    if (! str_contains($content, $search)) {
        fwrite(STDERR, "Pattern not found in $file\n");
        exit(1);
    }
    file_put_contents($path, str_replace($search, $replace, $content));
    echo "$file patched\n";
}

$web = __DIR__.'/../../routes/web.php';
$routeLine = "Route::get('/sayfa/{slug}', [App\\Http\\Controllers\\PageController::class, 'show'])->name('page.show');";
$webContent = file_get_contents($web);
if (! str_contains($webContent, 'page.show')) {
    $needle = "Route::get('/urun/{slug}', [App\\Http\\Controllers\\ProductController::class, 'show'])->name('product.show');";
    if (! str_contains($webContent, $needle)) {
        fwrite(STDERR, "web.php anchor not found\n");
        exit(1);
    }
    $webContent = str_replace(
        $needle,
        $needle."\n\n// Kurumsal / yasal sayfalar\n".$routeLine,
        $webContent
    );
    file_put_contents($web, $webContent);
    echo "routes/web.php patched\n";
} else {
    echo "routes/web.php already patched\n";
}

$home = __DIR__.'/../../app/Http/Controllers/HomeController.php';
$homeContent = file_get_contents($home);
if (! str_contains($homeContent, 'footer_legal')) {
    $homeContent = str_replace(
        "\$footerMenu = CacheHelper::getMenus('footer_links');",
        "\$footerMenu = CacheHelper::getMenus('footer_links');\n        \$footerLegalMenu = CacheHelper::getMenus('footer_legal');",
        $homeContent
    );
    $homeContent = str_replace(
        "'footer_links' => \$footerMenu,",
        "'footer_links' => \$footerMenu,\n                'footer_legal' => \$footerLegalMenu,",
        $homeContent
    );
    file_put_contents($home, $homeContent);
    echo "HomeController patched\n";
} else {
    echo "HomeController already patched\n";
}

$cache = __DIR__.'/../../app/Helpers/CacheHelper.php';
$cacheContent = file_get_contents($cache);
if (! str_contains($cacheContent, 'footer_legal')) {
    $cacheContent = str_replace(
        "Cache::forget('menus.footer_links');",
        "Cache::forget('menus.footer_links');\n            Cache::forget('menus.footer_legal');",
        $cacheContent
    );
    file_put_contents($cache, $cacheContent);
    echo "CacheHelper patched\n";
} else {
    echo "CacheHelper already patched\n";
}

$header = __DIR__.'/../../resources/js/Components/Frontend/Themes/Renkli/Header.vue';
$headerContent = file_get_contents($header);
$headerContent = str_replace(
    ['menu.url || \'#\'', 'child.url || \'#\'', 'subChild.url || \'#\''],
    ['menu.href || menu.url || \'#\'', 'child.href || child.url || \'#\'', 'subChild.href || subChild.url || \'#\''],
    $headerContent
);
file_put_contents($header, $headerContent);
echo "Renkli Header patched\n";
