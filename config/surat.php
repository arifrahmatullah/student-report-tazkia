<?php

return [
    // Kop surat. Isi alamat/kontak resmi lewat .env; dikosongkan agar tidak ada alamat karangan.
    'institusi' => env('SURAT_INSTITUSI', 'Universitas Tazkia'),
    'alamat' => array_values(array_filter(array_map('trim', explode('|', (string) env('SURAT_ALAMAT', ''))))),
    // Judul pada halaman transkrip.
    'judul_transkrip' => env('SURAT_JUDUL_TRANSKRIP', 'UNIVERSITAS ISLAM TAZKIA'),
    // Selisih hari untuk menyamakan hitungan Hijriah otomatis dengan penetapan Kemenag. Bawaan +1: dikalibrasi
    // dari surat contoh (14 Agustus 2026 = 1 Rabi’ul Awal 1448 H). Tetap bisa ditimpa per surat di formulir.
    'koreksi_hijriah' => (int) env('SURAT_KOREKSI_HIJRIAH', 1),
    'tempat_default' => env('SURAT_TEMPAT', 'Bogor'),
];
