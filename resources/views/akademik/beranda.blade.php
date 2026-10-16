@php
    $stat = [
        ['Pesan bulan ini', $pesanBulanIni, 'pengumuman dikirim'],
        ['Dijangkau bulan ini', $jangkauan, 'pesan masuk ke inbox orang tua'],
        ['Rata-rata terbaca', $persenDibaca === null ? null : $persenDibaca.'%', 'dari pesan bulan ini'],
        ['Orang tua terdaftar', $orangtua, $anakTertaut.' mahasiswa tertaut'],
    ];
@endphp
<x-app-layout title="Beranda akademik">
    <x-slot name="header">
        <p class="text-sm font-medium text-ink-muted">{{ now()->translatedFormat('l, d F Y') }}</p>
        <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-tazkia-900">Beranda akademik</h1>
        <p class="mt-1.5 text-ink-soft">Sampaikan kabar penting kepada orang tua mahasiswa.</p>
    </x-slot>

    <div class="space-y-6">
        <x-flash />

        <section class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ($stat as [$label, $nilai, $ket])
                <div class="card p-5">
                    <div class="text-xs font-medium text-ink-muted">{{ $label }}</div>
                    <div class="mt-2 text-3xl font-extrabold text-ink">{{ $nilai === null ? '–' : (is_numeric($nilai) ? number_format($nilai, 0, ',', '.') : $nilai) }}</div>
                    <div class="mt-0.5 text-xs text-ink-muted">{{ $ket }}</div>
                </div>
            @endforeach
        </section>

        <div class="grid gap-6 lg:grid-cols-3">
            <section class="card overflow-hidden lg:col-span-2">
                <div class="flex items-center justify-between border-b border-line px-6 py-4">
                    <h2 class="text-lg font-bold text-tazkia-900">Pesan terbaru</h2>
                    <a href="{{ route('akademik.pesan.index') }}" class="text-sm font-semibold text-tazkia-700 hover:text-accent-600">Lihat semua</a>
                </div>
                @forelse ($terbaru as $p)
                    <a href="{{ route('akademik.pesan.show', $p) }}" class="flex items-center gap-4 border-b border-line px-6 py-4 transition last:border-0 hover:bg-tazkia-50/60">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-tazkia-50 text-tazkia-700">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0L12 13.5 2.25 6.75"/></svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="truncate font-semibold text-ink">{{ $p->judul }}</div>
                            <div class="truncate text-xs text-ink-muted">{{ $p->target['label'] ?? '' }}</div>
                        </div>
                        <time class="shrink-0 text-xs text-ink-muted">{{ $p->created_at->translatedFormat('d M Y') }}</time>
                    </a>
                @empty
                    <p class="px-6 py-12 text-center text-sm text-ink-muted">Belum ada pesan yang dikirim.</p>
                @endforelse
            </section>

            <section class="relative overflow-hidden rounded-xl2 bg-gradient-to-br from-tazkia-700 to-tazkia-900 p-6 text-white shadow-card">
                <div class="absolute -right-10 -top-12 h-40 w-40 rounded-full bg-accent-500/30 blur-[50px]"></div>
                <div class="relative">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/20">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                    </span>
                    <h2 class="mt-4 text-xl font-extrabold leading-snug">Tulis pengumuman</h2>
                    <p class="mt-2 text-sm leading-relaxed text-white/75">Kirim ke penerima beasiswa, satu angkatan atau prodi, atau daftar NIM tertentu.</p>
                    <a href="{{ route('akademik.pesan.create') }}" class="mt-5 inline-flex items-center justify-center rounded-xl bg-accent-500 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-black/20 transition hover:bg-accent-600">Tulis pesan</a>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
