<?php

return [
    // Gateway WhatsApp pihak ketiga untuk kirim laporan harian ke grup.
    // Ambil token & ID grup dari dashboard fonnte.com, lalu isi sebagai
    // environment variable FONNTE_TOKEN dan FONNTE_GROUP_ID.
    'fonnte' => [
        'token' => env('FONNTE_TOKEN'),
        'group_id' => env('FONNTE_GROUP_ID'),
    ],
];
