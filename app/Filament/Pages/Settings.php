<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Pengaturan Aplikasi';

    protected static ?string $title = 'Pengaturan Aplikasi';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.settings';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'store_name' => Setting::get('store_name', 'Kasir 2026'),
            'store_address' => Setting::get('store_address', ''),
            'store_phone' => Setting::get('store_phone', ''),
            'receipt_footer' => Setting::get('receipt_footer', 'Terima kasih atas kunjungan Anda'),
            'tax_percent' => (float) Setting::get('tax_percent', 0),
            'low_stock_threshold' => (int) Setting::get('low_stock_threshold', 5),
            'midtrans_enabled' => Setting::bool('midtrans_enabled'),
            'midtrans_is_production' => Setting::bool('midtrans_is_production'),
            'midtrans_server_key' => Setting::get('midtrans_server_key', ''),
            'midtrans_client_key' => Setting::get('midtrans_client_key', ''),
            'payment_cash' => Setting::bool('payment_cash', true),
            'payment_qris' => Setting::bool('payment_qris', true),
            'payment_transfer' => Setting::bool('payment_transfer', true),
            'payment_ewallet' => Setting::bool('payment_ewallet', true),
            'payment_card' => Setting::bool('payment_card', true),
            'ai_provider' => Setting::get('ai_provider', 'local'),
            'ai_model' => Setting::get('ai_model', 'gpt-4o-mini'),
            'ai_api_key' => Setting::get('ai_api_key', ''),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Pengaturan')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Toko & Struk')
                            ->icon('heroicon-o-building-storefront')
                            ->schema([
                                Forms\Components\TextInput::make('store_name')
                                    ->label('Nama Toko')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('store_phone')
                                    ->label('Telepon Toko')
                                    ->tel()
                                    ->maxLength(30),
                                Forms\Components\Textarea::make('store_address')
                                    ->label('Alamat Toko')
                                    ->rows(2)
                                    ->columnSpanFull(),
                                Forms\Components\Textarea::make('receipt_footer')
                                    ->label('Pesan di Struk')
                                    ->rows(2)
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('tax_percent')
                                    ->label('Pajak (%)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->default(0)
                                    ->suffix('%'),
                                Forms\Components\TextInput::make('low_stock_threshold')
                                    ->label('Ambang Stok Menipis')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(5),
                            ])
                            ->columns(2),
                        Forms\Components\Tabs\Tab::make('Pembayaran Online')
                            ->icon('heroicon-o-credit-card')
                            ->schema([
                                Forms\Components\Toggle::make('midtrans_enabled')
                                    ->label('Aktifkan Midtrans (QRIS / E-Wallet)')
                                    ->helperText('Jika aktif, pembayaran non-tunai akan diarahkan ke halaman Midtrans Snap.')
                                    ->columnSpanFull(),
                                Forms\Components\Toggle::make('midtrans_is_production')
                                    ->label('Mode Produksi')
                                    ->helperText('Matikan untuk menggunakan sandbox.')
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('midtrans_server_key')
                                    ->label('Server Key')
                                    ->password()
                                    ->revealable()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('midtrans_client_key')
                                    ->label('Client Key')
                                    ->maxLength(255),
                                Forms\Components\Placeholder::make('midtrans_callback')
                                    ->label('URL Notifikasi (Midtrans)')
                                    ->content(fn (): string => url('/midtrans/notification'))
                                    ->columnSpanFull(),
                                Forms\Components\Section::make('Metode Pembayaran yang Tersedia')
                                    ->description('Aktifkan atau nonaktifkan metode pembayaran yang akan muncul di kasir.')
                                    ->schema([
                                        Forms\Components\Toggle::make('payment_cash')
                                            ->label('Tunai')
                                            ->helperText('Pembayaran tunai langsung')
                                            ->columnSpanFull(),
                                        Forms\Components\Toggle::make('payment_qris')
                                            ->label('QRIS')
                                            ->helperText('Pembayaran melalui kode QR QRIS')
                                            ->columnSpanFull(),
                                        Forms\Components\Toggle::make('payment_transfer')
                                            ->label('Transfer Bank')
                                            ->helperText('Transfer rekening bank')
                                            ->columnSpanFull(),
                                        Forms\Components\Toggle::make('payment_ewallet')
                                            ->label('E-Wallet')
                                            ->helperText('OVO, GoPay, Dana, dll.')
                                            ->columnSpanFull(),
                                        Forms\Components\Toggle::make('payment_card')
                                            ->label('Kartu Debit/Kredit')
                                            ->helperText('Kartu debit dan kartu kredit')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(1),
                            ])
                            ->columns(2),
                        Forms\Components\Tabs\Tab::make('Asisten AI')
                            ->icon('heroicon-o-sparkles')
                            ->schema([
                                Forms\Components\Select::make('ai_provider')
                                    ->label('Provider AI')
                                    ->options([
                                        'local' => 'Analisis Lokal (tanpa API)',
                                        'openai' => 'OpenAI',
                                        'anthropic' => 'Anthropic Claude',
                                    ])
                                    ->required()
                                    ->live(),
                                Forms\Components\TextInput::make('ai_model')
                                    ->label('Model')
                                    ->placeholder('gpt-4o-mini / claude-3-5-sonnet-latest')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('ai_api_key')
                                    ->label('API Key')
                                    ->password()
                                    ->revealable()
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                Forms\Components\Placeholder::make('ai_info')
                                    ->label('')
                                    ->content('Data bisnis (omset, laba, stok, pengeluaran, anomali) dikirim sebagai konteks ke provider AI hanya saat Anda mengirim pertanyaan.')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        $groups = [
            'store_name' => 'general',
            'store_address' => 'general',
            'store_phone' => 'general',
            'receipt_footer' => 'receipt',
            'tax_percent' => 'receipt',
            'low_stock_threshold' => 'inventory',
            'midtrans_enabled' => 'payment',
            'midtrans_is_production' => 'payment',
            'midtrans_server_key' => 'payment',
            'midtrans_client_key' => 'payment',
            'payment_cash' => 'payment',
            'payment_qris' => 'payment',
            'payment_transfer' => 'payment',
            'payment_ewallet' => 'payment',
            'payment_card' => 'payment',
            'ai_provider' => 'ai',
            'ai_model' => 'ai',
            'ai_api_key' => 'ai',
        ];

        foreach ($groups as $key => $group) {
            $value = $state[$key] ?? null;

            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }

            Setting::set($key, $value, $group);
        }

        Setting::forgetCache();

        Notification::make()
            ->title('Pengaturan disimpan')
            ->success()
            ->send();
    }
}
