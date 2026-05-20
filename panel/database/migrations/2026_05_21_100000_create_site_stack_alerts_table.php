<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_stack_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('domain_id')->constrained()->cascadeOnDelete();
            $table->string('domain_name', 255);
            $table->string('profile', 40)->default('standard');
            $table->string('severity', 16)->default('warning');
            $table->string('fingerprint', 64);
            $table->string('status', 16)->default('open');
            $table->json('issue_codes');
            $table->unsignedSmallInteger('issue_count')->default(0);
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status', 'created_at']);
            $table->index(['domain_id', 'fingerprint']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_stack_alerts');
    }
};
