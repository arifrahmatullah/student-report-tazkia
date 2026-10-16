<x-app-layout title="Pesan">
    <x-slot name="header">
        <h1 class="text-3xl font-extrabold tracking-tight text-tazkia-900">Pesan</h1>
        <p class="mt-1.5 text-ink-soft">Kabar dan pengumuman dari bagian akademik.</p>
    </x-slot>

    <div class="mx-auto max-w-3xl">
        @if ($pesan->count())
            <div class="card divide-y divide-line overflow-hidden">
                @foreach ($pesan as $p)
                    @php $baru = ! $p->dibaca_pada; @endphp
                    <a href="{{ route('orangtua.pesan.show', $p->id) }}" class="group flex items-start gap-4 p-5 transition hover:bg-tazkia-50/60 {{ $baru ? 'bg-accent-50/40' : '' }}">
                        <span class="mt-0.5 flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $baru ? 'bg-accent-500 text-white' : 'bg-canvas text-ink-muted' }}">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $baru ? 'M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0L12 13.5 2.25 6.75' : 'M21.75 9v.9a2.25 2.25 0 01-1.07 1.92l-7.5 4.62a2.25 2.25 0 01-2.36 0l-7.5-4.62A2.25 2.25 0 012.25 9.9V9m19.5 0a2.25 2.25 0 00-1.07-1.92l-7.5-4.62a2.25 2.25 0 00-2.36 0l-7.5 4.62A2.25 2.25 0 002.25 9' }}"/></svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-3">
                                <h2 class="truncate {{ $baru ? 'font-bold text-ink' : 'font-semibold text-ink-soft' }}">{{ $p->pesan->judul }}</h2>
                                <time class="shrink-0 text-xs text-ink-muted">{{ $p->pesan->created_at->translatedFormat('d M Y') }}</time>
                            </div>
                            <p class="mt-1 line-clamp-1 text-sm text-ink-muted">{{ \Illuminate\Support\Str::limit(strip_tags($p->pesan->isi ?? ''), 90) }}</p>
                            <div class="mt-2 flex items-center gap-2 text-xs">
                                <span class="rounded-full bg-tazkia-50 px-2.5 py-0.5 font-medium text-tazkia-700">{{ $p->nama_mahasiswa ?? $p->nim }}</span>
                                @if ($baru)<span class="rounded-full bg-accent-100 px-2.5 py-0.5 font-bold text-accent-700">Baru</span>@endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-6">{{ $pesan->links() }}</div>
        @else
            <div class="card px-6 py-16 text-center">
                <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-tazkia-50 text-tazkia-600">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0L12 13.5 2.25 6.75"/></svg>
                </span>
                <h2 class="mt-5 text-lg font-bold text-tazkia-900">Belum ada pesan</h2>
                <p class="mx-auto mt-1.5 max-w-sm text-ink-soft">Pengumuman dari bagian akademik akan muncul di sini.</p>
            </div>
        @endif
    </div>
</x-app-layout>
