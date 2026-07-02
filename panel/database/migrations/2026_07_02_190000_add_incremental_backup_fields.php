<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plesk tarzı arttırımlı (incremental) yedekleme için gerekli alanlar.
 *
 * - backups.level: 0 = tam (full/base), 1+ = arttırımlı derinlik.
 * - backups.parent_backup_id: zincirdeki bir önceki yedek (arttırımlı için).
 * - backups.base_backup_id: zinciri başlatan level-0 tam yedek.
 * - backups.snapshot_path: engine tarafındaki GNU tar snapshot (.snar) yolu; bir
 *   sonraki arttırımlı yedek bu snapshot'tan devam eder.
 * - backup_schedules.full_interval_days: arttırımlı planlarda en az bu kadar günde
 *   bir tam (base) yedek alınır (zincir çok uzamasın / güvenli restore).
 * - backup_schedules.retention_count: saklanacak azami tam-yedek zinciri sayısı.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backups', function (Blueprint $table) {
            $table->unsignedSmallInteger('level')->default(0)->after('type');
            $table->unsignedBigInteger('parent_backup_id')->nullable()->after('level');
            $table->unsignedBigInteger('base_backup_id')->nullable()->after('parent_backup_id');
            $table->string('snapshot_path')->nullable()->after('file_path');

            $table->foreign('parent_backup_id')->references('id')->on('backups')->nullOnDelete();
            $table->foreign('base_backup_id')->references('id')->on('backups')->nullOnDelete();
            $table->index(['domain_id', 'destination_id', 'status']);
        });

        Schema::table('backup_schedules', function (Blueprint $table) {
            $table->unsignedSmallInteger('full_interval_days')->default(7)->after('type');
            $table->unsignedSmallInteger('retention_count')->nullable()->after('full_interval_days');
        });
    }

    public function down(): void
    {
        Schema::table('backups', function (Blueprint $table) {
            $table->dropForeign(['parent_backup_id']);
            $table->dropForeign(['base_backup_id']);
            $table->dropIndex(['domain_id', 'destination_id', 'status']);
            $table->dropColumn(['level', 'parent_backup_id', 'base_backup_id', 'snapshot_path']);
        });

        Schema::table('backup_schedules', function (Blueprint $table) {
            $table->dropColumn(['full_interval_days', 'retention_count']);
        });
    }
};
