<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domain_tlds', function (Blueprint $table) {
            if (! Schema::hasColumn('domain_tlds', 'wholesale_currency')) {
                $table->string('wholesale_currency', 8)->default('USD')->after('wholesale_renew');
            }
            if (! Schema::hasColumn('domain_tlds', 'auto_price')) {
                $table->boolean('auto_price')->default(true)->after('markup_percent');
            }
        });

        // EUR/GBP kur ayarlarini tohumla (varsa dokunma)
        $now = now();
        $settings = [
            ['group' => 'domain', 'key' => 'domain_eur_try_rate', 'value' => '0', 'type' => 'number', 'label' => 'EUR → TRY kuru (0 = devre dışı)'],
            ['group' => 'domain', 'key' => 'domain_gbp_try_rate', 'value' => '0', 'type' => 'number', 'label' => 'GBP → TRY kuru (0 = devre dışı)'],
        ];

        foreach ($settings as $setting) {
            DB::table('site_settings')->insertOrIgnore(array_merge($setting, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        Schema::table('domain_tlds', function (Blueprint $table) {
            if (Schema::hasColumn('domain_tlds', 'wholesale_currency')) {
                $table->dropColumn('wholesale_currency');
            }
            if (Schema::hasColumn('domain_tlds', 'auto_price')) {
                $table->dropColumn('auto_price');
            }
        });

        DB::table('site_settings')->whereIn('key', ['domain_eur_try_rate', 'domain_gbp_try_rate'])->delete();
    }
};
