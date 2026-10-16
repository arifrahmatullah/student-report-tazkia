@php
    $stat = [
        ['Beasiswa aktif', $beasiswaAktif, 'program di SIAKAD', 'M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5', 'tazkia'],
        ['Penerima aktif', $penerima, 'mahasiswa penerima', 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z', 'accent'],
        ['Orang tua terdaftar', $orangtua, 'akun orang tua', 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z', 'tazkia'],
        ['Anak tertaut', $anakTertaut, 'mahasiswa dipantau', 'M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244', 'accent'],
    ];
@endphp
<x-app-layout title="Beranda admin">
    <x-slot name="header">
        <p class="text-sm font-medium text-ink-muted">{{ now()->translatedFormat('l, d F Y') }}</p>
        <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-tazkia-900">Beranda admin</h1>
        <p class="mt-1.5 text-ink-soft">
            @if ($tahunAktif) Tahun akademik berjalan: <span class="font-semibold text-ink">{{ $tahunAktif['nama'] }}</span> @else Ringkasan aktivitas portal. @endif
        </p>
    </x-slot>

    <div class="space-y-6">
        <x-flash />

        @if ($jumlahPenandatangan === 0)
            <a href="{{ route('admin.penandatangan.create') }}" class="group flex items-center gap-4 rounded-2xl border border-accent-200 bg-accent-50 p-4 transition hover:shadow-card">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-accent-500 text-white">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                </span>
                <div class="flex-1">
                    <div class="font-bold text-ink">Tambahkan penandatangan surat terlebih dahulu</div>
                    <div class="text-sm text-ink-soft">Dibutuhkan sebelum membuat laporan hasil belajar.</div>
                </div>
                <svg class="h-5 w-5 text-accent-600 transition group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
            </a>
        @endif

        <section class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ($stat as [$label, $nilai, $ket, $ikon, $warna])
                <div class="card p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-ink-muted">{{ $label }}</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg {{ $warna === 'accent' ? 'bg-accent-50 text-accent-600' : 'bg-tazkia-50 text-tazkia-700' }}">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $ikon }}"/></svg>
                        </span>
                    </div>
                    <div class="mt-3 text-3xl font-extrabold text-ink">{{ $nilai === null ? '–' : number_format($nilai, 0, ',', '.') }}</div>
                    <div class="mt-0.5 text-xs text-ink-muted">{{ $nilai === null ? 'SIAKAD belum dapat dihubungi' : $ket }}</div>
                </div>
            @endforeach
        </section>

        <div class="grid gap-6 lg:grid-cols-3">
            <section class="card overflow-hidden lg:col-span-2">
                <div class="flex items-center justify-between border-b border-line px-6 py-4">
                    <h2 class="text-lg font-bold text-tazkia-900">Laporan terbaru</h2>
                    <a href="{{ route('admin.laporan.index') }}" class="text-sm font-semibold text-tazkia-700 hover:text-accent-600">Lihat semua</a>
                </div>
                @forelse ($terbaru as $s)
                    <a href="{{ route('admin.laporan.show', $s) }}" class="flex items-center gap-4 border-b border-line px-6 py-4 last:border-0 transition hover:bg-tazkia-50/60">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-tazkia-50 text-tazkia-700">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="truncate font-semibold text-ink">{{ $s->beasiswa_nama }}</div>
                            <div class="truncate text-xs text-ink-muted">{{ $s->nomor_surat }} · sampai {{ $s->sampai_tahun_akademik_nama }}</div>
                        </div>
                        <time class="shrink-0 text-xs text-ink-muted">{{ $s->tanggal_surat->translatedFormat('d M Y') }}</time>
                    </a>
                @empty
                    <p class="px-6 py-12 text-center text-sm text-ink-muted">Belum ada laporan yang dibuat.</p>
                @endforelse
            </section>

            <section class="relative overflow-hidden rounded-xl2 bg-gradient-to-br from-tazkia-700 to-tazkia-900 p-6 text-white shadow-card">
                <div class="absolute -right-10 -top-12 h-40 w-40 rounded-full bg-accent-500/30 blur-[50px]"></div>
                <div class="relative">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/20">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    </span>
                    <h2 class="mt-4 text-xl font-extrabold leading-snug">Buat laporan hasil belajar</h2>
                    <p class="mt-2 text-sm leading-relaxed text-white/75">Surat pengantar, rincian IPK, dan transkrip tiap mahasiswa penerima beasiswa dalam satu PDF.</p>
                    <a href="{{ route('admin.laporan.create') }}" class="mt-5 inline-flex items-center justify-center rounded-xl bg-accent-500 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-black/20 transition hover:bg-accent-600">Mulai sekarang</a>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
