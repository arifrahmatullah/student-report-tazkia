<?php

namespace Tests\Feature;

use App\Jobs\KirimWaPesanJob;
use App\Models\Pesan;
use App\Models\User;
use App\Services\Siakad\SiakadClient;
use App\Services\WhatsApp\WhatsAppClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReminderKrsTest extends TestCase
{
    use RefreshDatabase;

    private const TA = ['id' => 't1', 'nama' => 'Ganjil 2026/2027', 'tahun' => '2026/2027', 'semester' => 'GASAL', 'tanggalSelesaiKrs' => '2026-10-03'];

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'siakad.api_key' => 'kunci-uji', 'siakad.base_url' => 'https://siakad.test',
            'whatsapp.token' => 'TOKEN-RAHASIA', 'whatsapp.phone_number_id' => '123', 'whatsapp.waba_id' => '999',
            'whatsapp.template_reminder_krs' => 'reminder_telat_krs', 'whatsapp.template_broadcast' => null, 'whatsapp.jeda_ms' => 0, 'whatsapp.nomor_uji' => '*',
        ]);
        foreach (['admin', 'akademik', 'orangtua'] as $r) {
            Role::findOrCreate($r);
        }
        Queue::fake();
    }

    private function akademik(): User
    {
        return User::factory()->create()->assignRole('akademik');
    }

    private function mhs(string $nim, string $nama, ?string $status = null, int $angkatan = 2025): array
    {
        return ['nim' => $nim, 'nama' => $nama, 'prodiId' => 'p1', 'prodiNama' => 'Manajemen Bisnis Syariah', 'angkatan' => $angkatan,
            'statusMahasiswa' => 'AKTIF', 'sudahIsiKrs' => false] + ($status ? ['statusKrs' => $status] : []);
    }

    private function fakeSiakad(array $belum = null, array $ta = self::TA, array $ubah = []): void
    {
        $belum ??= [$this->mhs('1001', 'Ani'), $this->mhs('1002', 'Budi', 'DRAFT'), $this->mhs('1003', 'Cici', 'DIAJUKAN')];
        Http::fake($ubah + [
            '*/api/integrasi/krs/belum-isi*' => Http::response($belum),
            '*/api/integrasi/tahun-akademik/aktif' => Http::response($ta),
            '*/api/integrasi/prodi' => Http::response([['id' => 'p1', 'nama' => 'Manajemen Bisnis Syariah']]),
            '*/message_templates*' => Http::response(['data' => [['name' => 'reminder_telat_krs', 'status' => 'APPROVED', 'category' => 'MARKETING',
                'components' => [['type' => 'BODY', 'text' => 'Ananda {{nama}} (NIM: {{nim}}) belum mengisi KRS Semester {{semester}} TA {{academic_year}}, batas {{date}}.']]]]]),
            '*/mahasiswa/1001/orang-tua' => Http::response(['ayah' => ['nama' => 'Budi Sr', 'noHp' => '0812-3456-7890'], 'ibu' => ['nama' => 'Siti', 'noHp' => '085711112222']]),
            '*/mahasiswa/1002/orang-tua' => Http::response(['ayah' => ['nama' => 'Cahyo'], 'ibu' => ['nama' => 'Dewi', 'noHp' => '085733334444']]),
            '*/mahasiswa/1003/orang-tua' => Http::response(['ayah' => ['nama' => 'Eko']]),
            'graph.facebook.com/*/messages' => Http::response(['messages' => [['id' => 'wamid.1']]]),
        ]);
    }

    // ---------------------------------------------------------------- akses

    public function test_hanya_staf_akademik_dan_admin(): void
    {
        $this->fakeSiakad();
        $ortu = User::factory()->create()->assignRole('orangtua');

        $url = ['/akademik/reminder-krs', '/akademik/log-wa', '/akademik/reminder-krs/kontak?nim[]=1001'];
        foreach ($url as $u) {
            $this->get($u)->assertRedirect('/login');
        }
        foreach ($url as $u) {
            $this->actingAs($ortu)->get($u)->assertForbidden();
        }
        $this->actingAs($ortu)->post('/akademik/reminder-krs/tes', ['nomor' => '0812'])->assertForbidden();
        $this->actingAs($ortu)->post('/akademik/reminder-krs/kirim', ['nim' => ['1001']])->assertForbidden();

        $this->actingAs($this->akademik())->get('/akademik/reminder-krs')->assertOk();
        $this->actingAs(User::factory()->create()->assignRole('admin'))->get('/akademik/log-wa')->assertOk();
    }

    // ------------------------------------------------------------ pratinjau

    public function test_layar_awal_meringkas_per_angkatan(): void
    {
        $this->fakeSiakad([$this->mhs('1', 'A', null, 2025), $this->mhs('2', 'B', 'DRAFT', 2025), $this->mhs('3', 'C', null, 2024)]);

        $this->actingAs($this->akademik())->get('/akademik/reminder-krs')->assertOk()
            ->assertSee('Angkatan 2025')->assertSee('Angkatan 2024')->assertSee('1 draf')
            ->assertSee('Ganjil 2026/2027')->assertSee('03 Oktober 2026')->assertSee('APPROVED');
    }

    public function test_daftar_menampilkan_mahasiswa_dari_api_dan_tidak_mencentang_yang_diajukan(): void
    {
        $this->fakeSiakad();

        $res = $this->actingAs($this->akademik())->get('/akademik/reminder-krs?angkatan=2025')->assertOk()
            ->assertSee('Ani')->assertSee('Budi')->assertSee('Cici')
            ->assertSee('Belum diisi')->assertSee('Draf')->assertSee('Diajukan')
            ->assertSee('menunggu persetujuan dosen wali');
        Http::assertSent(fn ($r) => str_contains($r->url(), '/krs/belum-isi') && str_contains($r->url(), 'angkatan=2025'));

        // Pilihan awal hanya 1001 dan 1002 (1003 berstatus DIAJUKAN).
        $this->assertStringContainsString("dipilih: JSON.parse('[\\u00221001\\u0022,\\u00221002\\u0022]')", html_entity_decode($res->getContent()));
    }

    public function test_pratinjau_pesan_memakai_isi_template_dari_meta_dengan_variabel_terisi(): void
    {
        $this->fakeSiakad();

        $this->actingAs($this->akademik())->get('/akademik/reminder-krs?angkatan=2025')
            ->assertSee('Ananda Ani (NIM: 1001) belum mengisi KRS Semester Ganjil TA 2026/2027, batas 03 Oktober 2026.', false);
    }

    public function test_daftar_kosong(): void
    {
        $this->fakeSiakad([]);

        $this->actingAs($this->akademik())->get('/akademik/reminder-krs?angkatan=2025')->assertOk()->assertSee('Semua sudah mengisi KRS');
    }

    public function test_siakad_mati_tidak_merusak_halaman(): void
    {
        Http::fake(['*' => Http::response(['message' => 'x'], 500)]);

        $this->actingAs($this->akademik())->get('/akademik/reminder-krs?angkatan=2025')->assertOk()->assertSee('tidak dapat dimuat');
    }

    // --------------------------------------------------------------- kontak

    public function test_kontak_memilih_ayah_lalu_ibu_dan_menyamarkan_nomor(): void
    {
        $this->fakeSiakad();

        $res = $this->actingAs($this->akademik())->getJson('/akademik/reminder-krs/kontak?nim[]=1001&nim[]=1002&nim[]=1003')->assertOk();
        $res->assertJsonPath('1001.orangtua.ada', true)->assertJsonPath('1001.orangtua.peran', 'ayah')->assertJsonPath('1001.orangtua.nomor', '0812-••••-890');
        $res->assertJsonPath('1002.orangtua.peran', 'ibu'); // ayah tanpa nomor -> ibu
        $res->assertJsonPath('1003.orangtua.ada', false);
        $this->assertStringNotContainsString('6281234567890', $res->getContent(), 'nomor orang tua tetap disamarkan');
    }

    public function test_kontak_menolak_nim_tidak_valid_dan_menandai_galat_siakad(): void
    {
        $this->actingAs($this->akademik())->getJson('/akademik/reminder-krs/kontak?nim[]=abc')->assertStatus(422);

        Http::fake(['*' => Http::response(['m' => 'x'], 500)]);
        $this->actingAs($this->akademik())->getJson('/akademik/reminder-krs/kontak?nim[]=1001')->assertOk()->assertJsonPath('1001.galat', true)->assertJsonPath('1001.mahasiswa.ada', false);
    }

    // ------------------------------------------------------------------ tes

    public function test_tes_kirim_memakai_template_dengan_parameter_bernama_dan_tercatat_di_log(): void
    {
        $this->fakeSiakad();

        $this->actingAs($this->akademik())->post('/akademik/reminder-krs/tes', ['nomor' => '0812-3456-7890', 'nama' => 'Mahasiswa Tes', 'nim' => '99999'])
            ->assertSessionHas('hasil_tes', fn ($h) => $h[0]['status'] === 'terkirim' && $h[0]['nomor'] === '6281234567890');

        Http::assertSent(function ($r) {
            if (! str_contains($r->url(), 'graph.facebook.com/v22.0/123/messages')) {
                return false;
            }
            $p = collect($r['template']['components'][0]['parameters'])->pluck('text', 'parameter_name')->all();

            return $r['to'] === '6281234567890' && $r['template']['name'] === 'reminder_telat_krs' && $r['template']['language']['code'] === 'id'
                && $p === ['nama' => 'Mahasiswa Tes', 'nim' => '99999', 'semester' => 'Ganjil', 'academic_year' => '2026/2027', 'date' => '03 Oktober 2026']
                && $r->hasHeader('Authorization', 'Bearer TOKEN-RAHASIA');
        });

        $log = DB::table('whatsapp_logs')->first();
        $this->assertSame(['tes_reminder_krs', 'terkirim', '6281234567890', 'tes'], [$log->jenis, $log->status, $log->no_hp, $log->tipe]);
    }

    public function test_tes_kirim_nomor_tidak_valid_dan_gagal_dicatat(): void
    {
        $this->fakeSiakad(ubah: ['graph.facebook.com/*/messages' => Http::response(['error' => ['message' => '(#131030) Nomor tidak terdaftar di daftar uji']], 400)]);

        $this->actingAs($this->akademik())->post('/akademik/reminder-krs/tes', ['nomor' => 'abc, 0812-3456-7890'])
            ->assertSessionHas('hasil_tes', fn ($h) => $h[0]['status'] === 'nomor_tidak_valid' && $h[1]['status'] === 'gagal' && str_contains($h[1]['keterangan'], '131030'));

        $this->assertSame(['gagal' => 1, 'nomor_tidak_valid' => 1], DB::table('whatsapp_logs')->selectRaw('status, count(*) n')->groupBy('status')->orderBy('status')->pluck('n', 'status')->all());
        $this->assertStringNotContainsString('TOKEN-RAHASIA', (string) DB::table('whatsapp_logs')->where('status', 'gagal')->value('respons'));
    }

    public function test_tes_kirim_tanpa_nama_dan_nim_memakai_isian_bawaan(): void
    {
        $this->fakeSiakad();

        $this->actingAs($this->akademik())->post('/akademik/reminder-krs/tes', ['nomor' => '0812-3456-7890'])->assertSessionHasNoErrors()
            ->assertSessionHas('hasil_tes', fn ($h) => $h[0]['status'] === 'terkirim');

        Http::assertSent(function ($r) {
            $p = collect($r['template']['components'][0]['parameters'] ?? [])->pluck('text', 'parameter_name')->all();

            return str_contains($r->url(), '/messages') && $p['nama'] === 'Nama Mahasiswa' && $p['nim'] === '12345678';
        });
    }

    public function test_tes_kirim_dibatasi_jumlah_dan_laju_serta_butuh_konfigurasi(): void
    {
        $this->fakeSiakad();
        $staf = $this->akademik();

        $this->actingAs($staf)->post('/akademik/reminder-krs/tes', ['nomor' => '0811111111111, 0822222222222, 0833333333333, 0844444444444'])
            ->assertSessionHasErrors(['nomor' => 'Tes kirim maksimal ke 3 nomor sekaligus.']);

        RateLimiter::clear('tes-krs:'.$staf->id);
        foreach (range(1, 10) as $i) {
            RateLimiter::hit('tes-krs:'.$staf->id, 3600);
        }
        $this->actingAs($staf)->post('/akademik/reminder-krs/tes', ['nomor' => '0812-3456-7890'])->assertSessionHasErrors('nomor');
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'graph.facebook.com'));

        RateLimiter::clear('tes-krs:'.$staf->id);
        config(['whatsapp.token' => null]);
        $this->actingAs($staf)->post('/akademik/reminder-krs/tes', ['nomor' => '0812-3456-7890'])
            ->assertSessionHasErrors(['nomor' => 'WhatsApp belum dikonfigurasi (WA_TOKEN dan WA_PHONE_NUMBER_ID di .env).']);
    }

    // ---------------------------------------------------------------- kirim

    private function kirim(array $ubah = [])
    {
        return $this->actingAs($this->akademik())->post('/akademik/reminder-krs/kirim', $ubah + ['angkatan' => 2025, 'nim' => ['1001', '1002'], 'ke_orangtua' => 1, 'kirim_wa' => 1]);
    }

    public function test_kirim_membuat_pesan_reminder_dan_mengantre_whatsapp(): void
    {
        $this->fakeSiakad();

        $this->kirim()->assertSessionHasNoErrors();

        $p = Pesan::firstOrFail();
        $this->assertSame(['reminder_krs', 'diproses', 2], [$p->jenis, $p->status, $p->jumlah_mahasiswa]);
        $this->assertSame('Pengingat pengisian KRS Ganjil 2026/2027', $p->judul);
        $this->assertSame(['semester' => 'Ganjil', 'academic_year' => '2026/2027', 'date' => '03 Oktober 2026'], $p->target['krs']);
        $this->assertStringContainsString('03 Oktober 2026', $p->isi);
        Queue::assertPushed(KirimWaPesanJob::class, fn ($j) => $j->pesanId === $p->id);
    }

    public function test_hanya_yang_masih_belum_krs_dikirimi_nim_lain_diabaikan(): void
    {
        // 1002 sudah mengisi (tidak ada lagi di daftar API); 7777 tidak pernah ada di daftar.
        $this->fakeSiakad([$this->mhs('1001', 'Ani')]);

        $this->kirim(['nim' => ['1001', '1002', '7777']])->assertSessionHasNoErrors()
            ->assertSessionHas('status', fn ($s) => str_contains($s, '2 mahasiswa dilewati karena sudah mengisi KRS'));

        $this->assertSame(['1001'], array_column(Pesan::firstOrFail()->daftar_nim, 'nim'));
        $this->assertSame(2, Pesan::firstOrFail()->target['dilewati_sudah_krs']);
    }

    public function test_tidak_ada_yang_dikirim_bila_semua_sudah_mengisi(): void
    {
        $this->fakeSiakad([]);

        $this->kirim()->assertSessionHasErrors('nim');
        $this->assertDatabaseCount('pesan', 0);
        Queue::assertNothingPushed();
    }

    public function test_kirim_tanpa_whatsapp_hanya_ke_inbox_dan_tanpa_antrean(): void
    {
        $this->fakeSiakad();
        $ortu = User::factory()->create()->assignRole('orangtua');
        $ortu->anak()->create(['nim' => '1001', 'nama_mahasiswa' => 'Ani']);

        $this->kirim(['kirim_wa' => 0])->assertSessionHasNoErrors();

        Queue::assertNothingPushed();
        $this->assertSame('selesai', Pesan::firstOrFail()->status);
        $this->actingAs($ortu)->get('/orangtua/pesan')->assertSee('Pengingat pengisian KRS');
    }

    public function test_validasi_kirim(): void
    {
        $this->fakeSiakad();

        $this->kirim(['nim' => []])->assertSessionHasErrors('nim');
        $this->kirim(['angkatan' => null])->assertSessionHasErrors('nim');
        $this->kirim(['nim' => ['abc']])->assertSessionHasErrors('nim.0');
        $this->kirim(['ke_orangtua' => 0])->assertSessionHasErrors('ke_orangtua');
        $this->kirim(['ke_orangtua' => 0, 'ke_mahasiswa' => 1, 'kirim_wa' => 0])->assertSessionHasErrors('kirim_wa');
        $this->assertDatabaseCount('pesan', 0);
    }

    public function test_kirim_whatsapp_ditolak_bila_belum_dikonfigurasi_atau_template_belum_disetujui(): void
    {
        $this->fakeSiakad(ubah: ['*/message_templates*' => Http::response(['data' => [['name' => 'reminder_telat_krs', 'status' => 'PAUSED', 'components' => []]]])]);

        config(['whatsapp.token' => null]);
        $this->kirim()->assertSessionHasErrors(['kirim_wa' => 'WhatsApp belum dikonfigurasi, jadi pengingat hanya dapat dikirim ke inbox portal.']);

        config(['whatsapp.token' => 'TOKEN-RAHASIA']);
        $this->kirim()->assertSessionHasErrors(['kirim_wa' => 'Template "reminder_telat_krs" berstatus PAUSED di Meta, belum boleh dipakai mengirim.']);
        $this->assertDatabaseCount('pesan', 0);
        Http::assertNotSent(fn ($r) => str_contains($r->url(), '/messages'));
    }

    // ---------------------------------------------------------- pekerjaan WA

    private function jalankan(Pesan $p): void
    {
        (new KirimWaPesanJob($p->id))->handle(app(SiakadClient::class), app(WhatsAppClient::class));
    }

    public function test_pekerjaan_reminder_mengirim_satu_kontak_per_mahasiswa_dengan_parameter_bernama(): void
    {
        $this->fakeSiakad();
        $this->kirim(['nim' => ['1001', '1002', '1003']])->assertSessionHasNoErrors();
        $pesan = Pesan::firstOrFail();

        $this->jalankan($pesan);

        $baris = fn ($nim) => $pesan->penerima()->where('nim', $nim)->where('tipe', '!=', 'akun')->get();
        $this->assertSame(['ayah', 'terkirim', '6281234567890'], [$baris('1001')[0]->tipe, $baris('1001')[0]->wa_status, $baris('1001')[0]->no_hp]);
        $this->assertCount(1, $baris('1001'), 'satu kontak saja walau ibu juga bernomor');
        $this->assertSame(['ibu', 'terkirim'], [$baris('1002')[0]->tipe, $baris('1002')[0]->wa_status], 'ayah tanpa nomor -> ibu');
        $this->assertSame(['ayah', 'tanpa_nomor'], [$baris('1003')[0]->tipe, $baris('1003')[0]->wa_status]);

        Http::assertSent(function ($r) {
            if (! str_contains($r->url(), '/messages')) {
                return false;
            }
            $p = collect($r['template']['components'][0]['parameters'])->pluck('text', 'parameter_name')->all();

            return $r['to'] === '6281234567890' && $p === ['nama' => 'Ani', 'nim' => '1001', 'semester' => 'Ganjil', 'academic_year' => '2026/2027', 'date' => '03 Oktober 2026'];
        });
        $this->assertSame('selesai', $pesan->fresh()->status);

        // Semua hasil, termasuk yang dilewati, ada di log dan terhubung ke pesannya.
        $this->assertSame(['tanpa_nomor' => 1, 'terkirim' => 2], DB::table('whatsapp_logs')->where('pesan_id', $pesan->id)->where('jenis', 'reminder_krs')
            ->selectRaw('status, count(*) n')->groupBy('status')->orderBy('status')->pluck('n', 'status')->all());
    }

    public function test_pekerjaan_reminder_juga_ke_nomor_mahasiswa_bila_diminta(): void
    {
        $this->fakeSiakad(ubah: ['*/mahasiswa/1001/orang-tua' => Http::response(['ayah' => ['nama' => 'A', 'noHp' => '081234567890'], 'noHpMahasiswa' => '085711112222'])]);
        $this->kirim(['nim' => ['1001'], 'ke_mahasiswa' => 1])->assertSessionHasNoErrors();
        $pesan = Pesan::firstOrFail();

        $this->jalankan($pesan);

        $this->assertEqualsCanonicalizing(['ayah', 'mahasiswa'], $pesan->penerima()->where('tipe', '!=', 'akun')->pluck('tipe')->all());
    }

    // ------------------------------------------------------------------ log

    public function test_halaman_log_menyaring_jenis_status_dan_pencarian(): void
    {
        $this->fakeSiakad();
        $this->kirim(['nim' => ['1001', '1002', '1003']]);
        $this->jalankan(Pesan::firstOrFail());
        $this->actingAs($this->akademik())->post('/akademik/reminder-krs/tes', ['nomor' => '0812-3456-7890']);

        $staf = $this->akademik();
        $this->actingAs($staf)->get('/akademik/log-wa')->assertOk()->assertSee('Pengingat KRS')->assertSee('Tes kirim')->assertSee('Tanpa nomor')->assertSee('Diterima WhatsApp');
        $this->actingAs($staf)->get('/akademik/log-wa?jenis=tes_reminder_krs')->assertSee('Tes kirim')->assertDontSee('Cahyo');
        $this->actingAs($staf)->get('/akademik/log-wa?status=tanpa_nomor')->assertSee('Eko')->assertDontSee('wamid');
        $this->actingAs($staf)->get('/akademik/log-wa?q=1002')->assertSee('1002')->assertDontSee('1003');
        $this->actingAs($staf)->get('/akademik/log-wa?pesan='.Pesan::firstOrFail()->uuid)->assertSee('Pesan #'.Pesan::firstOrFail()->id);
        $this->actingAs($staf)->get('/akademik/log-wa?status=ngawur')->assertSessionHasErrors('status');
        $this->actingAs($staf)->get('/akademik/log-wa?q=%25')->assertOk(); // karakter LIKE tidak menjadi wildcard
    }

    // ------------------------------------------- jenjang & hanya yang aktif

    private function dataJenjang(): array
    {
        $m = fn ($nim, $nama, $prodi, $angkatan, $status = 'AKTIF') => ['nim' => $nim, 'nama' => $nama, 'prodiId' => $prodi, 'prodiNama' => $prodi === 'pS2' ? 'Magister Ekonomi Syariah' : 'Manajemen Bisnis Syariah',
            'angkatan' => $angkatan, 'statusMahasiswa' => $status, 'sudahIsiKrs' => false];

        return [
            $m('1001', 'Sarjana Dua Lima', 'pS1', 2025), $m('1002', 'Sarjana Dua Empat', 'pS1', 2024),
            $m('1003', 'Magister Dua Lima', 'pS2', 25), $m('1004', 'Magister Dua Empat', 'pS2', 24),
            $m('1005', 'Sudah Lulus', 'pS1', 2025, 'LULUS'), $m('1006', 'Sedang Cuti', 'pS2', 25, 'CUTI'),
        ];
    }

    private function fakeJenjangKrs(): void
    {
        $this->fakeSiakad($this->dataJenjang(), self::TA, [
            '*/api/integrasi/prodi' => Http::response([
                ['id' => 'pS1', 'nama' => 'Manajemen Bisnis Syariah', 'jenjang' => 'S1'], ['id' => 'pS2', 'nama' => 'Magister Ekonomi Syariah', 'jenjang' => 'S2'],
            ]),
            // Seperti API sungguhan: angkatan dan prodiId disaring di sisi SIAKAD.
            '*/api/integrasi/krs/belum-isi*' => function ($req) {
                parse_str(parse_url($req->url(), PHP_URL_QUERY) ?? '', $q);

                return Http::response(collect($this->dataJenjang())
                    ->when(isset($q['angkatan']), fn ($c) => $c->where('angkatan', (int) $q['angkatan']))
                    ->when(isset($q['prodiId']), fn ($c) => $c->where('prodiId', $q['prodiId']))->values()->all());
            },
        ]);
    }

    public function test_ringkasan_dipisah_per_jenjang_dengan_penulisan_angkatan_masing_masing(): void
    {
        $this->fakeJenjangKrs();

        $this->actingAs($this->akademik())->get('/akademik/reminder-krs')->assertOk()
            ->assertSee('S1 · Angkatan 2025', false)->assertSee('S2 · Angkatan 25', false)->assertSee('S2 · Angkatan 24', false)
            ->assertDontSee('Sudah Lulus')->assertSee('Semua S2 (', false);
    }

    public function test_mahasiswa_tidak_aktif_tidak_pernah_ikut_walau_api_mengirimnya(): void
    {
        $this->fakeJenjangKrs();

        $this->actingAs($this->akademik())->get('/akademik/reminder-krs?angkatan=2025')->assertOk()
            ->assertSee('Sarjana Dua Lima')->assertDontSee('Sudah Lulus');

        $res = $this->actingAs($this->akademik())->get('/akademik/reminder-krs?jenjang=S2&angkatan=25')->assertOk()
            ->assertSee('Magister Dua Lima')->assertDontSee('Sedang Cuti')->assertDontSee('Sudah Lulus');
        $this->assertSame(1, substr_count($res->getContent(), 'name="nim[]"'), 'hanya 1 mahasiswa S2 angkatan 25 yang aktif');
    }

    public function test_s2_dengan_angkatan_2025_menampilkan_angkatan_25_dan_menyaring_jenjang(): void
    {
        $this->fakeJenjangKrs();

        $this->actingAs($this->akademik())->get('/akademik/reminder-krs?jenjang=S2&angkatan=2025')->assertOk()
            ->assertSee('Magister Dua Lima')->assertDontSee('Sarjana Dua Lima')->assertSee('S2 · Angkatan 25', false);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/krs/belum-isi') && str_contains($r->url(), 'angkatan=25') && ! str_contains($r->url(), 'angkatan=2025'));

        $this->actingAs($this->akademik())->get('/akademik/reminder-krs?jenjang=S1&angkatan=25')->assertOk()
            ->assertSee('Sarjana Dua Lima')->assertDontSee('Magister Dua Lima');
        Http::assertSent(fn ($r) => str_contains($r->url(), '/krs/belum-isi') && str_contains($r->url(), 'angkatan=2025'));
    }

    public function test_jenjang_saja_menampilkan_semua_angkatan_jenjang_itu(): void
    {
        $this->fakeJenjangKrs();

        $res = $this->actingAs($this->akademik())->get('/akademik/reminder-krs?jenjang=S2')->assertOk()
            ->assertSee('Magister Dua Lima')->assertSee('Magister Dua Empat')->assertDontSee('Sarjana');
        $this->assertSame(2, substr_count($res->getContent(), 'name="nim[]"'));
    }

    public function test_kirim_menghormati_jenjang_angkatan_dan_hanya_yang_aktif(): void
    {
        $this->fakeJenjangKrs();

        // 3 aktif S2 angkatan 25; 6 sedang cuti; 1 tidak dikirim form (diabaikan karena bukan S2).
        $this->actingAs($this->akademik())->post('/akademik/reminder-krs/kirim', [
            'jenjang' => 'S2', 'angkatan' => 2025, 'nim' => ['1003', '1006', '1001'], 'ke_orangtua' => 1, 'kirim_wa' => 0,
        ])->assertSessionHasNoErrors();

        $pesan = Pesan::firstOrFail();
        $this->assertSame(['1003'], array_column($pesan->daftar_nim, 'nim'));
        $this->assertSame('Belum mengisi KRS · S2 · Angkatan 25', $pesan->target['label']);
        $this->assertSame(2, $pesan->target['dilewati_sudah_krs']);
    }

    public function test_kirim_boleh_hanya_dengan_jenjang_dan_menolak_jenjang_palsu(): void
    {
        $this->fakeJenjangKrs();

        $this->actingAs($this->akademik())->post('/akademik/reminder-krs/kirim', ['jenjang' => 'S1', 'nim' => ['1001'], 'ke_orangtua' => 1, 'kirim_wa' => 0])->assertSessionHasNoErrors();
        $this->actingAs($this->akademik())->post('/akademik/reminder-krs/kirim', ['jenjang' => 'S9', 'nim' => ['1001'], 'ke_orangtua' => 1, 'kirim_wa' => 0])->assertSessionHasErrors('jenjang');
    }

    // ------------------------------------------------ nomor mahasiswa

    public function test_kontak_menampilkan_nomor_mahasiswa_utuh_dan_menandai_yang_tidak_valid(): void
    {
        $this->fakeSiakad(ubah: [
            '*/mahasiswa/1001/orang-tua' => Http::response(['ayah' => ['nama' => 'A', 'noHp' => '081234567890'], 'noHpMahasiswa' => '0857-1111-2222']),
            '*/mahasiswa/1002/orang-tua' => Http::response(['ayah' => ['nama' => 'B'], 'noHpMahasiswa' => 'abc']),
            '*/mahasiswa/1003/orang-tua' => Http::response(['ayah' => ['nama' => 'C']]),
        ]);

        $res = $this->actingAs($this->akademik())->getJson('/akademik/reminder-krs/kontak?nim[]=1001&nim[]=1002&nim[]=1003')->assertOk();
        $res->assertJsonPath('1001.mahasiswa.ada', true)->assertJsonPath('1001.mahasiswa.nomor', '0857-1111-2222');
        $res->assertJsonPath('1002.mahasiswa.ada', false)->assertJsonPath('1002.mahasiswa.mentah', 'abc');
        $res->assertJsonPath('1003.mahasiswa.ada', false)->assertJsonMissingPath('1003.mahasiswa.mentah');
    }

    public function test_halaman_daftar_menyediakan_kolom_nomor_mahasiswa_dan_mahasiswa_terpilih_bawaan(): void
    {
        $this->fakeSiakad();

        $html = html_entity_decode($this->actingAs($this->akademik())->get('/akademik/reminder-krs?angkatan=2025')->assertOk()
            ->assertSee('Nomor mahasiswa')->assertSee('Nomor orang tua')->assertSee('Kirim ke')->getContent());
        $this->assertStringContainsString('keMahasiswa: true, keOrangtua: false', $html, 'bawaan: kirim ke mahasiswa, bukan orang tua');
    }

    // ------------------------------------------ template per penerima

    private function pesanReminder(array $ubah = []): Pesan
    {
        return Pesan::create($ubah + [
            'pengirim_id' => $this->akademik()->id, 'jenis' => 'reminder_krs', 'judul' => 'J', 'isi' => 'x',
            'target' => ['krs' => ['semester' => 'Ganjil', 'academic_year' => '2026/2027', 'date' => '03 Oktober 2026']],
            'daftar_nim' => [['nim' => '1001', 'nama' => 'Ani']], 'jumlah_mahasiswa' => 1,
            'ke_orangtua' => false, 'ke_mahasiswa' => true, 'kirim_wa' => true, 'status' => 'diproses',
        ]);
    }

    private function fakeKontakLengkap(): void
    {
        $this->fakeSiakad(ubah: ['*/mahasiswa/1001/orang-tua' => Http::response([
            'ayah' => ['nama' => 'Budi', 'noHp' => '081234567890'], 'noHpMahasiswa' => '085711112222',
        ])]);
    }

    public function test_pesan_ke_mahasiswa_dikirim_ke_nomor_mahasiswa_dengan_parameter_bernama(): void
    {
        $this->fakeKontakLengkap();
        $pesan = $this->pesanReminder();

        $this->jalankan($pesan);

        $baris = $pesan->penerima()->where('tipe', '!=', 'akun')->get();
        $this->assertCount(1, $baris);
        $this->assertSame(['mahasiswa', '6285711112222', 'terkirim'], [$baris[0]->tipe, $baris[0]->no_hp, $baris[0]->wa_status]);
        Http::assertSent(function ($r) {
            $p = collect($r['template']['components'][0]['parameters'] ?? [])->pluck('text', 'parameter_name')->all();

            return str_contains($r->url(), '/messages') && $r['to'] === '6285711112222' && ($p['nama'] ?? null) === 'Ani' && ($p['nim'] ?? null) === '1001';
        });
    }

    public function test_tanpa_template_khusus_mahasiswa_memakai_template_orang_tua_sebagai_cadangan(): void
    {
        $this->fakeKontakLengkap();
        config(['whatsapp.template_reminder_krs_mahasiswa' => null]);

        $this->jalankan($this->pesanReminder());

        Http::assertSent(fn ($r) => str_contains($r->url(), '/messages') && $r['template']['name'] === 'reminder_telat_krs');
        $this->assertSame('reminder_telat_krs', DB::table('whatsapp_logs')->where('tipe', 'mahasiswa')->value('template'));
    }

    public function test_template_khusus_mahasiswa_dipakai_untuk_mahasiswa_dan_template_lama_untuk_orang_tua(): void
    {
        $this->fakeKontakLengkap();
        config(['whatsapp.template_reminder_krs_mahasiswa' => 'reminder_krs_untuk_mahasiswa']);
        $pesan = $this->pesanReminder(['ke_orangtua' => true]);

        $this->jalankan($pesan);

        $dipakai = DB::table('whatsapp_logs')->where('pesan_id', $pesan->id)->pluck('template', 'tipe')->all();
        $this->assertSame(['ayah' => 'reminder_telat_krs', 'mahasiswa' => 'reminder_krs_untuk_mahasiswa'], $dipakai);
    }

    public function test_tes_kirim_memakai_template_sesuai_penerima_yang_dipilih(): void
    {
        $this->fakeSiakad();
        config(['whatsapp.template_reminder_krs_mahasiswa' => 'reminder_krs_untuk_mahasiswa']);
        $staf = $this->akademik();

        $this->actingAs($staf)->post('/akademik/reminder-krs/tes', ['nomor' => '0812-3456-7890', 'untuk' => 'mahasiswa'])->assertSessionHasNoErrors();
        Http::assertSent(fn ($r) => str_contains($r->url(), '/messages') && $r['template']['name'] === 'reminder_krs_untuk_mahasiswa');

        $this->actingAs($staf)->post('/akademik/reminder-krs/tes', ['nomor' => '0812-3456-7890', 'untuk' => 'orangtua'])->assertSessionHasNoErrors();
        Http::assertSent(fn ($r) => str_contains($r->url(), '/messages') && $r['template']['name'] === 'reminder_telat_krs');

        $this->actingAs($staf)->post('/akademik/reminder-krs/tes', ['nomor' => '0812-3456-7890', 'untuk' => 'dosen'])->assertSessionHasErrors('untuk');
    }

    public function test_peringatan_template_mahasiswa_tampil_hanya_bila_belum_ada(): void
    {
        $this->fakeSiakad();
        config(['whatsapp.template_reminder_krs_mahasiswa' => null]);
        $this->actingAs($this->akademik())->get('/akademik/reminder-krs?angkatan=2025')->assertSee('Belum ada template khusus mahasiswa')->assertSee('Yth. {{nama}}', false);

        config(['whatsapp.template_reminder_krs_mahasiswa' => 'reminder_krs_untuk_mahasiswa']);
        $this->actingAs($this->akademik())->get('/akademik/reminder-krs?angkatan=2025')->assertDontSee('Belum ada template khusus mahasiswa')->assertSee('reminder_krs_untuk_mahasiswa');
    }

    public function test_kirim_ke_mahasiswa_menolak_bila_template_mahasiswa_belum_disetujui(): void
    {
        config(['whatsapp.template_reminder_krs_mahasiswa' => 'reminder_krs_untuk_mahasiswa']);
        $this->fakeSiakad(ubah: ['*/message_templates*' => Http::response(['data' => [
            ['name' => 'reminder_telat_krs', 'status' => 'APPROVED', 'components' => []],
            ['name' => 'reminder_krs_untuk_mahasiswa', 'status' => 'PENDING', 'components' => []],
        ]])]);

        $this->actingAs($this->akademik())->post('/akademik/reminder-krs/kirim', [
            'angkatan' => 2025, 'nim' => ['1001'], 'ke_mahasiswa' => 1, 'kirim_wa' => 1,
        ])->assertSessionHasErrors(['kirim_wa' => 'Template "reminder_krs_untuk_mahasiswa" berstatus PENDING di Meta, belum boleh dipakai mengirim.']);
        $this->assertDatabaseCount('pesan', 0);
    }

    // ------------------------------------------------ rentang 4 tahun terakhir

    private function dataRentang(): array
    {
        $m = fn ($nim, $nama, $prodi, $angkatan) => ['nim' => $nim, 'nama' => $nama, 'prodiId' => $prodi, 'prodiNama' => $prodi === 'pS2' ? 'Magister' : 'Sarjana',
            'angkatan' => $angkatan, 'statusMahasiswa' => 'AKTIF', 'sudahIsiKrs' => false];

        return [
            $m('2601', 'Baru S1', 'pS1', 2026), $m('2202', 'Batas S1', 'pS1', 2022), $m('2101', 'Lama S1', 'pS1', 2021), $m('1501', 'Sangat Lama S1', 'pS1', 2015),
            $m('2505', 'Baru S2', 'pS2', 25), $m('2207', 'Batas S2', 'pS2', 22), $m('2108', 'Lama S2', 'pS2', 21), $m('0709', 'Sangat Lama S2', 'pS2', 7),
        ];
    }

    private function fakeRentangKrs(): void
    {
        $this->fakeSiakad($this->dataRentang(), self::TA, [
            '*/api/integrasi/prodi' => Http::response([['id' => 'pS1', 'nama' => 'Sarjana', 'jenjang' => 'S1'], ['id' => 'pS2', 'nama' => 'Magister', 'jenjang' => 'S2']]),
            '*/api/integrasi/krs/belum-isi*' => function ($req) {
                parse_str(parse_url($req->url(), PHP_URL_QUERY) ?? '', $q);

                return Http::response(collect($this->dataRentang())->when(isset($q['angkatan']), fn ($c) => $c->where('angkatan', (int) $q['angkatan']))->values()->all());
            },
        ]);
    }

    public function test_daftar_bawaan_hanya_empat_tahun_terakhir_dan_menawarkan_semua_angkatan(): void
    {
        $this->fakeRentangKrs();

        $res = $this->actingAs($this->akademik())->get('/akademik/reminder-krs?jenjang=S1')->assertOk()
            ->assertSee('Baru S1')->assertSee('Batas S1')->assertDontSee('Lama S1')->assertDontSee('Sangat Lama S1')
            ->assertSee('2022–2026')->assertSee('2 mahasiswa dari angkatan lebih lama disembunyikan')->assertSee('Tampilkan semua angkatan');
        $this->assertSame(2, substr_count($res->getContent(), 'name="nim[]"'));

        $this->actingAs($this->akademik())->get('/akademik/reminder-krs?jenjang=S2')
            ->assertSee('Baru S2')->assertSee('Batas S2')->assertDontSee('Lama S2')->assertDontSee('Sangat Lama S2');
    }

    public function test_semua_angkatan_atau_angkatan_yang_disebut_menampilkan_yang_lama(): void
    {
        $this->fakeRentangKrs();

        $this->actingAs($this->akademik())->get('/akademik/reminder-krs?jenjang=S1&lama=1')->assertOk()
            ->assertSee('Lama S1')->assertSee('Sangat Lama S1')->assertSee('semua angkatan')->assertSee('Kembali ke angkatan 2022–2026');

        $this->actingAs($this->akademik())->get('/akademik/reminder-krs?jenjang=S1&angkatan=2015')->assertOk()
            ->assertSee('Sangat Lama S1')->assertDontSee('Baru S1')->assertSee('rentang 4 tahun terakhir tidak berlaku');
    }

    public function test_ringkasan_awal_menyembunyikan_angkatan_lama_dengan_tautan_semua(): void
    {
        $this->fakeRentangKrs();

        $this->actingAs($this->akademik())->get('/akademik/reminder-krs')->assertOk()
            ->assertSee('S1 · Angkatan 2026', false)->assertSee('S1 · Angkatan 2022', false)->assertSee('S2 · Angkatan 25', false)->assertSee('S2 · Angkatan 22', false)
            ->assertDontSee('Angkatan 2021')->assertDontSee('Angkatan 2015')->assertDontSee('S2 · Angkatan 21', false)->assertDontSee('S2 · Angkatan 7', false)
            ->assertSee('4 mahasiswa dari angkatan lebih lama disembunyikan');

        $this->actingAs($this->akademik())->get('/akademik/reminder-krs?lama=1')->assertSee('S1 · Angkatan 2015', false)->assertSee('S2 · Angkatan 7', false);
    }

    public function test_kirim_menghormati_rentang_angkatan_walau_nim_lama_dikirim_formulir(): void
    {
        $this->fakeRentangKrs();
        $staf = $this->akademik();

        $this->actingAs($staf)->post('/akademik/reminder-krs/kirim', ['jenjang' => 'S1', 'nim' => ['2601', '2101', '1501'], 'ke_orangtua' => 1, 'kirim_wa' => 0])->assertSessionHasNoErrors();
        $this->assertSame(['2601'], array_column(Pesan::firstOrFail()->daftar_nim, 'nim'), 'angkatan lama diabaikan tanpa "lama"');

        Pesan::query()->delete();
        $this->actingAs($staf)->post('/akademik/reminder-krs/kirim', ['jenjang' => 'S1', 'lama' => 1, 'nim' => ['2601', '2101', '1501'], 'ke_orangtua' => 1, 'kirim_wa' => 0])->assertSessionHasNoErrors();
        $this->assertEqualsCanonicalizing(['2601', '2101', '1501'], array_column(Pesan::firstOrFail()->daftar_nim, 'nim'));

        Pesan::query()->delete();
        $this->actingAs($staf)->post('/akademik/reminder-krs/kirim', ['jenjang' => 'S1', 'angkatan' => 2015, 'nim' => ['1501', '2601'], 'ke_orangtua' => 1, 'kirim_wa' => 0])->assertSessionHasNoErrors();
        $this->assertSame(['1501'], array_column(Pesan::firstOrFail()->daftar_nim, 'nim'), 'angkatan disebut langsung: hanya angkatan itu');
    }
}
