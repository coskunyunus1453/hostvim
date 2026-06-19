<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Yeni faturalama/destek yeteneklerini oluşturur ve mevcut müşteri/bayi rollerine atar.
 * Önbelleğe bağımlı olmamak için doğrudan Spatie pivot tablolarına yazar (idempotent).
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
                $id = DB::table('permissions')->insertGetId([
                    'name' => $name,
                    'guard_name' => $guard,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $permIds[$name] = $id;
        }

        $grants = [
            'user' => ['billing:read', 'billing:write', 'support:read', 'support:write'],
            'reseller' => ['billing:read', 'billing:write', 'billing:admin', 'support:read', 'support:write', 'support:admin'],
        ];

        foreach ($grants as $roleName => $abilities) {
            $roleId = DB::table('roles')->where('name', $roleName)->where('guard_name', $guard)->value('id');
            if ($roleId === null) {
                continue;
            }
            foreach ($abilities as $ability) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permIds[$ability],
                    'role_id' => $roleId,
                ]);
            }
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
        // Yetenekler kalsın; geri alma gerekmiyor.
    }
};
