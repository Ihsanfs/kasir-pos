<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Support\Rupiah;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AiService
{
    public function provider(): string
    {
        return (string) Setting::get('ai_provider', 'local');
    }

    public function isConfigured(): bool
    {
        return $this->provider() !== 'local' && filled(Setting::get('ai_api_key'));
    }

    /**
     * Jawab pertanyaan pengguna. Jika API key belum diisi / gagal,
     * gunakan analisis data lokal sebagai fallback.
     */
    public function answer(string $question, array $history = []): string
    {
        $question = trim($question);

        if ($question === '') {
            return 'Silakan ajukan pertanyaan tentang penjualan, stok, laba, atau pengeluaran.';
        }

        if ($this->isConfigured()) {
            try {
                return $this->askProvider($question, $history);
            } catch (\Throwable $e) {
                return $this->localAnswer($question)
                    ."\n\n---\n_Catatan: AI online tidak dapat dihubungi ({$e->getMessage()}). Jawaban di atas dihasilkan dari analisis data lokal._";
            }
        }

        return $this->localAnswer($question);
    }

    /**
     * Ringkasan data bisnis yang dikirim sebagai konteks ke model AI.
     */
    public function buildContext(): array
    {
        $now = now();
        $start30 = $now->copy()->subDays(29)->startOfDay();
        $end = $now->copy()->endOfDay();

        $paidQuery = fn () => Transaction::query()->where('payment_status', 'paid');

        $revenue = fn (string $start, string $end) => (float) $paidQuery()
            ->whereBetween('created_at', [$start, $end])
            ->sum('total');

        $count = fn (string $start, string $end) => (int) $paidQuery()
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $todayRevenue = $revenue($now->copy()->startOfDay(), $now->copy()->endOfDay());
        $todayCount = $count($now->copy()->startOfDay(), $now->copy()->endOfDay());
        $weekRevenue = $revenue($now->copy()->subDays(6)->startOfDay(), $end);
        $weekCount = $count($now->copy()->subDays(6)->startOfDay(), $end);
        $monthRevenue = $revenue($start30, $end);
        $monthCount = $count($start30, $end);

        $monthCogs = (float) TransactionItem::query()
            ->whereHas('transaction', fn ($query) => $query
                ->where('payment_status', 'paid')
                ->whereBetween('created_at', [$start30, $end]))
            ->selectRaw('COALESCE(SUM(cost_price * qty), 0) as total')
            ->value('total');

        $monthExpenses = (float) Expense::query()
            ->whereBetween('date', [$start30->toDateString(), $end->toDateString()])
            ->sum('amount');

        $expensesByCategory = Expense::query()
            ->whereBetween('date', [$start30->toDateString(), $end->toDateString()])
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'kategori' => $row->category,
                'total' => (float) $row->total,
            ])
            ->all();

        $topProducts = TransactionItem::query()
            ->whereHas('transaction', fn ($query) => $query
                ->where('payment_status', 'paid')
                ->whereBetween('created_at', [$start30, $end]))
            ->selectRaw('product_name, SUM(qty) as qty, SUM(subtotal) as revenue')
            ->groupBy('product_name')
            ->orderByDesc('qty')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'produk' => $row->product_name,
                'qty_terjual' => (int) $row->qty,
                'omset' => (float) $row->revenue,
            ])
            ->all();

        $lowStock = Product::query()
            ->active()
            ->lowStock()
            ->orderBy('stock')
            ->limit(15)
            ->get()
            ->map(fn (Product $product) => [
                'produk' => $product->name,
                'stok' => $product->stock,
                'stok_minimum' => $product->min_stock,
                'satuan' => $product->unit,
            ])
            ->all();

        $restock = Product::query()
            ->active()
            ->lowStock()
            ->orderBy('stock')
            ->limit(10)
            ->get()
            ->map(function (Product $product) use ($start30, $end) {
                $sold = (int) TransactionItem::query()
                    ->where('product_id', $product->id)
                    ->whereHas('transaction', fn ($query) => $query
                        ->where('payment_status', 'paid')
                        ->whereBetween('created_at', [$start30, $end]))
                    ->sum('qty');

                $dailyAverage = $sold / 30;
                $daysLeft = $dailyAverage > 0 ? round($product->stock / $dailyAverage, 1) : null;
                $recommended = $dailyAverage > 0
                    ? max(0, (int) ceil($dailyAverage * 14) - $product->stock)
                    : max(0, $product->min_stock - $product->stock);

                return [
                    'produk' => $product->name,
                    'stok' => $product->stock,
                    'rata_rata_terjual_per_hari' => round($dailyAverage, 2),
                    'perkiraan_habis_dalam_hari' => $daysLeft,
                    'rekomendasi_restock_14_hari' => $recommended,
                ];
            })
            ->all();

        $averageTransaction = (float) $paidQuery()
            ->whereBetween('created_at', [$start30, $end])
            ->avg('total');

        $anomalies = $paidQuery()
            ->whereBetween('created_at', [$start30, $end])
            ->latest()
            ->limit(300)
            ->get(['invoice_number', 'subtotal', 'discount', 'total', 'created_at'])
            ->filter(function (Transaction $transaction) use ($averageTransaction) {
                $bigDiscount = $transaction->subtotal > 0
                    && $transaction->discount > ($transaction->subtotal * 0.3);

                $outlier = $averageTransaction > 0
                    && $transaction->total > ($averageTransaction * 5);

                return $bigDiscount || $outlier || $transaction->total <= 0;
            })
            ->take(10)
            ->map(fn (Transaction $transaction) => [
                'invoice' => $transaction->invoice_number,
                'tanggal' => $transaction->created_at?->format('Y-m-d H:i'),
                'subtotal' => (float) $transaction->subtotal,
                'diskon' => (float) $transaction->discount,
                'total' => (float) $transaction->total,
            ])
            ->values()
            ->all();

        $recentTransactions = $paidQuery()
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (Transaction $transaction) => [
                'invoice' => $transaction->invoice_number,
                'tanggal' => $transaction->created_at?->format('Y-m-d H:i'),
                'total' => (float) $transaction->total,
                'metode' => $transaction->payment_method,
            ])
            ->all();

        return [
            'toko' => [
                'nama' => Setting::get('store_name', 'Kasir 2026'),
                'pajak_persen' => (float) Setting::get('tax_percent', 0),
                'mata_uang' => 'IDR',
            ],
            'tanggal_analisis' => $now->format('Y-m-d H:i'),
            'penjualan' => [
                'hari_ini' => ['omset' => $todayRevenue, 'transaksi' => $todayCount],
                '7_hari_terakhir' => ['omset' => $weekRevenue, 'transaksi' => $weekCount],
                '30_hari_terakhir' => ['omset' => $monthRevenue, 'transaksi' => $monthCount],
            ],
            'laba_rugi_30_hari' => [
                'omset' => $monthRevenue,
                'hpp' => $monthCogs,
                'laba_kotor' => $monthRevenue - $monthCogs,
                'pengeluaran' => $monthExpenses,
                'laba_bersih' => $monthRevenue - $monthCogs - $monthExpenses,
            ],
            'pengeluaran_per_kategori_30_hari' => $expensesByCategory,
            'produk_terlaris_30_hari' => $topProducts,
            'stok_menipis' => $lowStock,
            'prediksi_restock' => $restock,
            'rata_rata_nilai_transaksi' => $averageTransaction,
            'anomali_transaksi' => $anomalies,
            'transaksi_terbaru' => $recentTransactions,
        ];
    }

    private function systemPrompt(): string
    {
        $context = json_encode($this->buildContext(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $store = Setting::get('store_name', 'Kasir 2026');

        return <<<PROMPT
Anda adalah analis bisnis untuk {$store}, sebuah toko ritel di Indonesia.
Gunakan HANYA data yang diberikan di bawah ini. Jangan mengarang angka.
Jawab dalam Bahasa Indonesia yang ringkas dan praktis, memakai poin-poin bila perlu.
Fokuskan jawaban pada: penjualan, laba, stok, restock, pengeluaran, dan anomali.
Jika data tidak tersedia, katakan dengan jujur bahwa datanya belum ada.
Untuk nominal uang, tulis dalam format Rupiah (contoh: Rp 150.000).

DATA BISNIS (JSON):
{$context}
PROMPT;
    }

    private function askProvider(string $question, array $history): string
    {
        $provider = $this->provider();
        $apiKey = (string) Setting::get('ai_api_key');
        $defaultModel = $provider === 'anthropic' ? 'claude-3-5-sonnet-latest' : 'gpt-4o-mini';
        $model = (string) Setting::get('ai_model', $defaultModel);

        $messages = [];

        foreach (array_slice($history, -8) as $message) {
            if (! in_array($message['role'] ?? '', ['user', 'assistant'], true)) {
                continue;
            }

            $messages[] = [
                'role' => $message['role'],
                'content' => (string) ($message['content'] ?? ''),
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $question];

        if ($provider === 'anthropic') {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
                'model' => $model,
                'max_tokens' => 1200,
                'system' => $this->systemPrompt(),
                'messages' => $messages,
            ]);

            if ($response->failed()) {
                throw new RuntimeException($response->json('error.message') ?? 'HTTP '.$response->status());
            }

            return trim((string) $response->json('content.0.text', ''));
        }

        $response = Http::withToken($apiKey)->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
            'model' => $model,
            'temperature' => 0.3,
            'messages' => array_merge(
                [['role' => 'system', 'content' => $this->systemPrompt()]],
                $messages,
            ),
        ]);

        if ($response->failed()) {
            throw new RuntimeException($response->json('error.message') ?? 'HTTP '.$response->status());
        }

        return trim((string) $response->json('choices.0.message.content', ''));
    }

    /**
     * Analisis lokal tanpa API, berbasis kata kunci.
     */
    public function localAnswer(string $question): string
    {
        $context = $this->buildContext();
        $question = mb_strtolower($question);
        $has = fn (array $keywords) => collect($keywords)->contains(
            fn (string $keyword) => str_contains($question, $keyword)
        );

        if ($has(['stok', 'restock', 'menipis', 'reorder'])) {
            return $this->stockAnswer($context);
        }

        if ($has(['terlaris', 'produk', 'paling laku', 'best seller'])) {
            return $this->topProductAnswer($context);
        }

        if ($has(['anomali', 'audit', 'mencurigakan', 'kecurangan'])) {
            return $this->anomalyAnswer($context);
        }

        if ($has(['pengeluaran', 'biaya', 'expense'])) {
            return $this->expenseAnswer($context);
        }

        if ($has(['laba', 'profit', 'rugi', 'margin'])) {
            return $this->profitAnswer($context);
        }

        if ($has(['omset', 'penjualan', 'pendapatan', 'transaksi', 'hari ini'])) {
            return $this->salesAnswer($context);
        }

        return $this->overviewAnswer($context);
    }

    private function salesAnswer(array $context): string
    {
        $sales = $context['penjualan'];

        return implode("\n", [
            '**Ringkasan Penjualan**',
            '- Hari ini: **'.Rupiah::format($sales['hari_ini']['omset']).'** dari '.$sales['hari_ini']['transaksi'].' transaksi.',
            '- 7 hari terakhir: **'.Rupiah::format($sales['7_hari_terakhir']['omset']).'** dari '.$sales['7_hari_terakhir']['transaksi'].' transaksi.',
            '- 30 hari terakhir: **'.Rupiah::format($sales['30_hari_terakhir']['omset']).'** dari '.$sales['30_hari_terakhir']['transaksi'].' transaksi.',
            '- Rata-rata nilai transaksi: **'.Rupiah::format($context['rata_rata_nilai_transaksi']).'**.',
        ]);
    }

    private function profitAnswer(array $context): string
    {
        $profit = $context['laba_rugi_30_hari'];

        return implode("\n", [
            '**Laba Rugi 30 Hari Terakhir**',
            '- Omset: '.Rupiah::format($profit['omset']),
            '- HPP: '.Rupiah::format($profit['hpp']),
            '- Laba kotor: **'.Rupiah::format($profit['laba_kotor']).'**',
            '- Pengeluaran operasional: '.Rupiah::format($profit['pengeluaran']),
            '- Laba bersih: **'.Rupiah::format($profit['laba_bersih']).'**',
        ]);
    }

    private function topProductAnswer(array $context): string
    {
        $products = $context['produk_terlaris_30_hari'];

        if (empty($products)) {
            return 'Belum ada data penjualan produk dalam 30 hari terakhir.';
        }

        $lines = ['**Produk Terlaris 30 Hari Terakhir**'];

        foreach ($products as $index => $product) {
            $lines[] = ($index + 1).'. '.$product['produk'].' — '.$product['qty_terjual'].' terjual ('.Rupiah::format($product['omset']).')';
        }

        return implode("\n", $lines);
    }

    private function stockAnswer(array $context): string
    {
        $stock = $context['stok_menipis'];

        if (empty($stock)) {
            return 'Semua stok produk dalam kondisi aman. Tidak ada produk yang menyentuh batas minimum.';
        }

        $lines = ['**Produk dengan Stok Menipis**'];

        foreach ($stock as $product) {
            $lines[] = '- '.$product['produk'].': stok **'.$product['stok'].' '.$product['satuan'].'** (minimum '.$product['stok_minimum'].').';
        }

        $restock = $context['prediksi_restock'];

        if (! empty($restock)) {
            $lines[] = '';
            $lines[] = '**Prediksi Restock (14 hari)**';

            foreach (array_slice($restock, 0, 5) as $item) {
                $days = $item['perkiraan_habis_dalam_hari'];
                $lines[] = '- '.$item['produk'].': sisa '.$item['stok'].', rata-rata terjual '.$item['rata_rata_terjual_per_hari'].'/hari'
                    .($days !== null ? ', perkiraan habis dalam '.$days.' hari' : '')
                    .'. Saran restock: **'.$item['rekomendasi_restock_14_hari'].' unit**.';
            }
        }

        return implode("\n", $lines);
    }

    private function expenseAnswer(array $context): string
    {
        $expenses = $context['pengeluaran_per_kategori_30_hari'];
        $total = $context['laba_rugi_30_hari']['pengeluaran'];

        if (empty($expenses)) {
            return 'Belum ada pengeluaran yang tercatat dalam 30 hari terakhir.';
        }

        $lines = ['**Pengeluaran 30 Hari Terakhir: '.Rupiah::format($total).'**'];

        foreach ($expenses as $expense) {
            $lines[] = '- '.$expense['kategori'].': '.Rupiah::format($expense['total']);
        }

        return implode("\n", $lines);
    }

    private function anomalyAnswer(array $context): string
    {
        $anomalies = $context['anomali_transaksi'];

        if (empty($anomalies)) {
            return 'Tidak ditemukan anomali transaksi pada 30 hari terakhir. Diskon, nilai transaksi, dan total pembayaran berada dalam batas wajar.';
        }

        $lines = ['**Anomali Transaksi yang Perlu Dicek**'];

        foreach ($anomalies as $anomaly) {
            $notes = [];

            if ($anomaly['subtotal'] > 0 && $anomaly['diskon'] > $anomaly['subtotal'] * 0.3) {
                $notes[] = 'diskon di atas 30%';
            }

            if ($anomaly['total'] <= 0) {
                $notes[] = 'total nol';
            }

            if (empty($notes)) {
                $notes[] = 'nilai transaksi jauh di atas rata-rata';
            }

            $lines[] = '- '.$anomaly['invoice'].' ('.$anomaly['tanggal'].'): total '.Rupiah::format($anomaly['total'])
                .' — '.implode(', ', $notes).'.';
        }

        return implode("\n", $lines);
    }

    private function overviewAnswer(array $context): string
    {
        $sales = $context['penjualan'];
        $profit = $context['laba_rugi_30_hari'];
        $lowStock = count($context['stok_menipis']);
        $anomalies = count($context['anomali_transaksi']);

        return implode("\n", [
            '**Ringkasan Bisnis**',
            '- Omset hari ini: **'.Rupiah::format($sales['hari_ini']['omset']).'** ('.$sales['hari_ini']['transaksi'].' transaksi).',
            '- Omset 30 hari: **'.Rupiah::format($sales['30_hari_terakhir']['omset']).'**, laba bersih **'.Rupiah::format($profit['laba_bersih']).'**.',
            '- Produk stok menipis: **'.$lowStock.' produk**.',
            '- Anomali transaksi terdeteksi: **'.$anomalies.' transaksi**.',
            '',
            'Anda bisa bertanya: "produk terlaris", "stok menipis", "laba bulan ini", "pengeluaran", atau "audit anomali".',
        ]);
    }
}
