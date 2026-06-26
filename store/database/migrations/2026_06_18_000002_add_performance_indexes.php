<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index(['product_category_id', 'is_active', 'sort_order']);
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->index(['is_published', 'published_at']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index(['status', 'payment_status']);
            $table->index('customer_email');
        });

        Schema::table('contact_messages', function (Blueprint $table) {
            $table->index('is_read');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['product_category_id', 'is_active', 'sort_order']);
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropIndex(['is_published', 'published_at']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status', 'payment_status']);
            $table->dropIndex(['customer_email']);
        });

        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropIndex(['is_read']);
        });
    }
};
