@php
    $statusUi = [
        'selesai' => ['Terkirim', 'bg-emerald-50 text-emerald-700'],
        'diproses' => ['Diproses', 'bg-accent-50 text-accent-700'],
        'antre' => ['Antre', 'bg-canvas text-ink-soft'],
        'gagal' => ['Gagal', 'bg-red-50 text-red-700'],
    ];
@endphp
<x-app-layout title="Pesan">
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-tazkia-900">Pesan ke orang tua</h1>
                <p class="mt-1.5 text-ink-soft">Riwayat pengumuman yang sudah dikirim dan sejauh mana pesan terbaca.</p>
            </div>
            <a href="{{ route('akademik.pesan.create') }}" class="btn-primary shadow-lg shadow-tazkia-700/25">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                Tulis pesan
            </a>
        </div>
    </x-slot>

    <div class="space-y-4">
        <x-flash />

        @if ($daftar->count())
            <div class="card overflow-hidden">
                <div class="divide-y divide-line">
                    @foreach ($daftar as $p)
                        @php
                            [$labelStatus, $kelasStatus] = $statusUi[$p->status] ?? [$p->status, 'bg-canvas text-ink-soft'];
                            $persen = $p->akun_count > 0 ? round($p->dibaca_count / $p->akun_count * 100) : null;
                        @endphp
                        <a href="{{ route('akademik.pesan.show', $p) }}" class="block p-5 transition hover:bg-tazkia-50/50 sm:p-6">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <h2 class="truncate font-bold text-tazkia-900">{{ $p->judul }}</h2>
                                    <p class="mt-1 truncate text-sm text-ink-soft">{{ $p->target['label'] ?? '' }}</p>
                                </div>
                                <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $kelasStatus }}">{{ $labelStatus }}</span>
                            </div>

                            <div class="mt-4 flex flex-wrap items-center gap-x-6 gap-y-2 text-xs text-ink-muted">
                                <span><strong class="text-ink-soft">{{ $p->jumlah_mahasiswa }}</strong> mahasiswa</span>
                                <span><strong class="text-ink-soft">{{ $p->akun_count }}</strong> akun orang tua dijangkau</span>
                                @if ($p->kirim_wa)<span><strong class="text-ink-soft">{{ $p->wa_terkirim_count }}</strong> WhatsApp terkirim</span>@endif
                                <span class="ml-auto">{{ $p->pengirim->name ?? '-' }} · {{ $p->created_at->translatedFormat('d M Y, H:i') }}</span>
                            </div>

                            @if ($persen !== null)
                                <div class="mt-3 flex items-center gap-3">
                                    <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-canvas"><div class="h-full rounded-full bg-tazkia-700" style="width: {{ $persen }}%"></div></div>
                                    <span class="w-24 shrink-0 text-right text-xs font-semibold text-tazkia-700">{{ $persen }}% terbaca</span>
                                </div>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
            {{ $daftar->links() }}
        @else
            <div class="card px-6 py-16 text-center">
                <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-tazkia-50 text-tazkia-600">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z"/><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z"/></svg>
                </span>
                <h2 class="mt-5 text-lg font-bold text-tazkia-900">Belum ada pesan terkirim</h2>
                <p class="mx-auto mt-1.5 max-w-sm text-ink-soft">Tulis pengumuman pertama untuk orang tua mahasiswa.</p>
                <a href="{{ route('akademik.pesan.create') }}" class="btn-primary mt-6">Tulis pesan</a>
            </div>
        @endif
    </div>
</x-app-layout>
