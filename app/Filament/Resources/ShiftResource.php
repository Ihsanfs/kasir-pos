<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShiftResource\Pages;
use App\Models\Shift;
use App\Support\Rupiah;
use Filament\Tables;
use Filament\Tables\Table;

class ShiftResource extends \Filament\Resources\Resource
{
    protected static ?string $model = Shift::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Kasir';

    protected static ?string $navigationLabel = 'Shift & Tutup Kasir';

    protected static ?string $modelLabel = 'Shift';

    protected static ?string $pluralModelLabel = 'Shift';

    protected static ?int $navigationSort = 4;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('opened_at')
                    ->label('Dibuka')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('closed_at')
                    ->label('Ditutup')
                    ->dateTime('d M Y H:i')
                    ->placeholder('Masih berjalan'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Kasir')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('opening_cash')
                    ->label('Kas Awal')
                    ->formatStateUsing(fn ($state): string => Rupiah::format($state)),
                Tables\Columns\TextColumn::make('total_cash_sales')
                    ->label('Penjualan Tunai')
                    ->formatStateUsing(fn ($state): string => Rupiah::format($state)),
                Tables\Columns\TextColumn::make('expected_cash')
                    ->label('Kas Seharusnya')
                    ->formatStateUsing(fn ($state): string => Rupiah::format($state))
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('closing_cash')
                    ->label('Kas Aktual')
                    ->formatStateUsing(fn ($state): string => Rupiah::format($state))
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('difference')
                    ->label('Selisih')
                    ->formatStateUsing(fn ($state): string => Rupiah::format($state))
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state === null => 'gray',
                        (float) $state < 0 => 'danger',
                        (float) $state > 0 => 'warning',
                        default => 'success',
                    })
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('total_transactions')
                    ->label('Transaksi')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => $state === 'open' ? 'Terbuka' : 'Ditutup')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'open' ? 'success' : 'gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(['open' => 'Terbuka', 'closed' => 'Ditutup']),
            ])
            ->defaultSort('opened_at', 'desc');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Shift::query()->where('status', 'open')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShifts::route('/'),
        ];
    }
}
