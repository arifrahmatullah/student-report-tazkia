<?php

return [
    // Aktif hanya bila token dan ID nomor terisi. Tanpa itu, pesan tetap masuk ke inbox aplikasi.
    'token' => env('WA_TOKEN'),
    'phone_number_id' => env('WA_PHONE_NUMBER_ID'),
    // ID akun WhatsApp Business (WABA); dipakai hanya untuk MEMBACA status dan isi template sebelum mengirim.
    'waba_id' => env('WA_WABA_ID'),
    'api_url' => env('WA_API_URL', 'https://graph.facebook.com/v22.0'),

    // Template pengumuman umum: variabel isi berurutan {{1}} = nama penerima, {{2}} = judul pesan.
    // Contoh isi: "Yth. {{1}}, ada pesan baru dari Bagian Akademik: {{2}}. Silakan buka portal orang tua untuk membacanya."
    'template_broadcast' => env('WA_TEMPLATE_BROADCAST'),

    // Template pengingat KRS yang SUDAH DISETUJUI Meta, dengan variabel BERNAMA:
    // {{nama}} {{nim}} {{semester}} {{academic_year}} {{date}}
    'template_reminder_krs' => env('WA_TEMPLATE_REMINDER_KRS', 'reminder_telat_krs'),

    // Template pengingat KRS yang ditujukan langsung kepada MAHASISWA (variabel bernama sama). Template di atas
    // menyapa "Orang Tua/Wali Mahasiswa", jadi bila ini kosong pesan ke mahasiswa memakai template orang tua
    // (halaman memberi peringatan) sampai template khusus mahasiswa disetujui Meta.
    'template_reminder_krs_mahasiswa' => env('WA_TEMPLATE_REMINDER_KRS_MAHASISWA'),

    // PENGAMAN: di luar APP_ENV=production, WhatsApp HANYA dikirim ke nomor di daftar ini (pisahkan dengan koma),
    // supaya data uji yang berisi nomor orang tua sungguhan tidak pernah menerima pesan. Kosong = tidak ada yang
    // terkirim. Isi "*" untuk mengizinkan semua nomor (mis. saat menguji dengan server WhatsApp tiruan).
    // Di production daftar ini diabaikan.
    'nomor_uji' => env('WA_NOMOR_UJI'),

    'bahasa' => env('WA_TEMPLATE_LANG', 'id'),

    // Jeda antar pesan (milidetik) agar tidak menabrak batas laju Meta.
    'jeda_ms' => (int) env('WA_JEDA_MS', 150),
    'timeout' => (int) env('WA_TIMEOUT', 20),
];
