<?php
// Vercel: filesystem read-only, hanya /tmp yang bisa ditulis.
// File ini dipanggil dulu oleh api/index.php dan api/cron.php.
$tmp = '/tmp/laravel';
foreach (['storage/app/public','storage/framework/cache/data','storage/framework/sessions',
          'storage/framework/views','storage/logs','storage/fonts','bootstrap/cache'] as $d) {
    if (!is_dir("$tmp/$d")) { @mkdir("$tmp/$d", 0777, true); }
}
$env = [
    'LARAVEL_STORAGE_PATH' => "$tmp/storage",
    'VIEW_COMPILED_PATH'   => "$tmp/storage/framework/views",
    'APP_CONFIG_CACHE'     => "$tmp/bootstrap/cache/config.php",
    'APP_ROUTES_CACHE'     => "$tmp/bootstrap/cache/routes.php",
    'APP_EVENTS_CACHE'     => "$tmp/bootstrap/cache/events.php",
    'APP_PACKAGES_CACHE'   => "$tmp/bootstrap/cache/packages.php",
    'APP_SERVICES_CACHE'   => "$tmp/bootstrap/cache/services.php",
];
foreach ($env as $k => $v) { putenv("$k=$v"); $_ENV[$k] = $v; $_SERVER[$k] = $v; }
