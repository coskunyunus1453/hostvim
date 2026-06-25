<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('panel_package_id')->nullable()->after('product_category_id');
            $table->index('panel_package_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('panel_order_id')->nullable()->after('order_number');
            $table->string('panel_order_number', 64)->nullable()->after('panel_order_id');
            $table->string('panel_provision_status', 24)->default('pending')->after('payment_data');
            $table->text('panel_provision_error')->nullable()->after('panel_provision_status');
            $table->timestamp('panel_provisioned_at')->nullable()->after('panel_provision_error');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['panel_package_id']);
            $table->dropColumn('panel_package_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'panel_order_id',
                'panel_order_number',
                'panel_provision_status',
                'panel_provision_error',
                'panel_provisioned_at',
            ]);
        });
    }
};
