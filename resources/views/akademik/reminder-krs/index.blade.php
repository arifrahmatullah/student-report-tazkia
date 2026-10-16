@php
    $statusKrs = [
        null => ['Belum diisi', 'bg-red-50 text-red-700'],
        'DRAFT' => ['Draf', 'bg-accent-50 text-accent-700'],
        'DIAJUKAN' => ['Diajukan', 'bg-tazkia-50 text-tazkia-700'],
    ];
    $contoh = $daftar?->first(fn ($m) => ($m['statusKrs'] ?? null) !== 'DIAJUKAN') ?? $daftar?->first();
    $nimSemua = $daftar?->pluck('nim')->values();
    $awalDipilih = $daftar?->filter(fn ($m) => ($m['statusKrs'] ?? null) !== 'DIAJUKAN')->pluck('nim')->values();
    $fieldKirim = 'form="form-kirim"';
    $tahunAkhir = $tahunMulai;
    $tautan = fn (array $ubah) => route('akademik.reminder-krs.index', array_filter(array_merge(request()->only(['jenjang', 'angkatan', 'prodi_id']), $ubah), fn ($v) => $v !== null));
    $bubble = 'max-w-xl whitespace-pre-line rounded-2xl rounded-tl-sm bg-[#e7f6e0] p-4 text-sm leading-relaxed text-ink shadow-sm';
    $isiTemplate = function (?array $info) use ($contoh, $parameter) {
        if (! $info || ! $info['isi'] || ! $parameter || ! $contoh) {
            return null;
        }

        return strtr($info['isi'], ['{{nama}}' => $contoh['nama'], '{{nim}}' => $contoh['nim'], '{{semester}}' => $parameter['semester'],
            '{{academic_year}}' => $parameter['academic_year'], '{{date}}' => $parameter['date']]);
    };
    $saranTeksMahasiswa = "Assalamualaikum Wr. Wb.\n\nYth. {{nama}} (NIM: {{nim}}),\n\nBerdasarkan data BAAK, Anda belum mengisi KRS Semester {{semester}} TA {{academic_year}}. Mohon segera menyelesaikan pengisian KRS paling lambat {{date}}. Jika KRS tidak diisi pada waktu yang ditetapkan, Anda tidak dapat mengikuti perkuliahan dan ujian semester UTS/UAS.\n\nDemikian, terima kasih.\nWassalamualaikum Wr. Wb.";
@endphp
<x-app-layout title="Reminder KRS">
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-tazkia-900">Pengingat KRS</h1>
                <p class="mt-1.5 text-ink-soft">Lihat dulu mahasiswa yang belum KRS beserta nomornya, tes kirim, baru kirim.</p>
            </div>
            @if ($tahunAktif)
                <div class="rounded-xl border border-line bg-white px-4 py-2.5 text-sm shadow-card">
                    <div class="text-xs text-ink-muted">Tahun akademik berjalan</div>
                    <div class="font-bold text-tazkia-900">{{ $tahunAktif['nama'] }}</div>
                    @if (! empty($tahunAktif['tanggalSelesaiKrs']))
                        <div class="text-xs text-ink-soft">KRS ditutup <strong>{{ \Illuminate\Support\Carbon::parse($tahunAktif['tanggalSelesaiKrs'])->translatedFormat('d F Y') }}</strong></div>
                    @endif
                </div>
            @endif
        </div>
    </x-slot>

    <div class="space-y-6">
        <x-flash />

        @if ($gagal)
            <div class="flex items-center justify-between gap-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                <span>Data dari SIAKAD tidak dapat dimuat saat ini.</span>
                <a href="{{ url()->full() }}" class="shrink-0 font-bold hover:underline">Muat ulang</a>
            </div>
        @elseif (! $tahunAktif)
            <div class="rounded-xl border border-accent-200 bg-accent-50 px-4 py-3 text-sm text-ink" role="alert">Tidak ada tahun akademik aktif di SIAKAD, sehingga pengingat tidak dapat disusun.</div>
        @endif

        {{-- Status WhatsApp & template --}}
        <section class="card p-5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $waSiap ? 'bg-emerald-50 text-emerald-600' : 'bg-accent-50 text-accent-600' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM2.25 12.76c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.076-4.076a1.526 1.526 0 011.037-.443 48.282 48.282 0 005.68-.494c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/></svg>
                    </span>
                    <div>
                        <div class="text-sm font-bold text-tazkia-900">WhatsApp {{ $waSiap ? 'siap' : 'belum dikonfigurasi' }}</div>
                        <div class="mt-0.5 space-y-0.5 text-xs text-ink-muted">
                            <div>Mahasiswa: <code class="rounded bg-canvas px-1.5 py-0.5 text-[11px] text-ink-soft">{{ $templateMhs }}</code>
                                @if ($infoMhs) · <span class="font-semibold {{ $infoMhs['status'] === 'APPROVED' ? 'text-emerald-700' : 'text-red-700' }}">{{ $infoMhs['status'] }}</span> di Meta @elseif ($waSiap) · status tidak dapat diperiksa @endif
                                @unless ($adaTemplateMhs) · <span class="font-semibold text-accent-700">cadangan template orang tua</span> @endunless</div>
                            @if ($templateOrtu !== $templateMhs)
                                <div>Orang tua: <code class="rounded bg-canvas px-1.5 py-0.5 text-[11px] text-ink-soft">{{ $templateOrtu }}</code>
                                    @if ($infoOrtu) · <span class="font-semibold {{ $infoOrtu['status'] === 'APPROVED' ? 'text-emerald-700' : 'text-red-700' }}">{{ $infoOrtu['status'] }}</span> di Meta @endif</div>
                            @endif
                        </div>
                    </div>
                </div>
                @unless ($waSiap)
                    <p class="max-w-md text-xs leading-relaxed text-ink-muted">Isi <code>WA_TOKEN</code> dan <code>WA_PHONE_NUMBER_ID</code> di <code>.env</code>. Tanpa itu, pengingat hanya dapat masuk ke inbox portal orang tua yang sudah terhubung.</p>
                @endunless
            </div>

            @unless ($adaTemplateMhs)
                <div class="mt-4 rounded-xl border border-accent-200 bg-accent-50 p-4 text-sm leading-relaxed text-ink">
                    <p><strong>Belum ada template khusus mahasiswa.</strong> Template <code class="text-xs">{{ $templateOrtu }}</code> menyapa “Yth. Bapak/Ibu Orang Tua/Wali Mahasiswa” dan menyebut “Ananda …”, jadi mahasiswa yang menerimanya akan membaca sapaan untuk orang tuanya. Disarankan mendaftarkan template untuk mahasiswa di Meta lalu mengisi <code class="text-xs">WA_TEMPLATE_REMINDER_KRS_MAHASISWA</code> di <code class="text-xs">.env</code>.</p>
                    <details class="mt-2">
                        <summary class="cursor-pointer text-xs font-semibold text-accent-700">Lihat usulan teks template untuk mahasiswa</summary>
                        <pre class="mt-2 whitespace-pre-wrap rounded-lg bg-white p-3 text-xs leading-relaxed text-ink-soft">{{ $saranTeksMahasiswa }}</pre>
                        <p class="mt-1 text-xs text-ink-muted">Variabel bernama sama dengan template lama: nama, nim, semester, academic_year, date. Kategori dan persetujuan ditentukan Meta.</p>
                    </details>
                </div>
            @endunless
        </section>

        @if ($modeUji['terbatas'])
            <div class="flex gap-3 rounded-xl border border-accent-200 bg-accent-50 px-4 py-3 text-sm text-ink" role="status">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-accent-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.3 3.4c-.87 1.5.22 3.35 1.95 3.35h14.7c1.73 0 2.82-1.85 1.95-3.35L13.95 4.1a2.25 2.25 0 00-3.9 0L2.7 16.15zM12 15.75h.01"/></svg>
                <p>
                    <strong>Mode uji ({{ app()->environment() }}).</strong>
                    @if ($modeUji['nomor'])
                        WhatsApp hanya dikirim ke <strong>{{ count($modeUji['nomor']) }}</strong> nomor yang didaftarkan di <code>WA_NOMOR_UJI</code>; nomor lain tercatat sebagai “Diblokir”, tidak ada yang terkirim.
                    @else
                        Daftar <code>WA_NOMOR_UJI</code> kosong, jadi <strong>tidak ada WhatsApp yang akan terkirim</strong> (termasuk tes). Isi dengan nomor Anda untuk mencoba tes kirim. Pengaman ini mati sendiri di <code>production</code>.
                    @endif
                </p>
            </div>
        @endif

        {{-- Langkah 1: pilih mahasiswa --}}
        <section class="card p-6">
            <div class="flex items-center gap-3">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-tazkia-700 text-sm font-extrabold text-white">1</span>
                <h2 class="text-lg font-bold text-tazkia-900">Pilih jenjang, angkatan, atau program studi</h2>
            </div>
            <form method="GET" action="{{ route('akademik.reminder-krs.index') }}" class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-[0.8fr_0.9fr_1.6fr_auto] lg:items-end">
                @if ($lama)<input type="hidden" name="lama" value="1">@endif
                <div>
                    <x-input-label for="jenjang" value="Jenjang" />
                    <select id="jenjang" name="jenjang" class="field mt-1.5">
                        <option value="">Semua jenjang</option>
                        @foreach (\App\Services\Pesan\Jenjang::DAFTAR as $j)
                            <option value="{{ $j }}" @selected(($filter['jenjang'] ?? '') === $j)>{{ $j }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="angkatan" value="Angkatan" />
                    <x-text-input id="angkatan" name="angkatan" type="number" inputmode="numeric" min="1" max="2100" class="mt-1.5" :value="$filter['angkatan'] ?? ''" placeholder="{{ $tahunAkhir ?? '2026' }}" />
                </div>
                <div>
                    <x-input-label for="prodi_id" value="Program studi" />
                    <select id="prodi_id" name="prodi_id" class="field mt-1.5">
                        <option value="">Semua program studi</option>
                        @foreach ($prodi as $pr)
                            <option value="{{ $pr['id'] }}" @selected(($filter['prodi_id'] ?? '') === $pr['id'])>{{ $pr['jenjang'] ?? '' }} · {{ $pr['nama'] }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn-primary">Tampilkan mahasiswa</button>
                <p class="text-xs leading-relaxed text-ink-muted sm:col-span-2 lg:col-span-4">
                    Angkatan S1 ditulis 4 digit (<strong>2025</strong>) dan S2 2 digit (<strong>25</strong>); pilih jenjang agar isian angkatan disesuaikan otomatis. Hanya mahasiswa berstatus <strong>aktif</strong> yang ditampilkan.
                </p>
            </form>

            {{-- Rentang angkatan --}}
            @if ($tahunMin)
                <div class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-1 rounded-xl bg-tazkia-50 px-4 py-2.5 text-xs text-tazkia-900">
                    @if (! empty($filter['angkatan']))
                        <span>Angkatan dipilih langsung, jadi rentang 4 tahun terakhir tidak berlaku.</span>
                    @elseif ($lama)
                        <span>Menampilkan <strong>semua angkatan</strong>, termasuk yang lama.</span>
                        <a href="{{ $tautan(['lama' => null]) }}" class="font-semibold text-tazkia-700 underline hover:text-accent-600">Kembali ke angkatan {{ $tahunMin }}–{{ $tahunMulai }}</a>
                    @else
                        <span>Menampilkan angkatan <strong>{{ $tahunMin }}–{{ $tahunMulai }}</strong> (4 tahun terakhir).</span>
                        @if ($tersembunyi > 0)
                            <span class="text-ink-soft">{{ $tersembunyi }} mahasiswa dari angkatan lebih lama disembunyikan.</span>
                            <a href="{{ $tautan(['lama' => 1]) }}" class="font-semibold text-tazkia-700 underline hover:text-accent-600">Tampilkan semua angkatan</a>
                        @endif
                    @endif
                </div>
            @endif

            @if ($ringkasan)
                <div class="mt-6 space-y-6 border-t border-line pt-5">
                    <p class="text-sm text-ink-soft">Ringkasan mahasiswa <strong>aktif</strong> yang belum punya KRS disetujui. Pilih angkatan untuk melihat daftarnya:</p>
                    @forelse ($ringkasan as $jenjang => $perAngkatan)
                        <div>
                            <div class="mb-3 flex items-baseline justify-between">
                                <h3 class="text-sm font-bold uppercase tracking-wide text-tazkia-900">{{ $jenjang }}</h3>
                                <a href="{{ $tautan(['jenjang' => $jenjang, 'angkatan' => null, 'prodi_id' => null]) }}" class="text-xs font-semibold text-tazkia-700 hover:text-accent-600">Semua {{ $jenjang }} ({{ $perAngkatan->sum('jumlah') }}) &rarr;</a>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($perAngkatan as $angkatan => $r)
                                    <a href="{{ route('akademik.reminder-krs.index', ['jenjang' => $jenjang, 'angkatan' => $angkatan]) }}" class="group flex items-center justify-between rounded-xl border border-line p-4 transition hover:border-tazkia-300 hover:bg-tazkia-50/50">
                                        <div>
                                            <div class="font-bold text-tazkia-900">{{ \App\Services\Pesan\Jenjang::label($jenjang, $angkatan) }}</div>
                                            <div class="text-xs text-ink-muted">{{ $r['draf'] }} draf · {{ $r['diajukan'] }} diajukan</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-2xl font-extrabold text-accent-600">{{ $r['jumlah'] }}</div>
                                            <div class="text-[11px] text-ink-muted">belum KRS</div>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="rounded-xl bg-canvas px-4 py-8 text-center text-sm text-ink-muted">Tidak ada mahasiswa aktif yang belum KRS pada rentang angkatan ini.</p>
                    @endforelse
                </div>
            @endif
        </section>

        @if ($daftar !== null)
            @if ($daftar->isEmpty())
                <section class="card px-6 py-14 text-center">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                    </span>
                    <h2 class="mt-4 text-lg font-bold text-tazkia-900">Semua sudah mengisi KRS</h2>
                    <p class="mx-auto mt-1.5 max-w-sm text-ink-soft">Tidak ada mahasiswa aktif yang belum punya KRS disetujui pada pilihan ini.@if ($tersembunyi > 0) <a href="{{ $tautan(['lama' => 1]) }}" class="font-semibold text-tazkia-700 underline">Ada {{ $tersembunyi }} dari angkatan lebih lama.</a>@endif</p>
                </section>
            @else
                <div x-data="reminderKrs({
                        kontakUrl: @js(route('akademik.reminder-krs.kontak')),
                        semua: @js($nimSemua),
                        dipilih: @js($awalDipilih),
                        waSiap: @js($waSiap),
                        kirimWa: @js($waSiap),
                        adaTemplateMhs: @js($adaTemplateMhs),
                    })" x-init="periksa()" class="space-y-6">

                    {{-- Langkah 2: daftar --}}
                    <section class="card overflow-hidden">
                        <div class="border-b border-line p-6">
                            <div class="flex items-center gap-3">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-tazkia-700 text-sm font-extrabold text-white">2</span>
                                <h2 class="text-lg font-bold text-tazkia-900">
                                    Mahasiswa yang belum KRS
                                    @if ($labelFilter)
                                        <span class="ml-1 rounded-full bg-tazkia-50 px-2.5 py-0.5 align-middle text-xs font-semibold text-tazkia-700">{{ $labelFilter }}</span>
                                    @endif
                                </h2>
                            </div>
                            <p class="mt-2 text-sm text-ink-soft">
                                <strong class="text-ink">{{ $daftar->count() }}</strong> mahasiswa aktif belum punya KRS disetujui
                                ({{ $daftar->whereNull('statusKrs')->count() }} belum diisi · {{ $daftar->where('statusKrs', 'DRAFT')->count() }} draf · {{ $daftar->where('statusKrs', 'DIAJUKAN')->count() }} diajukan).
                            </p>
                            @if ($daftar->where('statusKrs', 'DIAJUKAN')->count() > 0)
                                <p class="mt-2 rounded-lg bg-tazkia-50 px-3 py-2 text-xs leading-relaxed text-tazkia-900">Yang berstatus <strong>Diajukan</strong> sudah mengisi dan tinggal menunggu persetujuan dosen wali, jadi tidak dicentang bawaan.</p>
                            @endif

                            {{-- Kirim ke siapa: pesan ditujukan kepada mahasiswa --}}
                            <div class="mt-5 flex flex-wrap items-center gap-x-6 gap-y-3 rounded-xl border border-line bg-canvas/60 px-4 py-3">
                                <span class="text-sm font-bold text-tazkia-900">Kirim ke</span>
                                <label class="flex cursor-pointer items-center gap-2">
                                    <input type="checkbox" name="ke_mahasiswa" value="1" form="form-kirim" x-model="keMahasiswa" @change="if (keMahasiswa && waSiap) kirimWa = true" class="h-4 w-4 rounded border-line text-tazkia-700 focus:ring-2 focus:ring-tazkia-700/20 focus:ring-offset-0">
                                    <span class="text-sm font-semibold text-ink">Mahasiswa</span><span class="text-xs text-ink-muted">(nomor mahasiswa)</span>
                                </label>
                                <label class="flex cursor-pointer items-center gap-2">
                                    <input type="checkbox" name="ke_orangtua" value="1" form="form-kirim" x-model="keOrangtua" class="h-4 w-4 rounded border-line text-tazkia-700 focus:ring-2 focus:ring-tazkia-700/20 focus:ring-offset-0">
                                    <span class="text-sm font-semibold text-ink">Orang tua</span><span class="text-xs text-ink-muted">(ayah, lalu ibu, lalu wali)</span>
                                </label>
                            </div>
                            <x-input-error :messages="$errors->get('ke_orangtua')" class="mt-2" />

                            <div class="mt-4 space-y-1.5 text-xs text-ink-muted">
                                <div>
                                    <strong class="text-ink-soft" x-text="dipilih.length"></strong> dipilih
                                    <span x-show="cek.selesai < semua.length && !cek.berhenti" class="ml-3 inline-flex items-center gap-1.5">
                                        <svg class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" opacity=".25"/><path d="M21 12a9 9 0 00-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                                        Memeriksa nomor… <span x-text="cek.selesai + ' / ' + semua.length"></span>
                                    </span>
                                </div>
                                <div x-show="cek.selesai >= semua.length || cek.berhenti" class="space-y-1">
                                    <div x-show="keMahasiswa">Nomor <strong class="text-ink-soft">mahasiswa</strong>:
                                        <strong class="text-emerald-700" x-text="dipilih.length - tanpaNomorDipilih('mahasiswa')"></strong> dari yang dipilih punya nomor valid ·
                                        <strong class="text-accent-700" x-text="tanpaNomorDipilih('mahasiswa')"></strong> tanpa nomor / tidak valid</div>
                                    <div x-show="keOrangtua">Nomor <strong class="text-ink-soft">orang tua</strong>:
                                        <strong class="text-emerald-700" x-text="dipilih.length - tanpaNomorDipilih('orangtua')"></strong> dari yang dipilih punya nomor ·
                                        <strong class="text-accent-700" x-text="tanpaNomorDipilih('orangtua')"></strong> tanpa nomor</div>
                                </div>
                            </div>
                        </div>

                        <div class="max-h-[34rem] overflow-auto">
                            <table class="min-w-full text-sm">
                                <thead class="sticky top-0 z-10 bg-canvas text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">
                                    <tr>
                                        <th class="w-12 px-6 py-3"><input type="checkbox" :checked="dipilih.length === semua.length" :indeterminate.prop="dipilih.length > 0 && dipilih.length < semua.length" @change="pilihSemua($event.target.checked)" class="h-4 w-4 rounded border-line text-tazkia-700 focus:ring-2 focus:ring-tazkia-700/20 focus:ring-offset-0" aria-label="Pilih semua"></th>
                                        <th class="px-3 py-3">Mahasiswa</th>
                                        <th class="px-3 py-3">Program studi</th>
                                        <th class="px-3 py-3">Status KRS</th>
                                        <th class="px-3 py-3" :class="keMahasiswa ? 'text-tazkia-700' : ''">Nomor mahasiswa</th>
                                        <th class="px-6 py-3" :class="keOrangtua ? 'text-tazkia-700' : 'opacity-60'">Nomor orang tua</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-line">
                                    @foreach ($daftar as $m)
                                        @php [$lbl, $kls] = $statusKrs[$m['statusKrs'] ?? null] ?? [$m['statusKrs'], 'bg-canvas text-ink-soft']; $n = $m['nim']; @endphp
                                        <tr class="transition hover:bg-tazkia-50/40" :class="dipilih.includes('{{ $n }}') ? '' : 'opacity-60'">
                                            <td class="px-6 py-3"><input type="checkbox" name="nim[]" value="{{ $n }}" {!! $fieldKirim !!} x-model="dipilih" class="h-4 w-4 rounded border-line text-tazkia-700 focus:ring-2 focus:ring-tazkia-700/20 focus:ring-offset-0" aria-label="Pilih {{ $m['nama'] }}"></td>
                                            <td class="px-3 py-3"><div class="font-semibold text-ink">{{ $m['nama'] }}</div><div class="text-xs text-ink-muted">{{ $n }} · Angkatan {{ $m['angkatan'] }}</div></td>
                                            <td class="px-3 py-3 text-ink-soft">
                                                @if (! empty($m['jenjang']))<span class="mr-1.5 rounded bg-canvas px-1.5 py-0.5 text-[11px] font-bold text-tazkia-700">{{ $m['jenjang'] }}</span>@endif{{ $m['prodiNama'] ?? '-' }}
                                            </td>
                                            <td class="px-3 py-3"><span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $kls }}">{{ $lbl }}</span></td>
                                            <td class="whitespace-nowrap px-3 py-3 text-xs" :class="keMahasiswa ? '' : 'opacity-60'">
                                                <template x-if="!kontak['{{ $n }}']"><span class="text-ink-muted">…</span></template>
                                                <template x-if="kontak['{{ $n }}'] && kontak['{{ $n }}'].mahasiswa.ada"><span class="font-mono font-semibold text-emerald-700" x-text="kontak['{{ $n }}'].mahasiswa.nomor"></span></template>
                                                <template x-if="kontak['{{ $n }}'] && !kontak['{{ $n }}'].mahasiswa.ada && kontak['{{ $n }}'].mahasiswa.mentah"><span class="font-semibold text-red-700">Tidak valid: <span class="font-mono" x-text="kontak['{{ $n }}'].mahasiswa.mentah"></span></span></template>
                                                <template x-if="kontak['{{ $n }}'] && !kontak['{{ $n }}'].mahasiswa.ada && !kontak['{{ $n }}'].mahasiswa.mentah"><span class="font-semibold text-accent-700" x-text="kontak['{{ $n }}'].galat ? 'Gagal diperiksa' : 'Tanpa nomor'"></span></template>
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-3 text-xs" :class="keOrangtua ? '' : 'opacity-60'">
                                                <template x-if="!kontak['{{ $n }}']"><span class="text-ink-muted">…</span></template>
                                                <template x-if="kontak['{{ $n }}'] && kontak['{{ $n }}'].orangtua.ada"><span class="text-emerald-700"><span class="font-semibold" x-text="kontak['{{ $n }}'].orangtua.nomor"></span> <span class="text-ink-muted" x-text="'(' + kontak['{{ $n }}'].orangtua.peran + ')'"></span></span></template>
                                                <template x-if="kontak['{{ $n }}'] && !kontak['{{ $n }}'].orangtua.ada"><span class="font-semibold text-accent-700" x-text="kontak['{{ $n }}'].galat ? 'Gagal diperiksa' : 'Tanpa nomor'"></span></template>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>

                    {{-- Langkah 3: pratinjau pesan --}}
                    <section class="card p-6">
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-tazkia-700 text-sm font-extrabold text-white">3</span>
                            <h2 class="text-lg font-bold text-tazkia-900">Pratinjau pesan WhatsApp</h2>
                        </div>
                        @if ($parameter && $contoh)
                            <p class="mt-3 text-xs text-ink-muted">Contoh untuk <strong class="text-ink-soft">{{ $contoh['nama'] }}</strong>. Teks resmi template dari Meta; yang berbeda hanya nama, NIM, semester, tahun, dan batas KRS ({{ $parameter['date'] }}).</p>
                            <div class="mt-4 space-y-5">
                                <div x-show="keMahasiswa">
                                    <div class="mb-1.5 text-xs font-bold uppercase tracking-wide text-tazkia-700">Ke mahasiswa</div>
                                    @if ($isiTemplate($infoMhs))
                                        <div class="{{ $bubble }}">{{ $isiTemplate($infoMhs) }}</div>
                                    @else
                                        <p class="text-sm text-ink-soft">Isi template <code class="rounded bg-canvas px-1.5 py-0.5 text-xs">{{ $templateMhs }}</code> tidak dapat dimuat dari Meta (isi <code>WA_WABA_ID</code>).</p>
                                    @endif
                                    @unless ($adaTemplateMhs)
                                        <p class="mt-2 text-xs font-semibold text-accent-700">Peringatan: ini template untuk orang tua, bukan untuk mahasiswa (lihat catatan di atas).</p>
                                    @endunless
                                </div>
                                <div x-show="keOrangtua">
                                    <div class="mb-1.5 text-xs font-bold uppercase tracking-wide text-tazkia-700">Ke orang tua</div>
                                    @if ($isiTemplate($infoOrtu))
                                        <div class="{{ $bubble }}">{{ $isiTemplate($infoOrtu) }}</div>
                                    @else
                                        <p class="text-sm text-ink-soft">Isi template <code class="rounded bg-canvas px-1.5 py-0.5 text-xs">{{ $templateOrtu }}</code> tidak dapat dimuat dari Meta.</p>
                                    @endif
                                </div>
                            </div>
                            <p class="mt-4 text-xs leading-relaxed text-ink-muted">Teks tetap di template, mis. “perkuliahan sudah masuk pekan ke-2”, jadi pastikan waktunya sesuai sebelum mengirim.</p>
                        @endif
                    </section>

                    {{-- Langkah 4: tes kirim --}}
                    <section class="card p-6" id="tes">
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-tazkia-700 text-sm font-extrabold text-white">4</span>
                            <h2 class="text-lg font-bold text-tazkia-900">Tes kirim dulu (disarankan)</h2>
                        </div>
                        <p class="mt-2 text-sm text-ink-soft">Kirim satu pesan contoh ke nomor WhatsApp Anda sendiri dengan template yang sama, untuk memastikan pesannya tampil benar.</p>

                        @if (session('hasil_tes'))
                            <ul class="mt-4 space-y-2" role="status">
                                @foreach (session('hasil_tes') as $h)
                                    <li class="flex items-start gap-3 rounded-xl px-4 py-3 text-sm {{ $h['status'] === 'terkirim' ? 'bg-emerald-50 text-emerald-800' : 'bg-red-50 text-red-800' }}">
                                        <span class="mt-0.5 font-bold">{{ $h['status'] === 'terkirim' ? '✓' : '✕' }}</span>
                                        <span><strong>{{ $h['nomor'] }}</strong> — {{ $h['status'] === 'terkirim' ? 'Terkirim ke WhatsApp.' : $h['keterangan'] }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <form method="POST" action="{{ route('akademik.reminder-krs.tes') }}" class="mt-5 grid gap-4 sm:grid-cols-3" x-data="{ busy: false }" @submit="busy = true">
                            @csrf
                            <div class="sm:col-span-2">
                                <x-input-label for="nomor" value="Nomor WhatsApp (pisahkan dengan koma, maksimal 3)" />
                                <x-text-input id="nomor" name="nomor" class="mt-1.5 {{ $errors->has('nomor') ? 'field-error' : '' }}" :value="old('nomor')" placeholder="081234567890" required />
                                <x-input-error :messages="$errors->get('nomor')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="untuk" value="Uji template untuk" />
                                <select id="untuk" name="untuk" class="field mt-1.5">
                                    <option value="mahasiswa" @selected(old('untuk', 'mahasiswa') === 'mahasiswa')>Mahasiswa</option>
                                    <option value="orangtua" @selected(old('untuk') === 'orangtua')>Orang tua</option>
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <x-input-label for="nama" value="Nama pada pesan contoh" />
                                <x-text-input id="nama" name="nama" class="mt-1.5" :value="old('nama', $contoh['nama'] ?? '')" />
                            </div>
                            <div>
                                <x-input-label for="nim-tes" value="NIM pada pesan contoh" />
                                <x-text-input id="nim-tes" name="nim" class="mt-1.5" :value="old('nim', $contoh['nim'] ?? '')" />
                            </div>
                            <div class="sm:col-span-3">
                                <button type="submit" class="btn-secondary" :disabled="busy || !{{ $waSiap ? 'true' : 'false' }}">
                                    <span x-show="!busy">Kirim tes</span><span x-show="busy" x-cloak>Mengirim…</span>
                                </button>
                                @unless ($waSiap)<span class="ml-3 text-xs text-accent-700">WhatsApp belum dikonfigurasi.</span>@endunless
                            </div>
                        </form>
                    </section>

                    {{-- Langkah 5: kirim --}}
                    <section class="card p-6">
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-accent-500 text-sm font-extrabold text-white">5</span>
                            <h2 class="text-lg font-bold text-tazkia-900">Kirim pengingat</h2>
                        </div>

                        @if ($tesTerakhir === 0 && $waSiap)
                            <p class="mt-3 rounded-lg bg-accent-50 px-3 py-2 text-xs text-accent-700">Belum ada tes kirim yang berhasil dalam 24 jam terakhir. Disarankan tes dulu di langkah 4.</p>
                        @endif

                        <form id="form-kirim" method="POST" action="{{ route('akademik.reminder-krs.kirim') }}" x-ref="kirim" @submit="konfirmasi($event)">
                            @csrf
                            <input type="hidden" name="jenjang" value="{{ $filter['jenjang'] ?? '' }}">
                            <input type="hidden" name="angkatan" value="{{ $filter['angkatan'] ?? '' }}">
                            <input type="hidden" name="prodi_id" value="{{ $filter['prodi_id'] ?? '' }}">
                            @if ($lama)<input type="hidden" name="lama" value="1">@endif
                        </form>
                        <x-input-error :messages="$errors->get('nim')" class="mt-3" />

                        <div class="mt-5 space-y-3">
                            <label class="flex items-start gap-3" :class="waSiap ? 'cursor-pointer' : 'cursor-not-allowed opacity-60'">
                                <input type="checkbox" name="kirim_wa" value="1" form="form-kirim" x-model="kirimWa" :disabled="!waSiap" class="mt-0.5 h-4 w-4 rounded border-line text-tazkia-700 focus:ring-2 focus:ring-tazkia-700/20 focus:ring-offset-0">
                                <span><span class="text-sm font-semibold text-ink">Kirim lewat WhatsApp</span><span class="block text-xs text-ink-muted">Hasil tiap pengiriman tercatat di Log WhatsApp. Orang tua yang sudah terhubung ke portal juga menerima pesan di inbox.</span></span>
                            </label>
                            <x-input-error :messages="$errors->get('kirim_wa')" />
                        </div>

                        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-xs text-ink-muted">Hanya yang <strong>masih</strong> belum punya KRS disetujui saat Anda menekan kirim yang dikirimi; yang baru mengisi dilewati otomatis.</p>
                            <button type="submit" form="form-kirim" class="btn-primary bg-accent-500 shadow-lg shadow-accent-500/25 hover:bg-accent-600 active:bg-accent-700" :disabled="dipilih.length === 0 || busy || (!keMahasiswa && !keOrangtua)">
                                <span x-show="!busy">Kirim pengingat ke <span x-text="dipilih.length"></span> mahasiswa</span>
                                <span x-show="busy" x-cloak>Mengirim…</span>
                            </button>
                        </div>
                    </section>
                </div>
            @endif
        @endif
    </div>

    <script>
        function reminderKrs(awal) {
            return {
                ...awal, busy: false, keMahasiswa: true, keOrangtua: false,
                kontak: {}, cek: { selesai: 0, berhenti: false },
                // Jumlah mahasiswa terpilih yang TIDAK punya nomor untuk jenis penerima tertentu.
                tanpaNomorDipilih(jenis) {
                    return this.dipilih.filter(n => {
                        const k = this.kontak[n];
                        return !k || !k[jenis === 'mahasiswa' ? 'mahasiswa' : 'orangtua'].ada;
                    }).length;
                },
                pilihSemua(centang) { this.dipilih = centang ? [...this.semua] : []; },
                // Nomor diperiksa bertahap (25 NIM per permintaan) agar daftar langsung terlihat tanpa menunggu SIAKAD.
                async periksa() {
                    for (let i = 0; i < this.semua.length; i += 25) {
                        const q = new URLSearchParams();
                        this.semua.slice(i, i + 25).forEach(n => q.append('nim[]', n));
                        try {
                            const r = await fetch(this.kontakUrl + '?' + q, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                            if (!r.ok) throw new Error();
                            Object.assign(this.kontak, await r.json());
                        } catch (e) {
                            this.semua.slice(i, i + 25).forEach(n => this.kontak[n] = { galat: true, mahasiswa: { ada: false }, orangtua: { ada: false } });
                        }
                        this.cek.selesai = Math.min(i + 25, this.semua.length);
                    }
                },
                konfirmasi(e) {
                    const lewat = [this.keMahasiswa ? 'mahasiswa' : null, this.keOrangtua ? 'orang tua' : null].filter(Boolean).join(' dan ');
                    const baris = [];
                    if (this.kirimWa && this.keMahasiswa && this.tanpaNomorDipilih('mahasiswa') > 0) baris.push(`${this.tanpaNomorDipilih('mahasiswa')} mahasiswa tidak punya nomor valid sehingga tidak menerima WhatsApp.`);
                    if (this.kirimWa && this.keOrangtua && this.tanpaNomorDipilih('orangtua') > 0) baris.push(`${this.tanpaNomorDipilih('orangtua')} tidak punya nomor orang tua.`);
                    if (this.kirimWa && this.keMahasiswa && !this.adaTemplateMhs) baris.push('PERHATIAN: template yang dipakai menyapa "Orang Tua/Wali", bukan mahasiswa.');
                    const catatan = baris.length ? '\n\n' + baris.join('\n') : '';
                    if (!confirm(`Kirim pengingat KRS ke ${lewat} dari ${this.dipilih.length} mahasiswa?${catatan}\n\nPesan tidak dapat ditarik kembali.`)) { e.preventDefault(); return; }
                    this.busy = true;
                },
            };
        }
    </script>
</x-app-layout>
