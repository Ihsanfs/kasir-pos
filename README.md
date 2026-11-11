# Kasir 2026 — Aplikasi POS (Point of Sale)

Aplikasi kasir modern berbasis **Laravel 12 + Filament v3 + MySQL** untuk toko retail / F&B.
Mendukung penjualan cepat, cetak struk thermal 58mm, laporan laba rugi, shift kasir, multi cabang,
pembayaran digital (QRIS via Midtrans), dan asisten AI untuk audit bisnis.

---

## 1. Fitur

### Kasir (POS)
- Pencarian produk + input barcode (Enter langsung menambahkan produk).
- Kategori produk dengan filter cepat.
- Dukungan **varian produk** (harga & stok terpisah per varian).
- Keranjang interaktif: tambah/kurang qty, hapus item, diskon, pajak otomatis.
- Tombol uang cepat + hitung **kembalian** otomatis.
- Pilih pelanggan (opsional) dan metode bayar (Tunai / QRIS / Transfer / Kartu).
- Stok berkurang otomatis saat checkout, dalam satu transaksi database.
- Setelah bayar: struk terbuka di tab baru, **auto-print**, lalu tab menutup sendiri.

### Struk 58mm
- Layout CSS khusus printer thermal 58mm (ESC/POS via browser print).
- Berisi nama toko, alamat, telepon, nomor invoice, kasir, item, total, bayar, kembalian, metode, footer.
- **QR code** pada setiap struk untuk verifikasi cepat via kamera (halaman `/pos/scan`). QR berisi URL cetak struk + nomor invoice. Teks nomor invoice juga ditampilkan di bawah QR untuk input manual.

### Verifikasi Struk via QR Code
- Setiap struk cetak mencakup QR code yang berisi URL langsung ke halaman cetak struk.
- Buka halaman **Scan QR Struk** (`/pos/scan`) dari detail transaksi atau menu kasir.
- Arahkan kamera ke QR code pada struk fisik untuk memverifikasi detail transaksi (invoice, waktu, total, metode pembayaran).
- Tombol "Buka Struk" membuka halaman cetak lengkap di browser.

### Shift & Kas
- Buka shift dengan kas awal, tutup shift dengan kas aktual.
- Perhitungan otomatis: kas seharusnya = kas awal + penjualan tunai.
- Selisih kas (lebih/kurang) tercatat untuk audit.

### Laporan Laba Rugi
- Filter rentang tanggal.
- Rumus:
  `Omset − HPP = Laba Kotor`
  `Laba Kotor − Pengeluaran = Laba Bersih`
- Ringkasan kartu: Omset, HPP, Laba Kotor, Pengeluaran, Laba Bersih (dengan margin).
- Tabel harian dan produk terlaris.
- Pencatatan pengeluaran operasional (sewa, listrik, gaji, dll).

### Data Master
- Produk (foto, SKU, barcode, satuan, harga modal, harga jual, stok, stok minimum, varian, status aktif).
- Kategori, pelanggan, cabang/toko, pengguna, dan pengaturan aplikasi.

### Dashboard
- Statistik hari ini: penjualan, laba kotor, pengeluaran bulan ini, stok menipis.
- Grafik penjualan, daftar produk stok rendah, transaksi terbaru.

### Asisten AI
- Mode **lokal** (tanpa API key): analisis berbasis aturan untuk penjualan, produk terlaris,
  stok/restock, laba rugi, pengeluaran, dan audit anomali transaksi.
- Mode **online** opsional: OpenAI atau Anthropic Claude (isi API key di Pengaturan).

### Pembayaran Digital (Midtrans & QRIS)
- **Metode pembayaran**: Tunai, QRIS, Transfer Bank, E-Wallet (OVO/GoPay/Dana), Kartu Debit/Kredit.
- Toggle enable/disable masing-masing metode di halaman Pengaturan → Pembayaran Online.
- Midtrans Snap untuk checkout non-tunai (QRIS, e-wallet, transfer, kartu).
- Webhook `/midtrans/notification` memperbarui status pembayaran transaksi secara otomatis.
- Setelah checkout non-tunai: QR code pembayaran ditampilkan di layar kasir, bisa di-scan pelanggan atau dibuka di tab baru.
- Struk cetak menampilkan ikon dan label metode pembayaran (💵 Tunai, 📱 QRIS, 🏦 Transfer, dll.).
- Halaman **Scan QR Struk** (`/pos/scan`) untuk verifikasi struk fisik melalui kamera.

### Multi Cabang & Pengguna
- Setiap transaksi, shift, pengeluaran, dan pengguna terhubung ke cabang.
- Role admin/kasir sederhana via status aktif pengguna (`is_active`).

---

## 2. Kebutuhan Sistem

| Komponen | Versi |
|---|---|
| PHP | 8.2+ (ekstensi `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`) |
| Composer | 2.x |
| MySQL | 8.0+ |
| Node.js | Tidak diperlukan (tanpa build Vite/npm) |

---

## 3. Instalasi

composer install
copy .env.example .env
php artisan key:generate
```

### Konfigurasi MySQL

Buat database:

```sql
CREATE DATABASE kasir_2026 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Sesuaikan `.env`:

```env
APP_NAME="Kasir 2026"
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kasir_2026
DB_USERNAME=root
DB_PASSWORD=
```

### Migrasi + data demo

```bash
php artisan migrate:fresh --seed
php artisan storage:link
```

Data demo mencakup: 1 cabang, 2 pengguna, 16 produk (2 varian), 4 pelanggan,
6 pengeluaran, dan transaksi 21 hari terakhir.

### Jalankan

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Buka `http://127.0.0.1:8000/admin`.

---

## 4. Akun Demo

| Peran | Email | Password |
|---|---|---|
| Administrator | `admin@kasir.test` | `password` |
| Kasir | `kasir@kasir.test` | `password` |

Halaman penting:

- Dashboard: `/admin`
- Kasir (POS): `/admin/pos`
- Riwayat Transaksi: `/admin/transactions`
- Shift: `/admin/shifts`
- Laporan Laba Rugi: `/admin/profit-loss-report`
- Asisten AI: `/admin/ai-assistant`
- Pengaturan: `/admin/settings`

---

## 5. Struktur Penting

```
app/
├─ Filament/
│  ├─ Pages/          Pos, ProfitLossReport, AiAssistant, Settings
│  ├─ Resources/      Category, Product, Customer, Branch, Expense, User, Transaction, Shift
│  └─ Widgets/        StatsOverview, SalesChart, LowStockProducts, RecentTransactions
├─ Models/            Branch, Category, Customer, Expense, Product, ProductVariant,
│                     Setting, Shift, Transaction, TransactionItem, User
└─ Services/
   ├─ PosService.php       checkout transaksional, stok, invoice, total shift
   ├─ MidtransService.php  Snap + verifikasi webhook
   └─ AiService.php        konteks bisnis + jawaban lokal/online

database/seeders/
├─ PosSeeder.php              pengguna, pengaturan, kategori, produk, pelanggan
├─ DemoExpenseSeeder.php      pengeluaran demo
└─ DemoTransactionSeeder.php   transaksi 21 hari terakhir

resources/views/
├─ filament/pages/pos.blade.php              UI kasir + listener print
├─ filament/pages/profit-loss-report.blade.php
├─ filament/pages/ai-assistant.blade.php
├─ filament/pages/settings.blade.php
├─ filament/resources/pos/receipt.blade.php  struk 58mm auto-print
└─ filament/resources/transaction/detail.blade.php
```

---

## 6. Alur Transaksi (PosService)

1. Validasi item & stok (termasuk varian).
2. Hitung subtotal, diskon, pajak, total.
3. Buat `transactions` + `transaction_items` dalam DB transaction.
4. Kurangi stok produk/varian.
5. Generate nomor invoice `INV-YYYYMMDD-XXXX`.
6. Perbarui agregat shift (total penjualan, tunai, jumlah transaksi).
7. (Opsional) buat Snap token Midtrans untuk non-tunai.

---

## 7. Pengujian

```bash
php artisan test --filter=PosApplicationTest
```

Test berjalan di SQLite in-memory (tidak mengubah data MySQL) dan mencakup:
render halaman admin, checkout + pengurangan stok, alur Livewire POS,
buka/tutup shift, dan analisis AI lokal.

---

## 8. Catatan Operasional

- **Cetak struk**: browser harus mengizinkan popup/print untuk `127.0.0.1`. Pilih printer thermal 58mm,
  margin minimal, skala 100%.
- **Midtrans**: isi Server Key & Client Key di Pengaturan → Pembayaran Online, lalu daftarkan
  URL notifikasi `https://domain-anda/midtrans/notification`.
- **AI online**: isi provider, model, dan API key di Pengaturan → Asisten AI. Tanpa API key,
  asisten tetap berfungsi dalam mode analisis lokal.
- **Backup**: `mysqldump -u root kasir_2026 > backup.sql`.

---

## 9. Lisensi

Dibangun di atas Laravel 12 dan Filament v3 (MIT). Kode aplikasi ini bebas digunakan
dan dimodifikasi sesuai kebutuhan bisnis Anda.
