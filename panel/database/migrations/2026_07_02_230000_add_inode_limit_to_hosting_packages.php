<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hosting_packages', function (Blueprint $table) {
            if (! Schema::hasColumn('hosting_packages', 'inode_limit')) {
                // -1 = limitsiz (mevcut paketler geriye dönük sınırsız kalır)
                $table->integer('inode_limit')->default(-1)->after('memory_limit_mb');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hosting_packages', function (Blueprint $table) {
            if (Schema::hasColumn('hosting_packages', 'inode_limit')) {
                $table->dropColumn('inode_limit');
            }
        });
    }
};
