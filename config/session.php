<?php

return [
    // "database" dipilih sebagai default (bukan "file") karena Laravel Cloud
    // filesystem-nya sementara/ephemeral dan bisa jalan di beberapa instance
    // sekaligus — kalau sesi disimpan sebagai file, bisa nyasar/hilang dan
    // menyebabkan error "CSRF token mismatch" secara acak. Disimpan di
    // database supaya konsisten di semua instance.
    'driver' => env('SESSION_DRIVER', 'database'),
    'lifetime' => (int) env('SESSION_LIFETIME', 120),
    'expire_on_close' => false,
    'encrypt' => env('SESSION_ENCRYPT', false),
    'files' => storage_path('framework/sessions'),
    'connection' => env('SESSION_CONNECTION'),
    'table' => env('SESSION_TABLE', 'sessions'),
    'store' => env('SESSION_STORE'),
    'lottery' => [2, 100],
    'cookie' => env('SESSION_COOKIE', 'kasir_romansa_session'),
    'path' => env('SESSION_PATH', '/'),
    'domain' => env('SESSION_DOMAIN'),
    'secure' => env('SESSION_SECURE_COOKIE'),
    'http_only' => true,
    'same_site' => env('SESSION_SAME_SITE', 'lax'),
    'partitioned' => false,
];
