<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_notifications', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 60);
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('icon', 60)->nullable();
            $table->string('color', 20)->default('gray');
            $table->string('action_url');
            $table->nullableMorphs('notifiable');
            $table->string('dedupe_key')->nullable()->unique();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['read_at', 'created_at']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
    }
};
