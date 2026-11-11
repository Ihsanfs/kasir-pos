<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Scan QR Struk — Kasir 2026</title>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 0;
            font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
            background: #f3f4f6;
            color: #111827;
        }

        .container {
            max-width: 520px;
            margin: 0 auto;
            padding: 24px 16px;
        }

        .header {
            text-align: center;
            margin-bottom: 24px;
        }

        .header h1 {
            margin: 0 0 8px;
            font-size: 1.25rem;
            color: #059669;
        }

        .header p {
            margin: 0;
            font-size: 0.9rem;
            color: #6b7280;
        }

        .camera-row {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 16px;
        }

        select {
            padding: 10px 14px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            background: #fff;
            font-size: 0.9rem;
            min-width: 220px;
        }

        #reader {
            width: 100%;
            height: 320px;
            background: #000;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
            font-size: 0.9rem;
        }

        .controls {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 20px;
        }

        button {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            transition: opacity 0.2s;
        }

        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-primary {
            background: #059669;
            color: #fff;
        }

        .btn-primary:not(:disabled):hover {
            background: #047857;
        }

        .btn-secondary {
            background: #e5e7eb;
            color: #111827;
        }

        .btn-secondary:not(:disabled):hover {
            background: #d1d5db;
        }

        .result {
            background: #fff;
            padding: 18px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            margin-bottom: 20px;
            display: none;
            text-align: center;
        }

        .result-title {
            font-weight: 700;
            color: #059669;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .result-invoice {
            font-size: 0.85rem;
            color: #6b7280;
            margin-bottom: 10px;
        }

        .result-invoice span {
            font-weight: 700;
            color: #111827;
        }

        .result-url {
            display: block;
            word-break: break-all;
            font-family: ui-monospace, monospace;
            font-size: 0.8rem;
            color: #059669;
            margin-bottom: 12px;
        }

        .hint {
            text-align: center;
            color: #9ca3af;
            font-size: 0.8rem;
            margin-top: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📷 Scan QR Struk</h1>
            <p>Arahkan kamera ke QR code pada struk kasir untuk memverifikasi transaksi.</p>
        </div>

        <div class="camera-row">
            <select id="cameraSelect"></select>
        </div>

        <div id="reader">Menunggu kamera…</div>

        <div class="controls">
            <button class="btn-primary" id="startBtn" disabled>Mulai Scan</button>
            <button class="btn-secondary" id="stopBtn" disabled>Stop</button>
        </div>

        <div class="result" id="result">
            <div class="result-title">✅ Struk Ditemukan</div>
            <div class="result-invoice">Invoice: <span id="resultInvoice"></span></div>
            <a id="resultLink" class="result-url" href="#" target="_blank"></a>
            <button class="btn-primary" style="width: 100%;" onclick="openResult()">Buka Struk</button>
        </div>

        <div class="hint">Pastikan izin kamera diberikan ke browser.</div>
    </div>

    <script>
        let html5QrcodeScanner = null;
        let currentCameraId = null;

        const cameraSelect = document.getElementById('cameraSelect');
        const startBtn = document.getElementById('startBtn');
        const stopBtn = document.getElementById('stopBtn');
        const result = document.getElementById('result');
        const resultInvoice = document.getElementById('resultInvoice');
        const resultLink = document.getElementById('resultLink');

        async function loadCameras() {
            try {
                const cameras = await Html5Qrcode.getCameras();
                cameraSelect.innerHTML = '';
                if (cameras && cameras.length) {
                    cameras.forEach((camera) => {
                        const option = document.createElement('option');
                        option.value = camera.id;
                        option.text = camera.label || 'Kamera ' + (cameraSelect.length + 1);
                        cameraSelect.appendChild(option);
                    });
                    currentCameraId = cameras[0].id;
                    startBtn.disabled = false;
                } else {
                    alert('Tidak ada kamera yang terdeteksi.');
                }
            } catch (err) {
                console.error(err);
                alert('Gagal memuat kamera: ' + err.message);
            }
        }

        function onScanSuccess(decodedText) {
            stopScan();
            const url = decodedText.trim();
            const invoiceMatch = url.match(/INV-[\d-]+/);
            resultInvoice.textContent = invoiceMatch ? invoiceMatch[0] : 'Tidak diketahui';
            resultLink.href = url;
            resultLink.textContent = url;
            result.style.display = 'block';
        }

        function onScanFailure(error) {
            // Normal during continuous scanning; ignore
        }

        function startScan() {
            result.style.display = 'none';
            const config = { fps: 10, qrbox: 200 };
            html5QrcodeScanner = new Html5Qrcode("reader");
            html5QrcodeScanner.start(currentCameraId, config, onScanSuccess, onScanFailure);
            startBtn.disabled = true;
            stopBtn.disabled = false;
        }

        function stopScan() {
            if (html5QrcodeScanner) {
                html5QrcodeScanner.stop().then(() => {
                    html5QrcodeScanner.clear();
                    html5QrcodeScanner = null;
                });
            }
            startBtn.disabled = false;
            stopBtn.disabled = true;
        }

        function openResult() {
            if (resultLink.href) {
                window.location.href = resultLink.href;
            }
        }

        startBtn.addEventListener('click', startScan);
        stopBtn.addEventListener('click', stopScan);

        cameraSelect.addEventListener('change', (e) => {
            currentCameraId = e.target.value;
            if (html5QrcodeScanner) {
                stopScan();
                startScan();
            }
        });

        loadCameras();
    </script>
</body>
</html>
