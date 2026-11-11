<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoTransactionSeeder extends Seeder
{
    public function run(): void
    {
        if (Transaction::query()->exists()) {
            return;
        }

        $branch = Branch::query()->where('code', 'PUSAT')->first();
        $user = User::query()->where('email', 'admin@kasir.test')->first();
        $customers = Customer::query()->pluck('id')->all();
        $products = Product::query()->active()->with('variants')->get();

        if (! $branch || ! $user || $products->isEmpty()) {
            return;
        }

        $sequence = 1;

        for ($day = 20; $day >= 0; $day--) {
            $date = now()->subDays($day);
            $transactionsToday = random_int(2, 6);

            for ($i = 0; $i < $transactionsToday; $i++) {
                $hour = random_int(8, 20);
                $minute = random_int(0, 59);
                $createdAt = $date->copy()->setTime($hour, $minute);

                $items = $products->random(random_int(1, 4));
                $subtotal = 0.0;
                $lineItems = [];

                foreach ($items as $product) {
                    $qty = random_int(1, 3);
                    $variant = $product->variants->isNotEmpty() ? $product->variants->random() : null;
                    $price = (float) ($variant?->sell_price ?? $product->sell_price);
                    $cost = (float) ($variant?->cost_price ?? $product->cost_price);
                    $lineSubtotal = round($price * $qty);

                    $subtotal += $lineSubtotal;

                    $lineItems[] = [
                        'product_id' => $product->id,
                        'product_variant_id' => $variant?->id,
                        'product_name' => $product->name,
                        'variant_name' => $variant?->name,
                        'unit' => $product->unit,
                        'price' => $price,
                        'cost_price' => $cost,
                        'qty' => $qty,
                        'subtotal' => $lineSubtotal,
                    ];
                }

                $discount = random_int(1, 10) === 1 ? round($subtotal * 0.1) : 0;
                $total = max(0, round($subtotal - $discount));
                $method = match (random_int(1, 10)) {
                    1, 2 => 'qris',
                    3 => 'transfer',
                    default => 'cash',
                };
                $paid = $method === 'cash' ? $total + (random_int(0, 1) ? random_int(1, 10) * 1000 : 0) : $total;

                $transaction = new Transaction([
                    'invoice_number' => 'INV-DEMO-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT),
                    'branch_id' => $branch->id,
                    'user_id' => $user->id,
                    'customer_id' => random_int(1, 3) === 1 && $customers !== [] ? $customers[array_rand($customers)] : null,
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'tax' => 0,
                    'total' => $total,
                    'paid' => $paid,
                    'change' => max(0, $paid - $total),
                    'payment_method' => $method,
                    'payment_status' => 'paid',
                    'note' => null,
                ]);

                $transaction->created_at = $createdAt;
                $transaction->updated_at = $createdAt;
                $transaction->save();

                $transaction->items()->createMany($lineItems);

                $sequence++;
            }
        }
    }
}
