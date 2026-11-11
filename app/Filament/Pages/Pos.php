<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\Transaction;
use App\Services\MidtransService;
use App\Services\PosService;
use App\Support\Rupiah;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;

class Pos extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'Kasir';

    protected static ?string $navigationLabel = 'Kasir (POS)';

    protected static ?string $title = 'Kasir (POS)';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.pos';

    public string $search = '';

    /** @var array<string, array<string, mixed>> */
    public array $cart = [];

    public $customerId = null;

    public $branchId = null;

    public string $paymentMethod = 'cash';

    public $discount = 0;

    public $paid = 0;

    public string $note = '';

    public ?string $paymentUrl = null;

    public ?string $paymentQrData = null;

    public function mount(): void
    {
        $this->branchId = Auth::user()?->branch_id
            ?? Branch::query()->where('is_active', true)->value('id');
    }

    #[Computed]
    public function paymentMethods(): array
    {
        $all = Transaction::PAYMENT_METHODS;

        $enabled = [];
        foreach (['cash', 'qris', 'transfer', 'ewallet', 'card'] as $method) {
            try {
                $enabled[$method] = Setting::bool("payment_{$method}", true);
            } catch (\Throwable) {
                $enabled[$method] = true;
            }
        }

        $icons = [
            'cash' => '💵',
            'qris' => '📱',
            'transfer' => '🏦',
            'ewallet' => '📲',
            'card' => '💳',
        ];

        $colors = [
            'cash' => '#059669',
            'qris' => '#2563eb',
            'transfer' => '#6366f1',
            'ewallet' => '#7c3aed',
            'card' => '#db2777',
        ];

        $result = [];

        foreach ($all as $value => $label) {
            if (! ($enabled[$value] ?? false)) {
                continue;
            }

            $result[] = [
                'value' => $value,
                'label' => $label,
                'icon' => $icons[$value] ?? '💳',
                'color' => $colors[$value] ?? '#6b7280',
            ];
        }

        return $result;
    }

    #[Computed]
    public function currentShift(): ?Shift
    {
        return Shift::query()
            ->where('user_id', Auth::id())
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();
    }

    #[Computed]
    public function branches()
    {
        return Branch::query()->where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function customers()
    {
        return Customer::query()->orderBy('name')->get();
    }

    #[Computed]
    public function products()
    {
        return Product::query()
            ->active()
            ->with([
                'category',
                'variants' => fn ($query) => $query->where('is_active', true)->orderBy('name'),
            ])
            ->when($this->search !== '', function ($query) {
                $keyword = '%'.trim($this->search).'%';

                $query->where(function ($query) use ($keyword) {
                    $query->where('name', 'like', $keyword)
                        ->orWhere('barcode', 'like', $keyword)
                        ->orWhere('sku', 'like', $keyword);
                });
            })
            ->orderBy('name')
            ->limit(120)
            ->get();
    }

    #[Computed]
    public function subtotal(): float
    {
        return (float) collect($this->cart)->sum(fn (array $item) => $item['price'] * $item['qty']);
    }

    #[Computed]
    public function discountAmount(): float
    {
        return min((float) $this->discount, $this->subtotal);
    }

    #[Computed]
    public function taxPercent(): float
    {
        return (float) Setting::get('tax_percent', 0);
    }

    #[Computed]
    public function taxAmount(): float
    {
        return round(($this->subtotal - $this->discountAmount) * $this->taxPercent / 100);
    }

    #[Computed]
    public function total(): float
    {
        return max(0, round($this->subtotal - $this->discountAmount + $this->taxAmount));
    }

    #[Computed]
    public function change(): float
    {
        return max(0, round((float) $this->paid - $this->total));
    }

    #[Computed]
    public function cartCount(): int
    {
        return (int) collect($this->cart)->sum('qty');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openShift')
                ->label('Buka Shift')
                ->icon('heroicon-o-play-circle')
                ->color('success')
                ->visible(fn (): bool => $this->currentShift === null)
                ->modalHeading('Buka Shift Kasir')
                ->modalDescription('Masukkan jumlah uang tunai awal di laci kasir.')
                ->form([
                    TextInput::make('opening_cash')
                        ->label('Kas Awal')
                        ->numeric()
                        ->prefix('Rp')
                        ->default(0)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    Shift::create([
                        'user_id' => Auth::id(),
                        'branch_id' => $this->branchId,
                        'opened_at' => now(),
                        'opening_cash' => (float) ($data['opening_cash'] ?? 0),
                        'status' => 'open',
                    ]);

                    unset($this->currentShift);

                    Notification::make()
                        ->title('Shift dibuka')
                        ->body('Selamat bekerja. Jangan lupa tutup kasir di akhir shift.')
                        ->success()
                        ->send();
                }),
            Action::make('closeShift')
                ->label('Tutup Kasir')
                ->icon('heroicon-o-lock-closed')
                ->color('danger')
                ->visible(fn (): bool => $this->currentShift !== null)
                ->modalHeading('Tutup Kasir & Rekonsiliasi')
                ->modalDescription('Hitung uang tunai aktual di laci, lalu masukkan jumlahnya.')
                ->form([
                    TextInput::make('closing_cash')
                        ->label('Kas Aktual di Laci')
                        ->numeric()
                        ->prefix('Rp')
                        ->required(),
                    Textarea::make('notes')
                        ->label('Catatan')
                        ->rows(2),
                ])
                ->action(function (array $data): void {
                    $shift = $this->currentShift;

                    if (! $shift) {
                        return;
                    }

                    $sales = (float) $shift->transactions()->where('payment_status', 'paid')->sum('total');
                    $cashSales = (float) $shift->transactions()
                        ->where('payment_status', 'paid')
                        ->where('payment_method', 'cash')
                        ->sum('total');
                    $expected = (float) $shift->opening_cash + $cashSales;
                    $closing = (float) ($data['closing_cash'] ?? 0);

                    $shift->update([
                        'closed_at' => now(),
                        'closing_cash' => $closing,
                        'expected_cash' => $expected,
                        'total_sales' => $sales,
                        'total_cash_sales' => $cashSales,
                        'total_transactions' => $shift->transactions()->count(),
                        'difference' => $closing - $expected,
                        'notes' => $data['notes'] ?? null,
                        'status' => 'closed',
                    ]);

                    unset($this->currentShift);

                    Notification::make()
                        ->title('Shift ditutup')
                        ->body('Total penjualan '.Rupiah::format($sales).', selisih kas '.Rupiah::format($closing - $expected).'.')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function addToCart(int $productId, ?int $variantId = null): void
    {
        $product = Product::query()->active()->with('variants')->find($productId);

        if (! $product) {
            return;
        }

        $variant = null;

        if ($variantId) {
            $variant = $product->variants->firstWhere('id', $variantId);

            if (! $variant) {
                return;
            }
        }

        $key = $product->id.':'.($variant?->id ?? '0');
        $price = (float) ($variant?->sell_price ?? $product->sell_price);
        $stock = (int) ($variant?->stock ?? $product->stock);

        if ($stock <= 0) {
            Notification::make()
                ->title('Stok habis')
                ->body($product->name.($variant ? ' - '.$variant->name : '').' tidak memiliki stok.')
                ->warning()
                ->send();

            return;
        }

        if (isset($this->cart[$key])) {
            if ($this->cart[$key]['qty'] >= $stock) {
                Notification::make()->title('Stok tidak mencukupi')->warning()->send();

                return;
            }

            $this->cart[$key]['qty']++;
        } else {
            $this->cart[$key] = [
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'name' => $product->name,
                'variant' => $variant?->name,
                'price' => $price,
                'stock' => $stock,
                'qty' => 1,
                'unit' => $product->unit,
            ];
        }

        $this->search = '';
    }

    public function increment(string $key): void
    {
        if (! isset($this->cart[$key])) {
            return;
        }

        if ($this->cart[$key]['qty'] >= $this->cart[$key]['stock']) {
            Notification::make()->title('Stok tidak mencukupi')->warning()->send();

            return;
        }

        $this->cart[$key]['qty']++;
    }

    public function decrement(string $key): void
    {
        if (! isset($this->cart[$key])) {
            return;
        }

        if ($this->cart[$key]['qty'] <= 1) {
            $this->removeItem($key);

            return;
        }

        $this->cart[$key]['qty']--;
    }

    public function removeItem(string $key): void
    {
        unset($this->cart[$key]);
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->discount = 0;
        $this->paid = 0;
    }

    public function setPaid(float $amount): void
    {
        $this->paid = (int) round($amount);
    }

    public function checkout(): void
    {
        if (! $this->currentShift) {
            Notification::make()
                ->title('Shift belum dibuka')
                ->body('Klik tombol "Buka Shift" terlebih dahulu sebelum memproses transaksi.')
                ->warning()
                ->send();

            return;
        }

        if (empty($this->cart)) {
            Notification::make()->title('Keranjang kosong')->warning()->send();

            return;
        }

        if ($this->paymentMethod === 'cash' && (float) $this->paid < $this->total) {
            Notification::make()
                ->title('Pembayaran kurang')
                ->body('Uang dibayar masih kurang '.Rupiah::format($this->total - (float) $this->paid).'.')
                ->warning()
                ->send();

            return;
        }

        $useMidtrans = $this->paymentMethod !== 'cash' && app(MidtransService::class)->isEnabled();

        try {
            $transaction = app(PosService::class)->checkout([
                'items' => collect($this->cart)->map(fn (array $item) => [
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['variant_id'],
                    'qty' => $item['qty'],
                ])->values()->all(),
                'branch_id' => $this->branchId,
                'user_id' => Auth::id(),
                'customer_id' => $this->customerId ?: null,
                'shift_id' => $this->currentShift->id,
                'discount' => (float) $this->discount,
                'paid' => (float) $this->paid,
                'payment_method' => $this->paymentMethod,
                'payment_status' => $useMidtrans ? 'pending' : 'paid',
                'note' => $this->note,
            ]);
        } catch (ValidationException $exception) {
            Notification::make()
                ->title('Transaksi gagal')
                ->body(collect($exception->errors())->flatten()->first() ?? 'Periksa kembali data transaksi.')
                ->danger()
                ->send();

            return;
        } catch (\Throwable $exception) {
            Notification::make()
                ->title('Transaksi gagal')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        $paymentUrl = null;

        if ($useMidtrans) {
            try {
                $paymentUrl = app(MidtransService::class)->createSnap($transaction);
            } catch (\Throwable $exception) {
                Notification::make()
                    ->title('Gagal membuat pembayaran online')
                    ->body($exception->getMessage())
                    ->warning()
                    ->send();
            }
        }

        if ($paymentUrl) {
            $this->paymentUrl = $paymentUrl;
            $this->paymentQrData = $paymentUrl;

            $this->dispatch('open-url', url: $paymentUrl);

            Notification::make()
                ->title('Transaksi dibuat')
                ->body('Invoice '.$transaction->invoice_number.'. Selesaikan pembayaran di tab yang terbuka.')
                ->success()
                ->send();
        } else {
            $this->paymentUrl = null;
            $this->paymentQrData = null;

            $this->dispatch('print-receipt', url: route('pos.print', $transaction));

            Notification::make()
                ->title('Transaksi berhasil')
                ->body('Invoice '.$transaction->invoice_number.' • '.Rupiah::format($transaction->total))
                ->success()
                ->send();
        }

        $this->resetCheckout();
    }

    private function resetCheckout(): void
    {
        $this->cart = [];
        $this->discount = 0;
        $this->paid = 0;
        $this->note = '';
        $this->customerId = null;
        $this->paymentMethod = 'cash';
        $this->paymentUrl = null;
        $this->paymentQrData = null;
        $this->search = '';

        unset($this->subtotal, $this->discountAmount, $this->taxAmount, $this->total, $this->change, $this->cartCount);
    }
}
