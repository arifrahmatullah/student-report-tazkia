@php
    $statusUi = [
        'terkirim' => ['Terkirim', 'bg-emerald-50 text-emerald-700'],
        'gagal' => ['Gagal', 'bg-red-50 text-red-700'],
        'tanpa_nomor' => ['Tanpa nomor', 'bg-accent-50 text-accent-700'],
        'nomor_tidak_valid' => ['Nomor tidak valid', 'bg-accent-50 text-accent-700'],
        'duplikat' => ['Nomor sama', 'bg-canvas text-ink-soft'],
        'diblokir' => ['Diblokir (mode uji)', 'bg-red-50 text-red-700'],
    ];
    $peran = ['ayah' => 'Ayah', 'ibu' => 'Ibu', 'wali' => 'Wali', 'mahasiswa' => 'Mahasiswa', 'kontak' => 'Kontak', 'tes' => 'Tes oleh'];
    $total = $ringkas->sum();
@endphp
<x-app-layout title="Log WhatsApp">
    <x-slot name="header">
        <h1 class="text-3xl font-extrabold tracking-tight text-tazkia-900">Log WhatsApp</h1>
        <p class="mt-1.5 text-ink-soft">Setiap percobaan kirim: pengingat KRS, pengumuman, dan tes kirim.</p>
    </x-slot>

    <div class="space-y-6">
        {{-- Ringkasan --}}
        <section class="grid grid-cols-2 gap-4 lg:grid-cols-5">
            <a href="{{ route('akademik.log-wa.index', array_filter(['jenis' => $f['jenis'] ?? null, 'q' => $f['q'] ?? null, 'pesan' => $f['pesan'] ?? null])) }}" class="card p-5 transition hover:border-tazkia-300 {{ empty($f['status']) ? 'ring-2 ring-tazkia-600/15' : '' }}">
                <div class="text-xs font-medium text-ink-muted">Semua</div><div class="mt-1 text-3xl font-extrabold text-ink">{{ $total }}</div>
            </a>
            @foreach (['terkirim', 'gagal', 'tanpa_nomor', 'nomor_tidak_valid'] as $st)
                <a href="{{ route('akademik.log-wa.index', array_filter(['jenis' => $f['jenis'] ?? null, 'q' => $f['q'] ?? null, 'pesan' => $f['pesan'] ?? null, 'status' => $st])) }}"
                   class="card p-5 transition hover:border-tazkia-300 {{ ($f['status'] ?? null) === $st ? 'ring-2 ring-tazkia-600/15' : '' }}">
                    <div class="text-xs font-medium text-ink-muted">{{ $statusUi[$st][0] }}</div>
                    <div class="mt-1 text-3xl font-extrabold {{ $st === 'terkirim' ? 'text-emerald-600' : ($st === 'gagal' ? 'text-red-600' : 'text-accent-600') }}">{{ $ringkas[$st] ?? 0 }}</div>
                </a>
            @endforeach
        </section>

        {{-- Penyaring --}}
        <form method="GET" class="card grid gap-4 p-5 sm:grid-cols-[1fr_1fr_1.4fr_auto] sm:items-end">
            @if (! empty($f['pesan']))<input type="hidden" name="pesan" value="{{ $f['pesan'] }}">@endif
            <div>
                <x-input-label for="jenis" value="Jenis" />
                <select id="jenis" name="jenis" class="field mt-1.5">
                    <option value="">Semua jenis</option>
                    @foreach ($jenis as $k => $v)<option value="{{ $k }}" @selected(($f['jenis'] ?? '') === $k)>{{ $v }}</option>@endforeach
                </select>
            </div>
            <div>
                <x-input-label for="status" value="Status" />
                <select id="status" name="status" class="field mt-1.5">
                    <option value="">Semua status</option>
                    @foreach ($statusUi as $k => [$v])<option value="{{ $k }}" @selected(($f['status'] ?? '') === $k)>{{ $v }}</option>@endforeach
                </select>
            </div>
            <div>
                <x-input-label for="q" value="Cari NIM, nama, atau nomor" />
                <x-text-input id="q" name="q" class="mt-1.5" :value="$f['q'] ?? ''" placeholder="Contoh: 2510101078" />
            </div>
            <button class="btn-primary">Terapkan</button>
        </form>

        @if (! empty($f['pesan']))
            <p class="text-sm text-ink-soft">Menampilkan log untuk satu pesan. <a href="{{ route('akademik.log-wa.index') }}" class="font-semibold text-tazkia-700 hover:text-accent-600">Tampilkan semua</a> · <a href="{{ route('akademik.pesan.show', $f['pesan']) }}" class="font-semibold text-tazkia-700 hover:text-accent-600">Lihat pesannya</a></p>
        @endif

        @if ($baris->count())
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-canvas text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">
                            <tr><th class="px-6 py-3">Waktu</th><th class="px-3 py-3">Jenis</th><th class="px-3 py-3">Mahasiswa</th><th class="px-3 py-3">Penerima</th><th class="px-3 py-3">Nomor</th><th class="px-3 py-3">Status</th><th class="px-6 py-3">Keterangan</th></tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($baris as $b)
                                @php [$lbl, $kls] = $statusUi[$b->status] ?? [$b->status, 'bg-canvas text-ink-soft']; @endphp
                                <tr class="align-top transition hover:bg-tazkia-50/40">
                                    <td class="whitespace-nowrap px-6 py-3.5 text-xs text-ink-muted">{{ \Illuminate\Support\Carbon::parse($b->created_at)->translatedFormat('d M Y') }}<br>{{ \Illuminate\Support\Carbon::parse($b->created_at)->format('H:i:s') }}</td>
                                    <td class="px-3 py-3.5 text-xs">
                                        <span class="font-semibold text-ink-soft">{{ $jenis[$b->jenis] ?? $b->jenis }}</span>
                                        @if ($b->pesan_id)<a href="{{ route('akademik.pesan.show', $uuidPesan[$b->pesan_id] ?? 0) }}" class="mt-0.5 block text-tazkia-700 hover:text-accent-600">Pesan #{{ $b->pesan_id }}</a>@endif
                                    </td>
                                    <td class="px-3 py-3.5"><div class="font-semibold text-ink">{{ $b->nama_mahasiswa ?? '-' }}</div><div class="text-xs text-ink-muted">{{ $b->nim }}</div></td>
                                    <td class="px-3 py-3.5 text-xs text-ink-soft">{{ $peran[$b->tipe] ?? $b->tipe }}@if ($b->nama_penerima)<div class="text-ink-muted">{{ $b->nama_penerima }}</div>@endif</td>
                                    <td class="whitespace-nowrap px-3 py-3.5 font-mono text-xs text-ink-soft">{{ $b->no_hp ?? '–' }}</td>
                                    <td class="px-3 py-3.5"><span class="whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $kls }}">{{ $lbl }}</span></td>
                                    <td class="max-w-xs px-6 py-3.5 text-xs leading-relaxed {{ $b->status === 'gagal' ? 'text-red-700' : 'text-ink-muted' }}">
                                        @if ($b->status === 'terkirim') Diterima WhatsApp @else {{ \Illuminate\Support\Str::limit((string) $b->respons, 160) }} @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            {{ $baris->links() }}
            <p class="text-xs leading-relaxed text-ink-muted">“Terkirim” berarti pesan diterima oleh WhatsApp untuk diteruskan. Apakah sudah sampai atau dibaca penerima belum dilacak di sini.</p>
        @else
            <div class="card px-6 py-16 text-center">
                <h2 class="text-lg font-bold text-tazkia-900">Belum ada catatan</h2>
                <p class="mx-auto mt-1.5 max-w-sm text-ink-soft">{{ $f ? 'Tidak ada log yang cocok dengan penyaring ini.' : 'Log akan muncul setelah ada pengiriman atau tes kirim WhatsApp.' }}</p>
            </div>
        @endif
    </div>
</x-app-layout>
