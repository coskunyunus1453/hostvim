<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_names', function (Blueprint $table) {
            $table->id();
            $table->string('registrar_api', 40)->default('spaceship');
            $table->string('domain')->unique();
            $table->string('status', 32)->nullable();
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('auto_renew')->default(false);
            $table->string('privacy', 16)->nullable();
            $table->boolean('locked')->default(false);
            $table->string('ns_provider', 16)->nullable();
            $table->json('nameservers')->nullable();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_email')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index('registrar_api');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_names');
    }
};
