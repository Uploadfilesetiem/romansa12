<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Roti Bakar Romansa {{ $start }} - {{ $end }}</title>
<style>
  body { font-family: DejaVu Sans, sans-serif; padding: 10px; color: #23283f; font-size: 11px; }
  h1 { font-size: 18px; margin-bottom: 0; font-family: Georgia, serif; }
  h2 { font-size: 13px; margin: 18px 0 6px; font-family: Georgia, serif; color: #23283f; }
  .sub { font-size: 10px; color: #888; margin-top: 2px; margin-bottom: 14px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 10px; }
  th, td { border: 1px solid #ccc; padding: 5px 6px; text-align: left; }
  th { background: #23283f; color: #fff; }
  tfoot td { font-weight: bold; background: #f2ede1; }
  .text-right { text-align: right; }
  .ringkasan { font-size: 11px; margin-bottom: 14px; }
  .ringkasan b { display: inline-block; width: 140px; }
  .empty { color: #999; font-style: italic; }
</style>
</head>
<body>
  <h1>Laporan Roti Bakar Romansa</h1>
  <div class="sub">Periode {{ $start }} s/d {{ $end }}</div>

  <div class="ringkasan">
    <div><b>Total Tunai</b> Rp{{ number_format($totalTunai, 0, ',', '.') }}</div>
    <div><b>Total QRIS</b> Rp{{ number_format($totalQris, 0, ',', '.') }}</div>
    <div><b>Grand Total</b> Rp{{ number_format($totalTunai + $totalQris, 0, ',', '.') }}</div>
    <div><b>Jumlah Transaksi</b> {{ $data->count() }}</div>
  </div>

  <h2>Catatan Selai/Menu Keluar</h2>
  <table>
    <thead>
      <tr><th>Menu / Selai</th><th class="text-right">Qty Terjual</th><th class="text-right">Omzet</th></tr>
    </thead>
    <tbody>
      @forelse ($rekapMenu as $r)
        <tr>
          <td>{{ $r['nama'] }}</td>
          <td class="text-right">{{ $r['qty'] }}</td>
          <td class="text-right">Rp{{ number_format($r['total'], 0, ',', '.') }}</td>
        </tr>
      @empty
        <tr><td colspan="3" class="empty">Belum ada menu terjual.</td></tr>
      @endforelse
    </tbody>
  </table>

  @if (count($daftarCatatan))
    <h2>Catatan Kasir per Item</h2>
    <table>
      <thead><tr><th>Waktu</th><th>Menu</th><th>Catatan</th></tr></thead>
      <tbody>
        @foreach ($daftarCatatan as $c)
          <tr>
            <td>{{ $c['waktu']->format('d/m/Y H:i') }}</td>
            <td>{{ $c['nama_produk'] }}</td>
            <td>{{ $c['catatan'] }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif

  <h2>Riwayat Transaksi Tunai</h2>
  <table>
    <thead>
      <tr><th>Kode</th><th>Waktu</th><th>Menu</th><th>Catatan</th><th class="text-right">Qty</th><th class="text-right">Subtotal</th></tr>
    </thead>
    <tbody>
      @forelse ($barisTunai as $b)
        <tr>
          <td>{{ $b['kode'] }}</td>
          <td>{{ $b['waktu']->format('d/m/Y H:i') }}</td>
          <td>{{ $b['nama_produk'] }}</td>
          <td>{{ $b['catatan'] ?? '-' }}</td>
          <td class="text-right">{{ $b['qty'] }}</td>
          <td class="text-right">Rp{{ number_format($b['subtotal'], 0, ',', '.') }}</td>
        </tr>
      @empty
        <tr><td colspan="6" class="empty">Belum ada transaksi tunai.</td></tr>
      @endforelse
    </tbody>
    <tfoot>
      <tr><td colspan="5">Total Tunai</td><td class="text-right">Rp{{ number_format($totalTunai, 0, ',', '.') }}</td></tr>
    </tfoot>
  </table>

  <h2>Riwayat Transaksi QRIS</h2>
  <table>
    <thead>
      <tr><th>Kode</th><th>Waktu</th><th>Menu</th><th>Catatan</th><th class="text-right">Qty</th><th class="text-right">Subtotal</th></tr>
    </thead>
    <tbody>
      @forelse ($barisQris as $b)
        <tr>
          <td>{{ $b['kode'] }}</td>
          <td>{{ $b['waktu']->format('d/m/Y H:i') }}</td>
          <td>{{ $b['nama_produk'] }}</td>
          <td>{{ $b['catatan'] ?? '-' }}</td>
          <td class="text-right">{{ $b['qty'] }}</td>
          <td class="text-right">Rp{{ number_format($b['subtotal'], 0, ',', '.') }}</td>
        </tr>
      @empty
        <tr><td colspan="6" class="empty">Belum ada transaksi QRIS.</td></tr>
      @endforelse
    </tbody>
    <tfoot>
      <tr><td colspan="5">Total QRIS</td><td class="text-right">Rp{{ number_format($totalQris, 0, ',', '.') }}</td></tr>
    </tfoot>
  </table>
</body>
</html>
