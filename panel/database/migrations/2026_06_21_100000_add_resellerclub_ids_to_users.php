<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('resellerclub_customer_id')->nullable()->after('onboarding_completed_at');
            $table->unsignedBigInteger('resellerclub_contact_id')->nullable()->after('resellerclub_customer_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['resellerclub_customer_id', 'resellerclub_contact_id']);
        });
    }
};
