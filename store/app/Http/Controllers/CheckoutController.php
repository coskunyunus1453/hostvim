<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\CampaignService;
use App\Services\CartService;
use App\Services\Payment\PaymentManager;
use App\Services\SettingsService;
use App\Services\TemplatedMailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class CheckoutController extends Controller
{
    public function index(CartService $cart, CampaignService $campaigns)
    {
        try {
            $items = $cart->validatedItems();
        } catch (InvalidArgumentException $e) {
            return redirect()->route('cart.index')->with('error', $e->getMessage());
        }

        if (empty($items)) {
            return redirect()->route('products.index')->with('error', 'Sepetiniz boş.');
        }

        $paymentMethods = PaymentMethod::where('is_active', true)->orderBy('sort_order')->get();
        $subtotal = $cart->subtotal();
        $discount = $cart->couponDiscount();
        $appliedCoupon = $campaigns->appliedCoupon();
        $hasHosting = $cart->hasHosting();

        return view('checkout.index', [
            'items' => $items,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $cart->total(),
            'appliedCoupon' => $appliedCoupon,
            'paymentMethods' => $paymentMethods,
            'hasHosting' => $hasHosting,
            'needsDomainInput' => $cart->hostingNeedsDomainInput(),
            'customerDefaults' => $this->customerDefaults(),
        ]);
    }

    /** @return array<string, string|null> */
    private function customerDefaults(): array
    {
        $user = auth()->user();
        if (! $user) {
            return [];
        }

        return [
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'customer_phone' => $user->phone,
            'customer_company' => $user->billing_company ?? $user->company,
            'customer_address' => $user->billing_address ?? $user->address,
        ];
    }

    public function process(Request $request, CartService $cart, CampaignService $campaigns, PaymentManager $payments)
    {
        try {
            $items = $cart->validatedItems();
        } catch (InvalidArgumentException $e) {
            return redirect()->route('cart.index')->with('error', $e->getMessage());
        }

        if (empty($items)) {
            return redirect()->route('products.index')->with('error', 'Sepetiniz boş veya geçersiz ürünler içeriyor.');
        }

        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'customer_company' => 'nullable|string|max:255',
            'customer_address' => 'nullable|string|max:500',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'service_domain' => [
                Rule::requiredIf(fn () => collect($items)->contains(fn ($i) => ($i['item_type'] ?? '') === 'hosting') && app(CartService::class)->hostingNeedsDomainInput()),
                'nullable',
                'string',
                'max:253',
                'regex:/^([a-z0-9]([a-z0-9\-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i',
            ],
        ]);

        $paymentMethod = PaymentMethod::query()
            ->where('id', $validated['payment_method_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $subtotal = $cart->subtotal();
        $discount = $cart->couponDiscount();
        $total = $cart->total();
        $appliedCoupon = $campaigns->appliedCoupon();

        if ($appliedCoupon && $discount > 0) {
            try {
                $campaigns->validateCouponForCart($appliedCoupon->code, $items);
            } catch (InvalidArgumentException $e) {
                return redirect()->route('checkout.index')->with('error', $e->getMessage());
            }
        }

        $order = null;
        $createdAccount = null;

        try {
            // Misafir siparisi: mevcut hesaba bagla, yoksa otomatik hesap olustur.
            $accountUserId = auth()->id();
            if ($accountUserId === null) {
                $existing = User::query()->where('email', $validated['customer_email'])->first();
                if ($existing !== null) {
                    $accountUserId = $existing->id;
                } else {
                    $createdAccount = User::create([
                        'name' => $validated['customer_name'],
                        'email' => $validated['customer_email'],
                        'phone' => $validated['customer_phone'] ?? null,
                        'company' => $validated['customer_company'] ?? null,
                        'address' => $validated['customer_address'] ?? null,
                        'password' => Str::random(40),
                        'is_admin' => false,
                    ]);
                    $accountUserId = $createdAccount->id;
                }
            }

            $order = DB::transaction(function () use ($items, $validated, $paymentMethod, $subtotal, $discount, $total, $appliedCoupon, $accountUserId) {
                $serviceDomain = isset($validated['service_domain'])
                    ? strtolower(trim((string) $validated['service_domain']))
                    : '';

                $order = Order::create([
                    'order_number' => Order::generateOrderNumber(),
                    'user_id' => $accountUserId,
                    'payment_method_id' => $paymentMethod->id,
                    'status' => 'pending',
                    'payment_status' => 'pending',
                    'subtotal' => $subtotal,
                    'discount_amount' => $discount,
                    'coupon_code' => $appliedCoupon?->code,
                    'campaign_id' => $appliedCoupon?->id,
                    'total' => $total,
                    'currency' => 'TRY',
                    'billing_cycle' => $items[array_key_first($items)]['billing_cycle'],
                    'customer_name' => $validated['customer_name'],
                    'customer_email' => $validated['customer_email'],
                    'customer_phone' => $validated['customer_phone'] ?? null,
                    'customer_company' => $validated['customer_company'] ?? null,
                    'customer_address' => $validated['customer_address'] ?? null,
                ]);

                foreach ($items as $cartKey => $item) {
                    $itemType = (string) ($item['item_type'] ?? 'hosting');
                    $lineDomain = $item['service_domain'] ?? null;
                    if ($itemType === 'hosting' && empty($lineDomain) && $serviceDomain !== '') {
                        $lineDomain = $serviceDomain;
                    }

                    $meta = [];
                    if (! empty($item['addons'])) {
                        $meta['addons'] = $item['addons'];
                    }
                    if (! empty($item['registrar_api'])) {
                        $meta['registrar_api'] = $item['registrar_api'];
                    }
                    if ($itemType === 'cloud' && ! empty($item['install_panel'])) {
                        $meta['install_panel'] = true;
                    }

                    OrderItem::create([
                        'order_id' => $order->id,
                        'item_type' => $itemType,
                        'product_id' => $item['product_id'],
                        'product_name' => $item['product_name'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'unit_cost' => $item['unit_cost'] ?? null,
                        'total' => $item['unit_price'] * $item['quantity'],
                        'billing_cycle' => $item['billing_cycle'],
                        'domain_name' => $item['domain_name'] ?? null,
                        'domain_years' => $item['domain_years'] ?? null,
                        'service_domain' => $itemType === 'hosting' ? $lineDomain : null,
                        'domain_mode' => $item['domain_mode'] ?? null,
                        'config_meta' => $meta !== [] ? $meta : null,
                    ]);
                }

                return $order->load('items', 'paymentMethod');
            });

            $result = $payments->initiate($order, $paymentMethod);

            if (($result['type'] ?? 'error') === 'error') {
                $order->update(['status' => 'cancelled', 'payment_status' => 'failed']);
                Log::warning('Ödeme başlatılamadı', ['order' => $order->order_number, 'message' => $result['message'] ?? '']);

                return redirect()->route('checkout.index')->with('error', $result['message'] ?? 'Ödeme başlatılamadı.');
            }

            if ($appliedCoupon) {
                $campaigns->incrementUsage($appliedCoupon);
            }

            if ($createdAccount !== null) {
                $this->sendAccountCreatedMail($createdAccount);
            }

            $cart->clear();

            if (($result['type'] ?? '') === 'bank_transfer') {
                $this->sendBankTransferPendingMail($order, $paymentMethod, $result);

                return view('checkout.bank-transfer', compact('order', 'result', 'paymentMethod'));
            }

            return match ($result['type']) {
                'iframe' => view('checkout.paytr', compact('order', 'result')),
                'redirect' => redirect()->away($result['payment_page_url']),
                default => redirect()->route('checkout.index')->with('error', 'Bilinmeyen ödeme yanıtı.'),
            };
        } catch (InvalidArgumentException $e) {
            return redirect()->route('checkout.index')->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Checkout hatası', ['exception' => $e->getMessage()]);
            if ($order) {
                $order->update(['status' => 'cancelled', 'payment_status' => 'failed']);
            }

            return redirect()->route('checkout.index')->with('error', 'Sipariş işlenirken bir hata oluştu.');
        }
    }

    public function success(Order $order)
    {
        return view('checkout.success', compact('order'));
    }

    public function fail(Order $order)
    {
        return view('checkout.fail', compact('order'));
    }

    /**
     * Misafir checkout'ta otomatik olusturulan hesap icin sifre belirleme
     * (Laravel password broker token'i) e-postasi gonderir.
     */
    private function sendAccountCreatedMail(User $user): void
    {
        try {
            $token = Password::broker()->createToken($user);
            $url = route('password.reset', ['token' => $token, 'email' => $user->email]);
            $expireMinutes = (int) (config('auth.passwords.users.expire') ?? 60);

            app(TemplatedMailService::class)->send('account-created', $user->email, [
                'customer_name' => $user->name,
                'customer_email' => $user->email,
                'set_password_url' => $url,
                'login_url' => route('login'),
                'account_url' => route('account.dashboard'),
                'expire_minutes' => (string) $expireMinutes,
                'site_name' => (string) app(SettingsService::class)->get('site_name', config('app.name')),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Otomatik hesap maili gönderilemedi', ['email' => $user->email, 'error' => $e->getMessage()]);
        }
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function sendBankTransferPendingMail(Order $order, PaymentMethod $paymentMethod, array $result): void
    {
        app(TemplatedMailService::class)->send('bank-transfer-pending', $order->customer_email, [
            'customer_name' => $order->customer_name,
            'order_number' => $order->order_number,
            'total' => number_format((float) $order->total, 2, ',', '.'),
            'currency' => $order->currency,
            'bank_instructions' => nl2br(e((string) ($paymentMethod->instructions ?? ''))),
            'payment_reference' => (string) ($result['order_number'] ?? $order->order_number),
        ]);
    }
}
