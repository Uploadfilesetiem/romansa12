@extends('layouts.app')
@section('title', 'Laporan')

@section('content')
  <div class="page-title">Laporan</div>

  <form method="GET" action="{{ route('laporan.index') }}" class="filter-row">
    <input type="date" name="start" class="input" value="{{ $start }}">
    <input type="date" name="end" class="input" value="{{ $end }}">
    <select name="metode" class="input" style="flex:0 0 110px;">
      <option value="">Semua</option>
      <option value="tunai" @selected($metode === 'tunai')>Tunai</option>
      <option value="qris" @selected($metode === 'qris')>QRIS</option>
    </select>
    <button type="submit" class="btn-small navy">Filter</button>
  </form>

  <div class="menu-grid" style="grid-template-columns: 1fr 1fr; margin-bottom:16px;">
    <div class="card card-pad fade-item">
      <div class="section-sub">Total Tunai</div>
      <div style="font-weight:800;font-size:17px;color:var(--navy);">Rp{{ number_format($totalTunai,0,',','.') }}</div>
    </div>
    <div class="card card-pad fade-item" style="animation-delay:.05s">
      <div class="section-sub">Total QRIS</div>
      <div style="font-weight:800;font-size:17px;color:var(--navy);">Rp{{ number_format($totalQris,0,',','.') }}</div>
    </div>
  </div>

  <div class="card table-wrap" style="margin-bottom:16px;">
    <div style="padding:12px 14px 0;">
      <div class="section-title">Catatan Selai/Menu Keluar</div>
      <div class="section-sub">Rekap jumlah tiap menu (selai) yang terjual pada periode ini, dari yang paling laris.</div>
    </div>
    <table class="list" style="margin-top:8px;">
      <thead><tr><th>Menu / Selai</th><th class="text-right">Qty</th><th class="text-right">Omzet</th></tr></thead>
      <tbody>
        @forelse ($rekapMenu as $r)
          <tr>
            <td>{{ $r['nama'] }}</td>
            <td class="text-right" style="font-weight:700;">{{ $r['qty'] }}</td>
            <td class="text-right">Rp{{ number_format($r['total'],0,',','.') }}</td>
          </tr>
        @empty
          <tr><td colspan="3" class="rekap-empty">Belum ada menu yang terjual pada periode ini.</td></tr>
        @endforelse
      </tbody>
      @if (count($rekapMenu))
        <tfoot>
          <tr>
            <td>Total</td>
            <td class="text-right">{{ array_sum(array_column($rekapMenu, 'qty')) }}</td>
            <td class="text-right">Rp{{ number_format($totalTunai + $totalQris,0,',','.') }}</td>
          </tr>
        </tfoot>
      @endif
    </table>
  </div>

  @if (count($daftarCatatan))
    <div class="card table-wrap" style="margin-bottom:16px;">
      <div style="padding:12px 14px 0;">
        <div class="section-title">Catatan Kasir per Item</div>
        <div class="section-sub">Catatan bebas yang diketik kasir (mis. nama selai/topping spesifik) pada periode ini.</div>
      </div>
      <table class="list" style="margin-top:8px;">
        <thead><tr><th>Waktu</th><th>Menu</th><th>Catatan</th></tr></thead>
        <tbody>
          @foreach ($daftarCatatan as $c)
            <tr>
              <td style="white-space:nowrap;">{{ $c['waktu']->translatedFormat('d M, H:i') }}</td>
              <td>{{ $c['nama_produk'] }}</td>
              <td>{{ $c['catatan'] }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif

  <div class="flex-between" style="margin-bottom:10px;">
    <div class="section-title">Riwayat Transaksi</div>
    <a href="{{ route('laporan.pdf', request()->query()) }}">
      <button type="button" class="btn-small ghost">⬇️ Download PDF</button>
    </a>
  </div>

  <div class="card table-wrap" style="margin-bottom:16px;">
    <div style="padding:12px 14px 0;">
      <div class="section-title">Tunai</div>
      <div class="section-sub">Menu &amp; catatan yang dibayar tunai pada periode ini.</div>
    </div>
    <table class="list" style="margin-top:8px;">
      <thead>
        <tr><th>Kode</th><th>Waktu</th><th>Menu</th><th>Catatan</th><th class="text-right">Qty</th><th class="text-right">Subtotal</th><th class="text-center">Aksi</th></tr>
      </thead>
      <tbody>
        @forelse ($barisTunai as $b)
          <tr>
            <td style="white-space:nowrap;">{{ $b['kode'] }}</td>
            <td style="white-space:nowrap;">{{ $b['waktu']->translatedFormat('d M, H:i') }}</td>
            <td>{{ $b['nama_produk'] }}</td>
            <td>{{ $b['catatan'] ?? '-' }}</td>
            <td class="text-right">{{ $b['qty'] }}</td>
            <td class="text-right">Rp{{ number_format($b['subtotal'],0,',','.') }}</td>
            <td class="text-center">
              @if ($b['item_pertama'])
                <form method="POST" action="{{ route('laporan.batalkan', $b['transaksi_id']) }}" data-confirm="Batalkan transaksi {{ $b['kode'] }}? Stok roti akan dikembalikan.">
                  @csrf @method('DELETE')
                  <button type="submit" class="btn-mini hapus">Batalkan</button>
                </form>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="7" class="rekap-empty">Belum ada transaksi tunai pada periode ini.</td></tr>
        @endforelse
      </tbody>
      @if (count($barisTunai))
        <tfoot>
          <tr><td colspan="5">Total Tunai</td><td class="text-right" colspan="2">Rp{{ number_format($totalTunai,0,',','.') }}</td></tr>
        </tfoot>
      @endif
    </table>
  </div>

  <div class="card table-wrap">
    <div style="padding:12px 14px 0;">
      <div class="section-title">QRIS</div>
      <div class="section-sub">Menu &amp; catatan yang dibayar QRIS pada periode ini.</div>
    </div>
    <table class="list" style="margin-top:8px;">
      <thead>
        <tr><th>Kode</th><th>Waktu</th><th>Menu</th><th>Catatan</th><th class="text-right">Qty</th><th class="text-right">Subtotal</th><th class="text-center">Aksi</th></tr>
      </thead>
      <tbody>
        @forelse ($barisQris as $b)
          <tr>
            <td style="white-space:nowrap;">{{ $b['kode'] }}</td>
            <td style="white-space:nowrap;">{{ $b['waktu']->translatedFormat('d M, H:i') }}</td>
            <td>{{ $b['nama_produk'] }}</td>
            <td>{{ $b['catatan'] ?? '-' }}</td>
            <td class="text-right">{{ $b['qty'] }}</td>
            <td class="text-right">Rp{{ number_format($b['subtotal'],0,',','.') }}</td>
            <td class="text-center">
              @if ($b['item_pertama'])
                <form method="POST" action="{{ route('laporan.batalkan', $b['transaksi_id']) }}" data-confirm="Batalkan transaksi {{ $b['kode'] }}? Stok roti akan dikembalikan.">
                  @csrf @method('DELETE')
                  <button type="submit" class="btn-mini hapus">Batalkan</button>
                </form>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="7" class="rekap-empty">Belum ada transaksi QRIS pada periode ini.</td></tr>
        @endforelse
      </tbody>
      @if (count($barisQris))
        <tfoot>
          <tr><td colspan="5">Total QRIS</td><td class="text-right" colspan="2">Rp{{ number_format($totalQris,0,',','.') }}</td></tr>
        </tfoot>
      @endif
    </table>
  </div>
@endsection
