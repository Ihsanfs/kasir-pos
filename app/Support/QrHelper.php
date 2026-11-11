<?php

namespace App\Support;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class QrHelper
{
    /**
     * Hasilkan QR code sebagai string SVG inline.
     *
     * @param  string  $content  Konten QR (biasanya URL cetak struk + inv parameter)
     * @param  int     $size     Ukuran modul QR (px)
     * @return string
     */
    public static function svg(string $content, int $size = 6): string
    {
        $options = new QROptions([
            'scale'           => $size,
            'eccLevel'        => EccLevel::H,
            'backgroundColor' => '#ffffff',
            'foregroundColor' => '#000000',
            'addQuietzone'    => true,
            'outputBase64'    => false,
        ]);

        $qrCode = new QRCode($options);

        return $qrCode->render($content);
    }
}
