<x-app-layout :title="$baris->pesan->judul">
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('orangtua.pesan.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-ink-soft transition hover:text-tazkia-700">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            Semua pesan
        </a>

        <article class="card mt-4 overflow-hidden">
            <header class="border-b border-line px-6 py-6 sm:px-8">
                <h1 class="text-2xl font-extrabold leading-snug tracking-tight text-tazkia-900">{{ $baris->pesan->judul }}</h1>
                <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-ink-muted">
                    <span class="flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-tazkia-700 text-xs font-bold text-white">A</span>
                        <span class="font-semibold text-ink-soft">Bagian Akademik</span>
                    </span>
                    <time>{{ $baris->pesan->created_at->translatedFormat('l, d F Y · H:i') }}</time>
                    <span class="rounded-full bg-tazkia-50 px-2.5 py-0.5 text-xs font-medium text-tazkia-700">Terkait {{ $baris->nama_mahasiswa ?? $baris->nim }}</span>
                </div>
            </header>

            {{-- e() + nl2br: isi ditulis staf, tetap diperlakukan sebagai teks biasa. --}}
            <div class="px-6 py-7 text-[15px] leading-8 text-ink sm:px-8">{!! nl2br(e($baris->pesan->isi)) !!}</div>
        </article>
    </div>
</x-app-layout>
