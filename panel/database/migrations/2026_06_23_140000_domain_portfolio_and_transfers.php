<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domain_registrations', function (Blueprint $table) {
            $table->boolean('auto_renew')->default(true)->after('expires_at');
            $table->boolean('locked')->default(false)->after('auto_renew');
            $table->string('source_registrar', 64)->nullable()->after('registrar');
        });

        Schema::create('domain_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('domain_registration_id')->nullable()->constrained()->nullOnDelete();
            $table->string('domain');
            $table->string('direction', 8)->default('in'); // in = bize taşı, out = dışarı
            $table->string('source_registrar', 64)->nullable();
            $table->string('auth_code')->nullable();
            $table->string('status', 24)->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('domain');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_transfers');

        Schema::table('domain_registrations', function (Blueprint $table) {
            $table->dropColumn(['auto_renew', 'locked', 'source_registrar']);
        });
    }
};
