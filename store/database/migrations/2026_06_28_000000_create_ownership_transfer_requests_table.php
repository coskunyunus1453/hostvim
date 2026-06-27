<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hesaplar arası domain / hosting sahipliği devri talepleri.
 * Müşteri talep oluşturur, admin onaylar/reddeder.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ownership_transfer_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('number')->unique();
            // Talebi açan (mevcut sahip)
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // domain | hosting
            $table->string('type', 20);
            // Devredilecek varlık referansları
            $table->foreignId('domain_name_id')->nullable()->constrained('domain_names')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            // İşlem için domain adı (domain adı veya hosting service_domain)
            $table->string('subject_domain')->nullable();
            // Devralacak hesap
            $table->string('target_email');
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            // pending | approved | rejected | cancelled
            $table->string('status', 20)->default('pending');
            $table->text('customer_note')->nullable();
            $table->text('admin_note')->nullable();
            $table->boolean('panel_synced')->default(false);
            $table->text('panel_sync_error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('target_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ownership_transfer_requests');
    }
};
