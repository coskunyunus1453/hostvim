<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('price_quarterly', 12, 2)->nullable()->after('price_monthly');
            $table->decimal('price_semiannual', 12, 2)->nullable()->after('price_quarterly');
            $table->decimal('price_biennial', 12, 2)->nullable()->after('price_yearly');
            $table->decimal('price_triennial', 12, 2)->nullable()->after('price_biennial');
            $table->decimal('cost_quarterly', 12, 2)->nullable()->after('cost_monthly');
            $table->decimal('cost_semiannual', 12, 2)->nullable()->after('cost_quarterly');
            $table->decimal('cost_biennial', 12, 2)->nullable()->after('cost_yearly');
            $table->decimal('cost_triennial', 12, 2)->nullable()->after('cost_biennial');
        });

        Schema::create('product_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price_monthly', 12, 2)->nullable();
            $table->decimal('price_yearly', 12, 2)->nullable();
            $table->decimal('price_onetime', 12, 2)->nullable();
            $table->string('billing_mode', 24)->default('match_parent');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('domain_mode', 24)->nullable()->after('service_domain');
            $table->json('config_meta')->nullable()->after('domain_mode');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['domain_mode', 'config_meta']);
        });

        Schema::dropIfExists('product_addons');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'price_quarterly', 'price_semiannual', 'price_biennial', 'price_triennial',
                'cost_quarterly', 'cost_semiannual', 'cost_biennial', 'cost_triennial',
            ]);
        });
    }
};
