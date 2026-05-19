<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('panel_releases', function (Blueprint $table): void {
            $table->id();
            $table->string('version', 32)->unique();
            $table->string('channel', 32)->default('stable');
            /** customer | pro | all */
            $table->string('profile', 32)->default('all');
            $table->string('title', 255);
            $table->text('changelog');
            $table->string('artifact_url', 2048)->nullable();
            $table->string('artifact_sha256', 64)->nullable();
            $table->string('git_tag', 64)->nullable();
            $table->string('min_panel_version', 32)->nullable();
            $table->boolean('requires_engine_restart')->default(true);
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['is_published', 'published_at', 'channel', 'profile']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('panel_releases');
    }
};
