<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cloud_providers', function (Blueprint $table) {
            $table->id();
            $table->string('api_name', 40)->unique();
            $table->string('display_name', 80);
            $table->text('credentials')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamp('last_tested_at')->nullable();
            $table->string('last_test_status', 40)->nullable();
            $table->text('last_test_message')->nullable();
            $table->timestamps();
        });

        Schema::create('cloud_servers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider_api', 40);
            $table->string('external_id', 64)->nullable();
            $table->string('hostname', 120);
            $table->string('region', 40)->nullable();
            $table->string('plan', 80)->nullable();
            $table->string('image', 80)->nullable();
            $table->string('ipv4', 45)->nullable();
            $table->string('ipv6', 64)->nullable();
            $table->text('root_password')->nullable();
            $table->string('status', 24)->default('pending');
            $table->text('provision_error')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('provisioned_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index(['provider_api', 'external_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('cloud_provider_api', 40)->nullable()->after('panel_package_id');
            $table->string('cloud_region', 40)->nullable()->after('cloud_provider_api');
            $table->string('cloud_plan', 80)->nullable()->after('cloud_region');
            $table->string('cloud_image', 80)->nullable()->after('cloud_plan');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('cloud_provision_status', 24)->default('pending')->after('panel_provisioned_at');
            $table->text('cloud_provision_error')->nullable()->after('cloud_provision_status');
            $table->timestamp('cloud_provisioned_at')->nullable()->after('cloud_provision_error');
        });

        $now = now();
        $sort = 10;
        foreach (config('cloud_providers.providers', []) as $key => $provider) {
            DB::table('cloud_providers')->insert([
                'api_name' => $provider['api_name'] ?? $key,
                'display_name' => $provider['name'] ?? $key,
                'is_enabled' => false,
                'sort_order' => $sort,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $sort += 10;
        }

        $settings = [
            ['group' => 'cloud', 'key' => 'cloud_provision_enabled', 'value' => '1', 'type' => 'boolean', 'label' => 'Bulut VPS otomatik kurulum aktif'],
            ['group' => 'cloud', 'key' => 'cloud_eur_try_rate', 'value' => '38', 'type' => 'number', 'label' => 'EUR → TRY kuru (Hetzner)'],
            ['group' => 'cloud', 'key' => 'cloud_usd_try_rate', 'value' => '35', 'type' => 'number', 'label' => 'USD → TRY kuru'],
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
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['cloud_provision_status', 'cloud_provision_error', 'cloud_provisioned_at']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['cloud_provider_api', 'cloud_region', 'cloud_plan', 'cloud_image']);
        });

        Schema::dropIfExists('cloud_servers');
        Schema::dropIfExists('cloud_providers');
        DB::table('site_settings')->where('group', 'cloud')->delete();
    }
};
