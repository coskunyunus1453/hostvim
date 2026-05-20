<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backups', function (Blueprint $table) {
            $table->index(['user_id', 'id'], 'backups_user_id_id_index');
        });

        Schema::table('deployment_runs', function (Blueprint $table) {
            $table->index(['user_id', 'id'], 'deployment_runs_user_id_id_index');
            $table->index(['domain_id', 'id'], 'deployment_runs_domain_id_id_index');
        });

        Schema::table('installer_runs', function (Blueprint $table) {
            $table->index(['user_id', 'id'], 'installer_runs_user_id_id_index');
            $table->index(['domain_id', 'id'], 'installer_runs_domain_id_id_index');
        });

        Schema::table('cron_job_runs', function (Blueprint $table) {
            $table->index(['user_id', 'id'], 'cron_job_runs_user_id_id_index');
        });

        Schema::table('cron_jobs', function (Blueprint $table) {
            $table->index(['user_id', 'status'], 'cron_jobs_user_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('backups', function (Blueprint $table) {
            $table->dropIndex('backups_user_id_id_index');
        });
        Schema::table('deployment_runs', function (Blueprint $table) {
            $table->dropIndex('deployment_runs_user_id_id_index');
            $table->dropIndex('deployment_runs_domain_id_id_index');
        });
        Schema::table('installer_runs', function (Blueprint $table) {
            $table->dropIndex('installer_runs_user_id_id_index');
            $table->dropIndex('installer_runs_domain_id_id_index');
        });
        Schema::table('cron_job_runs', function (Blueprint $table) {
            $table->dropIndex('cron_job_runs_user_id_id_index');
        });
        Schema::table('cron_jobs', function (Blueprint $table) {
            $table->dropIndex('cron_jobs_user_status_index');
        });
    }
};
