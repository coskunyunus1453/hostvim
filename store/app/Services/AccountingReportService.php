<?php

namespace App\Services;

use App\Models\BusinessExpense;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SiteSetting;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AccountingReportService
{
    /**
     * @return array{
     *   revenue: float,
     *   discounts: float,
     *   cogs: float,
     *   gross_profit: float,
     *   gross_margin: float|null,
     *   expenses: float,
     *   net_profit: float,
     *   net_margin: float|null,
     *   order_count: int,
     *   paid_order_count: int,
     *   item_count: int,
     *   unknown_cost_lines: int
     * }
     */
    public function summary(?Carbon $from = null, ?Carbon $to = null): array
    {
        [$from, $to] = $this->normalizeRange($from, $to);

        $orders = Order::query()
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$from, $to]);

        $revenue = (float) (clone $orders)->sum('total');
        $discounts = (float) (clone $orders)->sum('discount_amount');
        $paidOrderCount = (int) (clone $orders)->count();

        $lineStats = $this->paidLineStats($from, $to);
        $expenses = $this->expenseTotal($from, $to);

        $grossProfit = round($lineStats['revenue'] - $lineStats['cogs'], 2);
        $netProfit = round($grossProfit - $expenses, 2);

        return [
            'revenue' => round($revenue, 2),
            'discounts' => round($discounts, 2),
            'cogs' => round($lineStats['cogs'], 2),
            'gross_profit' => $grossProfit,
            'gross_margin' => $this->marginPercent($lineStats['revenue'], $lineStats['cogs']),
            'expenses' => round($expenses, 2),
            'net_profit' => $netProfit,
            'net_margin' => $this->marginPercent($lineStats['revenue'], $lineStats['cogs'] + $expenses),
            'order_count' => $paidOrderCount,
            'paid_order_count' => $paidOrderCount,
            'item_count' => $lineStats['item_count'],
            'unknown_cost_lines' => $lineStats['unknown_cost_lines'],
        ];
    }

    /**
     * @return list<array{
     *   key: string,
     *   label: string,
     *   item_type: string,
     *   quantity: int,
     *   revenue: float,
     *   cogs: float,
     *   profit: float,
     *   margin: float|null,
     *   order_lines: int
     * }>
     */
    public function productProfitability(?Carbon $from = null, ?Carbon $to = null, int $limit = 25): array
    {
        [$from, $to] = $this->normalizeRange($from, $to);

        $rows = $this->paidLinesQuery($from, $to)
            ->select([
                'order_items.product_id',
                'order_items.product_name',
                'order_items.item_type',
                DB::raw('SUM(order_items.quantity) as qty'),
                DB::raw('SUM(order_items.unit_price * order_items.quantity) as revenue'),
                DB::raw('SUM(COALESCE(order_items.unit_cost, 0) * order_items.quantity) as cogs'),
                DB::raw('COUNT(*) as line_count'),
            ])
            ->groupBy('order_items.product_id', 'order_items.product_name', 'order_items.item_type')
            ->get();

        return $rows->map(function ($row) {
            $revenue = (float) $row->revenue;
            $cogs = (float) $row->cogs;
            $profit = round($revenue - $cogs, 2);

            return [
                'key' => ($row->product_id ?: '0').'|'.$row->product_name,
                'label' => (string) $row->product_name,
                'item_type' => (string) ($row->item_type ?: 'product'),
                'quantity' => (int) $row->qty,
                'revenue' => round($revenue, 2),
                'cogs' => round($cogs, 2),
                'profit' => $profit,
                'margin' => $this->marginPercent($revenue, $cogs),
                'order_lines' => (int) $row->line_count,
            ];
        })
            ->sortByDesc('profit')
            ->values()
            ->take($limit)
            ->all();
    }

    /**
     * @return list<array{
     *   email: string,
     *   name: string,
     *   order_count: int,
     *   revenue: float,
     *   cogs: float,
     *   profit: float,
     *   margin: float|null,
     *   last_order_at: string|null,
     *   days_since_last: int|null
     * }>
     */
    public function customerRanking(?Carbon $from = null, ?Carbon $to = null, int $limit = 25): array
    {
        [$from, $to] = $this->normalizeRange($from, $to);

        $orders = Order::query()
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$from, $to])
            ->with(['items'])
            ->get();

        return $orders->groupBy(fn (Order $o) => strtolower(trim($o->customer_email)))
            ->map(function (Collection $group, string $email) {
                /** @var Order $latest */
                $latest = $group->sortByDesc('created_at')->first();
                $revenue = 0.0;
                $cogs = 0.0;

                foreach ($group as $order) {
                    foreach ($order->items as $item) {
                        $revenue += (float) $item->unit_price * (int) $item->quantity;
                        $cogs += (float) ($item->unit_cost ?? 0) * (int) $item->quantity;
                    }
                }

                $profit = round($revenue - $cogs, 2);
                $lastAt = $latest?->created_at;

                return [
                    'email' => $email,
                    'name' => (string) $latest?->customer_name,
                    'order_count' => $group->count(),
                    'revenue' => round($revenue, 2),
                    'cogs' => round($cogs, 2),
                    'profit' => $profit,
                    'margin' => $this->marginPercent($revenue, $cogs),
                    'last_order_at' => $lastAt?->toDateString(),
                    'days_since_last' => $lastAt ? (int) $lastAt->diffInDays(now()) : null,
                ];
            })
            ->sortByDesc('profit')
            ->values()
            ->take($limit)
            ->all();
    }

    /**
     * @return list<array{
     *   email: string,
     *   name: string,
     *   last_order_at: string,
     *   days_inactive: int,
     *   lifetime_revenue: float,
     *   lifetime_profit: float,
     *   order_count: int
     * }>
     */
    public function inactiveCustomers(int $inactiveDays = 90, int $limit = 50): array
    {
        $cutoff = now()->subDays(max(1, $inactiveDays));

        $customers = Order::query()
            ->where('payment_status', 'paid')
            ->with('items')
            ->get()
            ->groupBy(fn (Order $o) => strtolower(trim($o->customer_email)));

        return $customers
            ->map(function (Collection $orders, string $email) {
                $latest = $orders->sortByDesc('created_at')->first();
                $revenue = 0.0;
                $cogs = 0.0;

                foreach ($orders as $order) {
                    foreach ($order->items as $item) {
                        $revenue += (float) $item->unit_price * (int) $item->quantity;
                        $cogs += (float) ($item->unit_cost ?? 0) * (int) $item->quantity;
                    }
                }

                return [
                    'email' => $email,
                    'name' => (string) $latest?->customer_name,
                    'last_order_at' => $latest?->created_at?->toDateString() ?? '',
                    'days_inactive' => (int) $latest?->created_at?->diffInDays(now()),
                    'lifetime_revenue' => round($revenue, 2),
                    'lifetime_profit' => round($revenue - $cogs, 2),
                    'order_count' => $orders->count(),
                    '_latest' => $latest?->created_at,
                ];
            })
            ->filter(fn (array $row) => $row['_latest'] !== null && $row['_latest']->lt($cutoff))
            ->sortByDesc('lifetime_revenue')
            ->values()
            ->take($limit)
            ->map(function (array $row) {
                unset($row['_latest']);

                return $row;
            })
            ->all();
    }

    /**
     * @return list<array{category: string, label: string, amount: float, count: int}>
     */
    public function expensesByCategory(?Carbon $from = null, ?Carbon $to = null): array
    {
        [$from, $to] = $this->normalizeRange($from, $to);

        return BusinessExpense::query()
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->select('category', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as cnt'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'category' => (string) $row->category,
                'label' => BusinessExpense::CATEGORIES[$row->category] ?? $row->category,
                'amount' => round((float) $row->total, 2),
                'count' => (int) $row->cnt,
            ])
            ->all();
    }

    /**
     * @return list<array{type: string, label: string, revenue: float, cogs: float, profit: float, margin: float|null}>
     */
    public function revenueByServiceType(?Carbon $from = null, ?Carbon $to = null): array
    {
        [$from, $to] = $this->normalizeRange($from, $to);

        $labels = [
            'hosting' => 'Web Hosting',
            'domain_register' => 'Domain',
            'manual' => 'VPS / VDS / Manuel',
            'product' => 'Diğer ürün',
        ];

        $rows = $this->paidLinesQuery($from, $to)
            ->select([
                'order_items.item_type',
                DB::raw('SUM(order_items.unit_price * order_items.quantity) as revenue'),
                DB::raw('SUM(COALESCE(order_items.unit_cost, 0) * order_items.quantity) as cogs'),
            ])
            ->groupBy('order_items.item_type')
            ->get();

        return $rows->map(function ($row) use ($labels) {
            $type = (string) ($row->item_type ?: 'product');
            $revenue = (float) $row->revenue;
            $cogs = (float) $row->cogs;

            return [
                'type' => $type,
                'label' => $labels[$type] ?? $type,
                'revenue' => round($revenue, 2),
                'cogs' => round($cogs, 2),
                'profit' => round($revenue - $cogs, 2),
                'margin' => $this->marginPercent($revenue, $cogs),
            ];
        })
            ->sortByDesc('revenue')
            ->values()
            ->all();
    }

    /**
     * @return list<array{date: string, revenue: float, cogs: float, profit: float}>
     */
    public function dailyTrend(int $days = 30): array
    {
        $days = max(7, min(90, $days));
        $from = now()->subDays($days - 1)->startOfDay();
        $to = now()->endOfDay();

        $lines = $this->paidLinesQuery($from, $to)
            ->select([
                DB::raw('DATE(orders.created_at) as day'),
                DB::raw('SUM(order_items.unit_price * order_items.quantity) as revenue'),
                DB::raw('SUM(COALESCE(order_items.unit_cost, 0) * order_items.quantity) as cogs'),
            ])
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        return collect(range($days - 1, 0))->map(function (int $i) use ($lines) {
            $day = now()->subDays($i)->toDateString();
            $row = $lines->get($day);
            $revenue = (float) ($row->revenue ?? 0);
            $cogs = (float) ($row->cogs ?? 0);

            return [
                'date' => $day,
                'revenue' => round($revenue, 2),
                'cogs' => round($cogs, 2),
                'profit' => round($revenue - $cogs, 2),
            ];
        })->reverse()->values()->all();
    }

    public function resolveUnitCostForCartItem(array $item): ?float
    {
        $type = (string) ($item['item_type'] ?? 'hosting');

        if ($type === 'domain_register') {
            $years = max(1, (int) ($item['domain_years'] ?? 1));
            $perYear = (float) (SiteSetting::query()->where('key', 'accounting_default_domain_cost')->value('value') ?? 0);

            return $perYear > 0 ? round($perYear * $years, 2) : null;
        }

        if (! empty($item['product_id'])) {
            $product = Product::query()->find($item['product_id']);
            if ($product === null) {
                return null;
            }

            return $product->getCostForCycle((string) ($item['billing_cycle'] ?? 'monthly'));
        }

        return null;
    }

    /**
     * @return array{revenue: float, cogs: float, item_count: int, unknown_cost_lines: int}
     */
    private function paidLineStats(Carbon $from, Carbon $to): array
    {
        $row = $this->paidLinesQuery($from, $to)
            ->select([
                DB::raw('SUM(order_items.unit_price * order_items.quantity) as revenue'),
                DB::raw('SUM(COALESCE(order_items.unit_cost, 0) * order_items.quantity) as cogs'),
                DB::raw('SUM(order_items.quantity) as qty'),
                DB::raw('SUM(CASE WHEN order_items.unit_cost IS NULL THEN 1 ELSE 0 END) as unknown_lines'),
            ])
            ->first();

        return [
            'revenue' => (float) ($row->revenue ?? 0),
            'cogs' => (float) ($row->cogs ?? 0),
            'item_count' => (int) ($row->qty ?? 0),
            'unknown_cost_lines' => (int) ($row->unknown_lines ?? 0),
        ];
    }

    private function paidLinesQuery(Carbon $from, Carbon $to)
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.payment_status', 'paid')
            ->whereBetween('orders.created_at', [$from, $to]);
    }

    private function expenseTotal(Carbon $from, Carbon $to): float
    {
        return (float) BusinessExpense::query()
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount');
    }

  /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function normalizeRange(?Carbon $from, ?Carbon $to): array
    {
        $to = ($to ?? now())->copy()->endOfDay();
        $from = ($from ?? now()->startOfMonth())->copy()->startOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }

    private function marginPercent(float $revenue, float $cost): ?float
    {
        if ($revenue <= 0) {
            return null;
        }

        return round((($revenue - $cost) / $revenue) * 100, 1);
    }
}
