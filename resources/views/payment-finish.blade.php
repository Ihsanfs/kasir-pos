<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pembayaran</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
            font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
            color: #0f172a;
        }

        .card {
            max-width: 420px;
            padding: 2rem;
            margin: 1rem;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(15, 23, 42, .08);
        }

        .icon {
            width: 56px;
            height: 56px;
            margin: 0 auto 1rem;
            border-radius: 999px;
            background: #ecfdf5;
            color: #059669;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
        }

        h1 {
            font-size: 1.2rem;
            margin: 0 0 .5rem;
        }

        p {
            color: #64748b;
            font-size: .9rem;
            line-height: 1.5;
            margin: 0 0 1.25rem;
        }

        a {
            display: inline-block;
            padding: .7rem 1.2rem;
            border-radius: 10px;
            background: #059669;
            color: #fff;
            font-size: .85rem;
            font-weight: 700;
            text-decoration: none;
        }

        button {
            margin-top: .75rem;
            display: block;
            width: 100%;
            padding: .6rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #fff;
            color: #475569;
            font-size: .8rem;
            font-weight: 600;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">✓</div>
        <h1>Pembayaran Selesai</h1>
        <p>
            Terima kasih. Status pembayaran akan diperbarui otomatis setelah notifikasi dari
            penyedia pembayaran diterima. Anda dapat kembali ke halaman kasir.
        </p>
        <a href="{{ url('/admin/pos') }}">Kembali ke Kasir</a>
        <button type="button" onclick="window.close()">Tutup Tab</button>
    </div>
</body>
</html>
