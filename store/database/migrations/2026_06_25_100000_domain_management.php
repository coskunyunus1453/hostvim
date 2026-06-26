<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_registrars', function (Blueprint $table) {
            $table->id();
            $table->string('api_name', 40)->unique();
            $table->string('display_name', 80);
            $table->text('credentials')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamp('last_synced_at')->nullable();
            $table->string('last_sync_status', 40)->nullable();
            $table->text('last_sync_message')->nullable();
            $table->timestamps();
        });

        Schema::create('domain_tlds', function (Blueprint $table) {
            $table->id();
            $table->string('tld', 32)->unique();
            $table->decimal('register_price', 12, 2)->default(0);
            $table->decimal('renew_price', 12, 2)->default(0);
            $table->decimal('transfer_price', 12, 2)->nullable();
            $table->decimal('wholesale_register', 12, 2)->nullable();
            $table->decimal('wholesale_renew', 12, 2)->nullable();
            $table->string('wholesale_registrar_api', 40)->nullable();
            $table->string('registrar_api_name', 40)->nullable();
            $table->decimal('markup_percent', 5, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamp('prices_synced_at')->nullable();
            $table->timestamps();
        });

        $now = now();
        $providers = config('domain_registrars.providers', []);
        $sort = 10;
        foreach ($providers as $key => $provider) {
            DB::table('domain_registrars')->insert([
                'api_name' => $provider['api_name'] ?? $key,
                'display_name' => $provider['name'] ?? $key,
                'is_enabled' => false,
                'sort_order' => $sort,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $sort += 10;
        }

        $defaults = [
            ['tld' => '.com', 'register_price' => 399, 'renew_price' => 399, 'sort_order' => 10],
            ['tld' => '.net', 'register_price' => 449, 'renew_price' => 449, 'sort_order' => 20],
            ['tld' => '.org', 'register_price' => 449, 'renew_price' => 449, 'sort_order' => 30],
            ['tld' => '.info', 'register_price' => 299, 'renew_price' => 299, 'sort_order' => 40],
            ['tld' => '.xyz', 'register_price' => 199, 'renew_price' => 199, 'sort_order' => 50],
            ['tld' => '.com.tr', 'register_price' => 149, 'renew_price' => 149, 'sort_order' => 60, 'registrar_api_name' => 'metunic'],
            ['tld' => '.net.tr', 'register_price' => 149, 'renew_price' => 149, 'sort_order' => 70, 'registrar_api_name' => 'metunic'],
            ['tld' => '.org.tr', 'register_price' => 149, 'renew_price' => 149, 'sort_order' => 80, 'registrar_api_name' => 'metunic'],
        ];

        foreach ($defaults as $row) {
            DB::table('domain_tlds')->insert(array_merge($row, [
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        $settings = [
            ['group' => 'domain', 'key' => 'domain_register_enabled', 'value' => '1', 'type' => 'boolean', 'label' => 'Domain satışı aktif'],
            ['group' => 'domain', 'key' => 'domain_usd_try_rate', 'value' => (string) config('domain_registrars.default_usd_try_rate', 35), 'type' => 'number', 'label' => 'USD → TRY kuru'],
            ['group' => 'domain', 'key' => 'domain_default_markup_percent', 'value' => (string) config('domain_registrars.default_markup_percent', 15), 'type' => 'number', 'label' => 'Varsayılan kar marjı (%)'],
            ['group' => 'domain', 'key' => 'domain_auto_import_tlds', 'value' => '0', 'type' => 'boolean', 'label' => 'API senkronunda yeni TLD otomatik ekle'],
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
        Schema::dropIfExists('domain_tlds');
        Schema::dropIfExists('domain_registrars');
        DB::table('site_settings')->where('group', 'domain')->delete();
    }
};
