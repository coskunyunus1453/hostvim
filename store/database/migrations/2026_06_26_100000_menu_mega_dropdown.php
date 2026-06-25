<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('menu_id')->constrained('menu_items')->nullOnDelete();
            $table->string('dropdown_style', 20)->nullable()->after('target');
            $table->string('icon', 40)->nullable()->after('dropdown_style');
            $table->string('description', 500)->nullable()->after('icon');
            $table->string('badge', 40)->nullable()->after('description');
            $table->string('panel_title', 120)->nullable()->after('badge');
            $table->text('panel_text')->nullable()->after('panel_title');
            $table->string('panel_cta_label', 80)->nullable()->after('panel_text');
            $table->string('panel_cta_url', 500)->nullable()->after('panel_cta_label');
        });

        $now = now();
        $settings = [
            ['group' => 'nav', 'key' => 'nav_services_mega_title', 'value' => 'Doğru paketi seçin', 'type' => 'text', 'label' => 'Hizmetler mega menü başlık'],
            ['group' => 'nav', 'key' => 'nav_services_mega_text', 'value' => 'Web hosting, VPS ve domain hizmetlerini tek panelden yönetin. 7/24 destek ve NVMe altyapı.', 'type' => 'textarea', 'label' => 'Hizmetler mega menü metin'],
            ['group' => 'nav', 'key' => 'nav_services_mega_cta_label', 'value' => 'Tüm paketleri gör', 'type' => 'text', 'label' => 'Hizmetler mega CTA'],
            ['group' => 'nav', 'key' => 'nav_services_mega_cta_url', 'value' => '/urunler', 'type' => 'text', 'label' => 'Hizmetler mega CTA URL'],
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
        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn([
                'parent_id', 'dropdown_style', 'icon', 'description', 'badge',
                'panel_title', 'panel_text', 'panel_cta_label', 'panel_cta_url',
            ]);
        });
        DB::table('site_settings')->where('group', 'nav')->delete();
    }
};
