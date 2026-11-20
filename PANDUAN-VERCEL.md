# Panduan Pasang Kasir Romansa di Vercel (+ laporan WhatsApp Fonnte)

## Gambaran singkat
- Vercel menjalankan PHP lewat `api/index.php` (runtime `vercel-php`).
- Data TIDAK disimpan di Vercel. Pakai database online gratis.
- Laporan harian ke WhatsApp dikirim oleh **Vercel Cron** (`api/cron.php`) setiap hari.

## Langkah 1 - Database online (Neon, gratis)
1. Daftar di neon.tech, buat project + database.
2. Salin: host, database, username, password.
   (Pakai Postgres: `DB_CONNECTION=pgsql`, `DB_SSLMODE=require`.)

## Langkah 2 - Buat tabel + isi menu (sekali saja)
Dari VM InstaBox / komputer yang punya PHP + Composer:
```
composer install --no-dev
cp .env.example .env
php artisan key:generate --show      # salin hasilnya untuk APP_KEY di Vercel
```
Isi `.env` dengan data Neon, lalu:
```
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=ProdukSeeder --force
```

## Langkah 3 - Push ke GitHub
Upload isi folder ini ke repo GitHub (`.env` dan `vendor` jangan ikut).

## Langkah 4 - Deploy di Vercel
1. vercel.com -> Add New -> Project -> pilih repo.
2. Framework Preset: **Other**. Build/Output biarkan kosong.
3. Isi Environment Variables (lihat tabel), lalu **Deploy**.

| Nama | Isi |
|---|---|
| APP_NAME | Kasir Roti Bakar Romansa |
| APP_ENV | production |
| APP_KEY | hasil `php artisan key:generate --show` (diawali base64:) |
| APP_DEBUG | false |
| APP_URL | https://NAMA-PROJECT.vercel.app |
| APP_TIMEZONE | Asia/Jakarta |
| LOG_CHANNEL | stderr |
| DB_CONNECTION | pgsql |
| DB_HOST / DB_PORT / DB_DATABASE / DB_USERNAME / DB_PASSWORD | dari Neon |
| DB_SSLMODE | require |
| SESSION_DRIVER | database |
| CACHE_STORE | database |
| QUEUE_CONNECTION | sync |
| FONNTE_TOKEN | token dari fonnte.com |
| FONNTE_GROUP_ID | ID grup WhatsApp tujuan |
| CRON_SECRET | teks acak panjang buatanmu sendiri |

Setelah mengubah variabel, **Redeploy** (variabel baru baru terbaca setelah deploy ulang).

## Langkah 5 - Fonnte
1. Login fonnte.com, hubungkan nomor WhatsApp pengirim (scan QR).
2. Salin **Token** perangkat -> `FONNTE_TOKEN`.
3. ID grup -> `FONNTE_GROUP_ID` (format sesuai dashboard Fonnte, biasanya `xxxx@g.us`).
   Nomor pengirim harus sudah ada di grup itu.

## Langkah 6 - Tes laporan
Buka di browser HP (ganti nilainya):
```
https://NAMA-PROJECT.vercel.app/api/cron?key=ISI_CRON_SECRET
```
Atau untuk tanggal tertentu: tambahkan `&tanggal=2026-11-19`.
Kalau berhasil tampil "Laporan harian ... berhasil dikirim" dan WA masuk ke grup.

## Jadwal otomatis
`vercel.json` menjadwalkan `0 17 * * *` (UTC) = **00:00 WIB**.
Di paket gratis (Hobby), Vercel hanya menjamin jalan di rentang jam itu
(sekitar 00:00-00:59 WIB) dan hanya boleh 1x per hari. Laporan tetap untuk "kemarin".

## Batasan & catatan
- Upload file ke server tidak tersimpan (read-only). Aplikasi ini tidak butuh.
- Pencarian produk di `/api/order-masuk` memakai LIKE; di Postgres huruf besar/kecil
  dibedakan. Samakan penulisan nama menu dengan yang dikirim bot WA.
- Kalau error 500: set `APP_DEBUG=true` sementara, atau lihat Vercel -> Logs.
- Jangan bagikan FONNTE_TOKEN, CRON_SECRET, atau password DB.
