<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Müşteri/bayi rollerinde destek yetenekleri eksik kaldıysa yeniden atar (idempotent).
 */
return new class extends Migration
{
    public function up(): void
    {
        $guard = 'web';
        $grants = [
            'user' => ['support:read', 'support:write'],
            'reseller' => ['support:read', 'support:write', 'support:admin'],
        ];

        foreach ($grants as $roleName => $abilities) {
            $roleId = DB::table('roles')->where('name', $roleName)->where('guard_name', $guard)->value('id');
            if ($roleId === null) {
                continue;
            }

            foreach ($abilities as $ability) {
                $permId = DB::table('permissions')->where('name', $ability)->where('guard_name', $guard)->value('id');
                if ($permId === null) {
                    continue;
                }

                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permId,
                    'role_id' => $roleId,
                ]);
            }
        }

        if (function_exists('app')) {
            try {
                app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
            } catch (\Throwable) {
                //
            }
        }
    }

    public function down(): void
    {
        //
    }
};
