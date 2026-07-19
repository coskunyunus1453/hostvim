<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('password_set_at')->nullable()->after('force_password_change');
        });

        // Daha önce şifresini değiştirmiş müşteriler — yeni siparişte tekrar zorunlu tutulmasın.
        DB::table('users')
            ->where('force_password_change', false)
            ->whereNull('password_set_at')
            ->update(['password_set_at' => DB::raw('COALESCE(updated_at, created_at)')]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('password_set_at');
        });
    }
};
