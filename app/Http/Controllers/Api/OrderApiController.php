<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Produk;
use App\Models\Transaksi;
use App\Models\TransaksiItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderApiController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'queue_number'  => 'required|integer',
            'customer_id'   => 'required|string',
            'customer_name' => 'required|string',
            'item_name'     => 'required|string',
            'price'         => 'required|numeric',
            'note'          => 'nullable|string',
            'status'        => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            // Menggunakan kolom 'nama' sesuai skema database produk Laravel
            $produk = Produk::where('nama', 'LIKE', '%' . $validated['item_name'] . '%')->first();

            $transaksi = Transaksi::create([
                'kode_transaksi' => 'WA-' . date('Ymd') . '-' . str_pad($validated['queue_number'], 3, '0', STR_PAD_LEFT),
                'nama_pelanggan' => $validated['customer_name'] . ' (WA)',
                'total_harga'    => $validated['price'],
                'metode_bayar'   => 'pending',
                'status'         => 'menunggu_konfirmasi',
            ]);

            TransaksiItem::create([
                'transaksi_id' => $transaksi->id,
                'produk_id'    => $produk ? $produk->id : null,
                'nama_produk'  => $validated['item_name'],
                'harga'        => $validated['price'],
                'jumlah'       => 1,
                'subtotal'     => $validated['price'],
                'catatan'      => $validated['note'] ?? '-',
            ]);

            DB::commit();

            Log::info('Order WA berhasil disimpan ke database kasir:', ['id' => $transaksi->id]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Pesanan berhasil masuk ke sistem kasir',
                'data'    => $transaksi
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menyimpan Order WA: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menyimpan pesanan ke kasir',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
