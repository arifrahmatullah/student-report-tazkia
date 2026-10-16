<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#194189">
        <meta name="description" content="Portal orang tua Universitas Tazkia: pantau IPK, KRS, kehadiran, dan tagihan putra-putri Anda, serta terima kabar langsung dari bagian akademik.">

        <title>Portal Orang Tua · Universitas Tazkia</title>
        <link rel="icon" href="{{ asset('img/logo-tazkia.png') }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans bg-white">

        {{-- Navigasi --}}
        <header x-data="{ open: false }" class="sticky top-0 z-50 border-b border-line/70 bg-white/80 backdrop-blur-lg">
            <div class="mx-auto flex h-[4.5rem] max-w-6xl items-center justify-between px-6">
                <a href="{{ url('/') }}" class="flex items-center gap-3">
                    <img src="{{ asset('img/logo-tazkia.png') }}" alt="Universitas Tazkia" class="h-11 w-auto">
                    <span class="leading-tight"><span class="block text-[15px] font-extrabold tracking-tight text-tazkia-900">Universitas Tazkia</span><span class="block text-[11px] font-medium text-ink-muted">Portal Orang Tua</span></span>
                </a>

                <nav class="hidden items-center gap-8 text-sm font-medium text-ink-soft md:flex">
                    <a href="#fitur" class="transition hover:text-tazkia-700">Fitur</a>
                    <a href="#cara-kerja" class="transition hover:text-tazkia-700">Cara kerja</a>
                    <a href="#faq" class="transition hover:text-tazkia-700">Pertanyaan</a>
                </nav>

                <div class="hidden items-center gap-3 md:flex">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn-primary !py-2.5">Buka dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="px-3 text-sm font-semibold text-tazkia-700 transition hover:text-accent-600">Masuk</a>
                        <a href="{{ route('register') }}" class="btn-primary !py-2.5">Daftar</a>
                    @endauth
                </div>

                <button @click="open = !open" class="flex h-10 w-10 items-center justify-center rounded-lg text-ink-soft hover:bg-canvas md:hidden" aria-label="Menu">
                    <svg x-show="!open" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
                    <svg x-show="open" x-cloak class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
                </button>
            </div>

            <div x-show="open" x-cloak x-transition class="border-t border-line bg-white px-6 pb-5 pt-3 md:hidden">
                <div class="flex flex-col gap-1 text-sm font-medium text-ink-soft">
                    <a @click="open = false" href="#fitur" class="rounded-lg px-3 py-2.5 hover:bg-canvas">Fitur</a>
                    <a @click="open = false" href="#cara-kerja" class="rounded-lg px-3 py-2.5 hover:bg-canvas">Cara kerja</a>
                    <a @click="open = false" href="#faq" class="rounded-lg px-3 py-2.5 hover:bg-canvas">Pertanyaan</a>
                </div>
                <div class="mt-3 grid grid-cols-2 gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn-primary col-span-2">Buka dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="btn-secondary">Masuk</a>
                        <a href="{{ route('register') }}" class="btn-primary">Daftar</a>
                    @endauth
                </div>
            </div>
        </header>

        {{-- Hero --}}
        <section class="relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-b from-tazkia-50 via-white to-white"></div>
            <div class="absolute -right-40 -top-40 h-[34rem] w-[34rem] rounded-full bg-accent-100/60 blur-[100px]"></div>
            <div class="absolute -left-40 top-40 h-[28rem] w-[28rem] rounded-full bg-tazkia-100/70 blur-[100px]"></div>

            <div class="relative mx-auto grid max-w-6xl items-center gap-14 px-6 pb-20 pt-14 lg:grid-cols-[1.05fr_0.95fr] lg:pb-28 lg:pt-20">
                <div>
                    <span class="inline-flex items-center gap-2 rounded-full bg-white px-3.5 py-1.5 text-xs font-semibold text-tazkia-700 shadow-sm ring-1 ring-line">
                        <span class="h-1.5 w-1.5 rounded-full bg-accent-500"></span>
                        Portal resmi orang tua mahasiswa
                    </span>

                    <h1 class="mt-6 text-4xl font-extrabold leading-[1.08] tracking-tight text-tazkia-900 sm:text-5xl lg:text-[3.5rem]">
                        Pantau perjalanan akademik
                        <span class="relative whitespace-nowrap text-tazkia-700">
                            putra-putri Anda
                            <svg class="absolute -bottom-2 left-0 w-full text-accent-500" viewBox="0 0 300 12" fill="none" preserveAspectRatio="none" aria-hidden="true"><path d="M3 8c60-6 140-8 294-2" stroke="currentColor" stroke-width="4" stroke-linecap="round"/></svg>
                        </span>
                    </h1>

                    <p class="mt-7 max-w-xl text-lg leading-relaxed text-ink-soft">
                        Lihat IPK, status KRS, kehadiran, dan tagihan secara langsung dari data kampus, serta terima kabar penting dari bagian akademik tanpa perlu bertanya satu per satu.
                    </p>

                    <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn-primary px-7 py-3.5 text-[15px] shadow-lg shadow-tazkia-700/25">Buka dashboard</a>
                        @else
                            <a href="{{ route('register') }}" class="btn-primary px-7 py-3.5 text-[15px] shadow-lg shadow-tazkia-700/25">
                                Daftar sebagai orang tua
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                            </a>
                            <a href="{{ route('login') }}" class="btn-secondary px-7 py-3.5 text-[15px]">Saya sudah punya akun</a>
                        @endauth
                    </div>

                    <dl class="mt-12 grid max-w-md grid-cols-3 gap-6 border-t border-line pt-8">
                        <div><dt class="sr-only">Sumber data</dt><dd class="text-sm font-bold text-tazkia-800">Data langsung<br>dari SIAKAD</dd></div>
                        <div><dt class="sr-only">Keamanan</dt><dd class="text-sm font-bold text-tazkia-800">Verifikasi<br>tanggal lahir</dd></div>
                        <div><dt class="sr-only">Akses</dt><dd class="text-sm font-bold text-tazkia-800">Bisa dibuka<br>dari ponsel</dd></div>
                    </dl>
                </div>

                <div class="relative mx-auto w-full max-w-md lg:max-w-none">
                    <div class="absolute -inset-4 rounded-[2rem] bg-gradient-to-br from-tazkia-700/10 to-accent-500/10 blur-2xl"></div>
                    <x-dashboard-preview class="relative lg:rotate-[1.5deg]" />

                    <div class="absolute -bottom-6 -left-4 hidden items-center gap-3 rounded-xl bg-white px-4 py-3 shadow-xl ring-1 ring-black/5 sm:flex">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-accent-100 text-accent-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0L12 13.5 2.25 6.75"/></svg>
                        </span>
                        <div class="text-xs leading-tight"><div class="font-bold text-ink">Pesan baru</div><div class="text-ink-muted">dari Bagian Akademik</div></div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Fitur --}}
        <section id="fitur" class="scroll-mt-20 bg-white py-20 lg:py-28">
            <div class="mx-auto max-w-6xl px-6">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-bold uppercase tracking-[0.14em] text-accent-600">Fitur</p>
                    <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-tazkia-900 sm:text-4xl">Semua yang perlu Anda ketahui, di satu tempat</h2>
                    <p class="mt-4 text-lg text-ink-soft">Ringkas, jelas, dan selalu diperbarui dari sistem akademik kampus.</p>
                </div>

                <div class="mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @php
                        $fitur = [
                            ['Prestasi akademik', 'IPK, total SKS, dan IPS setiap semester ditampilkan sebagai grafik yang mudah dibaca.', 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z', 'tazkia'],
                            ['Status KRS', 'Ketahui apakah KRS sudah disetujui, masih draf, atau belum diisi, lengkap dengan batas waktunya.', 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'accent'],
                            ['Kehadiran', 'Rekap hadir, izin, sakit, dan alpha per mata kuliah, dengan penanda saat kehadiran menurun.', 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5', 'tazkia'],
                            ['Tagihan', 'Pantau total tagihan, yang sudah dibayar, sisa, serta jatuh tempo terdekat.', 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z', 'accent'],
                            ['Pesan dari akademik', 'Pengumuman dan kabar penting dari bagian akademik masuk ke kotak pesan Anda.', 'M14.86 17.08a23 23 0 005.45-1.08A8.97 8.97 0 0118 9.75V9A6 6 0 006 9v.75a8.97 8.97 0 01-2.31 6.25 23 23 0 005.45 1.08m5.72 0a24 24 0 01-5.72 0m5.72 0a3 3 0 11-5.72 0', 'tazkia'],
                            ['Aman & terverifikasi', 'Hanya orang tua yang tahu tanggal lahir mahasiswa yang dapat menautkan akun, dan hanya melihat anak sendiri.', 'M9 12.75L11.25 15 15 9.75m-3-7.04A11.96 11.96 0 013.6 6 12 12 0 003 9.75c0 5.59 3.82 10.29 9 11.62 5.18-1.33 9-6.03 9-11.62 0-1.31-.21-2.57-.6-3.75h-.15c-3.2 0-6.1-1.25-8.25-3.29z', 'accent'],
                        ];
                    @endphp
                    @foreach ($fitur as [$judul, $isi, $ikon, $warna])
                        <div class="group rounded-2xl border border-line bg-white p-7 transition hover:-translate-y-1 hover:border-tazkia-200 hover:shadow-card">
                            <span class="flex h-12 w-12 items-center justify-center rounded-xl {{ $warna === 'accent' ? 'bg-accent-50 text-accent-600' : 'bg-tazkia-50 text-tazkia-700' }}">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $ikon }}"/></svg>
                            </span>
                            <h3 class="mt-5 text-lg font-bold text-tazkia-900">{{ $judul }}</h3>
                            <p class="mt-2 leading-relaxed text-ink-soft">{{ $isi }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Cara kerja --}}
        <section id="cara-kerja" class="scroll-mt-20 bg-canvas py-20 lg:py-28">
            <div class="mx-auto max-w-6xl px-6">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-bold uppercase tracking-[0.14em] text-accent-600">Cara kerja</p>
                    <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-tazkia-900 sm:text-4xl">Mulai dalam tiga langkah</h2>
                </div>

                <ol class="relative mt-14 grid gap-8 md:grid-cols-3">
                    <div class="absolute left-[16.6%] right-[16.6%] top-7 hidden border-t-2 border-dashed border-tazkia-200 md:block"></div>
                    @foreach ([
                        ['Buat akun', 'Daftar dengan nama, email, dan nomor WhatsApp Anda.'],
                        ['Tautkan anak', 'Masukkan NIM dan tanggal lahir anak untuk memastikan Anda orang tua yang berhak.'],
                        ['Pantau & terima kabar', 'Lihat perkembangan studi kapan saja dan baca pesan dari bagian akademik.'],
                    ] as $i => [$judul, $isi])
                        <li class="relative text-center">
                            <span class="relative z-10 mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-tazkia-700 text-xl font-extrabold text-white ring-8 ring-canvas">{{ $i + 1 }}</span>
                            <h3 class="mt-5 text-lg font-bold text-tazkia-900">{{ $judul }}</h3>
                            <p class="mx-auto mt-2 max-w-xs leading-relaxed text-ink-soft">{{ $isi }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>

        {{-- FAQ --}}
        <section id="faq" class="scroll-mt-20 bg-white py-20 lg:py-28">
            <div class="mx-auto max-w-3xl px-6">
                <div class="text-center">
                    <p class="text-sm font-bold uppercase tracking-[0.14em] text-accent-600">Pertanyaan umum</p>
                    <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-tazkia-900 sm:text-4xl">Yang sering ditanyakan</h2>
                </div>

                <div class="mt-12 divide-y divide-line rounded-2xl border border-line">
                    @foreach ([
                        ['Dari mana data ini berasal?', 'Seluruh data akademik diambil langsung dari SIAKAD Tazkia, sehingga sama dengan yang dilihat pihak kampus.'],
                        ['Mengapa saya diminta tanggal lahir anak?', 'Untuk memastikan hanya orang tua yang berhak yang dapat melihat data. Tanggal lahir hanya dipakai saat verifikasi dan tidak kami simpan.'],
                        ['Bisakah dua orang tua memantau anak yang sama?', 'Bisa. Ayah, ibu, atau wali masing-masing dapat membuat akun dan menautkan anak yang sama.'],
                        ['Data tidak tampil atau tampak keliru?', 'Beberapa bagian dapat sesekali belum tersedia. Jika datanya tampak keliru, hubungi bagian akademik kampus.'],
                    ] as [$tanya, $jawab])
                        <details class="group px-6 py-5 [&_summary::-webkit-details-marker]:hidden">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold text-tazkia-900">
                                {{ $tanya }}
                                <svg class="h-5 w-5 shrink-0 text-ink-muted transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                            </summary>
                            <p class="mt-3 leading-relaxed text-ink-soft">{{ $jawab }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- CTA --}}
        <section class="px-6 pb-20 lg:pb-28">
            <div class="relative mx-auto max-w-6xl overflow-hidden rounded-3xl bg-gradient-to-br from-tazkia-700 via-tazkia-800 to-tazkia-950 px-8 py-16 text-center sm:px-16">
                <div class="absolute -right-20 -top-24 h-72 w-72 rounded-full bg-accent-500/30 blur-[90px]"></div>
                <div class="absolute -bottom-24 -left-16 h-72 w-72 rounded-full bg-tazkia-400/25 blur-[90px]"></div>
                <div class="relative">
                    <h2 class="mx-auto max-w-2xl text-3xl font-extrabold tracking-tight text-white sm:text-4xl">Siap mendampingi studi putra-putri Anda?</h2>
                    <p class="mx-auto mt-4 max-w-xl text-lg text-white/70">Buat akun dalam hitungan menit dan mulai pantau perkembangannya hari ini.</p>
                    <div class="mt-9 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        @auth
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-xl bg-accent-500 px-7 py-3.5 text-[15px] font-bold text-white shadow-lg shadow-black/20 transition hover:bg-accent-600">Buka dashboard</a>
                        @else
                            <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-xl bg-accent-500 px-7 py-3.5 text-[15px] font-bold text-white shadow-lg shadow-black/20 transition hover:bg-accent-600">Daftar sekarang</a>
                            <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-xl border border-white/25 px-7 py-3.5 text-[15px] font-semibold text-white transition hover:bg-white/10">Masuk</a>
                        @endauth
                    </div>
                </div>
            </div>
        </section>

        {{-- Footer --}}
        <footer class="border-t border-line bg-white">
            <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-6 py-8 text-sm text-ink-muted sm:flex-row">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('img/logo-tazkia.png') }}" alt="" class="h-9 w-auto">
                    <span>&copy; {{ now()->year }} Universitas Tazkia. Hak cipta dilindungi.</span>
                </div>
                <div class="flex gap-6">
                    <a href="{{ route('login') }}" class="transition hover:text-tazkia-700">Masuk</a>
                    <a href="{{ route('register') }}" class="transition hover:text-tazkia-700">Daftar</a>
                </div>
            </div>
        </footer>
    </body>
</html>
