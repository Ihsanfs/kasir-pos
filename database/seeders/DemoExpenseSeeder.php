<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoExpenseSeeder extends Seeder
{
    public function run(): void
    {
        if (Expense::query()->exists()) {
            return;
        }

        $branch = Branch::query()->where('code', 'PUSAT')->first();
        $user = User::query()->where('email', 'admin@kasir.test')->first();

        if (! $branch || ! $user) {
            return;
        }

        $expenses = [
            ['Sewa Tempat', 'Sewa tempat usaha bulanan', 250000, 20],
            ['Listrik & Air', 'Tagihan listrik dan air', 85000, 15],
            ['Gaji Karyawan', 'Gaji karyawan paruh waktu', 150000, 10],
            ['Operasional', 'Pembelian perlengkapan kebersihan', 45000, 7],
            ['Transportasi', 'Ongkos kirim bahan baku', 30000, 4],
            ['Pembelian Stok', 'Restock bahan minuman', 150000, 2],
        ];

        foreach ($expenses as [$category, $description, $amount, $daysAgo]) {
            Expense::query()->create([
                'branch_id' => $branch->id,
                'user_id' => $user->id,
                'date' => now()->subDays($daysAgo)->toDateString(),
                'category' => $category,
                'description' => $description,
                'amount' => $amount,
            ]);
        }
    }
}
