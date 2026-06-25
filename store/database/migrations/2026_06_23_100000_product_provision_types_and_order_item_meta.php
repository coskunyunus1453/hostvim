<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('provision_type', 24)->default('hosting')->after('panel_package_id');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('item_type', 24)->default('product')->after('order_id');
            $table->string('domain_name')->nullable()->after('billing_cycle');
            $table->unsignedTinyInteger('domain_years')->nullable()->after('domain_name');
            $table->string('service_domain')->nullable()->after('domain_years');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('provision_type');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['item_type', 'domain_name', 'domain_years', 'service_domain']);
        });
    }
};
