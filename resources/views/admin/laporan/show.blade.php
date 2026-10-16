@php
    $penerima = collect($surat->snapshot['penerima'] ?? []);
    $dec = fn ($n) => number_format((float) $n, 2, ',', '');
    $diambil = isset($surat->snapshot['diambil_pada']) ? \Illuminate\Support\Carbon::parse($surat->snapshot['diambil_pada']) : $surat->created_at;
@endphp
<x-app-layout :title="$surat->beasiswa_nama">
    <x-slot name="header">
        <a href="{{ route('admin.laporan.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-ink-soft transition hover:text-tazkia-700">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            Semua laporan
        </a>
        <div class="mt-3 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-tazkia-900">{{ $surat->beasiswa_nama }}</h1>
                <p class="mt-1.5 text-ink-soft">Laporan sampai {{ $surat->sampai_tahun_akademik_nama }} · {{ $penerima->count() }} mahasiswa</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.laporan.pdf', $surat) }}" target="_blank" class="btn-primary shadow-lg shadow-tazkia-700/25">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                    Unduh PDF
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <x-flash />

        <div class="flex gap-3 rounded-xl bg-tazkia-50 p-4 text-sm leading-relaxed text-tazkia-900">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-tazkia-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.04-.02a.75.75 0 011.06.85l-.7 2.8a.75.75 0 001.06.85l.04-.02M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.01"/></svg>
            <p>Angka pada laporan ini adalah salinan dari SIAKAD per <strong>{{ $diambil->translatedFormat('d F Y, H:i') }}</strong>. Jika nilai di SIAKAD berubah setelahnya, buat laporan baru agar angkanya terbarui.</p>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                {{-- Rincian IPK --}}
                <section class="card overflow-hidden">
                    <div class="border-b border-line px-6 py-4"><h2 class="text-lg font-bold text-tazkia-900">Rincian IPK</h2></div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-canvas/60 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">
                                <tr><th class="px-6 py-3">No</th><th class="px-3 py-3">NIM</th><th class="px-3 py-3">Nama</th><th class="px-3 py-3">Program studi</th><th class="px-3 py-3 text-center">Angkatan</th><th class="px-3 py-3 text-center">SKS</th><th class="px-6 py-3 text-right">IPK</th></tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @foreach ($penerima as $p)
                                    <tr>
                                        <td class="px-6 py-3.5 text-ink-muted">{{ $loop->iteration }}</td>
                                        <td class="px-3 py-3.5 text-ink-soft">{{ $p['nim'] }}</td>
                                        <td class="px-3 py-3.5 font-semibold text-ink">{{ $p['nama'] }}</td>
                                        <td class="px-3 py-3.5 text-ink-soft">{{ $p['prodiNama'] ?? '-' }}</td>
                                        <td class="px-3 py-3.5 text-center text-ink-soft">{{ $p['angkatan'] ?? '-' }}</td>
                                        <td class="px-3 py-3.5 text-center text-ink-soft">{{ $p['totalSks'] ?? '-' }}</td>
                                        <td class="px-6 py-3.5 text-right text-base font-extrabold text-tazkia-700">{{ $dec($p['ipk'] ?? 0) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>

                {{-- Transkrip --}}
                <section class="space-y-3">
                    <h2 class="text-lg font-bold text-tazkia-900">Transkrip per mahasiswa</h2>
                    @foreach ($penerima as $p)
                        <details class="card group overflow-hidden" @if ($loop->first) open @endif>
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-6 py-4 [&::-webkit-details-marker]:hidden">
                                <div>
                                    <div class="font-bold text-tazkia-900">{{ $p['nama'] }}</div>
                                    <div class="text-xs text-ink-muted">{{ $p['nim'] }} · {{ count($p['semester'] ?? []) }} semester · {{ $p['totalSks'] ?? 0 }} SKS · IPK {{ $dec($p['ipk'] ?? 0) }}</div>
                                </div>
                                <svg class="h-5 w-5 shrink-0 text-ink-muted transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                            </summary>
                            <div class="space-y-5 border-t border-line px-6 py-5">
                                @foreach ($p['semester'] ?? [] as $smt)
                                    <div>
                                        <div class="mb-2 flex items-baseline justify-between text-sm">
                                            <span class="font-bold text-ink">Semester {{ $smt['semesterKe'] }} <span class="font-normal text-ink-muted">· {{ $smt['tahunAkademik'] ?? '' }}</span></span>
                                            <span class="text-xs text-ink-muted">IPS <strong class="text-tazkia-700">{{ $dec($smt['ips'] ?? 0) }}</strong></span>
                                        </div>
                                        <div class="overflow-x-auto rounded-xl border border-line">
                                            <table class="min-w-full text-xs">
                                                <thead class="bg-canvas/60 text-left font-semibold text-ink-muted"><tr><th class="px-3 py-2">Kode</th><th class="px-3 py-2">Mata kuliah</th><th class="px-3 py-2 text-center">SKS</th><th class="px-3 py-2 text-center">Nilai</th><th class="px-3 py-2 text-center">Bobot</th><th class="px-3 py-2 text-right">Mutu</th></tr></thead>
                                                <tbody class="divide-y divide-line">
                                                    @foreach ($smt['matakuliah'] as $mk)
                                                        <tr><td class="px-3 py-2 text-ink-muted">{{ $mk['kode'] ?? '' }}</td><td class="px-3 py-2 text-ink">{{ $mk['nama'] }}</td><td class="px-3 py-2 text-center">{{ $mk['sks'] }}</td><td class="px-3 py-2 text-center font-semibold">{{ $mk['nilai'] }}</td><td class="px-3 py-2 text-center">{{ $dec($mk['bobot']) }}</td><td class="px-3 py-2 text-right">{{ $dec($mk['mutu']) }}</td></tr>
                                                    @endforeach
                                                    <tr class="bg-canvas/60 font-bold"><td colspan="2" class="px-3 py-2 text-right">Jumlah</td><td class="px-3 py-2 text-center">{{ $smt['totalSks'] ?? '' }}</td><td></td><td></td><td class="px-3 py-2 text-right">{{ $dec($smt['totalMutu'] ?? 0) }}</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endforeach
                                <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-tazkia-50 px-4 py-3 text-sm">
                                    <span class="text-tazkia-900">Total <strong>{{ $p['totalSks'] ?? 0 }}</strong> SKS · mutu <strong>{{ $dec($p['totalMutu'] ?? 0) }}</strong></span>
                                    <span class="font-extrabold text-tazkia-700">IPK {{ $dec($p['ipk'] ?? 0) }}</span>
                                </div>
                            </div>
                        </details>
                    @endforeach
                </section>
            </div>

            {{-- Samping: data surat --}}
            <aside class="space-y-6">
                <section class="card p-6">
                    <h2 class="text-lg font-bold text-tazkia-900">Surat pengantar</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div><dt class="text-xs text-ink-muted">Nomor</dt><dd class="font-semibold text-ink">{{ $surat->nomor_surat }}</dd></div>
                        <div><dt class="text-xs text-ink-muted">Tanggal</dt><dd class="font-semibold text-ink">{{ $surat->tempat_surat }}, {{ $surat->tanggal_surat->translatedFormat('j F Y') }} M / {{ $hijriah }}@unless ($surat->tanggal_hijriah) <span class="font-normal text-ink-muted">(Hijriah otomatis)</span>@endunless</dd></div>
                        <div><dt class="text-xs text-ink-muted">Lampiran</dt><dd class="font-semibold text-ink">{{ $surat->lampiran }}</dd></div>
                        <div><dt class="text-xs text-ink-muted">Perihal</dt><dd class="font-semibold text-ink">{{ $surat->perihal }}</dd></div>
                        <div>
                            <dt class="text-xs text-ink-muted">Kepada Yth.</dt>
                            <dd class="font-semibold text-ink">{{ $surat->penerima_nama }}<div class="text-xs font-normal text-ink-soft">{{ $surat->penerima_jabatan }}</div><div class="mt-1 whitespace-pre-line text-xs font-normal text-ink-muted">{{ $surat->penerima_alamat }}</div></dd>
                        </div>
                        <div><dt class="text-xs text-ink-muted">Beasiswa pada surat</dt><dd class="font-semibold text-ink">{{ $surat->nama_beasiswa_surat }}</dd></div>
                        <div>
                            <dt class="text-xs text-ink-muted">Penandatangan</dt>
                            <dd class="font-semibold text-ink">
                                @if ($surat->penandatangan) {{ $surat->penandatangan->nama }}<div class="text-xs font-normal text-ink-muted">{{ $surat->penandatangan->jabatan }}</div>
                                @else <span class="font-normal text-red-600">Penandatangan telah dihapus</span> @endif
                            </dd>
                        </div>
                    </dl>
                </section>

                <form method="POST" action="{{ route('admin.laporan.destroy', $surat) }}" onsubmit="return confirm('Hapus laporan ini? Tindakan ini tidak dapat dibatalkan.')" class="text-center">
                    @csrf @method('DELETE')
                    <button class="text-sm text-ink-muted underline-offset-2 transition hover:text-red-600 hover:underline">Hapus laporan</button>
                </form>
            </aside>
        </div>
    </div>
</x-app-layout>
