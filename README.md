# Kasir Roti Bakar Romansa — siap deploy ke Laravel Cloud

Aplikasi ini Laravel 11 biasa, sudah disesuaikan supaya jalan mulus di
**Laravel Cloud** (cloud.laravel.com) tanpa error.

## Kenapa strukturnya begini (biar tidak error lagi)

- **Laravel 11**, bukan Laravel 10 — Laravel Cloud mensyaratkan Symfony
  7.4+, yang baru dipakai mulai Laravel 11.
- **Database MySQL/PostgreSQL**, BUKAN SQLite — filesystem Laravel Cloud
  bersifat sementara (ephemeral): setiap kali deploy ulang, isi file di
  server di-reset. Kalau pakai SQLite (database berupa file), datanya akan
  hilang setiap deploy. Karena itu, database WAJIB di-attach lewat
  dashboard Laravel Cloud (Postgres atau MySQL), bukan disimpan sebagai file.
- **Sesi & cache disimpan di database** (bukan file) — supaya tidak error
  acak seperti "CSRF token mismatch" kalau aplikasi berjalan di lebih dari
  satu instance sekaligus.

## Cara deploy ke Laravel Cloud

1. Push folder project ini ke repository Git (GitHub/GitLab/Bitbucket).
2. Buka [cloud.laravel.com](https://cloud.laravel.com) → buat project baru →
   hubungkan ke repository tadi.
3. Di halaman **Infrastructure**, klik **Add Database** → pilih **Postgres**
   atau **MySQL** → attach ke environment `production`. Laravel Cloud akan
   otomatis mengisi `DB_CONNECTION`, `DB_HOST`, `DB_DATABASE`, dst.
4. Di **Settings → General**, pilih PHP 8.2/8.3/8.4 (yang tersedia).
5. Di **Settings → Build Command**, isi:
   ```
   composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
   php artisan optimize
   ```
6. Di **Settings → Deploy Commands** (dijalankan sebelum trafik dialihkan),
   isi:
   ```
   php artisan migrate --force
   ```
7. Di **Settings → Environment Variables**, minimal isi:
   ```
   APP_NAME=Kasir Roti Bakar Romansa
   APP_ENV=production
   APP_DEBUG=false
   ```
   Untuk `APP_KEY`, generate dulu di komputer lokal (`php artisan key:generate --show`)
   lalu tempel hasilnya sebagai `APP_KEY`, ATAU pakai tombol "Generate Key" kalau
   dashboard Laravel Cloud menyediakannya.
8. Klik **Save & Deploy**.
9. Setelah deploy sukses, buka salah satu terminal/console yang disediakan
   Laravel Cloud dan jalankan sekali saja untuk mengisi menu awal:
   ```
   php artisan db:seed --class=ProdukSeeder --force
   ```

## Menjalankan di komputer/HP sendiri dulu (sebelum deploy, opsional)

```
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```
Buka `http://127.0.0.1:8000`. Konfigurasi default `.env.example` memang
sengaja pakai SQLite untuk testing lokal ini — begitu di-deploy ke Laravel
Cloud dan database di-attach, environment variable dari Cloud akan menimpa
pengaturan ini secara otomatis, jadi tidak perlu diubah manual.

## Fitur

- **Stok bersama (stok roti tawar)** — semua menu memakai satu stok yang
  sama (default 20), bisa diatur/di-setting kapan saja di halaman Stok.
- **QRIS otomatis terisi nominal** — tempel QRIS statis toko kamu sendiri
  (dari bank/GoPay/DANA/dll) di halaman Stok, lalu setiap pelanggan pilih
  bayar QRIS di kasir, nominalnya otomatis tertanam di QR (jadi QRIS
  dinamis) — pelanggan tidak perlu ketik manual lagi. Diproses sendiri
  mengikuti format standar EMVCo QRIS, **bukan payment gateway** (tidak
  butuh akun Midtrans/Xendit/dsb). Karena tidak ada verifikasi otomatis,
  kasir tetap konfirmasi manual setelah pelanggan membayar.
- **Catatan selai/menu keluar** — halaman Laporan menampilkan rekap tiap
  menu yang laku beserta jumlah & omzetnya pada periode yang dipilih.
- **Uang cepat** — tombol +Rp20.000 / +Rp50.000 / +Rp100.000 / Uang Pas
  di form pembayaran tunai.
- **Tampilan & animasi** — desain kertas struk hangat (navy + gold + krem),
  bottom sheet ala aplikasi mobile, animasi masuk untuk kartu menu, tombol,
  toast notifikasi, dan centang animasi saat transaksi berhasil. Semua
  CSS/JS ditulis manual tanpa CDN pihak ketiga.

## Cara isi QRIS toko

1. Siapkan QRIS cetak/stiker milik tokomu.
2. Scan pakai aplikasi pemindai QR apa saja yang bisa menampilkan **teks**
   hasil scan (bukan aplikasi e-wallet). Hasilnya teks panjang diawali
   `00020101...`.
3. Buka aplikasi → menu **Stok** → kartu "QRIS Toko" → tempel teks tadi →
   Simpan.

## Struktur penting

- `app/Http/Controllers/KasirController.php` — logika kasir & bayar
- `app/Http/Controllers/StokController.php` — kelola menu, stok bersama, QRIS
- `app/Http/Controllers/LaporanController.php` — laporan & rekap selai
- `app/Http/Controllers/QrisController.php` — render QRIS dinamis (SVG)
- `app/Services/QrisService.php` — logika ubah QRIS statis → dinamis (EMVCo)
- `database/migrations/` — struktur tabel
- `database/seeders/ProdukSeeder.php` — daftar menu awal (30 menu)
- `resources/views/` — semua tampilan (Blade)
- `public/css/app.css`, `public/js/*.js` — tampilan & animasi custom
