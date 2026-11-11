<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosService
{
    /**
     * Simpan transaksi, hitung ulang harga dari database, dan kurangi stok.
     *
     * @param  array{
     *     items: array<int, array{product_id: int, product_variant_id?: int|null, qty: int}>,
     *     branch_id?: int|null,
     *     user_id?: int|null,
     *     customer_id?: int|null,
     *     shift_id?: int|null,
     *     discount?: float|int,
     *     paid?: float|int,
     *     payment_method?: string,
     *     note?: string|null
     * }  $data
     */
    public function checkout(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $items = collect($data['items'] ?? [])
                ->filter(fn (array $item) => ! empty($item['product_id']) && (int) ($item['qty'] ?? 0) > 0);

            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['cart' => 'Keranjang masih kosong.']);
            }

            $prepared = [];
            $subtotal = 0.0;

            foreach ($items as $item) {
                $product = Product::query()->lockForUpdate()->findOrFail($item['product_id']);
                $variant = null;
                $price = (float) $product->sell_price;
                $cost = (float) $product->cost_price;
                $stock = (int) $product->stock;
                $label = $product->name;

                if (! empty($item['product_variant_id'])) {
                    $variant = ProductVariant::query()
                        ->lockForUpdate()
                        ->where('product_id', $product->id)
                        ->findOrFail($item['product_variant_id']);

                    $price = (float) $variant->sell_price;
                    $cost = (float) $variant->cost_price;
                    $stock = (int) $variant->stock;
                    $label .= ' - '.$variant->name;
                }

                $qty = (int) $item['qty'];

                if ($stock < $qty) {
                    throw ValidationException::withMessages([
                        'cart' => "Stok {$label} tidak cukup. Tersisa {$stock}.",
                    ]);
                }

                $lineSubtotal = round($price * $qty);

                $prepared[] = [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'product_name' => $product->name,
                    'variant_name' => $variant?->name,
                    'unit' => $product->unit,
                    'price' => $price,
                    'cost_price' => $cost,
                    'qty' => $qty,
                    'subtotal' => $lineSubtotal,
                    'stock_target' => $variant ?? $product,
                ];

                $subtotal += $lineSubtotal;
            }

            $discount = min((float) ($data['discount'] ?? 0), $subtotal);
            $taxPercent = (float) Setting::get('tax_percent', 0);
            $tax = round(($subtotal - $discount) * $taxPercent / 100);
            $total = max(0, round($subtotal - $discount + $tax));

            $method = $data['payment_method'] ?? 'cash';
            $paid = $method === 'cash' ? (float) ($data['paid'] ?? 0) : $total;
            $paymentStatus = $data['payment_status'] ?? 'paid';

            if (! in_array($paymentStatus, ['paid', 'pending'], true)) {
                $paymentStatus = 'paid';
            }

            if ($method === 'cash' && $paid < $total) {
                throw ValidationException::withMessages([
                    'paid' => 'Uang yang dibayarkan kurang dari total belanja.',
                ]);
            }

            $shift = ! empty($data['shift_id']) ? Shift::query()->find($data['shift_id']) : null;

            $transaction = Transaction::create([
                'invoice_number' => Transaction::generateInvoiceNumber(),
                'branch_id' => $data['branch_id'] ?? null,
                'user_id' => $data['user_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'shift_id' => $shift?->id,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total,
                'paid' => $paid,
                'change' => max(0, round($paid - $total)),
                'payment_method' => $method,
                'payment_status' => $paymentStatus,
                'note' => $data['note'] ?? null,
            ]);

            foreach ($prepared as $line) {
                $stockTarget = $line['stock_target'];
                unset($line['stock_target']);

                $transaction->items()->create($line);
                $stockTarget->decrement('stock', $line['qty']);
            }

            if ($shift && $paymentStatus === 'paid') {
                $shift->increment('total_sales', $total);
                $shift->increment('total_transactions');

                if ($method === 'cash') {
                    $shift->increment('total_cash_sales', $total);
                }
            }

            return $transaction->load(['items', 'customer', 'user', 'branch', 'shift']);
        });
    }
}
