<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use App\Support\Rupiah;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentTransactions extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Transaksi Terbaru';

    protected function getTableQuery(): Builder
    {
        return Transaction::query()->with(['customer', 'user'])->latest();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice')
                    ->weight('bold')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i'),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Pelanggan')
                    ->default('Umum'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Kasir'),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->formatStateUsing(fn ($state): string => Rupiah::format($state))
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => Transaction::STATUSES[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'failed', 'expired' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}
