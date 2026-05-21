<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saas_product_modules', function (Blueprint $table) {
            $table->json('ui_paths')->nullable()->after('description');
            $table->json('api_route_prefixes')->nullable()->after('ui_paths');
        });
    }

    public function down(): void
    {
        Schema::table('saas_product_modules', function (Blueprint $table) {
            $table->dropColumn(['ui_paths', 'api_route_prefixes']);
        });
    }
};
