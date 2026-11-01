<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserNotCommunityBanned;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLandingLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(function (Request $request): string {
            $lang = $request->query('lang');
            if (! is_string($lang) || $lang === '') {
                $sessionLocale = $request->session()->get('landing_locale');
                $lang = is_string($sessionLocale) ? $sessionLocale : '';
            }

            return $lang !== '' ? route('login', ['lang' => $lang]) : route('login');
        });
        $middleware->redirectUsersTo(fn () => route('community.index'));
        // Host başlığı zehirlenmesi (ör. parola sıfırlama linki) — local dışında APP_URL alan adı + alt alan adları.
        $middleware->trustHosts(at: function (): array {
            $host = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
            $apex = preg_replace('/^www\./', '', $host) ?: $host;

            return $apex !== '' ? ['^(.+\.)?'.preg_quote($apex).'$'] : [];
        });
        $middleware->web(append: [
            SetLandingLocale::class,
            SecurityHeaders::class,
        ]);
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'community.active' => EnsureUserNotCommunityBanned::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
