<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Merkezi (şirket yönetimli) otomatik yedekleme:
 * - backup_destinations.is_system: Şirketin kendi Google Drive havuz hesapları
 *   (müşteri hedeflerinden ayrık). Müşteri arayüzünde gösterilmez.
 * - backup_schedules.is_managed: Merkezi otomasyon tarafından oluşturulan/güncellenen
 *   zamanlamalar (tüm hosting siteleri için günlük).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backup_destinations', function (Blueprint $table) {
            if (! Schema::hasColumn('backup_destinations', 'is_system')) {
                $table->boolean('is_system')->default(false)->index()->after('is_active');
            }
        });

        Schema::table('backup_schedules', function (Blueprint $table) {
            if (! Schema::hasColumn('backup_schedules', 'is_managed')) {
                $table->boolean('is_managed')->default(false)->index()->after('enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('backup_destinations', function (Blueprint $table) {
            if (Schema::hasColumn('backup_destinations', 'is_system')) {
                $table->dropColumn('is_system');
            }
        });

        Schema::table('backup_schedules', function (Blueprint $table) {
            if (Schema::hasColumn('backup_schedules', 'is_managed')) {
                $table->dropColumn('is_managed');
            }
        });
    }
};
