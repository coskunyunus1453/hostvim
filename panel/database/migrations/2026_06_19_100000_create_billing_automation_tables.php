<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Panelze yerel faturalama & otomasyon çekirdeği (WHMCS yerine):
 * sipariş → fatura → ödeme → otomatik hizmet kurulumu → yenileme/askı/fesih + destek talepleri.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reseller_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('pending'); // pending, active, cancelled, fraud
            $table->string('currency', 3)->default('TRY');
            $table->decimal('total', 12, 2)->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hosting_package_id')->nullable()->constrained()->nullOnDelete();
            $table->string('billing_cycle', 20)->default('monthly'); // monthly, yearly
            $table->string('domain')->nullable();
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('setup_fee', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            // draft, unpaid, paid, overdue, cancelled, refunded
            $table->string('status', 20)->default('unpaid');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('currency', 3)->default('TRY');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method', 40)->nullable();
            $table->string('transaction_ref')->nullable();
            $table->unsignedTinyInteger('reminders_sent')->default(0);
            $table->timestamp('last_reminder_at')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'due_at']);
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('department', 40)->default('general'); // general, technical, billing
            $table->string('subject');
            // open, answered, customer_reply, on_hold, closed
            $table->string('status', 20)->default('open');
            $table->string('priority', 10)->default('medium'); // low, medium, high
            $table->foreignId('domain_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('last_reply_at')->nullable();
            $table->string('last_reply_by', 12)->nullable(); // customer, staff
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'last_reply_at']);
        });

        Schema::create('support_ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_staff')->default(false);
            $table->text('body');
            $table->json('attachments')->nullable();
            $table->timestamps();

            $table->index('support_ticket_id');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('domain_id')->nullable()->after('hosting_package_id')->constrained()->nullOnDelete();
            $table->timestamp('next_due_at')->nullable()->after('ends_at');
            $table->boolean('auto_renew')->default(true)->after('next_due_at');
            $table->decimal('setup_fee', 12, 2)->default(0)->after('amount');
            // pending, active, suspended, terminated — yerel provision yaşam döngüsü
            $table->string('service_status', 20)->default('pending')->after('status');
            $table->timestamp('provisioned_at')->nullable()->after('service_status');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('domain_id');
            $table->dropColumn(['next_due_at', 'auto_renew', 'setup_fee', 'service_status', 'provisioned_at']);
        });

        Schema::dropIfExists('support_ticket_messages');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
