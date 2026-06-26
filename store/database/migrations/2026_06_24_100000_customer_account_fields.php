<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('panel_user_id')->nullable()->after('id')->index();
            $table->string('district')->nullable()->after('city');
            $table->string('postal_code', 20)->nullable()->after('district');
            $table->string('country', 2)->default('TR')->after('postal_code');
            $table->string('tax_office')->nullable()->after('country');
            $table->string('tax_number', 32)->nullable()->after('tax_office');
            $table->string('billing_company')->nullable()->after('tax_number');
            $table->text('billing_address')->nullable()->after('billing_company');
            $table->string('billing_city')->nullable()->after('billing_address');
            $table->string('billing_district')->nullable()->after('billing_city');
            $table->string('billing_postal_code', 20)->nullable()->after('billing_district');
            $table->string('billing_country', 2)->default('TR')->after('billing_postal_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'panel_user_id',
                'district',
                'postal_code',
                'country',
                'tax_office',
                'tax_number',
                'billing_company',
                'billing_address',
                'billing_city',
                'billing_district',
                'billing_postal_code',
                'billing_country',
            ]);
        });
    }
};
