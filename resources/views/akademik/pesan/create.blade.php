@php
    $in = fn ($k) => $errors->has($k) ? 'field-error' : '';
    $seksi = 'card p-6 sm:p-7';
    $mode = [
        ['beasiswa', 'Penerima beasiswa', 'Mahasiswa pada satu program beasiswa.', 'M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342'],
        ['angkatan', 'Prodi / angkatan', 'Mahasiswa aktif menurut program studi atau angkatan.', 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z'],
        ['nim', 'Daftar NIM', 'Ketik atau tempel NIM tertentu.', 'M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z'],
    ];
@endphp
<x-app-layout title="Tulis pesan">
    <x-slot name="header">
        <a href="{{ route('akademik.pesan.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-ink-soft transition hover:text-tazkia-700">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            Semua pesan
        </a>
        <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-tazkia-900">Tulis pesan</h1>
        <p class="mt-1.5 text-ink-soft">Pesan masuk ke inbox portal orang tua yang sudah menautkan anaknya.</p>
    </x-slot>

    @if ($gagal)
        <div class="mb-6 flex items-center justify-between gap-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
            <span>Sebagian data dari SIAKAD tidak dapat dimuat, sehingga pilihan di bawah mungkin belum lengkap.</span>
            <a href="{{ route('akademik.pesan.create') }}" class="shrink-0 font-bold hover:underline">Muat ulang</a>
        </div>
    @endif

    <form method="POST" action="{{ route('akademik.pesan.store') }}" class="space-y-6"
          x-data="pesanForm({
              url: @js(route('akademik.pesan.estimasi')),
              waAktif: @js($waAktif),
              mode: @js(old('mode', 'beasiswa')),
              beasiswa_id: @js(old('beasiswa_id', '')), jenjang: @js(old('jenjang', '')), prodi_id: @js(old('prodi_id', '')), angkatan: @js(old('angkatan', '')), nim: @js(old('nim', '')),
              keOrangtua: @js((bool) old('ke_orangtua', true)), keMahasiswa: @js((bool) old('ke_mahasiswa', false)), kirimWa: @js((bool) old('kirim_wa', false)),
              isi: @js(old('isi', '')),
          })"
          x-init="cek()" @submit="kirim($event)">
        @csrf

        {{-- 1. Penerima --}}
        <section class="{{ $seksi }}">
            <div class="flex items-center gap-3">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-tazkia-700 text-sm font-extrabold text-white">1</span>
                <h2 class="text-lg font-bold text-tazkia-900">Siapa penerimanya?</h2>
            </div>

            <div class="mt-6 grid gap-3 sm:grid-cols-3" role="radiogroup">
                @foreach ($mode as [$nilai, $judul, $ket, $ikon])
                    <label class="relative flex cursor-pointer flex-col rounded-xl border p-4 transition"
                           :class="mode === '{{ $nilai }}' ? 'border-tazkia-600 bg-tazkia-50 ring-2 ring-tazkia-600/15' : 'border-line bg-white hover:border-tazkia-200'">
                        <input type="radio" name="mode" value="{{ $nilai }}" x-model="mode" @change="cek()" class="sr-only">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg" :class="mode === '{{ $nilai }}' ? 'bg-tazkia-700 text-white' : 'bg-canvas text-tazkia-700'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $ikon }}"/></svg>
                        </span>
                        <span class="mt-3 text-sm font-bold text-tazkia-900">{{ $judul }}</span>
                        <span class="mt-0.5 text-xs leading-relaxed text-ink-muted">{{ $ket }}</span>
                    </label>
                @endforeach
            </div>
            <x-input-error :messages="$errors->get('mode')" class="mt-3" />

            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2" x-show="mode === 'beasiswa'" x-cloak>
                    <x-input-label for="beasiswa_id" value="Program beasiswa" />
                    <select id="beasiswa_id" name="beasiswa_id" x-model="beasiswa_id" @change="cek()" :disabled="mode !== 'beasiswa'" class="field mt-1.5 {{ $in('beasiswa_id') }}">
                        <option value="">Pilih beasiswa…</option>
                        @foreach ($beasiswa as $b)
                            <option value="{{ $b['id'] }}">{{ $b['nama'] }} — {{ $b['jumlahPenerima'] }} penerima</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('beasiswa_id')" class="mt-2" />
                </div>

                <div x-show="mode !== 'nim'" x-cloak>
                    <x-input-label for="jenjang" value="Jenjang" />
                    <select id="jenjang" name="jenjang" x-model="jenjang" @change="cek()" :disabled="mode === 'nim'" class="field mt-1.5">
                        <option value="">Semua jenjang</option>
                        @foreach (\App\Services\Pesan\Jenjang::DAFTAR as $j)
                            <option value="{{ $j }}">{{ $j }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-show="mode !== 'nim'" x-cloak>
                    <x-input-label for="prodi_id" value="Program studi" />
                    <select id="prodi_id" name="prodi_id" x-model="prodi_id" @change="cek()" :disabled="mode === 'nim'" class="field mt-1.5">
                        <option value="">Semua program studi</option>
                        @foreach ($prodi as $p)
                            <option value="{{ $p['id'] }}">{{ $p['nama'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-show="mode !== 'nim'" x-cloak>
                    <x-input-label for="angkatan" value="Angkatan" />
                    <x-text-input id="angkatan" name="angkatan" type="number" inputmode="numeric" min="1" max="2100" x-model="angkatan" @input.debounce.600ms="cek()" x-bind:disabled="mode === 'nim'" class="mt-1.5 {{ $in('angkatan') }}" placeholder="Semua angkatan" />
                    <x-input-error :messages="$errors->get('angkatan')" class="mt-2" />
                </div>
                <p class="-mt-2 text-xs leading-relaxed text-ink-muted sm:col-span-2" x-show="mode !== 'nim'" x-cloak>
                    <span x-show="mode === 'angkatan'">Pilih jenjang, program studi, atau isi angkatan (minimal salah satu). </span>
                    Angkatan S1 ditulis 4 digit (2025) dan S2 2 digit (25); pilih jenjang agar disesuaikan otomatis. Hanya mahasiswa <strong>aktif</strong> yang dituju.
                </p>

                <div class="sm:col-span-2" x-show="mode === 'nim'" x-cloak>
                    <x-input-label for="nim" value="Daftar NIM" />
                    <textarea id="nim" name="nim" rows="4" x-model="nim" @input.debounce.700ms="cek()" :disabled="mode !== 'nim'" class="field mt-1.5 font-mono text-sm {{ $in('nim') }}" placeholder="2510101078&#10;2410101024"></textarea>
                    <p class="mt-1.5 text-xs text-ink-muted">Pisahkan dengan baris baru, spasi, atau koma. Maksimal {{ \App\Services\Pesan\PenerimaResolver::MAKS_NIM_MANUAL }} NIM.</p>
                    <x-input-error :messages="$errors->get('nim')" class="mt-2" />
                </div>
            </div>

            {{-- Estimasi --}}
            <div class="mt-6 rounded-xl border border-dashed border-tazkia-200 bg-tazkia-50/60 p-4 text-sm" aria-live="polite">
                <template x-if="est.status === 'kosong'"><p class="text-ink-muted">Pilih penerima untuk melihat perkiraan jumlahnya.</p></template>
                <template x-if="est.status === 'memuat'"><p class="flex items-center gap-2 text-ink-soft"><svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" opacity=".25"/><path d="M21 12a9 9 0 00-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg> Menghitung penerima…</p></template>
                <template x-if="est.status === 'galat'"><p class="text-red-700" x-text="est.pesan"></p></template>
                <template x-if="est.status === 'siap'">
                    <div>
                        <p class="text-tazkia-900"><strong class="text-lg font-extrabold" x-text="est.d.mahasiswa"></strong> mahasiswa akan dituju.</p>
                        <p class="mt-1 text-ink-soft"><strong x-text="est.d.terhubung"></strong> sudah punya akun orang tua di portal (<span x-text="est.d.akun"></span> akun) · <strong x-text="est.d.belum_terhubung" class="text-accent-700"></strong> belum terhubung.</p>
                        <p class="mt-1 text-xs text-ink-muted" x-show="est.d.belum_terhubung > 0">Pesan ke inbox hanya sampai ke orang tua yang sudah menautkan anaknya.</p>
                        <p class="mt-1 text-xs text-ink-muted" x-show="est.d.tidak_aktif > 0" x-text="est.d.tidak_aktif + ' mahasiswa tidak aktif dilewati.'"></p>
                        <p class="mt-1 text-xs text-ink-muted" x-show="est.d.di_luar_rentang > 0" x-text="est.d.di_luar_rentang + ' mahasiswa dari angkatan lebih lama (di luar ' + est.d.rentang + ') tidak ikut. Isi angkatan untuk memilih angkatan lama.'"></p>
                        <p class="mt-1 text-xs text-red-700" x-show="est.d.tidak_ditemukan.length" x-text="'NIM tidak ditemukan di SIAKAD: ' + est.d.tidak_ditemukan.join(', ')"></p>
                    </div>
                </template>
            </div>

            {{-- Jenis penerima & kanal --}}
            <div class="mt-6 space-y-3 border-t border-line pt-6">
                <h3 class="text-sm font-bold text-tazkia-900">Dikirim ke</h3>
                <label class="flex cursor-pointer items-start gap-3">
                    <input type="checkbox" name="ke_orangtua" value="1" x-model="keOrangtua" class="mt-0.5 h-4 w-4 rounded border-line text-tazkia-700 focus:ring-2 focus:ring-tazkia-700/20 focus:ring-offset-0">
                    <span><span class="text-sm font-semibold text-ink">Orang tua</span><span class="block text-xs text-ink-muted">Masuk ke inbox portal (dan WhatsApp bila diaktifkan).</span></span>
                </label>
                <label class="flex cursor-pointer items-start gap-3">
                    <input type="checkbox" name="ke_mahasiswa" value="1" x-model="keMahasiswa" @change="if (keMahasiswa && waAktif) kirimWa = true" class="mt-0.5 h-4 w-4 rounded border-line text-tazkia-700 focus:ring-2 focus:ring-tazkia-700/20 focus:ring-offset-0">
                    <span><span class="text-sm font-semibold text-ink">Mahasiswa</span><span class="block text-xs text-ink-muted">Mahasiswa tidak punya inbox di portal, jadi hanya lewat WhatsApp ke nomor mereka.</span></span>
                </label>
                <x-input-error :messages="$errors->get('ke_orangtua')" />

                <label class="flex items-start gap-3" :class="waAktif ? 'cursor-pointer' : 'cursor-not-allowed opacity-60'">
                    <input type="checkbox" name="kirim_wa" value="1" x-model="kirimWa" :disabled="!waAktif" class="mt-0.5 h-4 w-4 rounded border-line text-tazkia-700 focus:ring-2 focus:ring-tazkia-700/20 focus:ring-offset-0">
                    <span>
                        <span class="text-sm font-semibold text-ink">Kirim juga notifikasi WhatsApp</span>
                        <span class="block text-xs text-ink-muted" x-show="waAktif">Memakai template resmi. Hanya nomor yang tercatat di SIAKAD yang bisa dijangkau.</span>
                        <span class="block text-xs text-accent-700" x-show="!waAktif">WhatsApp belum dikonfigurasi (token, ID nomor, dan template di .env). Pesan hanya masuk ke inbox portal.</span>
                    </span>
                </label>
                <x-input-error :messages="$errors->get('kirim_wa')" />
            </div>
        </section>

        {{-- 2. Pesan --}}
        <section class="{{ $seksi }}">
            <div class="flex items-center gap-3">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-tazkia-700 text-sm font-extrabold text-white">2</span>
                <h2 class="text-lg font-bold text-tazkia-900">Isi pesan</h2>
            </div>

            <div class="mt-6 space-y-5">
                <div>
                    <x-input-label for="judul" value="Judul" />
                    <x-text-input id="judul" name="judul" maxlength="150" class="mt-1.5 {{ $in('judul') }}" :value="old('judul')" required placeholder="Contoh: Batas akhir pengisian KRS" />
                    <x-input-error :messages="$errors->get('judul')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="isi" value="Pesan" />
                    <textarea id="isi" name="isi" rows="9" maxlength="5000" x-model="isi" required class="field mt-1.5 leading-relaxed {{ $in('isi') }}" placeholder="Tulis pesan untuk orang tua…"></textarea>
                    <div class="mt-1.5 flex justify-between text-xs text-ink-muted">
                        <span>Teks biasa; baris baru dipertahankan.</span>
                        <span x-text="isi.length + ' / 5000'"></span>
                    </div>
                    <x-input-error :messages="$errors->get('isi')" class="mt-2" />
                </div>
            </div>
        </section>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="{{ route('akademik.pesan.index') }}" class="btn-secondary">Batal</a>
            <button type="submit" class="btn-primary shadow-lg shadow-tazkia-700/25" x-bind:disabled="busy">
                <span x-show="!busy">Kirim pesan</span>
                <span x-show="busy" x-cloak>Mengirim…</span>
            </button>
        </div>
    </form>

    <script>
        function pesanForm(awal) {
            return {
                ...awal, busy: false,
                est: { status: 'kosong', d: null, pesan: '' },
                _timer: null, _seq: 0,
                // Cukup lengkap untuk dihitung?
                siap() {
                    if (this.mode === 'beasiswa') return !!this.beasiswa_id;
                    if (this.mode === 'angkatan') return !!(this.jenjang || this.prodi_id || this.angkatan);
                    return this.nim.trim().length > 0;
                },
                cek() {
                    if (!this.siap()) { this.est = { status: 'kosong', d: null, pesan: '' }; return; }
                    this.est.status = 'memuat';
                    const id = ++this._seq;
                    const q = new URLSearchParams({ mode: this.mode });
                    if (this.mode === 'beasiswa') q.set('beasiswa_id', this.beasiswa_id);
                    if (this.mode !== 'nim') { if (this.jenjang) q.set('jenjang', this.jenjang); if (this.prodi_id) q.set('prodi_id', this.prodi_id); if (this.angkatan) q.set('angkatan', this.angkatan); }
                    if (this.mode === 'nim') q.set('nim', this.nim);
                    fetch(this.url + '?' + q.toString(), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(async (r) => ({ ok: r.ok, j: await r.json().catch(() => ({})) }))
                        .then(({ ok, j }) => {
                            if (id !== this._seq) return; // jawaban lama diabaikan
                            if (ok) this.est = { status: 'siap', d: j, pesan: '' };
                            else this.est = { status: 'galat', d: null, pesan: j.pesan || (j.errors ? Object.values(j.errors)[0][0] : 'Penerima belum dapat dihitung.') };
                        })
                        .catch(() => { if (id === this._seq) this.est = { status: 'galat', d: null, pesan: 'Tidak dapat menghitung penerima. Periksa koneksi Anda.' }; });
                },
                kirim(e) {
                    const n = this.est.status === 'siap' ? this.est.d.mahasiswa : null;
                    const lewat = [this.keOrangtua ? 'orang tua' : null, this.keMahasiswa ? 'mahasiswa' : null].filter(Boolean).join(' dan ');
                    const teks = n !== null
                        ? `Kirim pesan ini ke ${lewat} dari ${n} mahasiswa? Pesan tidak dapat ditarik kembali.`
                        : 'Kirim pesan ini sekarang? Pesan tidak dapat ditarik kembali.';
                    if (!confirm(teks)) { e.preventDefault(); return; }
                    this.busy = true;
                },
            };
        }
    </script>
</x-app-layout>
