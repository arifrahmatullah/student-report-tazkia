@php
    $nama = \Illuminate\Support\Str::of(auth()->user()->name)->before(' ');
    $jam = now()->hour;
    $salam = $jam < 11 ? 'Selamat pagi' : ($jam < 15 ? 'Selamat siang' : ($jam < 18 ? 'Selamat sore' : 'Selamat malam'));
@endphp
<x-app-layout title="Beranda">
    <x-slot name="header">
        <p class="text-sm font-medium text-ink-muted">{{ now()->translatedFormat('l, d F Y') }}</p>
        <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-tazkia-900">{{ $salam }}, {{ $nama }}</h1>
        <p class="mt-1.5 text-ink-soft">Berikut perkembangan putra-putri Anda.</p>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))
            <div class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ session('status') }}
            </div>
        @endif

        @if ($pesanBaru > 0)
            <a href="{{ route('orangtua.pesan.index') }}" class="group flex items-center gap-4 rounded-2xl border border-accent-200 bg-accent-50 p-4 transition hover:shadow-card">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-accent-500 text-white">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0L12 13.5 2.25 6.75"/></svg>
                </span>
                <div class="flex-1">
                    <div class="font-bold text-ink">{{ $pesanBaru }} pesan baru dari bagian akademik</div>
                    <div class="text-sm text-ink-soft">Ketuk untuk membaca.</div>
                </div>
                <svg class="h-5 w-5 text-accent-600 transition group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
            </a>
        @endif

        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($anak as $a)
                @php $m = $ringkasan[$a->nim] ?? null; $aktif = ($m['status'] ?? null) === 'AKTIF'; @endphp
                <a href="{{ route('orangtua.anak.show', $a->nim) }}" class="group card flex flex-col p-6 transition hover:-translate-y-0.5 hover:border-tazkia-200">
                    <div class="flex items-start justify-between gap-3">
                        <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-tazkia-600 to-tazkia-800 text-xl font-extrabold text-white">
                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($m['nama'] ?? $a->nama_mahasiswa ?? '?', 0, 1)) }}
                        </span>
                        @if ($m)
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $aktif ? 'bg-emerald-50 text-emerald-700' : 'bg-canvas text-ink-soft' }}">{{ ucfirst(strtolower($m['status'] ?? '')) }}</span>
                        @endif
                    </div>

                    <h3 class="mt-4 text-lg font-bold leading-snug text-tazkia-900">{{ $m['nama'] ?? $a->nama_mahasiswa ?? 'Mahasiswa' }}</h3>
                    <p class="text-sm text-ink-muted">NIM {{ $a->nim }}</p>
                    <p class="mt-1 text-sm text-ink-soft">{{ $m['prodiNama'] ?? 'Data program studi belum tersedia' }}@isset($m['angkatan']) · Angkatan {{ $m['angkatan'] }}@endisset</p>

                    @if ($m)
                        <div class="mt-5 grid grid-cols-3 gap-2 border-t border-line pt-4">
                            <div><div class="text-[11px] text-ink-muted">IPK</div><div class="text-lg font-extrabold text-tazkia-700">{{ isset($m['ipk']) ? number_format($m['ipk'], 2, ',', '') : '-' }}</div></div>
                            <div><div class="text-[11px] text-ink-muted">SKS</div><div class="text-lg font-extrabold text-ink">{{ $m['totalSks'] ?? '-' }}</div></div>
                            <div><div class="text-[11px] text-ink-muted">Semester</div><div class="text-lg font-extrabold text-ink">{{ $m['semester'] ?? '-' }}</div></div>
                        </div>
                    @endif

                    <div class="mt-5 flex items-center gap-1.5 text-sm font-semibold text-tazkia-700 transition group-hover:text-accent-600">
                        Lihat perkembangan
                        <svg class="h-4 w-4 transition group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                    </div>
                </a>
            @endforeach

            <a href="{{ route('orangtua.klaim.create') }}"
               class="group flex min-h-[15rem] flex-col items-center justify-center rounded-xl2 border-2 border-dashed border-tazkia-200 bg-white/60 p-6 text-center transition hover:border-tazkia-400 hover:bg-tazkia-50">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-tazkia-100 text-tazkia-700 transition group-hover:bg-tazkia-700 group-hover:text-white">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                </span>
                <span class="mt-4 font-bold text-tazkia-900">{{ $anak->isEmpty() ? 'Tautkan anak pertama Anda' : 'Tambah anak' }}</span>
                <span class="mt-1 max-w-[14rem] text-sm text-ink-soft">Siapkan NIM dan tanggal lahir mahasiswa.</span>
            </a>
        </div>
    </div>
</x-app-layout>
