<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;

class ProfitLossReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Laporan Laba Rugi';

    protected static ?string $title = 'Laporan Laba Rugi';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.profit-loss-report';

    public string $startDate = '';

    public string $endDate = '';

    public $branchId = null;

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = now()->toDateString();
    }

    #[Computed]
    public function branches()
    {
        return Branch::query()->orderBy('name')->get();
    }

    private function dateRange(): array
    {
        $start = $this->startDate !== '' ? Carbon::parse($this->startDate)->startOfDay() : now()->startOfMonth();
        $end = $this->endDate !== '' ? Carbon::parse($this->endDate)->endOfDay() : now()->endOfDay();

        if ($start->gt($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [$start, $end];
    }

    #[Computed]
    public function summary(): array
    {
        [$start, $end] = $this->dateRange();

        $transactions = Transaction::query()
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$start, $end])
            ->when($this->branchId, fn ($query) => $query->where('branch_id', $this->branchId));

        $revenue = (float) (clone $transactions)->sum('total');
        $transactionCount = (int) (clone $transactions)->count();

        $cogs = (float) TransactionItem::query()
            ->whereHas('transaction', function ($query) use ($start, $end) {
                $query->where('payment_status', 'paid')
                    ->whereBetween('created_at', [$start, $end])
                    ->when($this->branchId, fn ($query) => $query->where('branch_id', $this->branchId));
            })
            ->selectRaw('COALESCE(SUM(cost_price * qty), 0) as total')
            ->value('total');

        $itemsSold = (int) TransactionItem::query()
            ->whereHas('transaction', function ($query) use ($start, $end) {
                $query->where('payment_status', 'paid')
                    ->whereBetween('created_at', [$start, $end])
                    ->when($this->branchId, fn ($query) => $query->where('branch_id', $this->branchId));
            })
            ->sum('qty');

        $expenses = (float) Expense::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->when($this->branchId, fn ($query) => $query->where('branch_id', $this->branchId))
            ->sum('amount');

        $grossProfit = $revenue - $cogs;
        $netProfit = $grossProfit - $expenses;

        return [
            'revenue' => $revenue,
            'transactions' => $transactionCount,
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'expenses' => $expenses,
            'net_profit' => $netProfit,
            'items_sold' => $itemsSold,
            'margin' => $revenue > 0 ? ($netProfit / $revenue) * 100 : 0,
            'start' => $start,
            'end' => $end,
        ];
    }

    #[Computed]
    public function dailyRows(): array
    {
        [$start, $end] = $this->dateRange();

        $revenues = Transaction::query()
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$start, $end])
            ->when($this->branchId, fn ($query) => $query->where('branch_id', $this->branchId))
            ->selectRaw('DATE(created_at) as day, SUM(total) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $cogs = TransactionItem::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->where('transactions.payment_status', 'paid')
            ->whereBetween('transactions.created_at', [$start, $end])
            ->when($this->branchId, fn ($query) => $query->where('transactions.branch_id', $this->branchId))
            ->selectRaw('DATE(transactions.created_at) as day, SUM(transaction_items.cost_price * transaction_items.qty) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $expenses = Expense::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->when($this->branchId, fn ($query) => $query->where('branch_id', $this->branchId))
            ->selectRaw('DATE(date) as day, SUM(amount) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $rows = [];
        $cursor = $start->copy()->startOfDay();

        while ($cursor->lte($end) && count($rows) < 366) {
            $key = $cursor->toDateString();
            $revenue = (float) ($revenues[$key] ?? 0);
            $cost = (float) ($cogs[$key] ?? 0);
            $expense = (float) ($expenses[$key] ?? 0);

            $rows[] = [
                'date' => $cursor->copy(),
                'revenue' => $revenue,
                'cogs' => $cost,
                'expenses' => $expense,
                'net' => $revenue - $cost - $expense,
            ];

            $cursor->addDay();
        }

        return array_reverse($rows);
    }

    #[Computed]
    public function topProducts(): array
    {
        [$start, $end] = $this->dateRange();

        return TransactionItem::query()
            ->whereHas('transaction', function ($query) use ($start, $end) {
                $query->where('payment_status', 'paid')
                    ->whereBetween('created_at', [$start, $end])
                    ->when($this->branchId, fn ($query) => $query->where('branch_id', $this->branchId));
            })
            ->selectRaw('product_name, SUM(qty) as qty, SUM(subtotal) as revenue, SUM(cost_price * qty) as cogs')
            ->groupBy('product_name')
            ->orderByDesc('qty')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->product_name,
                'qty' => (int) $row->qty,
                'revenue' => (float) $row->revenue,
                'cogs' => (float) $row->cogs,
                'profit' => (float) $row->revenue - (float) $row->cogs,
            ])
            ->all();
    }

    #[Computed]
    public function expenseRows(): array
    {
        [$start, $end] = $this->dateRange();

        return Expense::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->when($this->branchId, fn ($query) => $query->where('branch_id', $this->branchId))
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'category' => $row->category,
                'total' => (float) $row->total,
            ])
            ->all();
    }
}
