<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $providers = config('domain_registrars.providers', []);
        $sort = (int) (DB::table('domain_registrars')->max('sort_order') ?: 0);

        foreach ($providers as $key => $provider) {
            $apiName = (string) ($provider['api_name'] ?? $key);
            $exists = DB::table('domain_registrars')->where('api_name', $apiName)->exists();
            if ($exists) {
                // Display name'i katalogla senkron tut (credentials'a dokunma).
                DB::table('domain_registrars')
                    ->where('api_name', $apiName)
                    ->update([
                        'display_name' => (string) ($provider['name'] ?? $apiName),
                        'updated_at' => $now,
                    ]);

                continue;
            }

            $sort += 10;
            DB::table('domain_registrars')->insert([
                'api_name' => $apiName,
                'display_name' => (string) ($provider['name'] ?? $apiName),
                'is_enabled' => false,
                'sort_order' => $sort,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('domain_registrars')->where('api_name', 'domainnameapi')->delete();
    }
};
