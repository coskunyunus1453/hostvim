<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Paylaşımlı public demo hesabı: gerçek müşteri/admin işlemlerini engeller.
 * Demo kullanıcı yalnızca kendi (seed) sitelerini görür (user role + user_id scope).
 */
class RestrictDemoUser
{
    /** @var list<string> */
    private array $blockedPrefixes = [
        'api/admin',
        'api/users',
        'api/roles',
        'api/permissions',
        'api/system',
        'api/license',
        'api/vendor',
        'api/impersonat',
        'api/whmcs',
        'api/store',
        'api/integrations',
        'api/security',
        'api/panel-update',
        'api/updates',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null || ! $user->isDemo()) {
            return $next($request);
        }

        $path = trim($request->path(), '/');

        foreach ($this->blockedPrefixes as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return response()->json([
                    'message' => 'Bu işlem public demo hesabında kapalıdır. Kendi sunucunuzda kurarak tüm özellikleri kullanabilirsiniz.',
                    'code' => 'demo_forbidden',
                    'demo' => true,
                ], 403);
            }
        }

        // Demo: yeni site oluşturmayı sınırla (seed siteleri bozulmasın)
        if ($request->isMethod('post') && ($path === 'api/domains' || $path === 'api/sites')) {
            $count = $user->domains()->count();
            $max = (int) config('panelze.public_demo.max_sites', 3);
            if ($count >= $max) {
                return response()->json([
                    'message' => "Demo hesap en fazla {$max} site tutabilir. Satın alıp kendi sunucunuza kurunca sınırsız yönetirsiniz.",
                    'code' => 'demo_site_limit',
                    'demo' => true,
                ], 403);
            }
            $name = strtolower(trim((string) $request->input('name', '')));
            if ($name !== '' && ! str_starts_with($name, 'demo-')) {
                return response()->json([
                    'message' => 'Demo hesapta yeni site adları «demo-» ile başlamalıdır (ör. demo-deneme.local).',
                    'code' => 'demo_site_name',
                    'demo' => true,
                ], 422);
            }
        }

        // Seed showcase sitelerini silmeyi engelle
        if ($request->isMethod('delete') && preg_match('#^api/domains/(\d+)$#', $path, $m)) {
            $domain = $user->domains()->where('id', (int) $m[1])->first();
            $protected = config('panelze.public_demo.protected_domains', []);
            if ($domain && is_array($protected) && in_array($domain->name, $protected, true)) {
                return response()->json([
                    'message' => 'Örnek demo siteleri silinemez; demo her birkaç saatte sıfırlanır.',
                    'code' => 'demo_protected_domain',
                    'demo' => true,
                ], 403);
            }
        }

        if ($request->isMethod('put') || $request->isMethod('patch') || $request->isMethod('post')) {
            if ($path === 'api/user/password' || $path === 'api/profile/password') {
                return response()->json([
                    'message' => 'Demo şifresi sabittir ve periyodik sıfırlanır. Kendi kurulumunuzda şifreyi özgürce değiştirirsiniz.',
                    'code' => 'demo_password_locked',
                    'demo' => true,
                ], 403);
            }
        }

        return $next($request);
    }
}
