<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('panel:recover-stuck-provisions')->everyFiveMinutes()->withoutOverlapping();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'odeme/paytr/callback',
            'odeme/iyzico/callback',
            'odeme/stripe/webhook',
            'odeme/payoneer/webhook',
        ]);

        $middleware->web(prepend: [
            \App\Http\Middleware\SecurityHeadersMiddleware::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\PageCacheMiddleware::class,
            \App\Http\Middleware\CompressResponseMiddleware::class,
        ]);

        $middleware->alias([
            'panel.sync' => \App\Http\Middleware\SyncPanelUserAfterResponse::class,
            'captcha' => \App\Http\Middleware\VerifyCaptcha::class,
            'registration' => \App\Http\Middleware\EnsureRegistrationEnabled::class,
        ]);

        $middleware->redirectUsersTo(function (Request $request) {
            $user = $request->user();

            if ($user?->is_admin) {
                return url('/admin');
            }

            return route('account.dashboard');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
