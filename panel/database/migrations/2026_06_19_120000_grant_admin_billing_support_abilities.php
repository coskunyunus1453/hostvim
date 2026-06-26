<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Yönetici rolüne yerel faturalama ve destek yönetim yeteneklerini atar (idempotent).
 */
return new class extends Migration
{
    public function up(): void
    {
        $guard = 'web';
        $names = ['billing:read', 'billing:write', 'billing:admin', 'support:read', 'support:write', 'support:admin'];

        $permIds = [];
        foreach ($names as $name) {
            $id = DB::table('permissions')->where('name', $name)->where('guard_name', $guard)->value('id');
            if ($id === null) {
                continue;
            }
            $permIds[$name] = $id;
        }

        $roleId = DB::table('roles')->where('name', 'admin')->where('guard_name', $guard)->value('id');
        if ($roleId === null) {
            return;
        }

        foreach ($permIds as $permId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permId,
                'role_id' => $roleId,
            ]);
        }

        if (function_exists('app')) {
            try {
                app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
            } catch (\Throwable) {
                // önbellek yoksa sorun değil
            }
        }
    }

    public function down(): void
    {
        // Yetenekler kalsın.
    }
};
