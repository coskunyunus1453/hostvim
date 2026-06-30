<?php

namespace App\Http\Middleware;

use App\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRegistrationEnabled
{
    public function __construct(private readonly SettingsService $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        $enabled = filter_var($this->settings->get('registration_enabled', '0'), FILTER_VALIDATE_BOOLEAN);

        if (! $enabled) {
            if ($request->isMethod('GET')) {
                return redirect()
                    ->route('login')
                    ->with('error', 'Yeni üye kaydı şu anda kapalıdır. Hesap açmak için lütfen bizimle iletişime geçin.');
            }

            abort(403, 'Yeni üye kaydı kapalıdır.');
        }

        return $next($request);
    }
}
