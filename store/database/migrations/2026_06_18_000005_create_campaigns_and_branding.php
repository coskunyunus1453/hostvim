<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('badge_text')->nullable();
            $table->string('code')->nullable()->unique();
            $table->string('discount_type')->default('percent');
            $table->decimal('discount_value', 10, 2)->default(0);
            $table->decimal('min_order', 12, 2)->nullable();
            $table->string('applies_to')->default('all');
            $table->json('target_ids')->nullable();
            $table->json('billing_cycles')->nullable();
            $table->json('display_modes')->nullable();
            $table->boolean('requires_code')->default(false);
            $table->boolean('show_countdown')->default(true);
            $table->string('bar_color')->nullable();
            $table->string('cta_text')->nullable();
            $table->string('cta_url')->nullable();
            $table->string('popup_image')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('discount_amount', 12, 2)->default(0)->after('subtotal');
            $table->string('coupon_code')->nullable()->after('discount_amount');
            $table->foreignId('campaign_id')->nullable()->after('coupon_code')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('campaign_id');
            $table->dropColumn(['discount_amount', 'coupon_code']);
        });

        Schema::dropIfExists('campaigns');
    }
};
