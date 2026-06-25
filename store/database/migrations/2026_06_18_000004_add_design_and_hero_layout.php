<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hero_sections', function (Blueprint $table) {
            $table->string('layout_variant')->default('split')->after('page');
            $table->string('stat_1_value')->nullable()->after('image');
            $table->string('stat_1_label')->nullable()->after('stat_1_value');
            $table->string('stat_2_value')->nullable()->after('stat_1_label');
            $table->string('stat_2_label')->nullable()->after('stat_2_value');
            $table->string('stat_3_value')->nullable()->after('stat_2_label');
            $table->string('stat_3_label')->nullable()->after('stat_3_value');
        });
    }

    public function down(): void
    {
        Schema::table('hero_sections', function (Blueprint $table) {
            $table->dropColumn([
                'layout_variant',
                'stat_1_value', 'stat_1_label',
                'stat_2_value', 'stat_2_label',
                'stat_3_value', 'stat_3_label',
            ]);
        });
    }
};
