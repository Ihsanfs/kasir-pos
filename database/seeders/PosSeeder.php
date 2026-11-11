<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PosSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::query()->updateOrCreate(
            ['code' => 'PUSAT'],
            [
                'name' => 'Toko Pusat',
                'address' => 'Jl. Merdeka No. 10, Jakarta',
                'phone' => '0812-3456-7890',
                'is_active' => true,
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'admin@kasir.test'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                'branch_id' => $branch->id,
                'is_active' => true,
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'kasir@kasir.test'],
            [
                'name' => 'Kasir Satu',
                'password' => Hash::make('password'),
                'branch_id' => $branch->id,
                'is_active' => true,
            ],
        );

        $settings = [
            ['store_name', 'Kasir 2026', 'general'],
            ['store_address', 'Jl. Merdeka No. 10, Jakarta', 'general'],
            ['store_phone', '0812-3456-7890', 'general'],
            ['receipt_footer', "Terima kasih sudah berbelanja\nBarang yang sudah dibeli tidak dapat ditukar.", 'receipt'],
            ['tax_percent', '0', 'receipt'],
            ['low_stock_threshold', '5', 'inventory'],
            ['midtrans_enabled', '0', 'payment'],
            ['midtrans_is_production', '0', 'payment'],
            ['midtrans_server_key', '', 'payment'],
            ['midtrans_client_key', '', 'payment'],
            ['ai_provider', 'local', 'ai'],
            ['ai_model', 'gpt-4o-mini', 'ai'],
            ['ai_api_key', '', 'ai'],
        ];

        foreach ($settings as [$key, $value, $group]) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => $group],
            );
        }

        $categories = [
            'Makanan' => 'Menu makanan utama',
            'Minuman' => 'Minuman dingin dan panas',
            'Snack' => 'Camilan dan makanan ringan',
            'Sembako' => 'Kebutuhan pokok sehari-hari',
            'Lainnya' => 'Produk lain-lain',
        ];

        foreach ($categories as $name => $description) {
            Category::query()->updateOrCreate(
                ['name' => $name],
                ['description' => $description, 'is_active' => true],
            );
        }

        $categoryIds = Category::query()->pluck('id', 'name');

        $products = [
            ['Nasi Goreng Spesial', 'Makanan', 15000, 20000, 40, 'porsi', 'MKN-001', '8990000000001'],
            ['Mie Ayam', 'Makanan', 12000, 17000, 35, 'porsi', 'MKN-002', '8990000000002'],
            ['Ayam Geprek', 'Makanan', 13000, 18000, 30, 'porsi', 'MKN-003', '8990000000003'],
            ['Sate Ayam', 'Makanan', 15000, 22000, 25, 'porsi', 'MKN-004', '8990000000004'],
            ['Es Teh Manis', 'Minuman', 2000, 5000, 100, 'pcs', 'MNM-001', '8990000000005'],
            ['Es Jeruk', 'Minuman', 3000, 7000, 80, 'pcs', 'MNM-002', '8990000000006'],
            ['Kopi Hitam', 'Minuman', 4000, 8000, 60, 'pcs', 'MNM-003', '8990000000007'],
            ['Es Kopi Susu', 'Minuman', 7000, 13000, 50, 'pcs', 'MNM-004', '8990000000008'],
            ['Air Mineral', 'Minuman', 2500, 4000, 120, 'botol', 'MNM-005', '8990000000009'],
            ['Kentang Goreng', 'Snack', 8000, 13000, 45, 'porsi', 'SNK-001', '8990000000010'],
            ['Pisang Goreng', 'Snack', 5000, 9000, 50, 'porsi', 'SNK-002', '8990000000011'],
            ['Roti Bakar', 'Snack', 6000, 11000, 35, 'porsi', 'SNK-003', '8990000000012'],
            ['Beras 1 kg', 'Sembako', 12000, 15000, 60, 'kg', 'SBK-001', '8990000000013'],
            ['Gula 1 kg', 'Sembako', 14000, 17000, 45, 'kg', 'SBK-002', '8990000000014'],
            ['Minyak Goreng 1 L', 'Sembako', 15000, 19000, 40, 'liter', 'SBK-003', '8990000000015'],
            ['Telur 1 kg', 'Sembako', 25000, 29000, 30, 'kg', 'SBK-004', '8990000000016'],
        ];

        foreach ($products as [$name, $category, $cost, $price, $stock, $unit, $sku, $barcode]) {
            $product = Product::query()->updateOrCreate(
                ['sku' => $sku],
                [
                    'category_id' => $categoryIds[$category] ?? null,
                    'name' => $name,
                    'barcode' => $barcode,
                    'unit' => $unit,
                    'cost_price' => $cost,
                    'sell_price' => $price,
                    'stock' => $stock,
                    'min_stock' => 10,
                    'is_active' => true,
                ],
            );

            if ($sku === 'MNM-004') {
                $product->variants()->updateOrCreate(
                    ['name' => 'Regular'],
                    [
                        'sku' => 'MNM-004-R',
                        'cost_price' => 7000,
                        'sell_price' => 13000,
                        'stock' => 30,
                        'is_active' => true,
                    ],
                );

                $product->variants()->updateOrCreate(
                    ['name' => 'Large'],
                    [
                        'sku' => 'MNM-004-L',
                        'cost_price' => 9000,
                        'sell_price' => 16000,
                        'stock' => 20,
                        'is_active' => true,
                    ],
                );
            }
        }

        $customers = [
            ['Budi Santoso', '0812-1111-2222', 'budi@example.com', 'Jl. Melati No. 5'],
            ['Siti Aminah', '0813-3333-4444', 'siti@example.com', 'Jl. Kenanga No. 8'],
            ['Andi Wijaya', '0814-5555-6666', 'andi@example.com', 'Jl. Anggrek No. 12'],
            ['Rina Lestari', '0815-7777-8888', 'rina@example.com', 'Jl. Dahlia No. 3'],
        ];

        foreach ($customers as [$name, $phone, $email, $address]) {
            Customer::query()->updateOrCreate(
                ['name' => $name],
                ['phone' => $phone, 'email' => $email, 'address' => $address],
            );
        }
    }
}
