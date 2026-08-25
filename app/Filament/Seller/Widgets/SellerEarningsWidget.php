<?php

namespace App\Filament\Seller\Widgets;

use App\Domain\Commissions\Models\Commission;
use App\Domain\Orders\Models\OrderSeller;
use App\Support\Money;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

final class SellerEarningsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    /** @return list<Stat> */
    protected function getStats(): array
    {
        $user = Auth::user();
        $uid = $user?->id;
        $monthStart = now()->startOfMonth();

        $orderEarnings = (int) OrderSeller::query()
            ->where('seller_id', $uid)
            ->where('transfer_status', 'transferred')
            ->whereHas('order', fn ($q) => $q->where('paid_at', '>=', $monthStart))
            ->sum('subtotal_minor');

        $commissionEarnings = (int) Commission::query()
            ->where('seller_id', $uid)
            ->where('transfer_status', 'transferred')
            ->where('balance_paid_at', '>=', $monthStart)
            ->sum('quote_minor');

        $pendingQuotes = Commission::query()
            ->where('seller_id', $uid)
            ->where('state', 'requested')
            ->count();

        $awaitingShipment = OrderSeller::query()
            ->where('seller_id', $uid)
            ->whereNull('shipped_at')
            ->whereHas('order', fn ($q) => $q->where('state', 'paid'))
            ->count();

        return [
            Stat::make(__('studio.earnings_month'), Money::format($orderEarnings + $commissionEarnings).' SAR')
                ->description(__('studio.earnings_month_desc'))
                ->color('success')
                ->icon('heroicon-m-banknotes'),
            Stat::make(__('studio.pending_quotes'), (string) $pendingQuotes)
                ->description(__('studio.pending_quotes_desc'))
                ->color('warning')
                ->icon('heroicon-m-hand-raised'),
            Stat::make(__('studio.awaiting_shipment'), (string) $awaitingShipment)
                ->description(__('studio.awaiting_shipment_desc'))
                ->color('info')
                ->icon('heroicon-m-truck'),
        ];
    }
}
