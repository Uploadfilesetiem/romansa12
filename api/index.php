<?php
require __DIR__ . '/../vercel-bootstrap.php';

// Sajikan file statis (css/js/gambar) dari folder public/ langsung lewat PHP.
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = realpath(__DIR__ . '/../public' . $path);
$root = realpath(__DIR__ . '/../public');
if ($file && $root && str_starts_with($file, $root . DIRECTORY_SEPARATOR) && is_file($file)
    && strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'php') {
    $types = ['css'=>'text/css','js'=>'application/javascript','svg'=>'image/svg+xml',
              'png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','gif'=>'image/gif',
              'ico'=>'image/x-icon','webp'=>'image/webp','json'=>'application/json','woff2'=>'font/woff2'];
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
    header('Cache-Control: public, max-age=86400');
    readfile($file);
    exit;
}

require __DIR__ . '/../public/index.php';
