<?php

// Menu navigasi per role. 'aktif' = pola nama route untuk menandai menu terpilih.
return [
    'orangtua' => [
        ['label' => 'Beranda', 'route' => 'orangtua.beranda', 'aktif' => 'orangtua.beranda'],
        ['label' => 'Pesan', 'route' => 'orangtua.pesan.index', 'aktif' => 'orangtua.pesan.*'],
    ],
    'akademik' => [
        ['label' => 'Beranda', 'route' => 'akademik.beranda', 'aktif' => 'akademik.beranda'],
        ['label' => 'Pesan', 'route' => 'akademik.pesan.index', 'aktif' => 'akademik.pesan.*'],
        ['label' => 'Reminder KRS', 'route' => 'akademik.reminder-krs.index', 'aktif' => 'akademik.reminder-krs.*'],
        ['label' => 'Log WhatsApp', 'route' => 'akademik.log-wa.index', 'aktif' => 'akademik.log-wa.*'],
    ],
    'admin' => [
        ['label' => 'Beranda', 'route' => 'admin.beranda', 'aktif' => 'admin.beranda'],
        ['label' => 'Laporan', 'route' => 'admin.laporan.index', 'aktif' => 'admin.laporan.*'],
        ['label' => 'Pesan', 'route' => 'akademik.pesan.index', 'aktif' => 'akademik.pesan.*'],
        ['label' => 'Reminder KRS', 'route' => 'akademik.reminder-krs.index', 'aktif' => 'akademik.reminder-krs.*'],
        ['label' => 'Log WhatsApp', 'route' => 'akademik.log-wa.index', 'aktif' => 'akademik.log-wa.*'],
        ['label' => 'Penandatangan', 'route' => 'admin.penandatangan.index', 'aktif' => 'admin.penandatangan.*'],
        ['label' => 'Pengguna', 'route' => 'admin.pengguna.index', 'aktif' => 'admin.pengguna.*'],
    ],
];
