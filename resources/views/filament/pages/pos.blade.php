@use('App\Support\Rupiah')
@use('App\Models\Transaction')

<x-filament-panels::page>
    <style>
        .pos-app {
            --pos-bg: #f8fafc;
            --pos-card: #ffffff;
            --pos-border: #e2e8f0;
            --pos-text: #0f172a;
            --pos-muted: #64748b;
            --pos-accent: #059669;
            --pos-accent-soft: #ecfdf5;
            --pos-danger: #dc2626;
            --pos-warning: #d97706;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .dark .pos-app {
            --pos-bg: #0f172a;
            --pos-card: #1e293b;
            --pos-border: #334155;
            --pos-text: #f1f5f9;
            --pos-muted: #94a3b8;
            --pos-accent: #34d399;
            --pos-accent-soft: rgba(52, 211, 153, 0.12);
            --pos-danger: #f87171;
            --pos-warning: #fbbf24;
        }

        .pos-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .75rem;
        }

        .pos-search {
            position: relative;
            flex: 1 1 320px;
        }

        .pos-search-icon {
            position: absolute;
            inset-inline-start: .85rem;
            inset-block-start: 50%;
            transform: translateY(-50%);
            color: var(--pos-muted);
            font-size: 1rem;
            pointer-events: none;
        }

        .pos-search input {
            width: 100%;
            padding: .8rem 1rem .8rem 2.5rem;
            border-radius: .85rem;
            border: 1px solid var(--pos-border);
            background: var(--pos-card);
            color: var(--pos-text);
            font-size: .95rem;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }

        .pos-search input:focus {
            border-color: var(--pos-accent);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--pos-accent) 18%, transparent);
        }

        .pos-toolbar-right {
            display: flex;
            align-items: center;
            gap: .5rem;
            flex-wrap: wrap;
        }

        .pos-select {
            appearance: none;
            padding: .62rem 2rem .62rem .8rem;
            border-radius: .7rem;
            border: 1px solid var(--pos-border);
            background-color: var(--pos-card);
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%2364748b'%3E%3Cpath fill-rule='evenodd' d='M5.23 7.21a.75.75 0 011.06.02L10 11.06l3.71-3.83a.75.75 0 111.08 1.04l-4.25 4.39a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z' clip-rule='evenodd'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right .6rem center;
            background-size: 1.1rem;
            color: var(--pos-text);
            font-size: .875rem;
            outline: none;
        }

        .pos-shift {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .55rem .85rem;
            border-radius: 999px;
            font-size: .78rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .pos-shift::before {
            content: "";
            width: .5rem;
            height: .5rem;
            border-radius: 999px;
            background: currentColor;
        }

        .pos-shift.is-open {
            background: var(--pos-accent-soft);
            color: var(--pos-accent);
        }

        .pos-shift.is-closed {
            background: color-mix(in srgb, var(--pos-warning) 14%, transparent);
            color: var(--pos-warning);
        }

        .pos-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 390px;
            gap: 1rem;
            align-items: start;
        }

        .pos-products {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(165px, 1fr));
            gap: .7rem;
            max-height: calc(100vh - 250px);
            overflow-y: auto;
            padding: .15rem .25rem .25rem .1rem;
        }

        .pos-products::-webkit-scrollbar,
        .pos-cart-items::-webkit-scrollbar {
            width: 6px;
        }

        .pos-products::-webkit-scrollbar-thumb,
        .pos-cart-items::-webkit-scrollbar-thumb {
            background: var(--pos-border);
            border-radius: 999px;
        }

        .pos-product-card {
            display: flex;
            flex-direction: column;
            gap: .55rem;
            padding: .8rem;
            border-radius: .85rem;
            border: 1px solid var(--pos-border);
            background: var(--pos-card);
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            transition: transform .12s, box-shadow .12s, border-color .12s;
        }

        .pos-product-card:hover {
            transform: translateY(-1px);
            border-color: color-mix(in srgb, var(--pos-accent) 40%, var(--pos-border));
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
        }

        .pos-product-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .4rem;
        }

        .pos-product-name {
            font-size: .86rem;
            font-weight: 700;
            line-height: 1.25;
            color: var(--pos-text);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .pos-product-stock {
            flex-shrink: 0;
            font-size: .68rem;
            font-weight: 600;
            padding: .18rem .4rem;
            border-radius: 999px;
            background: var(--pos-accent-soft);
            color: var(--pos-accent);
        }

        .pos-product-stock.is-low {
            background: color-mix(in srgb, var(--pos-danger) 14%, transparent);
            color: var(--pos-danger);
        }

        .pos-product-add {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .3rem;
            width: 100%;
            padding: .55rem .1rem .1rem;
            border: none;
            background: transparent;
            cursor: pointer;
            border-top: 1px dashed var(--pos-border);
            margin-top: auto;
        }

        .pos-product-add:disabled {
            cursor: not-allowed;
            opacity: .5;
        }

        .pos-price {
            font-size: .82rem;
            font-weight: 800;
            color: var(--pos-accent);
        }

        .pos-add {
            font-size: .68rem;
            font-weight: 700;
            color: var(--pos-muted);
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        .pos-variants {
            display: flex;
            flex-direction: column;
            gap: .35rem;
            border-top: 1px dashed var(--pos-border);
            padding-top: .5rem;
            margin-top: auto;
        }

        .pos-variant {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .35rem;
            padding: .4rem .55rem;
            border-radius: .5rem;
            border: 1px solid var(--pos-border);
            background: var(--pos-bg);
            color: var(--pos-text);
            font-size: .72rem;
            cursor: pointer;
            text-align: left;
        }

        .pos-variant:hover:not(:disabled) {
            border-color: var(--pos-accent);
            color: var(--pos-accent);
        }

        .pos-variant:disabled {
            opacity: .45;
            cursor: not-allowed;
        }

        .pos-variant strong {
            color: var(--pos-accent);
        }

        .pos-empty {
            grid-column: 1 / -1;
            padding: 2rem;
            text-align: center;
            color: var(--pos-muted);
            border: 1px dashed var(--pos-border);
            border-radius: .85rem;
            background: var(--pos-card);
        }

        .pos-cart {
            position: sticky;
            top: 1rem;
            display: flex;
            flex-direction: column;
            border-radius: 1rem;
            border: 1px solid var(--pos-border);
            background: var(--pos-card);
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }

        .pos-cart-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: .9rem 1rem;
            border-bottom: 1px solid var(--pos-border);
            font-weight: 800;
            font-size: .95rem;
            color: var(--pos-text);
        }

        .pos-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.4rem;
            height: 1.4rem;
            padding: 0 .35rem;
            border-radius: 999px;
            background: var(--pos-accent);
            color: #fff;
            font-size: .68rem;
            font-weight: 800;
            margin-inline-start: .3rem;
        }

        .pos-link {
            border: none;
            background: transparent;
            color: var(--pos-danger);
            font-size: .72rem;
            font-weight: 600;
            cursor: pointer;
        }

        .pos-cart-items {
            display: flex;
            flex-direction: column;
            gap: .55rem;
            padding: .75rem;
            max-height: 34vh;
            overflow-y: auto;
        }

        .pos-cart-item {
            display: grid;
            grid-template-columns: 1fr auto auto auto;
            align-items: center;
            gap: .5rem;
            padding: .55rem .6rem;
            border-radius: .65rem;
            background: var(--pos-bg);
            border: 1px solid transparent;
        }

        .pos-cart-item:hover {
            border-color: var(--pos-border);
        }

        .pos-cart-name {
            font-size: .8rem;
            font-weight: 700;
            color: var(--pos-text);
            line-height: 1.2;
        }

        .pos-cart-name small {
            font-weight: 500;
            color: var(--pos-muted);
        }

        .pos-cart-meta {
            font-size: .68rem;
            color: var(--pos-muted);
            margin-top: .15rem;
        }

        .pos-qty {
            display: flex;
            align-items: center;
            gap: .15rem;
            background: var(--pos-card);
            border: 1px solid var(--pos-border);
            border-radius: .5rem;
            overflow: hidden;
        }

        .pos-qty button {
            width: 1.5rem;
            height: 1.6rem;
            border: none;
            background: transparent;
            color: var(--pos-text);
            font-weight: 800;
            cursor: pointer;
            line-height: 1;
        }

        .pos-qty button:hover {
            background: var(--pos-accent-soft);
            color: var(--pos-accent);
        }

        .pos-qty span {
            min-width: 1.4rem;
            text-align: center;
            font-size: .78rem;
            font-weight: 700;
            color: var(--pos-text);
        }

        .pos-line-total {
            font-size: .75rem;
            font-weight: 800;
            color: var(--pos-text);
            white-space: nowrap;
        }

        .pos-remove {
            border: none;
            background: transparent;
            color: var(--pos-muted);
            font-size: 1.05rem;
            line-height: 1;
            cursor: pointer;
            padding: 0 .1rem;
        }

        .pos-remove:hover {
            color: var(--pos-danger);
        }

        .pos-empty-cart {
            padding: 2rem 1rem;
            text-align: center;
            font-size: .8rem;
            color: var(--pos-muted);
        }

        .pos-checkout {
            display: flex;
            flex-direction: column;
            gap: .6rem;
            padding: .9rem 1rem 1rem;
            border-top: 1px solid var(--pos-border);
            background: color-mix(in srgb, var(--pos-bg) 55%, var(--pos-card));
        }

        .pos-field {
            display: flex;
            flex-direction: column;
            gap: .3rem;
        }

        .pos-field label {
            font-size: .7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--pos-muted);
        }

        .pos-field .pos-select,
        .pos-input {
            width: 100%;
        }

        .pos-input {
            padding: .6rem .75rem;
            border-radius: .65rem;
            border: 1px solid var(--pos-border);
            background: var(--pos-card);
            color: var(--pos-text);
            font-size: .9rem;
            font-weight: 600;
            outline: none;
        }

        .pos-input:focus {
            border-color: var(--pos-accent);
        }

        .pos-input-sm {
            max-width: 110px;
            padding: .3rem .5rem;
            text-align: right;
            font-size: .8rem;
        }

        .pos-summary {
            display: flex;
            flex-direction: column;
            gap: .35rem;
            padding: .7rem .8rem;
            border-radius: .7rem;
            background: var(--pos-card);
            border: 1px dashed var(--pos-border);
        }

        .pos-summary > div {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            font-size: .78rem;
            color: var(--pos-muted);
        }

        .pos-summary span:last-child {
            color: var(--pos-text);
            font-weight: 600;
        }

        .pos-summary .pos-total {
            padding-top: .4rem;
            margin-top: .15rem;
            border-top: 1px solid var(--pos-border);
            font-size: .92rem;
            font-weight: 800;
            color: var(--pos-text);
        }

        .pos-summary .pos-total span:last-child {
            color: var(--pos-accent);
            font-size: 1.02rem;
            font-weight: 900;
        }

        .pos-quick {
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
            margin-top: .3rem;
        }

        .pos-quick button {
            padding: .32rem .6rem;
            border-radius: 999px;
            border: 1px solid var(--pos-border);
            background: var(--pos-card);
            color: var(--pos-text);
            font-size: .7rem;
            font-weight: 700;
            cursor: pointer;
        }

        .pos-quick button:hover {
            border-color: var(--pos-accent);
            color: var(--pos-accent);
            background: var(--pos-accent-soft);
        }

        .pos-change {
            font-size: .78rem;
            color: var(--pos-muted);
            margin-top: .2rem;
        }

        .pos-change strong {
            color: var(--pos-accent);
            font-weight: 800;
        }

        .pos-checkout-btn {
            width: 100%;
            padding: .8rem 1rem;
            border: none;
            border-radius: .75rem;
            background: var(--pos-accent);
            color: #fff;
            font-size: .9rem;
            font-weight: 800;
            letter-spacing: .02em;
            cursor: pointer;
            transition: filter .15s, transform .12s;
            box-shadow: 0 8px 18px color-mix(in srgb, var(--pos-accent) 30%, transparent);
        }

        .pos-checkout-btn:hover:not(:disabled) {
            filter: brightness(1.05);
            transform: translateY(-1px);
        }

        .pos-checkout-btn:disabled {
            opacity: .6;
            cursor: wait;
        }

        .pos-hint {
            display: flex;
            align-items: center;
            gap: .5rem;
            padding: .65rem .85rem;
            border-radius: .7rem;
            background: color-mix(in srgb, var(--pos-warning) 12%, transparent);
            color: var(--pos-warning);
            font-size: .78rem;
            font-weight: 600;
        }

        @media (max-width: 1100px) {
            .pos-grid {
                grid-template-columns: 1fr;
            }

            .pos-cart {
                position: static;
            }

            .pos-products {
                max-height: none;
            }
        }
    </style>

    <div class="pos-app">
        <div class="pos-toolbar">
            <div class="pos-search">
                <span class="pos-search-icon">⌕</span>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Cari nama produk, barcode, atau SKU..."
                    autofocus
                >
            </div>

            <div class="pos-toolbar-right">
                <select class="pos-select" wire:model.live="branchId">
                    @foreach ($this->branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>

                <span class="pos-shift {{ $this->currentShift ? 'is-open' : 'is-closed' }}">
                    {{ $this->currentShift ? 'Shift terbuka' : 'Shift belum dibuka' }}
                </span>
            </div>
        </div>

        @if (! $this->currentShift)
            <div class="pos-hint">
                <span>⚠</span>
                <span>Buka shift terlebih dahulu (tombol "Buka Shift" di kanan atas) sebelum memproses transaksi.</span>
            </div>
        @endif

        <div class="pos-grid">
            <div class="pos-products">
                @forelse ($this->products as $product)
                    @php
                        $hasVariants = $product->variants->isNotEmpty();
                        $stockDisplay = $hasVariants ? $product->variants->sum('stock') : $product->stock;
                        $isLow = $stockDisplay <= $product->min_stock;
                    @endphp

                    <div class="pos-product-card" wire:key="product-{{ $product->id }}">
                        <div class="pos-product-head">
                            <div class="pos-product-name">{{ $product->name }}</div>
                            <div class="pos-product-stock {{ $isLow ? 'is-low' : '' }}">
                                {{ $hasVariants ? $product->variants->count().' varian' : 'Stok '.$product->stock }}
                            </div>
                        </div>

                        @if ($hasVariants)
                            <div class="pos-variants">
                                @foreach ($product->variants as $variant)
                                    <button
                                        type="button"
                                        class="pos-variant"
                                        wire:key="variant-{{ $variant->id }}"
                                        wire:click="addToCart({{ $product->id }}, {{ $variant->id }})"
                                        @disabled($variant->stock <= 0)
                                    >
                                        <span>{{ $variant->name }} <small>({{ $variant->stock }})</small></span>
                                        <strong>{{ Rupiah::format($variant->sell_price) }}</strong>
                                    </button>
                                @endforeach
                            </div>
                        @else
                            <button
                                type="button"
                                class="pos-product-add"
                                wire:click="addToCart({{ $product->id }})"
                                @disabled($product->stock <= 0)
                            >
                                <span class="pos-price">{{ Rupiah::format($product->sell_price) }}</span>
                                <span class="pos-add">{{ $product->stock > 0 ? '+ Tambah' : 'Habis' }}</span>
                            </button>
                        @endif
                    </div>
                @empty
                    <div class="pos-empty">
                        Produk tidak ditemukan. Coba kata kunci lain atau tambahkan produk di menu Master Data.
                    </div>
                @endforelse
            </div>

            <div class="pos-cart">
                <div class="pos-cart-head">
                    <div>
                        Keranjang
                        <span class="pos-badge">{{ $this->cartCount }}</span>
                    </div>

                    @if (count($cart) > 0)
                        <button type="button" class="pos-link" wire:click="clearCart">Kosongkan</button>
                    @endif
                </div>

                <div class="pos-cart-items">
                    @forelse ($cart as $key => $item)
                        <div class="pos-cart-item" wire:key="cart-{{ $key }}">
                            <div>
                                <div class="pos-cart-name">
                                    {{ $item['name'] }}
                                    @if ($item['variant'])
                                        <small>({{ $item['variant'] }})</small>
                                    @endif
                                </div>
                                <div class="pos-cart-meta">
                                    {{ Rupiah::format($item['price']) }} × {{ $item['qty'] }} {{ $item['unit'] }}
                                </div>
                            </div>

                            <div class="pos-qty">
                                <button type="button" wire:click="decrement('{{ $key }}')">−</button>
                                <span>{{ $item['qty'] }}</span>
                                <button type="button" wire:click="increment('{{ $key }}')">+</button>
                            </div>

                            <div class="pos-line-total">{{ Rupiah::format($item['price'] * $item['qty']) }}</div>

                            <button type="button" class="pos-remove" wire:click="removeItem('{{ $key }}')">×</button>
                        </div>
                    @empty
                        <div class="pos-empty-cart">
                            Keranjang masih kosong.<br>Pilih produk di sebelah kiri untuk memulai transaksi.
                        </div>
                    @endforelse
                </div>

                <div class="pos-checkout">
                    <div class="pos-field">
                        <label>Pelanggan</label>
                        <select class="pos-select" wire:model="customerId">
                            <option value="">Pelanggan Umum</option>
                            @foreach ($this->customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="pos-field">
                        <label>Metode Pembayaran</label>
                        <select class="pos-select" wire:model="paymentMethod">
                            @foreach ($this->paymentMethods as $method)
                                <option value="{{ $method['value'] }}" style="color: {{ $method['color'] }};">
                                    {{ $method['icon'] }} {{ $method['label'] }}
                                </option>
                            @endforeach
                        </select>
                        <div style="font-size: .68rem; opacity: .7; margin-top: .2rem;">
                            @if ($paymentMethod === 'qris')
                                Pembayaran via QRIS. Setelah konfirmasi, tampilkan kode QR untuk dipindai pelanggan.
                            @elseif ($paymentMethod === 'transfer')
                                Pembayaran via transfer bank. Pelanggan akan diarahkan ke halaman pembayaran.
                            @elseif ($paymentMethod === 'ewallet')
                                Pembayaran via e-wallet (OVO, GoPay, Dana, dll.).
                            @elseif ($paymentMethod === 'card')
                                Pembayaran via kartu debit/kredit.
                            @else
                                Pembayaran tunai langsung di kasir.
                            @endif
                        </div>
                    </div>

                    @if (! empty($this->paymentUrl))
                        <div class="pos-field" style="background: var(--pos-accent-soft); border: 1px solid var(--pos-accent); border-radius: .7rem; padding: .8rem; margin-top: .6rem;">
                            <div style="display: flex; align-items: center; gap: .5rem; margin-bottom: .6rem;">
                                <span style="font-size: 1.2rem;">📱</span>
                                <span style="font-weight: 700; font-size: .8rem;">Kode QR Pembayaran</span>
                            </div>
                            <div style="display: flex; justify-content: center; margin-bottom: .6rem;">
                                {!! \App\Support\QrHelper::svg($this->paymentUrl, 5) !!}
                            </div>
                            <div style="text-align: center; font-size: .72rem; opacity: .8;">
                                Scan untuk membuka halaman pembayaran online
                            </div>
                            <div style="margin-top: .6rem; text-align: center;">
                                <button type="button" class="pos-checkout-btn" style="background: var(--pos-accent);" onclick="window.open('{{ $this->paymentUrl }}', '_blank', 'noopener')">
                                    Buka Halaman Pembayaran
                                </button>
                            </div>
                        </div>
                    @endif

                    <div class="pos-summary">
                        <div>
                            <span>Subtotal</span>
                            <span>{{ Rupiah::format($this->subtotal) }}</span>
                        </div>
                        <div>
                            <span>Diskon</span>
                            <input
                                type="number"
                                min="0"
                                step="100"
                                class="pos-input pos-input-sm"
                                wire:model.live.debounce.300ms="discount"
                            >
                        </div>
                        <div>
                            <span>Pajak ({{ rtrim(rtrim(number_format($this->taxPercent, 2, '.', ''), '0'), '.') }}%)</span>
                            <span>{{ Rupiah::format($this->taxAmount) }}</span>
                        </div>
                        <div class="pos-total">
                            <span>Total</span>
                            <span>{{ Rupiah::format($this->total) }}</span>
                        </div>
                    </div>

                    @if ($paymentMethod === 'cash')
                        <div class="pos-field">
                            <label>Uang Dibayar</label>
                            <input
                                type="number"
                                min="0"
                                step="500"
                                class="pos-input"
                                wire:model.live.debounce.200ms="paid"
                            >
                            <div class="pos-quick">
                                <button type="button" wire:click="setPaid({{ $this->total }})">Uang Pas</button>
                                @foreach ([20000, 50000, 100000] as $nominal)
                                    <button type="button" wire:click="setPaid({{ $nominal }})">
                                        {{ number_format($nominal, 0, ',', '.') }}
                                    </button>
                                @endforeach
                            </div>
                            <div class="pos-change">
                                Kembalian: <strong>{{ Rupiah::format($this->change) }}</strong>
                            </div>
                        </div>
                    @endif

                    <div class="pos-field">
                        <label>Catatan</label>
                        <input type="text" class="pos-input" wire:model="note" placeholder="Opsional">
                    </div>

                    <button
                        type="button"
                        class="pos-checkout-btn"
                        wire:click="checkout"
                        wire:loading.attr="disabled"
                        wire:target="checkout"
                    >
                        <span wire:loading.remove wire:target="checkout">Proses Pembayaran</span>
                        <span wire:loading wire:target="checkout">Memproses...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            if (window.__posPrintListenerRegistered) {
                return;
            }

            window.__posPrintListenerRegistered = true;

            const openInNewTab = (url) => {
                if (url) {
                    window.open(url, '_blank', 'noopener');
                }
            };

            const register = () => {
                Livewire.on('print-receipt', (event) => {
                    openInNewTab(event?.url ?? event?.detail?.url);
                });

                Livewire.on('open-url', (event) => {
                    openInNewTab(event?.url ?? event?.detail?.url);
                });
            };

            document.addEventListener('livewire:init', register);

            if (window.Livewire) {
                register();
            }
        })();

    </script>
</x-filament-panels::page>
