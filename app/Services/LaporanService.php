<?php

namespace App\Services;

use App\Models\Transaksi;

// Logika laporan dipusatkan di sini supaya bisa dipakai bersama oleh
// halaman Laporan (web) dan perintah pengiriman laporan harian ke WhatsApp.
class LaporanService
{
    public function ambil(string $start, string $end, ?string $metode = null)
    {
        $q = Transaksi::with('items')
            ->whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end);

        if ($metode) {
            $q->where('metode_bayar', $metode);
        }

        return $q->orderByDesc('created_at')->get();
    }

    public function rekapMenu($data): array
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

    public function ringkasan($data): array
    {
        return [
            'totalTunai' => $data->where('metode_bayar', 'tunai')->sum('total'),
            'totalQris' => $data->where('metode_bayar', 'qris')->sum('total'),
        ];
    }

    public function daftarCatatan($data): array
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

    public function barisPerItem($data): array
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

    public function semuaData(string $start, string $end, ?string $metode = null): array
    {
        $data = $this->ambil($start, $end, $metode);
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
}
