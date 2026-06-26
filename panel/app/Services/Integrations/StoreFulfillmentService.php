<?php

namespace App\Services\Integrations;

use App\Models\HostingPackage;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Services\Billing\InvoiceService;
use App\Services\Billing\OrderService;
use App\Services\SafeAuditLogger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class StoreFulfillmentService
{
    public function __construct(
        private OrderService $orders,
        private InvoiceService $invoices,
    ) {}

    /**
     * @param  array{
     *   store_order_number:string,
     *   customer:array{name:string,email:string,phone?:?string,locale?:?string},
     *   items:list<array{
     *     item_type?:string,
     *     package_id?:int,
     *     billing_cycle?:string,
     *     domain?:?string,
     *     domain_years?:int,
     *     product_name?:string,
     *     unit_price?:float|int|string
     *   }>,
     *   payment:array{method?:string,reference?:?string}
     * }  $payload
     * @return array<string, mixed>
     */
    public function fulfill(array $payload): array
    {
        $storeOrderNumber = trim((string) ($payload['store_order_number'] ?? ''));
        if ($storeOrderNumber === '') {
            throw ValidationException::withMessages(['store_order_number' => 'Mağaza sipariş numarası gerekli.']);
        }

        return Cache::lock('store-fulfill:'.$storeOrderNumber, 60)->block(15, function () use ($payload, $storeOrderNumber): array {
            $existing = Order::query()
                ->where('store_order_number', $storeOrderNumber)
                ->first();

            if ($existing !== null) {
                return $this->responseForOrder($existing, false, null);
            }

            return $this->createFulfillment($payload, $storeOrderNumber);
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    public function status(string $storeOrderNumber): ?array
    {
        $storeOrderNumber = trim($storeOrderNumber);
        if ($storeOrderNumber === '') {
            return null;
        }

        $order = Order::query()
            ->where('store_order_number', $storeOrderNumber)
            ->first();

        if ($order === null) {
            return null;
        }

        return $this->responseForOrder($order, false, null);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function createFulfillment(array $payload, string $storeOrderNumber): array
    {
        $customer = $payload['customer'] ?? [];
        $email = strtolower(trim((string) ($customer['email'] ?? '')));
        $name = trim((string) ($customer['name'] ?? ''));
        if ($email === '' || $name === '') {
            throw ValidationException::withMessages(['customer' => 'Müşteri adı ve e-posta gerekli.']);
        }

        $items = $payload['items'] ?? [];
        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'En az bir ürün gerekli.']);
        }

        if (count($items) > 20) {
            throw ValidationException::withMessages(['items' => 'Tek istekte en fazla 20 kalem gönderilebilir.']);
        }

        $paymentMethod = trim((string) ($payload['payment']['method'] ?? 'store'));
        $paymentReference = trim((string) ($payload['payment']['reference'] ?? ''));
        if ($paymentReference === '') {
            $paymentReference = $storeOrderNumber;
        }

        $temporaryPassword = null;
        $userCreated = false;

        try {
            return DB::transaction(function () use (
                $storeOrderNumber,
                $email,
                $name,
                $customer,
                $items,
                $paymentMethod,
                $paymentReference,
                &$temporaryPassword,
                &$userCreated,
            ): array {
                $dupCheck = Order::query()
                    ->where('store_order_number', $storeOrderNumber)
                    ->lockForUpdate()
                    ->first();

                if ($dupCheck !== null) {
                    return $this->responseForOrder($dupCheck, false, null);
                }

                $user = User::query()->where('email', $email)->first();

                if ($user === null) {
                    $role = Role::query()->where('name', 'user')->where('guard_name', 'web')->firstOrFail();
                    $temporaryPassword = Str::password(14);
                    $user = User::create([
                        'name' => $name,
                        'email' => $email,
                        'password' => Hash::make($temporaryPassword),
                        'locale' => $this->resolveLocale($customer['locale'] ?? null),
                        'status' => 'active',
                        'force_password_change' => true,
                    ]);
                    $user->syncRoles([$role->name]);
                    $userCreated = true;
                } elseif ($user->name !== $name && $name !== '') {
                    $user->forceFill(['name' => $name])->save();
                }

                $orderItems = $this->normalizeItems($items);
                $placed = $this->orders->place($user, $orderItems);
                $order = $placed['order'];
                $invoice = $placed['invoice'];

                $order->forceFill([
                    'store_order_number' => $storeOrderNumber,
                    'notes' => 'Mağaza siparişi: '.$storeOrderNumber,
                ])->save();

                $this->invoices->markPaid($invoice->fresh(), $paymentMethod, $paymentReference);

                $order = $order->fresh('invoices');

                SafeAuditLogger::info('panelze.store.fulfill', [
                    'store_order_number' => $storeOrderNumber,
                    'panel_order_id' => $order->id,
                    'user_id' => $user->id,
                    'user_created' => $userCreated,
                ], request());

                return $this->responseForOrder($order, $userCreated, $temporaryPassword);
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);
            Log::error('panelze.store.fulfill_failed', [
                'message' => $e->getMessage(),
                'store_order_number' => $storeOrderNumber,
            ]);
            throw ValidationException::withMessages([
                'fulfill' => 'Sipariş panelde işlenemedi. Lütfen destek ile iletişime geçin.',
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function normalizeItems(array $items): array
    {
        $orderItems = [];

        foreach ($items as $row) {
            $type = strtolower(trim((string) ($row['item_type'] ?? 'hosting')));

            if ($type === 'domain_register') {
                $domain = isset($row['domain']) ? strtolower(trim((string) $row['domain'])) : '';
                if ($domain === '') {
                    throw ValidationException::withMessages(['items' => 'Alan adı gerekli.']);
                }

                $orderItems[] = [
                    'item_type' => 'domain_register',
                    'domain' => $domain,
                    'domain_years' => max(1, min(10, (int) ($row['domain_years'] ?? 1))),
                    'unit_price' => isset($row['unit_price']) ? (float) $row['unit_price'] : null,
                    'registrar_api' => isset($row['registrar_api']) ? strtolower(trim((string) $row['registrar_api'])) : null,
                ];

                continue;
            }

            if ($type === 'manual') {
                $productName = trim((string) ($row['product_name'] ?? ''));
                if ($productName === '') {
                    throw ValidationException::withMessages(['items' => 'Manuel ürün adı gerekli.']);
                }

                $rawCycle = (string) ($row['billing_cycle'] ?? 'monthly');
                $cycle = $rawCycle === 'yearly' ? 'yearly' : 'monthly';

                $orderItems[] = [
                    'item_type' => 'manual',
                    'product_name' => $productName,
                    'billing_cycle' => $cycle,
                    'unit_price' => (float) ($row['unit_price'] ?? 0),
                ];

                continue;
            }

            $packageId = (int) ($row['package_id'] ?? 0);
            HostingPackage::query()->where('is_active', true)->findOrFail($packageId);

            $rawCycle = (string) ($row['billing_cycle'] ?? 'monthly');
            if ($rawCycle === 'onetime') {
                throw ValidationException::withMessages([
                    'items' => 'Tek seferlik ödeme döngüsü panel provizyonunda desteklenmiyor.',
                ]);
            }

            $cycle = $rawCycle === 'yearly' ? 'yearly' : 'monthly';
            $domain = isset($row['domain']) ? strtolower(trim((string) $row['domain'])) : '';

            $orderItems[] = [
                'item_type' => 'hosting',
                'package_id' => $packageId,
                'billing_cycle' => $cycle,
                'domain' => $domain !== '' ? $domain : null,
            ];
        }

        return $orderItems;
    }

    /**
     * @return array<string, mixed>
     */
    private function responseForOrder(Order $order, bool $userCreated, ?string $temporaryPassword): array
    {
        $order->loadMissing('user');

        $base = $this->panelLoginBase();

        $result = [
            'ok' => true,
            'found' => true,
            'panel_order_number' => (string) $order->number,
            'panel_order_id' => (int) $order->id,
            'panel_user_id' => (int) $order->user_id,
            'panel_login_url' => $base,
            'user_created' => $userCreated,
            'needs_password_setup' => (bool) ($order->user?->force_password_change ?? false),
        ];

        if ($userCreated && $temporaryPassword !== null && $temporaryPassword !== '') {
            $result['temporary_password'] = $temporaryPassword;
        }

        return $result;
    }

    private function panelLoginBase(): string
    {
        $base = rtrim((string) config('panelze.store_integration.panel_login_url', ''), '/');
        if ($base === '') {
            $base = rtrim((string) env('PANELZE_PANEL_URL', ''), '/');
        }
        if ($base === '') {
            $base = rtrim((string) config('app.url', ''), '/');
        }

        return $base;
    }

    private function resolveLocale(?string $locale): string
    {
        $locale = $locale !== null ? strtolower(trim($locale)) : '';
        $allowed = config('panelze.available_locales', ['en', 'tr']);

        return in_array($locale, $allowed, true)
            ? $locale
            : (string) config('panelze.default_locale', 'en');
    }
}
