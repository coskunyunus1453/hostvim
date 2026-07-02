<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Siparişe KDV kırılımı: ödeme sırasında hesaplanan KDV oranı ve tutarı (raporlama +
 * faturayla tutarlılık için). KDV dahil modda total değişmez; tax_amount içeriden ayrıştırılan
 * KDV'dir. KDV hariç modda total = matrah + tax_amount.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->default(0)->after('campaign_id');
            }
            if (! Schema::hasColumn('orders', 'tax_amount')) {
                $table->decimal('tax_amount', 12, 2)->default(0)->after('tax_rate');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            foreach (['tax_rate', 'tax_amount'] as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
