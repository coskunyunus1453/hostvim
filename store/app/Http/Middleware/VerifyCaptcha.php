<?php

namespace App\Http\Middleware;

use App\Services\Security\CaptchaService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyCaptcha
{
    public function __construct(private readonly CaptchaService $captcha) {}

    public function handle(Request $request, Closure $next, string $context = 'default'): Response
    {
        if ($this->captcha->enabledFor($context) && ! $this->captcha->verify($request)) {
            return back()
                ->withInput($request->except(['password', 'password_confirmation', 'captcha_answer', CaptchaService::HONEYPOT_FIELD]))
                ->withErrors(['captcha' => 'Güvenlik doğrulaması başarısız oldu. Lütfen tekrar deneyin.']);
        }

        return $next($request);
    }
}
