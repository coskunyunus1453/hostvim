<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cloudflare_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('api_token');
            $table->string('account_id', 64)->nullable();
            $table->string('account_email', 255)->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });

        Schema::create('domain_cloudflare_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cloudflare_connection_id')->constrained('cloudflare_connections')->cascadeOnDelete();
            $table->string('zone_id', 64);
            $table->string('zone_name', 255);
            $table->string('ssl_mode', 32)->default('full');
            $table->string('status', 32)->default('active');
            $table->timestamp('linked_at')->nullable();
            $table->timestamps();

            $table->unique('domain_id');
            $table->index(['cloudflare_connection_id', 'zone_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_cloudflare_zones');
        Schema::dropIfExists('cloudflare_connections');
    }
};
