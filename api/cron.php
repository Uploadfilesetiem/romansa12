<?php
// Dipanggil Vercel Cron tiap hari -> kirim laporan harian ke WhatsApp via Fonnte.
require __DIR__ . '/../vercel-bootstrap.php';

$secret = getenv('CRON_SECRET');
$auth   = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$key    = $_GET['key'] ?? '';   // untuk tes manual dari browser HP
$ok = $secret && (hash_equals("Bearer $secret", $auth) || hash_equals($secret, $key));
if (!$ok) { http_response_code(401); exit('Unauthorized'); }

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$args = [];
if (!empty($_GET['tanggal'])) { $args['tanggal'] = $_GET['tanggal']; } // opsional: ?tanggal=2026-11-19
$code = $kernel->call('laporan:kirim-harian', $args);

header('Content-Type: text/plain; charset=utf-8');
http_response_code($code === 0 ? 200 : 500);
echo trim($kernel->output()) ?: ($code === 0 ? 'OK' : 'GAGAL');
