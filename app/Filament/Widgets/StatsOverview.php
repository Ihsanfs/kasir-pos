<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Support\Rupiah;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $todayQuery = fn () => Transaction::query()
            ->where('payment_status', 'paid')
            ->whereDate('created_at', today());

        $revenueToday = (float) $todayQuery()->sum('total');
        $transactionsToday = (int) $todayQuery()->count();

        $cogsToday = (float) TransactionItem::query()
            ->whereHas('transaction', fn ($query) => $query
                ->where('payment_status', 'paid')
                ->whereDate('created_at', today()))
            ->selectRaw('COALESCE(SUM(cost_price * qty), 0) as total')
            ->value('total');

        $grossToday = $revenueToday - $cogsToday;

        $monthExpenses = (float) Expense::query()
            ->whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->sum('amount');

        $lowStockCount = (int) Product::query()->active()->lowStock()->count();

        $yesterdayRevenue = (float) Transaction::query()
            ->where('payment_status', 'paid')
            ->whereDate('created_at', today()->subDay())
            ->sum('total');

        $difference = $yesterdayRevenue > 0
            ? (($revenueToday - $yesterdayRevenue) / $yesterdayRevenue) * 100
            : 0;

        return [
            Stat::make('Penjualan Hari Ini', Rupiah::format($revenueToday))
                ->description($transactionsToday.' transaksi • '.($difference >= 0 ? '+' : '').number_format($difference, 1).'% vs kemarin')
                ->descriptionIcon($difference >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($difference >= 0 ? 'success' : 'danger'),
            Stat::make('Laba Kotor Hari Ini', Rupiah::format($grossToday))
                ->description('Omset dikurangi HPP')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($grossToday >= 0 ? 'success' : 'danger'),
            Stat::make('Pengeluaran Bulan Ini', Rupiah::format($monthExpenses))
                ->description(now()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),
            Stat::make('Stok Menipis', $lowStockCount.' produk')
                ->description($lowStockCount > 0 ? 'Perlu segera restock' : 'Semua stok aman')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStockCount > 0 ? 'danger' : 'success'),
        ];
    }
}
