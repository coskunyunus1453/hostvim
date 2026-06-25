<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Disaridan senkronlanan sunucular bir siparise bagli olmayabilir.
        Schema::table('cloud_servers', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
        });
        Schema::table('cloud_servers', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable()->change();
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
        });

        $now = now();
        $settings = [
            ['group' => 'cloud', 'key' => 'cloud_auto_install_panel', 'value' => '0', 'type' => 'boolean', 'label' => 'VPS\'e otomatik Panelze paneli kur'],
            ['group' => 'cloud', 'key' => 'cloud_panelze_install_url', 'value' => '', 'type' => 'text', 'label' => 'Panelze kurulum betiği URL (remote-install.sh)'],
            ['group' => 'cloud', 'key' => 'cloud_panelze_panel_url', 'value' => '', 'type' => 'text', 'label' => 'Panelze panel adresi (mailde gösterilir)'],
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
        Schema::table('cloud_servers', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
        });
        Schema::table('cloud_servers', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable(false)->change();
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
        });

        DB::table('site_settings')->whereIn('key', [
            'cloud_auto_install_panel',
            'cloud_panelze_install_url',
            'cloud_panelze_panel_url',
        ])->delete();
    }
};
