<?php

namespace App\Http\Controllers;

use App\Services\QrisService;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QrisController extends Controller
{
    // Mengembalikan gambar QRIS (SVG) yang nominalnya sudah otomatis terisi
    // sesuai total belanja, dibuat dari QRIS statis milik toko yang
    // disimpan di halaman Stok. Diproses sepenuhnya di HP, tanpa internet.
    public function gambar(Request $request)
    {
        $nominal = (int) $request->query('nominal', 0);
        $qrisStatis = DB::table('stok_master')->where('id', 1)->value('qris_statis');

        if (!$qrisStatis) {
            return $this->svgPesan('QRIS toko belum diatur', 'Isi dulu di halaman Stok');
        }
        if ($nominal <= 0) {
            return $this->svgPesan('Nominal tidak valid', '');
        }

        try {
            $dinamis = (new QrisService())->toDinamis($qrisStatis, $nominal);
        } catch (\Exception $e) {
            return $this->svgPesan('Format QRIS tidak valid', 'Cek kembali di halaman Stok');
        }

        $qrCode = QrCode::create($dinamis)
            ->setSize(320)
            ->setMargin(8);

        $result = (new SvgWriter())->write($qrCode);

        return response($result->getString(), 200)->header('Content-Type', 'image/svg+xml');
    }

    private function svgPesan(string $baris1, string $baris2)
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="320" height="180">'
            .'<rect width="100%" height="100%" fill="#fbe7e2"/>'
            .'<text x="50%" y="46%" text-anchor="middle" font-family="sans-serif" font-size="14" fill="#922f16">'.htmlspecialchars($baris1).'</text>'
            .'<text x="50%" y="62%" text-anchor="middle" font-family="sans-serif" font-size="12" fill="#922f16">'.htmlspecialchars($baris2).'</text>'
            .'</svg>';

        return response($svg, 200)->header('Content-Type', 'image/svg+xml');
    }
}
