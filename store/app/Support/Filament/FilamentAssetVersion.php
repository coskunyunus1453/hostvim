<?php

namespace App\Support\Filament;

use Composer\InstalledVersions;

final class FilamentAssetVersion
{
    public static function query(): string
    {
        if (class_exists(InstalledVersions::class) && InstalledVersions::isInstalled('filament/filament')) {
            return InstalledVersions::getPrettyVersion('filament/filament') ?? '1';
        }

        return '1';
    }
}
