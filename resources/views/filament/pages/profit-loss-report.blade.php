@use('App\Support\Rupiah')

<x-filament-panels::page>
    <style>
        .pl-app {
            --pl-bg: #ffffff;
            --pl-soft: #f8fafc;
            --pl-border: #e2e8f0;
            --pl-text: #0f172a;
            --pl-muted: #64748b;
            --pl-green: #059669;
            --pl-red: #dc2626;
            --pl-amber: #d97706;
            --pl-blue: #2563eb;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .dark .pl-app {
            --pl-bg: #1e293b;
            --pl-soft: #0f172a;
            --pl-border: #334155;
            --pl-text: #f1f5f9;
            --pl-muted: #94a3b8;
            --pl-green: #34d399;
            --pl-red: #f87171;
            --pl-amber: #fbbf24;
            --pl-blue: #60a5fa;
        }

        .pl-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: .65rem;
            padding: .85rem 1rem;
            border: 1px solid var(--pl-border);
            border-radius: .9rem;
            background: var(--pl-bg);
        }

        .pl-field {
            display: flex;
            flex-direction: column;
            gap: .3rem;
            min-width: 150px;
        }

        .pl-field label {
            font-size: .68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--pl-muted);
        }

        .pl-input {
            padding: .55rem .7rem;
            border: 1px solid var(--pl-border);
            border-radius: .6rem;
            background: var(--pl-bg);
            color: var(--pl-text);
            font-size: .85rem;
            outline: none;
        }

        .pl-input:focus {
            border-color: var(--pl-blue);
        }

        .pl-print-btn {
            margin-inline-start: auto;
            padding: .6rem 1rem;
            border: none;
            border-radius: .65rem;
            background: var(--pl-blue);
            color: #fff;
            font-size: .82rem;
            font-weight: 700;
            cursor: pointer;
        }

        .pl-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: .75rem;
        }

        .pl-card {
            padding: .9rem 1rem;
            border: 1px solid var(--pl-border);
            border-radius: .9rem;
            background: var(--pl-bg);
            display: flex;
            flex-direction: column;
            gap: .25rem;
        }

        .pl-card .label {
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
            color: var(--pl-muted);
        }

        .pl-card .value {
            font-size: 1.28rem;
            font-weight: 900;
            color: var(--pl-text);
            line-height: 1.2;
        }

        .pl-card .sub {
            font-size: .72rem;
            color: var(--pl-muted);
        }

        .pl-card.green .value {
            color: var(--pl-green);
        }

        .pl-card.red .value {
            color: var(--pl-red);
        }

        .pl-card.amber .value {
            color: var(--pl-amber);
        }

        .pl-card.blue .value {
            color: var(--pl-blue);
        }

        .pl-columns {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: .75rem;
        }

        .pl-panel {
            border: 1px solid var(--pl-border);
            border-radius: .9rem;
            background: var(--pl-bg);
            overflow: hidden;
        }

        .pl-panel-head {
            padding: .75rem 1rem;
            border-bottom: 1px solid var(--pl-border);
            font-size: .85rem;
            font-weight: 800;
            color: var(--pl-text);
            background: var(--pl-soft);
        }

        .pl-table {
            width: 100%;
            border-collapse: collapse;
            font-size: .8rem;
        }

        .pl-table th {
            text-align: left;
            padding: .55rem .9rem;
            font-size: .66rem;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: var(--pl-muted);
            border-bottom: 1px solid var(--pl-border);
            white-space: nowrap;
        }

        .pl-table td {
            padding: .55rem .9rem;
            border-bottom: 1px solid color-mix(in srgb, var(--pl-border) 65%, transparent);
            color: var(--pl-text);
        }

        .pl-table tr:last-child td {
            border-bottom: none;
        }

        .pl-table td.num,
        .pl-table th.num {
            text-align: right;
            white-space: nowrap;
        }

        .pl-table tbody tr:hover td {
            background: var(--pl-soft);
        }

        .pl-positive {
            color: var(--pl-green);
            font-weight: 700;
        }

        .pl-negative {
            color: var(--pl-red);
            font-weight: 700;
        }

        .pl-empty {
            padding: 1.5rem 1rem;
            text-align: center;
            color: var(--pl-muted);
            font-size: .8rem;
        }

        .pl-scroll {
            max-height: 420px;
            overflow: auto;
        }

        .pl-note {
            font-size: .72rem;
            color: var(--pl-muted);
        }

        @media print {
            .fi-sidebar,
            .fi-topbar,
            .fi-header,
            .fi-page-header,
            .no-print {
                display: none !important;
            }

            .fi-main {
                padding: 0 !important;
            }

            .pl-app {
                gap: .5rem;
            }

            .pl-panel,
            .pl-card,
            .pl-toolbar {
                break-inside: avoid;
            }

            .pl-scroll {
                max-height: none;
                overflow: visible;
            }
        }
    </style>

    <div class="pl-app">
        <div class="pl-toolbar no-print">
            <div class="pl-field">
                <label>Dari Tanggal</label>
                <input type="date" class="pl-input" wire:model.live="startDate">
            </div>

            <div class="pl-field">
                <label>Sampai Tanggal</label>
                <input type="date" class="pl-input" wire:model.live="endDate">
            </div>

            <div class="pl-field">
                <label>Cabang</label>
                <select class="pl-input" wire:model.live="branchId">
                    <option value="">Semua Cabang</option>
                    @foreach ($this->branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>

            <button type="button" class="pl-print-btn" onclick="window.print()">Cetak Laporan</button>
        </div>

        <div class="pl-cards">
            <div class="pl-card blue">
                <span class="label">Omset</span>
                <span class="value">{{ Rupiah::format($this->summary['revenue']) }}</span>
                <span class="sub">{{ $this->summary['transactions'] }} transaksi • {{ $this->summary['items_sold'] }} item terjual</span>
            </div>

            <div class="pl-card amber">
                <span class="label">HPP (Harga Pokok)</span>
                <span class="value">{{ Rupiah::format($this->summary['cogs']) }}</span>
                <span class="sub">Total modal barang terjual</span>
            </div>

            <div class="pl-card green">
                <span class="label">Laba Kotor</span>
                <span class="value">{{ Rupiah::format($this->summary['gross_profit']) }}</span>
                <span class="sub">Omset − HPP</span>
            </div>

            <div class="pl-card red">
                <span class="label">Pengeluaran</span>
                <span class="value">{{ Rupiah::format($this->summary['expenses']) }}</span>
                <span class="sub">Biaya operasional periode ini</span>
            </div>

            <div class="pl-card {{ $this->summary['net_profit'] >= 0 ? 'green' : 'red' }}">
                <span class="label">Laba Bersih</span>
                <span class="value">{{ Rupiah::format($this->summary['net_profit']) }}</span>
                <span class="sub">Laba kotor − pengeluaran • margin {{ number_format($this->summary['margin'], 1) }}%</span>
            </div>
        </div>

        <div class="pl-note">
            Periode: {{ $this->summary['start']->format('d M Y') }} — {{ $this->summary['end']->format('d M Y') }}.
            Laba kotor dihitung dari harga modal (HPP) yang tersimpan pada setiap item transaksi.
        </div>

        <div class="pl-columns">
            <div class="pl-panel">
                <div class="pl-panel-head">Rincian Pengeluaran</div>

                @if (count($this->expenseRows) > 0)
                    <table class="pl-table">
                        <thead>
                            <tr>
                                <th>Kategori</th>
                                <th class="num">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->expenseRows as $expense)
                                <tr>
                                    <td>{{ $expense['category'] }}</td>
                                    <td class="num">{{ Rupiah::format($expense['total']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="pl-empty">Belum ada pengeluaran pada periode ini.</div>
                @endif
            </div>

            <div class="pl-panel">
                <div class="pl-panel-head">Produk Terlaris</div>

                @if (count($this->topProducts) > 0)
                    <div class="pl-scroll">
                        <table class="pl-table">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th class="num">Qty</th>
                                    <th class="num">Omset</th>
                                    <th class="num">Laba</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($this->topProducts as $product)
                                    <tr>
                                        <td>{{ $product['name'] }}</td>
                                        <td class="num">{{ $product['qty'] }}</td>
                                        <td class="num">{{ Rupiah::format($product['revenue']) }}</td>
                                        <td class="num {{ $product['profit'] >= 0 ? 'pl-positive' : 'pl-negative' }}">
                                            {{ Rupiah::format($product['profit']) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="pl-empty">Belum ada penjualan pada periode ini.</div>
                @endif
            </div>
        </div>

        <div class="pl-panel">
            <div class="pl-panel-head">Rekap Harian</div>

            <div class="pl-scroll">
                <table class="pl-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th class="num">Omset</th>
                            <th class="num">HPP</th>
                            <th class="num">Pengeluaran</th>
                            <th class="num">Laba Bersih</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->dailyRows as $row)
                            <tr>
                                <td>{{ $row['date']->format('d M Y') }}</td>
                                <td class="num">{{ Rupiah::format($row['revenue']) }}</td>
                                <td class="num">{{ Rupiah::format($row['cogs']) }}</td>
                                <td class="num">{{ Rupiah::format($row['expenses']) }}</td>
                                <td class="num {{ $row['net'] >= 0 ? 'pl-positive' : 'pl-negative' }}">
                                    {{ Rupiah::format($row['net']) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="pl-empty">Belum ada data pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
