<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['pages', 'blog_posts', 'products', 'product_categories'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('og_image')->nullable()->after('meta_description');
                $table->string('meta_keywords')->nullable()->after('og_image');
                $table->boolean('no_index')->default(false)->after('meta_keywords');
            });
        }
    }

    public function down(): void
    {
        foreach (['pages', 'blog_posts', 'products', 'product_categories'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn(['og_image', 'meta_keywords', 'no_index']);
            });
        }
    }
};
