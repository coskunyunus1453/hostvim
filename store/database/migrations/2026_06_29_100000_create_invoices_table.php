<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();

            $table->string('invoice_number')->unique();
            $table->string('type')->default('earchive'); // einvoice | earchive
            $table->string('status')->default('draft');   // draft | queued | issued | sent | accepted | rejected | error | cancelled

            $table->string('provider')->nullable();        // nilvera | parasut | mukellef
            $table->string('provider_uuid')->nullable();   // ETTN / GİB UUID
            $table->string('provider_invoice_id')->nullable();

            // Müşteri (alıcı) anlık kopyası
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_tax_office')->nullable();
            $table->string('customer_tax_number')->nullable();
            $table->text('customer_address')->nullable();

            // Tutarlar
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(20);
            $table->string('currency', 8)->default('TRY');

            $table->timestamp('issued_at')->nullable();
            $table->string('pdf_path')->nullable();
            $table->text('error_message')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('provider_uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
