<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backups', function (Blueprint $table) {
            $table->string('remote_path', 512)->nullable()->after('file_path');
            $table->string('remote_file_id', 128)->nullable()->after('remote_path');
        });
    }

    public function down(): void
    {
        Schema::table('backups', function (Blueprint $table) {
            $table->dropColumn(['remote_path', 'remote_file_id']);
        });
    }
};
