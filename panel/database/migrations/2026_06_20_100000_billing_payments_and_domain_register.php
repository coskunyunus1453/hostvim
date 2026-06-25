<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('payment_merchant_ref', 64)->nullable()->after('transaction_ref');
            $table->index('payment_merchant_ref');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('item_type', 24)->default('hosting')->after('order_id');
            $table->unsignedTinyInteger('domain_years')->default(1)->after('domain');
        });

        Schema::create('domain_tlds', function (Blueprint $table) {
            $table->id();
            $table->string('tld', 32)->unique();
            $table->decimal('register_price', 12, 2)->default(0);
            $table->decimal('renew_price', 12, 2)->default(0);
            $table->boolean('enabled')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('domain_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('domain');
            $table->unsignedTinyInteger('years')->default(1);
            $table->string('status', 24)->default('pending'); // pending, active, failed, cancelled
            $table->string('registrar', 40)->default('manual');
            $table->string('registrar_ref')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->unique('domain');
        });

        $now = now();
        $defaults = [
            ['tld' => '.com', 'register_price' => 299.00, 'renew_price' => 299.00, 'sort_order' => 10],
            ['tld' => '.net', 'register_price' => 349.00, 'renew_price' => 349.00, 'sort_order' => 20],
            ['tld' => '.org', 'register_price' => 349.00, 'renew_price' => 349.00, 'sort_order' => 30],
            ['tld' => '.com.tr', 'register_price' => 149.00, 'renew_price' => 149.00, 'sort_order' => 5],
        ];
        foreach ($defaults as $row) {
            DB::table('domain_tlds')->insertOrIgnore(array_merge($row, [
                'enabled' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_registrations');
        Schema::dropIfExists('domain_tlds');

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['item_type', 'domain_years']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['payment_merchant_ref']);
            $table->dropColumn('payment_merchant_ref');
        });
    }
};
