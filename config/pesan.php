<?php

return [
    // Berapa tahun ke belakang (dari tahun mulai tahun akademik aktif) angkatan masih ditampilkan secara bawaan.
    // 4 berarti pada tahun akademik 2026/2027 yang tampil angkatan 2022-2026, supaya daftar tidak dipenuhi
    // mahasiswa lama yang statusnya masih aktif tetapi tidak lagi berkuliah. Angkatan yang lebih lama tetap
    // bisa dilihat lewat "tampilkan semua angkatan" atau dengan mengisi angkatannya langsung.
    'tahun_ke_belakang' => (int) env('PESAN_TAHUN_KE_BELAKANG', 4),
];
