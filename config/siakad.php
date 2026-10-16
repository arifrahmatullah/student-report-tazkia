<?php

return [
    'base_url' => env('SIAKAD_BASE_URL', 'https://dev.smile.tazkia.ac.id'),
    'api_key' => env('SIAKAD_API_KEY'),
    'timeout' => (int) env('SIAKAD_TIMEOUT', 20),

    // Lama cache (detik) per jenis data.
    'ttl' => [
        'referensi' => 3600,   // prodi, tahun akademik
        'beasiswa' => 300,     // program & penerima
        'mahasiswa' => 300,    // krs, riwayat IPK, presensi, tagihan
        'orang_tua' => 600,
        'laporan' => 0,        // laporan resmi selalu diambil segar
    ],
];
