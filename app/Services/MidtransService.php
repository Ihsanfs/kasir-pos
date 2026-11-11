<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MidtransService
{
    public function isEnabled(): bool
    {
        return Setting::bool('midtrans_enabled') && filled(Setting::get('midtrans_server_key'));
    }

    /**
     * Buat transaksi Snap dan kembalikan URL pembayaran.
     */
    public function createSnap(Transaction $transaction): ?string
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $serverKey = (string) Setting::get('midtrans_server_key');
        $baseUrl = Setting::bool('midtrans_is_production')
            ? 'https://app.midtrans.com'
            : 'https://app.sandbox.midtrans.com';

        $items = $transaction->items->map(fn ($item) => [
            'id' => (string) ($item->product_variant_id ?? $item->product_id ?? $item->id),
            'price' => (int) round($item->price),
            'quantity' => (int) $item->qty,
            'name' => mb_substr($item->product_name.($item->variant_name ? ' - '.$item->variant_name : ''), 0, 50),
        ])->values()->all();

        if ($transaction->discount > 0) {
            $items[] = [
                'id' => 'DISCOUNT',
                'price' => -(int) round($transaction->discount),
                'quantity' => 1,
                'name' => 'Diskon',
            ];
        }

        if ($transaction->tax > 0) {
            $items[] = [
                'id' => 'TAX',
                'price' => (int) round($transaction->tax),
                'quantity' => 1,
                'name' => 'Pajak',
            ];
        }

        $response = Http::withBasicAuth($serverKey, '')
            ->acceptJson()
            ->timeout(20)
            ->post($baseUrl.'/snap/v1/transactions', [
                'transaction_details' => [
                    'order_id' => $transaction->invoice_number,
                    'gross_amount' => (int) round($transaction->total),
                ],
                'item_details' => $items,
                'customer_details' => [
                    'first_name' => $transaction->customer?->name ?? 'Pelanggan Umum',
                    'phone' => $transaction->customer?->phone ?? '',
                ],
                'callbacks' => [
                    'finish' => route('pos.finish'),
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'Midtrans: '.($response->json('error_messages.0') ?? $response->body())
            );
        }

        $transaction->update([
            'payment_status' => 'pending',
            'payment_reference' => $response->json('token'),
        ]);

        return $response->json('redirect_url');
    }

    /**
     * Proses notifikasi / webhook dari Midtrans.
     */
    public function handleNotification(array $payload): bool
    {
        $serverKey = (string) Setting::get('midtrans_server_key');

        if ($serverKey === '') {
            return false;
        }

        $signature = hash('sha512',
            ($payload['order_id'] ?? '').
            ($payload['status_code'] ?? '').
            ($payload['gross_amount'] ?? '').
            $serverKey
        );

        if (! hash_equals($signature, (string) ($payload['signature_key'] ?? ''))) {
            return false;
        }

        $transaction = Transaction::query()
            ->where('invoice_number', $payload['order_id'] ?? '')
            ->first();

        if (! $transaction) {
            return false;
        }

        $status = match ($payload['transaction_status'] ?? '') {
            'capture', 'settlement' => 'paid',
            'pending' => 'pending',
            'deny', 'cancel', 'failure' => 'failed',
            'expire' => 'expired',
            default => $transaction->payment_status,
        };

        $transaction->update([
            'payment_status' => $status,
            'payment_reference' => $payload['transaction_id'] ?? $transaction->payment_reference,
        ]);

        return true;
    }
}
