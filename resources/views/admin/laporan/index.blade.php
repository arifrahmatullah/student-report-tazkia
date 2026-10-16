<x-app-layout title="Laporan hasil belajar">
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-tazkia-900">Laporan hasil belajar</h1>
                <p class="mt-1.5 text-ink-soft">Surat laporan akademik mahasiswa penerima beasiswa.</p>
            </div>
            <a href="{{ route('admin.laporan.create') }}" class="btn-primary shadow-lg shadow-tazkia-700/25">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                Buat laporan
            </a>
        </div>
    </x-slot>

    <div class="space-y-4">
        <x-flash />

        @if ($daftar->count())
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="border-b border-line bg-canvas/60 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">
                            <tr>
                                <th class="px-6 py-3.5">Beasiswa</th>
                                <th class="px-4 py-3.5">Nomor surat</th>
                                <th class="px-4 py-3.5">Sampai</th>
                                <th class="px-4 py-3.5 text-center">Mahasiswa</th>
                                <th class="px-4 py-3.5">Tanggal</th>
                                <th class="px-6 py-3.5"><span class="sr-only">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($daftar as $s)
                                <tr class="transition hover:bg-tazkia-50/50">
                                    <td class="px-6 py-4">
                                        <a href="{{ route('admin.laporan.show', $s) }}" class="font-semibold text-tazkia-900 hover:text-tazkia-700">{{ $s->beasiswa_nama }}</a>
                                        <div class="text-xs text-ink-muted">oleh {{ $s->pembuat->name ?? '-' }}</div>
                                    </td>
                                    <td class="px-4 py-4 text-ink-soft">{{ $s->nomor_surat }}</td>
                                    <td class="px-4 py-4 text-ink-soft">{{ $s->sampai_tahun_akademik_nama }}</td>
                                    <td class="px-4 py-4 text-center"><span class="rounded-full bg-tazkia-50 px-2.5 py-1 text-xs font-bold text-tazkia-700">{{ count($s->snapshot['penerima'] ?? []) }}</span></td>
                                    <td class="whitespace-nowrap px-4 py-4 text-ink-soft">{{ $s->tanggal_surat->translatedFormat('d M Y') }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right">
                                        <a href="{{ route('admin.laporan.pdf', $s) }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-semibold text-tazkia-700 transition hover:bg-tazkia-50">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                            PDF
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            {{ $daftar->links() }}
        @else
            <div class="card px-6 py-16 text-center">
                <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-tazkia-50 text-tazkia-600">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                </span>
                <h2 class="mt-5 text-lg font-bold text-tazkia-900">Belum ada laporan</h2>
                <p class="mx-auto mt-1.5 max-w-sm text-ink-soft">Pilih beasiswa dan semester, dan sistem akan menyusun surat beserta transkripnya.</p>
                <a href="{{ route('admin.laporan.create') }}" class="btn-primary mt-6">Buat laporan pertama</a>
            </div>
        @endif
    </div>
</x-app-layout>
