@php
    $persenDibaca = $ringkas['akun'] > 0 ? round($ringkas['dibaca'] / $ringkas['akun'] * 100) : null;
    $belumTerhubung = $ringkas['mahasiswa'] - $ringkas['terhubung'];
    $waUi = [
        'terkirim' => ['Terkirim', 'bg-emerald-50 text-emerald-700'],
        'antre' => ['Antre', 'bg-canvas text-ink-soft'],
        'gagal' => ['Gagal', 'bg-red-50 text-red-700'],
        'tanpa_nomor' => ['Tanpa nomor', 'bg-accent-50 text-accent-700'],
        'nomor_tidak_valid' => ['Nomor tidak valid', 'bg-accent-50 text-accent-700'],
        'duplikat' => ['Nomor sama', 'bg-canvas text-ink-soft'],
        'diblokir' => ['Diblokir (mode uji)', 'bg-red-50 text-red-700'],
    ];
    $peran = ['ayah' => 'Ayah', 'ibu' => 'Ibu', 'wali' => 'Wali', 'mahasiswa' => 'Mahasiswa', 'kontak' => 'Kontak'];
    $diproses = $pesan->status === 'diproses';
@endphp
<x-app-layout :title="$pesan->judul">
    <x-slot name="header">
        <a href="{{ route('akademik.pesan.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-ink-soft transition hover:text-tazkia-700">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            Semua pesan
        </a>
        <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-tazkia-900">{{ $pesan->judul }}</h1>
        <p class="mt-1.5 text-ink-soft">{{ $pesan->target['label'] ?? '' }} · {{ $pesan->pengirim->name ?? '-' }} · {{ $pesan->created_at->translatedFormat('d F Y, H:i') }}</p>
    </x-slot>

    <div class="space-y-6">
        <x-flash />

        @if ($diproses)
            <div class="flex items-center gap-3 rounded-xl border border-accent-200 bg-accent-50 px-4 py-3 text-sm text-ink" role="status">
                <svg class="h-4 w-4 shrink-0 animate-spin text-accent-600" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" opacity=".25"/><path d="M21 12a9 9 0 00-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                Notifikasi WhatsApp masih dikirim di latar belakang. Muat ulang halaman ini untuk melihat perkembangannya.
            </div>
        @elseif ($pesan->status === 'gagal')
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">Pengiriman WhatsApp berhenti karena galat. Pesan di inbox portal tidak terpengaruh.</div>
        @endif

        {{-- Ringkasan --}}
        <section class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="card p-5"><div class="text-xs font-medium text-ink-muted">Mahasiswa dituju</div><div class="mt-1 text-3xl font-extrabold text-ink">{{ $ringkas['mahasiswa'] }}</div></div>
            <div class="card p-5">
                <div class="text-xs font-medium text-ink-muted">Sudah terhubung ke akun</div>
                <div class="mt-1 text-3xl font-extrabold text-tazkia-700">{{ $ringkas['terhubung'] }}</div>
                <div class="mt-0.5 text-xs text-ink-muted">{{ $ringkas['akun'] }} akun orang tua</div>
            </div>
            <div class="card p-5">
                <div class="text-xs font-medium text-ink-muted">Sudah dibaca</div>
                <div class="mt-1 text-3xl font-extrabold text-ink">{{ $persenDibaca === null ? '–' : $persenDibaca.'%' }}</div>
                <div class="mt-0.5 text-xs text-ink-muted">{{ $ringkas['dibaca'] }} dari {{ $ringkas['akun'] }} akun</div>
            </div>
            <div class="card p-5">
                <div class="text-xs font-medium text-ink-muted">Belum terhubung</div>
                <div class="mt-1 text-3xl font-extrabold {{ $belumTerhubung > 0 ? 'text-accent-600' : 'text-ink' }}">{{ $belumTerhubung }}</div>
                <div class="mt-0.5 text-xs text-ink-muted">tidak menerima di inbox</div>
            </div>
        </section>

        @if ($pesan->kirim_wa)
            <section class="card p-5 sm:p-6">
                <h2 class="text-sm font-bold text-tazkia-900">WhatsApp</h2>
                <div class="mt-3 flex flex-wrap gap-2">
                    @forelse ($ringkas['wa'] as $status => $n)
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $waUi[$status][1] ?? 'bg-canvas text-ink-soft' }}">{{ $waUi[$status][0] ?? $status }}: {{ $n }}</span>
                    @empty
                        <span class="text-sm text-ink-muted">{{ $diproses ? 'Belum ada yang diproses.' : 'Tidak ada kontak WhatsApp yang diproses.' }}</span>
                    @endforelse
                </div>
                @if (($ringkas['wa']['tanpa_nomor'] ?? 0) > 0)
                    <p class="mt-3 text-xs leading-relaxed text-ink-muted">“Tanpa nomor” berarti SIAKAD tidak menyimpan nomor HP untuk kontak tersebut. Pesan di inbox portal tetap sampai ke orang tua yang sudah menautkan anaknya.</p>
                @endif
            </section>
        @endif

        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Isi pesan --}}
            <section class="card self-start p-6 lg:col-span-1">
                <h2 class="text-sm font-bold text-tazkia-900">Isi pesan</h2>
                <div class="mt-3 whitespace-pre-line text-sm leading-7 text-ink">{{ $pesan->isi }}</div>
                @if (! empty($pesan->target['tidak_aktif']))
                    <p class="mt-4 rounded-lg bg-canvas p-3 text-xs text-ink-soft">{{ count($pesan->target['tidak_aktif']) }} mahasiswa tidak aktif dilewati.</p>
                @endif
                @if (! empty($pesan->target['di_luar_rentang']))
                    <p class="mt-4 rounded-lg bg-canvas p-3 text-xs text-ink-soft">{{ $pesan->target['di_luar_rentang'] }} mahasiswa dari angkatan lebih lama tidak ikut (rentang {{ $pesan->target['rentang'] ?? '4 tahun terakhir' }}).</p>
                @endif
                @if (! empty($pesan->target['tidak_ditemukan']))
                    <p class="mt-4 rounded-lg bg-red-50 p-3 text-xs text-red-800">NIM tidak ditemukan di SIAKAD dan dilewati: {{ implode(', ', $pesan->target['tidak_ditemukan']) }}</p>
                @endif
            </section>

            {{-- Per mahasiswa --}}
            <section class="card overflow-hidden lg:col-span-2">
                <div class="border-b border-line px-6 py-4"><h2 class="text-lg font-bold text-tazkia-900">Per mahasiswa</h2></div>
                <div class="max-h-[40rem] overflow-auto">
                    <table class="min-w-full text-sm">
                        <thead class="sticky top-0 bg-canvas text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">
                            <tr><th class="px-6 py-3">Mahasiswa</th><th class="px-3 py-3">Inbox portal</th>@if ($pesan->kirim_wa)<th class="px-6 py-3">WhatsApp</th>@endif</tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($perMahasiswa as $m)
                                <tr class="align-top">
                                    <td class="px-6 py-3.5"><div class="font-semibold text-ink">{{ $m['nama'] ?? '-' }}</div><div class="text-xs text-ink-muted">{{ $m['nim'] }}</div></td>
                                    <td class="px-3 py-3.5">
                                        @if ($m['akun'] === 0)
                                            <span class="rounded-full bg-accent-50 px-2.5 py-0.5 text-xs font-semibold text-accent-700">Belum terhubung</span>
                                        @else
                                            <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $m['dibaca'] === $m['akun'] ? 'bg-emerald-50 text-emerald-700' : 'bg-tazkia-50 text-tazkia-700' }}">{{ $m['dibaca'] }}/{{ $m['akun'] }} dibaca</span>
                                        @endif
                                    </td>
                                    @if ($pesan->kirim_wa)
                                        <td class="px-6 py-3.5">
                                            <div class="flex flex-wrap gap-1.5">
                                                @forelse ($m['wa'] as $w)
                                                    <span title="{{ $w->wa_error }}" class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $waUi[$w->wa_status][1] ?? 'bg-canvas text-ink-soft' }}">{{ $peran[$w->tipe] ?? $w->tipe }}: {{ $waUi[$w->wa_status][0] ?? $w->wa_status }}</span>
                                                @empty
                                                    <span class="text-xs text-ink-muted">–</span>
                                                @endforelse
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
