<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Support\Rupiah;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Produk';

    protected static ?string $modelLabel = 'Produk';

    protected static ?string $pluralModelLabel = 'Produk';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Produk')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Produk')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('category_id')
                            ->label('Kategori')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Kategori')
                                    ->required()
                                    ->unique(ignoreRecord: true),
                            ]),
                        Forms\Components\TextInput::make('sku')
                            ->label('SKU')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('barcode')
                            ->label('Barcode')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\Select::make('unit')
                            ->label('Satuan')
                            ->options([
                                'pcs' => 'pcs',
                                'porsi' => 'porsi',
                                'kg' => 'kg',
                                'gram' => 'gram',
                                'liter' => 'liter',
                                'botol' => 'botol',
                                'pak' => 'pak',
                                'box' => 'box',
                            ])
                            ->default('pcs')
                            ->required(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif Dijual')
                            ->default(true),
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Harga & Stok')
                    ->schema([
                        Forms\Components\TextInput::make('cost_price')
                            ->label('Harga Modal')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->required(),
                        Forms\Components\TextInput::make('sell_price')
                            ->label('Harga Jual')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->required(),
                        Forms\Components\TextInput::make('stock')
                            ->label('Stok')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        Forms\Components\TextInput::make('min_stock')
                            ->label('Stok Minimum')
                            ->numeric()
                            ->default(5)
                            ->required(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Foto Produk')
                    ->schema([
                        Forms\Components\FileUpload::make('image')
                            ->label('Gambar')
                            ->image()
                            ->disk('public')
                            ->directory('products')
                            ->maxSize(2048)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
                Forms\Components\Section::make('Varian Produk')
                    ->description('Opsional. Setiap varian memiliki harga dan stok sendiri.')
                    ->schema([
                        Forms\Components\Repeater::make('variants')
                            ->relationship()
                            ->label('')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Varian')
                                    ->required()
                                    ->maxLength(100),
                                Forms\Components\TextInput::make('sku')
                                    ->label('SKU Varian')
                                    ->maxLength(100),
                                Forms\Components\TextInput::make('cost_price')
                                    ->label('Harga Modal')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(0),
                                Forms\Components\TextInput::make('sell_price')
                                    ->label('Harga Jual')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(0)
                                    ->required(),
                                Forms\Components\TextInput::make('stock')
                                    ->label('Stok')
                                    ->numeric()
                                    ->default(0),
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Aktif')
                                    ->default(true),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('')
                    ->disk('public')
                    ->square()
                    ->size(42),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Product $record): string => (string) ($record->sku ?: $record->barcode)),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('barcode')
                    ->label('Barcode')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('cost_price')
                    ->label('Harga Modal')
                    ->formatStateUsing(fn ($state): string => Rupiah::format($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('sell_price')
                    ->label('Harga Jual')
                    ->formatStateUsing(fn ($state): string => Rupiah::format($state))
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('stock')
                    ->label('Stok')
                    ->badge()
                    ->color(fn (Product $record): string => $record->isLowStock() ? 'danger' : 'success')
                    ->description(fn (Product $record): string => 'min '.$record->min_stock.' '.$record->unit)
                    ->sortable(),
                Tables\Columns\TextColumn::make('variants_count')
                    ->label('Varian')
                    ->counts('variants')
                    ->badge()
                    ->color('info'),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktif'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name'),
                Tables\Filters\Filter::make('low_stock')
                    ->label('Stok Menipis')
                    ->query(fn (Builder $query): Builder => $query->whereColumn('stock', '<=', 'min_stock'))
                    ->toggle(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
