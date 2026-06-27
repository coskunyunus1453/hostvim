<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saas_license_products', function (Blueprint $table) {
            // null = tek seferlik (ömür boyu) ; 'month' | 'year' = yinelenen abonelik
            $table->string('billing_interval', 8)->nullable()->after('price_eur_minor');
        });

        Schema::table('saas_licenses', function (Blueprint $table) {
            // Ödeme sağlayıcısındaki abonelik kimliği (ör. Stripe sub_...) — yenilemeleri eşleştirmek için.
            $table->string('subscription_provider_id')->nullable()->after('billing_reference');
            $table->index('subscription_provider_id');
        });
    }

    public function down(): void
    {
        Schema::table('saas_licenses', function (Blueprint $table) {
            $table->dropIndex(['subscription_provider_id']);
            $table->dropColumn('subscription_provider_id');
        });

        Schema::table('saas_license_products', function (Blueprint $table) {
            $table->dropColumn('billing_interval');
        });
    }
};
