@use('App\Support\Rupiah')

<div style="display: flex; flex-direction: column; gap: .9rem; font-size: .85rem;">
    <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .5rem;">
        <div>
            <div style="font-size: .68rem; text-transform: uppercase; letter-spacing: .04em; opacity: .6;">Invoice</div>
            <div style="font-weight: 700;">{{ $transaction->invoice_number }}</div>
        </div>
        <div>
            <div style="font-size: .68rem; text-transform: uppercase; letter-spacing: .04em; opacity: .6;">Waktu</div>
            <div style="font-weight: 600;">{{ $transaction->created_at?->format('d M Y H:i') }}</div>
        </div>
        <div>
            <div style="font-size: .68rem; text-transform: uppercase; letter-spacing: .04em; opacity: .6;">Kasir</div>
            <div style="font-weight: 600;">{{ $transaction->user?->name ?? '-' }}</div>
        </div>
        <div>
            <div style="font-size: .68rem; text-transform: uppercase; letter-spacing: .04em; opacity: .6;">Pelanggan</div>
            <div style="font-weight: 600;">{{ $transaction->customer?->name ?? 'Pelanggan Umum' }}</div>
        </div>
        <div>
            <div style="font-size: .68rem; text-transform: uppercase; letter-spacing: .04em; opacity: .6;">Cabang</div>
            <div style="font-weight: 600;">{{ $transaction->branch?->name ?? '-' }}</div>
        </div>
        <div>
            <div style="font-size: .68rem; text-transform: uppercase; letter-spacing: .04em; opacity: .6;">Pembayaran</div>
            <div style="font-weight: 600;">
                <span style="display: inline-flex; align-items: center; gap: .35rem;">
                    <span style="font-size: 1.1rem;">
                        @php
                            $_icons = ['cash' => '💵', 'qris' => '📱', 'transfer' => '🏦', 'ewallet' => '📲', 'card' => '💳'];
                            $_colors = ['cash' => '#059669', 'qris' => '#2563eb', 'transfer' => '#6366f1', 'ewallet' => '#7c3aed', 'card' => '#db2777'];
                            $_icon = $_icons[$transaction->payment_method] ?? '💳';
                            $_color = $_colors[$transaction->payment_method] ?? '#6b7280';
                        @endphp
                        {{ $_icon }}
                    </span>
                    <span style="color: {{ $_color }}; font-weight: 800;">{{ $transaction->payment_method_label }}</span>
                </span>
                <span style="display: inline-block; margin-inline-start: .5rem; padding: .1rem .5rem; border-radius: 999px; font-size: .68rem; font-weight: 700; background: color-mix(in srgb, {{ $transaction->getStatusColorAttribute() }} 15%, transparent); color: {{ $transaction->getStatusColorAttribute() }};">
                    {{ $transaction->status_label }}
                </span>
            </div>
        </div>
    </div>

    <div style="border: 1px solid rgba(128, 128, 128, .25); border-radius: .6rem; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; font-size: .8rem;">
            <thead>
                <tr style="background: rgba(128, 128, 128, .08);">
                    <th style="text-align: left; padding: .5rem .65rem;">Item</th>
                    <th style="text-align: right; padding: .5rem .65rem;">Qty</th>
                    <th style="text-align: right; padding: .5rem .65rem;">Harga</th>
                    <th style="text-align: right; padding: .5rem .65rem;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($transaction->items as $item)
                    <tr style="border-top: 1px solid rgba(128, 128, 128, .15);">
                        <td style="padding: .5rem .65rem;">
                            {{ $item->product_name }}
                            @if ($item->variant_name)
                                <span style="opacity: .65;">({{ $item->variant_name }})</span>
                            @endif
                        </td>
                        <td style="padding: .5rem .65rem; text-align: right;">{{ $item->qty }}</td>
                        <td style="padding: .5rem .65rem; text-align: right;">{{ Rupiah::format($item->price) }}</td>
                        <td style="padding: .5rem .65rem; text-align: right; font-weight: 600;">{{ Rupiah::format($item->subtotal) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div style="display: flex; flex-direction: column; gap: .25rem; margin-inline-start: auto; min-width: 240px;">
        <div style="display: flex; justify-content: space-between; gap: 1rem;">
            <span style="opacity: .65;">Subtotal</span>
            <span>{{ Rupiah::format($transaction->subtotal) }}</span>
        </div>
        <div style="display: flex; justify-content: space-between; gap: 1rem;">
            <span style="opacity: .65;">Diskon</span>
            <span>{{ Rupiah::format($transaction->discount) }}</span>
        </div>
        <div style="display: flex; justify-content: space-between; gap: 1rem;">
            <span style="opacity: .65;">Pajak</span>
            <span>{{ Rupiah::format($transaction->tax) }}</span>
        </div>
        <div style="display: flex; justify-content: space-between; gap: 1rem; padding-top: .35rem; border-top: 1px solid rgba(128, 128, 128, .25); font-weight: 800; font-size: .95rem;">
            <span>Total</span>
            <span>{{ Rupiah::format($transaction->total) }}</span>
        </div>
        <div style="display: flex; justify-content: space-between; gap: 1rem;">
            <span style="opacity: .65;">Dibayar</span>
            <span>{{ Rupiah::format($transaction->paid) }}</span>
        </div>
        <div style="display: flex; justify-content: space-between; gap: 1rem;">
            <span style="opacity: .65;">Kembalian</span>
            <span>{{ Rupiah::format($transaction->change) }}</span>
        </div>
    </div>

    @if ($transaction->note)
        <div style="font-size: .8rem; opacity: .75;">Catatan: {{ $transaction->note }}</div>
    @endif

    <div style="display: flex; gap: .5rem; justify-content: flex-end; flex-wrap: wrap;">
        <a
            href="{{ route('pos.print', $transaction) }}"
            target="_blank"
            style="font-size: .78rem; font-weight: 700; color: #059669; text-decoration: none;"
        >
            Cetak Struk →
        </a>
        <a
            href="{{ route('pos.scan') }}"
            style="font-size: .78rem; font-weight: 700; color: #059669; text-decoration: none; border: 1px solid #059669; padding: 2px 8px; border-radius: 4px;"
        >
            📷 Scan QR Lainnya
        </a>
    </div>
</div>
