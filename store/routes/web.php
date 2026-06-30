<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HostingConfigureController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [RobotsController::class, 'index'])->name('robots');

Route::get('/', [HomeController::class, 'index'])->name('home');

// Modern tanıtım / satış sayfaları (yönetimden düzenlenebilir içerik)
Route::get('/web-hosting', [LandingController::class, 'hosting'])->name('hosting.index');
Route::get('/bulut-sunucu', [LandingController::class, 'cloud'])->name('cloud.index');

Route::get('/urunler', [ProductController::class, 'index'])->name('products.index');
Route::get('/urunler/{categorySlug}/{slug}', [ProductController::class, 'show'])->name('products.show');
Route::post('/urunler/{categorySlug}/{slug}/sepet', [ProductController::class, 'addToCart'])
    ->middleware('throttle:30,1')
    ->name('products.cart.add');

Route::prefix('siparis/hosting')->name('hosting.configure.')->group(function () {
    Route::get('/alan-adi', [HostingConfigureController::class, 'domain'])->name('domain');
    Route::post('/alan-adi', [HostingConfigureController::class, 'storeDomain'])->name('domain.store');
    Route::get('/ayarlar', [HostingConfigureController::class, 'options'])->name('options');
    Route::post('/ayarlar', [HostingConfigureController::class, 'storeOptions'])->name('options.store');
    Route::get('/ozet', [HostingConfigureController::class, 'review'])->name('review');
    Route::post('/tamamla', [HostingConfigureController::class, 'complete'])->name('complete');
    Route::get('/{categorySlug}/{slug}', [HostingConfigureController::class, 'start'])->name('start');
});
Route::get('/urunler/{slug}', [ProductController::class, 'category'])->name('products.category');

Route::get('/sayfa/{slug}', [PageController::class, 'show'])->name('pages.show');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

Route::get('/domain', [DomainController::class, 'index'])->name('domain.index');
Route::post('/domain/check', [DomainController::class, 'check'])
    ->middleware('throttle:30,1')
    ->name('domain.check');
Route::post('/domain/search', [DomainController::class, 'search'])
    ->middleware('throttle:30,1')
    ->name('domain.search');
Route::post('/domain/whois', [DomainController::class, 'whois'])
    ->middleware('throttle:30,1')
    ->name('domain.whois');
Route::post('/domain/sepet', [DomainController::class, 'addToCart'])
    ->middleware('throttle:20,1')
    ->name('domain.cart.add');

Route::get('/sepet', [CartController::class, 'index'])->name('cart.index');
Route::get('/sepet/sayac', [CartController::class, 'count'])->name('cart.count');
Route::delete('/sepet/{key}', [CartController::class, 'remove'])->name('cart.remove');
Route::delete('/sepet', [CartController::class, 'clear'])->name('cart.clear');

Route::get('/odeme', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/odeme/kupon', [\App\Http\Controllers\CouponController::class, 'apply'])->name('checkout.coupon.apply');
Route::delete('/odeme/kupon', [\App\Http\Controllers\CouponController::class, 'remove'])->name('checkout.coupon.remove');
Route::post('/odeme', [CheckoutController::class, 'process'])
    ->middleware(['throttle:10,1', 'captcha:checkout'])
    ->name('checkout.process');

Route::middleware('signed')->group(function () {
    Route::get('/odeme/basarili/{order}', [CheckoutController::class, 'success'])->name('payment.success');
    Route::get('/odeme/basarisiz/{order}', [CheckoutController::class, 'fail'])->name('payment.fail');
    Route::get('/odeme/stripe/donus/{order}', [PaymentController::class, 'stripeReturn'])->name('payment.stripe.return');
    Route::get('/odeme/paypal/donus/{order}', [PaymentController::class, 'paypalReturn'])->name('payment.paypal.return');
    Route::get('/odeme/payoneer/donus/{order}', [PaymentController::class, 'payoneerReturn'])->name('payment.payoneer.return');
});

Route::post('/odeme/paytr/callback', [PaymentController::class, 'paytrCallback'])
    ->middleware('throttle:60,1')
    ->name('payment.paytr.callback');

Route::post('/odeme/iyzico/callback', [PaymentController::class, 'iyzicoCallback'])
    ->middleware('throttle:60,1')
    ->name('payment.iyzico.callback');

Route::post('/odeme/stripe/webhook', [PaymentController::class, 'stripeWebhook'])
    ->middleware('throttle:120,1')
    ->name('payment.stripe.webhook');

Route::post('/odeme/payoneer/webhook', [PaymentController::class, 'payoneerWebhook'])
    ->middleware('throttle:120,1')
    ->name('payment.payoneer.webhook');

Route::get('/iletisim', [ContactController::class, 'index'])->name('contact.index');
Route::post('/iletisim', [ContactController::class, 'store'])
    ->middleware(['throttle:5,1', 'captcha:contact'])
    ->name('contact.store');

Route::middleware('guest')->group(function () {
    Route::get('/giris', [\App\Http\Controllers\Auth\AuthController::class, 'showLogin'])->name('login');
    Route::post('/giris', [\App\Http\Controllers\Auth\AuthController::class, 'login'])->middleware(['throttle:20,1', 'captcha:login']);
    Route::get('/kayit', [\App\Http\Controllers\Auth\AuthController::class, 'showRegister'])->middleware('registration')->name('register');
    Route::post('/kayit', [\App\Http\Controllers\Auth\AuthController::class, 'register'])->middleware(['throttle:10,1', 'registration', 'captcha:register']);
    Route::get('/sifremi-unuttum', [\App\Http\Controllers\Auth\PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/sifremi-unuttum', [\App\Http\Controllers\Auth\PasswordResetLinkController::class, 'store'])->middleware(['throttle:5,1', 'captcha:password'])->name('password.email');
    Route::get('/sifre-sifirla/{token}', [\App\Http\Controllers\Auth\NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/sifre-sifirla', [\App\Http\Controllers\Auth\NewPasswordController::class, 'store'])->middleware('throttle:10,1')->name('password.update');
});

Route::post('/cikis', [\App\Http\Controllers\Auth\AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'panel.sync'])->prefix('hesabim')->name('account.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Account\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profil', [\App\Http\Controllers\Account\ProfileController::class, 'edit'])->name('profile');
    Route::put('/profil', [\App\Http\Controllers\Account\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profil/sifre', [\App\Http\Controllers\Account\ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::get('/alan-adlarim', [\App\Http\Controllers\Account\DomainController::class, 'index'])->name('domains');
    Route::get('/alan-adlarim/{id}', [\App\Http\Controllers\Account\DomainController::class, 'show'])->whereNumber('id')->name('domains.show');
    Route::post('/alan-adlarim/{id}/dns', [\App\Http\Controllers\Account\DomainController::class, 'saveDns'])->whereNumber('id')->name('domains.dns');
    Route::post('/alan-adlarim/{id}/nameserver', [\App\Http\Controllers\Account\DomainController::class, 'nameservers'])->whereNumber('id')->name('domains.nameservers');
    Route::post('/alan-adlarim/{id}/yenile', [\App\Http\Controllers\Account\DomainController::class, 'renew'])->whereNumber('id')->name('domains.renew');
    Route::post('/alan-adlarim/{id}/gizlilik', [\App\Http\Controllers\Account\DomainController::class, 'privacy'])->whereNumber('id')->name('domains.privacy');
    Route::post('/alan-adlarim/{id}/oto-yenileme', [\App\Http\Controllers\Account\DomainController::class, 'autoRenew'])->whereNumber('id')->name('domains.autorenew');
    Route::post('/alan-adlarim/{id}/transfer-kodu', [\App\Http\Controllers\Account\DomainController::class, 'authCode'])->whereNumber('id')->name('domains.authcode');
    Route::get('/hostinglerim', [\App\Http\Controllers\Account\HostingController::class, 'index'])->name('hosting');
    Route::post('/hostinglerim/panel', [\App\Http\Controllers\Account\HostingController::class, 'panelLogin'])->name('hosting.panel');
    Route::get('/faturalarim', [\App\Http\Controllers\Account\InvoiceController::class, 'index'])->name('invoices');
    Route::get('/faturalarim/{invoiceId}', [\App\Http\Controllers\Account\InvoiceController::class, 'show'])->name('invoices.show');
    Route::post('/faturalarim/{invoiceId}/ode', [\App\Http\Controllers\Account\InvoiceController::class, 'pay'])->name('invoices.pay');
    Route::get('/siparislerim', [\App\Http\Controllers\Account\OrderController::class, 'index'])->name('orders');
    Route::get('/siparislerim/{orderId}', [\App\Http\Controllers\Account\OrderController::class, 'show'])->name('orders.show');
    Route::get('/destek', [\App\Http\Controllers\Account\SupportTicketController::class, 'index'])->name('support.index');
    Route::get('/destek/yeni', [\App\Http\Controllers\Account\SupportTicketController::class, 'create'])->name('support.create');
    Route::post('/destek', [\App\Http\Controllers\Account\SupportTicketController::class, 'store'])->name('support.store');
    Route::get('/destek/{ticket}', [\App\Http\Controllers\Account\SupportTicketController::class, 'show'])->name('support.show');
    Route::post('/destek/{ticket}/yanit', [\App\Http\Controllers\Account\SupportTicketController::class, 'reply'])->name('support.reply');
    Route::post('/destek/{ticket}/kapat', [\App\Http\Controllers\Account\SupportTicketController::class, 'close'])->name('support.close');
});
