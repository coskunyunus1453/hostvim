<?php

namespace App\Support;

class ResellerAssignablePermissions
{
    /** @var list<string> */
    private const DENY_EXACT = [
        'monitoring:server',
        'tools:run',
        'vendor:read',
        'vendor:write',
        'vendor:nodes',
        'vendor:billing',
        'vendor:support',
        'vendor:audit',
        'reseller:users',
        'reseller:packages',
        'reseller:roles',
        'reseller:white_label',
    ];

    /** @var list<string> */
    private const DENY_PREFIXES = [
        'security:',
        'webserver:',
        'php:',
        'reseller:',
        'vendor:',
    ];

    /**
     * @param  list<string>  $permissions
     */
    public static function isAllowedForResellerAssignable(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (! is_string($permission) || $permission === '') {
                continue;
            }
            if (in_array($permission, self::DENY_EXACT, true)) {
                return false;
            }
            foreach (self::DENY_PREFIXES as $prefix) {
                if (str_starts_with($permission, $prefix)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param  list<string>  $permissions
     */
    public static function assertResellerAssignable(array $permissions): void
    {
        foreach ($permissions as $permission) {
            if (! is_string($permission) || $permission === '') {
                continue;
            }
            if (in_array($permission, self::DENY_EXACT, true)) {
                abort(422, __('roles.reseller_permission_forbidden', ['permission' => $permission]));
            }
            foreach (self::DENY_PREFIXES as $prefix) {
                if (str_starts_with($permission, $prefix)) {
                    abort(422, __('roles.reseller_permission_forbidden', ['permission' => $permission]));
                }
            }
        }
    }
}
