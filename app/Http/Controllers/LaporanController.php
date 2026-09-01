<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    private function ambilData(Request $request): array
    {
        $start = $request->query('start', now()->toDateString());
        $end = $request->query('end', now()->toDateString());
        $metode = $request->query('metode');

        $q = Transaksi::with('items')
            ->whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end);

        if ($metode) {
            $q->where('metode_bayar', $metode);
        }

        $data = $q->orderByDesc('created_at')->get();

        return [$data, $start, $end, $metode];
    }

    private function rekapMenu($data): array
    {
        $rekap = [];
        foreach ($data as $t) {
            foreach ($t->items as $it) {
                $key = $it->nama_produk;
                if (!isset($rekap[$key])) {
                    $rekap[$key] = ['nama' => $key, 'qty' => 0, 'total' => 0];
                }
                $rekap[$key]['qty'] += $it->qty;
                $rekap[$key]['total'] += $it->subtotal;
            }
        }
        usort($rekap, fn ($a, $b) => $b['qty'] <=> $a['qty']);

        return array_values($rekap);
    }

    private function ringkasan($data): array
    {
        return [
            'totalTunai' => $data->where('metode_bayar', 'tunai')->sum('total'),
            'totalQris' => $data->where('metode_bayar', 'qris')->sum('total'),
        ];
    }

    // Daftar catatan bebas yang diketik kasir per item (mis. nama selai/topping
    // spesifik), diurutkan dari yang paling baru, supaya bisa ditelusuri.
    private function daftarCatatan($data): array
    {
        $daftar = [];
        foreach ($data as $t) {
            foreach ($t->items as $it) {
                if (!empty($it->catatan)) {
                    $daftar[] = [
                        'waktu' => $t->created_at,
                        'kode' => $t->kode,
                        'nama_produk' => $it->nama_produk,
                        'catatan' => $it->catatan,
                    ];
                }
            }
        }
        usort($daftar, fn ($a, $b) => $b['waktu'] <=> $a['waktu']);

        return $daftar;
    }

    // Susun baris per ITEM (bukan per transaksi), supaya nama menu & catatan
    // ikut tampil di tabel riwayat, dikelompokkan per metode bayar.
    private function barisPerItem($data): array
    {
        $baris = [];
        foreach ($data as $t) {
            foreach ($t->items as $idx => $it) {
                $baris[] = [
                    'transaksi_id' => $t->id,
                    'kode' => $t->kode,
                    'waktu' => $t->created_at,
                    'nama_produk' => $it->nama_produk,
                    'qty' => $it->qty,
                    'subtotal' => $it->subtotal,
                    'catatan' => $it->catatan,
                    'total_transaksi' => $t->total,
                    'item_pertama' => $idx === 0,
                ];
            }
        }

        return $baris;
    }

    private function semuaData(Request $request): array
    {
        [$data, $start, $end, $metode] = $this->ambilData($request);
        $ringkasan = $this->ringkasan($data);

        return [
            'data' => $data,
            'start' => $start,
            'end' => $end,
            'metode' => $metode,
            'totalTunai' => $ringkasan['totalTunai'],
            'totalQris' => $ringkasan['totalQris'],
            'rekapMenu' => $this->rekapMenu($data),
            'daftarCatatan' => $this->daftarCatatan($data),
            'barisTunai' => $this->barisPerItem($data->where('metode_bayar', 'tunai')),
            'barisQris' => $this->barisPerItem($data->where('metode_bayar', 'qris')),
        ];
    }

    public function index(Request $request)
    {
        return view('laporan.index', $this->semuaData($request));
    }

    // Download PDF sungguhan (bukan cuma dialog print browser).
    public function pdf(Request $request)
    {
        $viewData = $this->semuaData($request);

        $pdf = Pdf::loadView('laporan.pdf', $viewData)->setPaper('a4', 'portrait');

        $nama = "Laporan-RotiBakarRomansa-{$viewData['start']}_{$viewData['end']}.pdf";

        return $pdf->download($nama);
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
