<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\ChartWidget;

class SalesChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Tren Penjualan 14 Hari Terakhir';

    protected function getData(): array
    {
        $start = now()->subDays(13)->startOfDay();

        $rows = Transaction::query()
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, SUM(total) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $labels = [];
        $data = [];

        foreach (range(13, 0) as $offset) {
            $date = now()->subDays($offset);

            $labels[] = $date->format('d M');
            $data[] = (float) ($rows[$date->toDateString()] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Penjualan',
                    'data' => $data,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.15)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
