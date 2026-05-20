<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ssl_certificates', function (Blueprint $table) {
            $table->foreignId('site_subdomain_id')->nullable()->after('domain_id')
                ->constrained('site_subdomains')->cascadeOnDelete();
            $table->index(['domain_id', 'site_subdomain_id']);
        });

        Schema::table('site_subdomains', function (Blueprint $table) {
            $table->string('php_version', 10)->nullable()->after('document_root');
            $table->string('server_type', 32)->nullable()->after('php_version');
            $table->boolean('ssl_enabled')->default(false)->after('server_type');
            $table->timestamp('ssl_expiry')->nullable()->after('ssl_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('site_subdomains', function (Blueprint $table) {
            $table->dropColumn(['php_version', 'server_type', 'ssl_enabled', 'ssl_expiry']);
        });

        Schema::table('ssl_certificates', function (Blueprint $table) {
            $table->dropForeign(['site_subdomain_id']);
            $table->dropColumn('site_subdomain_id');
        });
    }
};
