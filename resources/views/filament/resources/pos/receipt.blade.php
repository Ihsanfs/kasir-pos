@php
    $storeName = \App\Models\Setting::get('store_name', config('app.name'));
    $storeAddress = \App\Models\Setting::get('store_address', '');
    $storePhone = \App\Models\Setting::get('store_phone', '');
    $footer = \App\Models\Setting::get('receipt_footer', 'Terima kasih atas kunjungan Anda');
    $autoPrint = request('auto', '1') !== '0';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=58mm, initial-scale=1">
    <title>Struk {{ $transaction->invoice_number }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            padding: 0;
            background: #f1f5f9;
        }

        body {
            font-family: "Courier New", ui-monospace, monospace;
            color: #000;
            display: flex;
            justify-content: center;
            padding: 16px 8px;
        }

        .receipt {
            width: 58mm;
            max-width: 100%;
            background: #fff;
            padding: 3mm 3mm 5mm;
            font-size: 9.5px;
            line-height: 1.45;
        }

        .center {
            text-align: center;
        }

        .store-name {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: .04em;
            margin-bottom: 1mm;
        }

        .muted {
            font-size: 9px;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 2mm 0;
        }

        .divider-solid {
            border-top: 1px solid #000;
            margin: 2mm 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }

        td {
            vertical-align: top;
            padding: 0.4mm 0;
        }

        .right {
            text-align: right;
        }

        .item-name {
            padding-right: 2mm;
        }

        .item-qty {
            white-space: nowrap;
            text-align: center;
            width: 12mm;
        }

        .item-total {
            white-space: nowrap;
            text-align: right;
            width: 18mm;
        }

        .total-row td {
            font-weight: 700;
            font-size: 10.5px;
        }

        .footer {
            margin-top: 3mm;
            text-align: center;
            font-size: 9px;
            white-space: pre-line;
        }

        .status {
            margin-top: 2mm;
            text-align: center;
            font-weight: 700;
            font-size: 10px;
            border: 1px solid #000;
            padding: 1mm;
        }

        .actions {
            position: fixed;
            right: 12px;
            bottom: 12px;
            display: flex;
            gap: 8px;
        }

        .actions button {
            font-family: system-ui, sans-serif;
            font-size: 12px;
            font-weight: 600;
            padding: 8px 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            background: #0f172a;
            color: #fff;
        }

        .actions button.secondary {
            background: #e2e8f0;
            color: #0f172a;
        }

        @page {
            size: 58mm auto;
            margin: 0;
        }

        @media print {
            html, body {
                background: #fff;
                padding: 0;
                display: block;
            }

            .receipt {
                width: 58mm;
                padding: 0 2mm 4mm;
                box-shadow: none;
            }

            .actions {
                display: none !important;
            }

            .qr-section {
                display: block !important;
                visibility: visible !important;
            }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="center">
            <div class="store-name">{{ $storeName }}</div>

            @if ($storeAddress)
                <div class="muted">{{ $storeAddress }}</div>
            @endif

            @if ($storePhone)
                <div class="muted">Telp: {{ $storePhone }}</div>
            @endif
        </div>

        <div class="divider"></div>

        <table>
            <tr>
                <td>No.</td>
                <td class="right">{{ $transaction->invoice_number }}</td>
            </tr>
            <tr>
                <td>Tanggal</td>
                <td class="right">{{ $transaction->created_at?->format('d/m/Y H:i') }}</td>
            </tr>
            <tr>
                <td>Kasir</td>
                <td class="right">{{ $transaction->user?->name ?? '-' }}</td>
            </tr>
            <tr>
                <td>Pelanggan</td>
                <td class="right">{{ $transaction->customer?->name ?? 'Umum' }}</td>
            </tr>
        </table>

        <div class="divider"></div>

        <table>
            @foreach ($transaction->items as $item)
                <tr>
                    <td class="item-name" colspan="2">
                        {{ $item->product_name }}
                        @if ($item->variant_name)
                            ({{ $item->variant_name }})
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="item-qty">{{ $item->qty }} x {{ number_format($item->price, 0, ',', '.') }}</td>
                    <td class="item-total">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </table>

        <div class="divider"></div>

        <table>
            <tr>
                <td>Subtotal</td>
                <td class="right">{{ number_format($transaction->subtotal, 0, ',', '.') }}</td>
            </tr>

            @if ($transaction->discount > 0)
                <tr>
                    <td>Diskon</td>
                    <td class="right">-{{ number_format($transaction->discount, 0, ',', '.') }}</td>
                </tr>
            @endif

            @if ($transaction->tax > 0)
                <tr>
                    <td>Pajak</td>
                    <td class="right">{{ number_format($transaction->tax, 0, ',', '.') }}</td>
                </tr>
            @endif

            <tr class="total-row">
                <td>TOTAL</td>
                <td class="right">{{ number_format($transaction->total, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Bayar</td>
                <td class="right">{{ number_format($transaction->paid, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Kembali</td>
                <td class="right">{{ number_format($transaction->change, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Metode</td>
                <td class="right">
                    @php
                        $_pmIcons = ['cash' => '💵', 'qris' => '📱', 'transfer' => '🏦', 'ewallet' => '📲', 'card' => '💳'];
                        $_pmIcon = $_pmIcons[$transaction->payment_method] ?? '💳';
                    @endphp
                    {{ $_pmIcon }} {{ $transaction->payment_method_label }}
                </td>
            </tr>
        </table>

        @if ($transaction->payment_status !== 'paid')
            <div class="status">{{ strtoupper($transaction->status_label) }}</div>
        @endif

        <div class="divider-solid"></div>

        <div class="qr-section" style="margin-top: 3mm; text-align: center;">
            <div class="muted" style="margin-bottom: 1mm;">Scan untuk verifikasi struk</div>
            <div style="display: flex; justify-content: center; margin-bottom: 1mm;">
                {!! \App\Support\QrHelper::svg(
                    url('/pos/print/'.$transaction->id.'?inv='.$transaction->invoice_number),
                    5
                ) !!}
            </div>
            <div style="margin-top: 1.5mm; font-size: 8px; font-weight: 700; letter-spacing: .02em; word-break: break-all;">{{ $transaction->invoice_number }}</div>
            <div style="margin-top: 0.5mm; font-size: 7px; color: #64748b;">Ketik nomor di atas jika tidak bisa scan</div>
        </div>

        <div class="footer">{{ $footer }}</div>
    </div>

    <div class="actions">
        <button type="button" onclick="window.print()">Cetak</button>
        <button type="button" class="secondary" onclick="window.close()">Tutup</button>
    </div>

    @if ($autoPrint)
        <script>
            window.addEventListener('load', function () {
                setTimeout(function () {
                    window.print();
                }, 350);
            });

            window.addEventListener('afterprint', function () {
                setTimeout(function () {
                    window.close();
                }, 250);
            });
        </script>
    @endif
</body>
</html>
