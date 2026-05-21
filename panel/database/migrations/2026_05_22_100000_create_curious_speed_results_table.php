<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curious_speed_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('client_ip', 45);
            $table->unsignedSmallInteger('panel_ping_ms')->nullable();
            $table->decimal('panel_download_mbps', 10, 2)->nullable();
            $table->decimal('panel_upload_mbps', 10, 2)->nullable();
            $table->unsignedSmallInteger('server_ping_ms')->nullable();
            $table->decimal('server_download_mbps', 10, 2)->nullable();
            $table->decimal('server_upload_mbps', 10, 2)->nullable();
            $table->decimal('delta_ping_ms', 8, 1)->nullable();
            $table->decimal('delta_download_mbps', 10, 2)->nullable();
            $table->decimal('delta_upload_mbps', 10, 2)->nullable();
            $table->string('server_label', 120)->nullable();
            $table->boolean('server_from_cache')->default(false);
            $table->string('server_error', 255)->nullable();
            $table->timestamps();

            $table->index(['client_ip', 'created_at']);
            $table->index(['user_id', 'client_ip', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curious_speed_results');
    }
};
