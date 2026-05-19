<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('landing_site_settings')) {
            return;
        }

        $defaultLocale = DB::table('landing_site_settings')
            ->where('key', 'landing.default_locale')
            ->value('value');

        if ($defaultLocale === 'tr' || $defaultLocale === null || $defaultLocale === '') {
            DB::table('landing_site_settings')->updateOrInsert(
                ['key' => 'landing.default_locale'],
                ['value' => 'en', 'updated_at' => now(), 'created_at' => now()]
            );
        }

        $enabledLocales = DB::table('landing_site_settings')
            ->where('key', 'landing.enabled_locales')
            ->value('value');

        $decoded = json_decode((string) $enabledLocales, true);
        $decoded = is_array($decoded) ? array_values(array_unique($decoded)) : [];

        $mustUpdateEnabled = $decoded === [] || $decoded === ['tr', 'en'] || ! in_array('en', $decoded, true);

        if ($mustUpdateEnabled) {
            DB::table('landing_site_settings')->updateOrInsert(
                ['key' => 'landing.enabled_locales'],
                ['value' => json_encode(['en', 'tr']), 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('landing_site_settings')) {
            return;
        }

        DB::table('landing_site_settings')->updateOrInsert(
            ['key' => 'landing.default_locale'],
            ['value' => 'tr', 'updated_at' => now(), 'created_at' => now()]
        );

        DB::table('landing_site_settings')->updateOrInsert(
            ['key' => 'landing.enabled_locales'],
            ['value' => json_encode(['tr', 'en']), 'updated_at' => now(), 'created_at' => now()]
        );
    }
};
