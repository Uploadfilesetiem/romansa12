<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use App\Services\LaporanService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    public function __construct(private LaporanService $laporan)
    {
    }

    private function paramTanggal(Request $request): array
    {
        return [
            $request->query('start', now()->toDateString()),
            $request->query('end', now()->toDateString()),
            $request->query('metode'),
        ];
    }

    public function index(Request $request)
    {
        [$start, $end, $metode] = $this->paramTanggal($request);

        return view('laporan.index', $this->laporan->semuaData($start, $end, $metode));
    }

    // Download PDF sungguhan (bukan cuma dialog print browser).
    public function pdf(Request $request)
    {
        [$start, $end, $metode] = $this->paramTanggal($request);
        $viewData = $this->laporan->semuaData($start, $end, $metode);

        $pdf = Pdf::loadView('laporan.pdf', $viewData)->setPaper('a4', 'portrait');

        return $pdf->download("Laporan-RotiBakarRomansa-{$start}_{$end}.pdf");
    }

    public function batalkan(Transaksi $transaksi)
    {
        $transaksi->load('items');
        $totalQty = $transaksi->items->sum('qty');

        DB::transaction(function () use ($transaksi, $totalQty) {
            if ($totalQty > 0) {
                DB::table('stok_master')->where('id', 1)->increment('stok', $totalQty, [
                    'updated_at' => now(),
                ]);
                DB::table('stok_log')->insert([
                    'produk_id' => null,
                    'jenis' => 'pembatalan',
                    'jumlah' => $totalQty,
                    'keterangan' => "Pembatalan transaksi #{$transaksi->id}",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $transaksi->delete();
        });

        return back()->with('sukses', 'Transaksi dibatalkan, stok roti sudah dikembalikan.');
    }
}
