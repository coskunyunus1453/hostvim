<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lisans aktivasyonları: bir opak lisans anahtarı bir panele kurulduğunda, hub
 * o panelin host'una bağlı imzalı (PLZ1) bir anahtar üretir ve burada kaydeder.
 * Aynı host tekrar aktive olursa yeni satır oluşmaz (limit tüketmez).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saas_license_activations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saas_license_id')->constrained('saas_licenses')->cascadeOnDelete();
            $table->string('host')->index();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['saas_license_id', 'host']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_license_activations');
    }
};
