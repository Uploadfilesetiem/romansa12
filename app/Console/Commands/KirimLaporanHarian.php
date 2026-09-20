<?php

namespace App\Console\Commands;

use App\Services\LaporanService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

// Dijalankan otomatis tiap jam 00:00 (lihat routes/console.php) untuk
// mengirim ringkasan stok + laporan PDF hari sebelumnya ke grup WhatsApp
// lewat Fonnte (gateway WhatsApp pihak ketiga).
class KirimLaporanHarian extends Command
{
    protected $signature = 'laporan:kirim-harian {tanggal? : Tanggal yang dilaporkan, default kemarin (YYYY-MM-DD)}';

    protected $description = 'Kirim ringkasan stok roti & laporan PDF harian ke grup WhatsApp via Fonnte';

    public function handle(LaporanService $laporan): int
    {
        $tanggal = $this->argument('tanggal') ?: now()->subDay()->toDateString();

        $token = config('services.fonnte.token');
        $target = config('services.fonnte.group_id');

        if (!$token || !$target) {
            $this->error('FONNTE_TOKEN atau FONNTE_GROUP_ID belum diatur di environment variables.');
            return self::FAILURE;
        }

        // Sisa stok SAAT INI (posisi akhir hari yang dilaporkan, karena
        // perintah ini jalan tepat jam 00:00 setelah hari itu berakhir).
        $sisaStok = (int) (DB::table('stok_master')->where('id', 1)->value('stok') ?? 0);

        $rotiMasuk = (int) DB::table('stok_log')
            ->where('jenis', 'tambah')
            ->whereDate('created_at', $tanggal)
            ->sum('jumlah');

        $terjual = (int) DB::table('stok_log')
            ->where('jenis', 'penjualan')
            ->whereDate('created_at', $tanggal)
            ->sum('jumlah');

        // Ada juga kemungkinan stok di-"Atur/Setting" langsung (bukan
        // ditambah/terjual) pada hari itu, mis. saat stock opname. Kalau ada,
        // hitungan "stok awal" di bawah ini tidak akurat 100% (karena "atur"
        // itu reset nilai, bukan penambahan/pengurangan) — jumlah kejadiannya
        // tetap kita tampilkan sebagai info tambahan biar kasir tahu.
        $jumlahAtur = (int) DB::table('stok_log')
            ->where('jenis', 'atur')
            ->whereDate('created_at', $tanggal)
            ->count();

        // Stok Awal + Masuk - Terjual = Sisa Akhir  =>  Stok Awal = Sisa Akhir - Masuk + Terjual
        $stokAwal = $sisaStok - $rotiMasuk + $terjual;

        $viewData = $laporan->semuaData($tanggal, $tanggal);

        $pdfBinary = Pdf::loadView('laporan.pdf', $viewData)->setPaper('a4', 'portrait')->output();
        $namaFile = "Laporan-RotiBakarRomansa-{$tanggal}.pdf";

        $pesan = "📋 *Laporan Harian Roti Bakar Romansa*\n"
            ."Tanggal: {$tanggal}\n\n"
            ."📦 Sisa roti kemarin (stok awal): {$stokAwal}\n"
            ."📥 Roti masuk: {$rotiMasuk}\n"
            ."🛒 Terjual: {$terjual}\n"
            ."🍞 Sisa stok akhir hari ini: {$sisaStok}\n"
            .($jumlahAtur > 0 ? "⚠️ Catatan: stok sempat di-atur ulang manual {$jumlahAtur}x hari ini, jadi hitungan stok awal di atas mungkin kurang akurat.\n" : '')
            ."\n💵 Total Tunai: Rp".number_format($viewData['totalTunai'], 0, ',', '.')."\n"
            ."📱 Total QRIS: Rp".number_format($viewData['totalQris'], 0, ',', '.')."\n\n"
            .'Laporan lengkap ada di file PDF terlampir.';

        $response = Http::withHeaders(['Authorization' => $token])
            ->attach('file', $pdfBinary, $namaFile)
            ->post('https://api.fonnte.com/send', [
                'target' => $target,
                'message' => $pesan,
                'filename' => $namaFile,
            ]);

        if ($response->failed()) {
            $this->error('Gagal mengirim ke Fonnte: '.$response->body());
            return self::FAILURE;
        }

        $this->info("Laporan harian ({$tanggal}) berhasil dikirim ke WhatsApp.");
        return self::SUCCESS;
    }
}
