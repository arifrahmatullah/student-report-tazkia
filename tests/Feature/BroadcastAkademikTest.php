<?php

namespace Tests\Feature;

use App\Jobs\KirimWaPesanJob;
use App\Models\ParentMahasiswa;
use App\Models\Pesan;
use App\Models\User;
use App\Services\Siakad\SiakadClient;
use App\Services\WhatsApp\WhatsAppClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BroadcastAkademikTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'siakad.api_key' => 'kunci-uji', 'siakad.base_url' => 'https://siakad.test',
            'whatsapp.token' => null, 'whatsapp.phone_number_id' => null, 'whatsapp.template_broadcast' => null,
            'whatsapp.jeda_ms' => 0, 'whatsapp.nomor_uji' => '*',
        ]);
        foreach (['admin', 'akademik', 'orangtua'] as $r) {
            Role::findOrCreate($r);
        }
        Queue::fake(); // pekerjaan WhatsApp diuji langsung, bukan lewat antrean
    }

    private function akademik(): User
    {
        return User::factory()->create()->assignRole('akademik');
    }

    private function ortu(string ...$nim): User
    {
        $u = User::factory()->create()->assignRole('orangtua');
        foreach ($nim as $n) {
            $u->anak()->create(['nim' => $n, 'nama_mahasiswa' => "Mhs $n"]);
        }

        return $u;
    }

    private function aktifkanWa(): void
    {
        config(['whatsapp.token' => 'TOKEN-RAHASIA', 'whatsapp.phone_number_id' => '123', 'whatsapp.template_broadcast' => 'notifikasi_akademik']);
    }

    private function pesan(array $ubah = []): array
    {
        return $ubah + ['mode' => 'beasiswa', 'beasiswa_id' => 'b1', 'judul' => 'Batas KRS', 'isi' => "Baris 1\nBaris 2", 'ke_orangtua' => 1];
    }

    private function fakePenerima(): void
    {
        Http::fake([
            '*/beasiswa/b1/penerima*' => Http::response([
                ['nim' => '1001', 'nama' => 'Ani', 'statusMahasiswa' => 'AKTIF'], ['nim' => '1002', 'nama' => 'Budi', 'statusMahasiswa' => 'AKTIF'],
            ]),
            '*/api/integrasi/beasiswa' => Http::response([['id' => 'b1', 'nama' => 'BEASISWA UJI', 'status' => 'AKTIF', 'jumlahPenerima' => 2]]),
            '*/api/integrasi/prodi' => Http::response([]),
        ]);
    }

    // ------------------------------------------------------------------ akses

    public function test_hanya_staf_akademik_dan_admin_boleh_masuk(): void
    {
        $this->get('/akademik/pesan')->assertRedirect('/login');
        $this->actingAs($this->ortu())->get('/akademik/pesan')->assertForbidden();
        $this->actingAs($this->ortu())->post('/akademik/pesan', $this->pesan())->assertForbidden();
        $this->actingAs($this->ortu())->get('/akademik/pesan/estimasi?mode=nim&nim=1')->assertForbidden();

        Http::fake(['*' => Http::response([])]);
        $this->actingAs($this->akademik())->get('/akademik')->assertOk();
        $this->actingAs($this->akademik())->get('/akademik/pesan')->assertOk();
        $this->actingAs($this->akademik())->get('/akademik/pesan/create')->assertOk();
        $this->actingAs(User::factory()->create()->assignRole('admin'))->get('/akademik/pesan')->assertOk();
    }

    // ------------------------------------------------------- penerima & inbox

    public function test_beasiswa_membuat_baris_inbox_per_akun_dan_satu_baris_kosong_bila_belum_terhubung(): void
    {
        $this->fakePenerima();
        $ayah = $this->ortu('1001');
        $ibu = $this->ortu('1001');

        $this->actingAs($this->akademik())->post('/akademik/pesan', $this->pesan())->assertSessionHasNoErrors();

        $pesan = Pesan::firstOrFail();
        $this->assertSame('selesai', $pesan->status);
        $this->assertSame(2, $pesan->jumlah_mahasiswa);
        $this->assertSame('Ani', $pesan->daftar_nim[0]['nama']);

        $this->assertDatabaseCount('pesan_penerima', 3);
        $this->assertDatabaseHas('pesan_penerima', ['pesan_id' => $pesan->id, 'nim' => '1001', 'user_id' => $ayah->id]);
        $this->assertDatabaseHas('pesan_penerima', ['pesan_id' => $pesan->id, 'nim' => '1001', 'user_id' => $ibu->id]);
        $this->assertDatabaseHas('pesan_penerima', ['pesan_id' => $pesan->id, 'nim' => '1002', 'user_id' => null]);
        Queue::assertNothingPushed();

        // Orang tua yang terhubung menerima; akun lain tidak.
        $this->actingAs($ayah)->get('/orangtua/pesan')->assertOk()->assertSee('Batas KRS');
        $this->actingAs($this->ortu('9999'))->get('/orangtua/pesan')->assertOk()->assertDontSee('Batas KRS');
    }

    public function test_mode_angkatan_menelusuri_semua_halaman_dengan_filter_aktif(): void
    {
        Http::fake(function ($req) {
            parse_str(parse_url($req->url(), PHP_URL_QUERY) ?? '', $q);
            $hal = (int) ($q['page'] ?? 0);

            return Http::response([
                'content' => $hal === 0 ? [['nim' => '1', 'nama' => 'A', 'status' => 'AKTIF'], ['nim' => '2', 'nama' => 'B', 'status' => 'AKTIF']] : [['nim' => '3', 'nama' => 'C', 'status' => 'AKTIF']],
                'page' => $hal, 'totalPages' => 2,
            ]);
        });

        $this->actingAs($this->akademik())->post('/akademik/pesan', $this->pesan(['mode' => 'angkatan', 'angkatan' => 2025, 'prodi_id' => 'p1']))
            ->assertSessionHasNoErrors();

        $this->assertSame(3, Pesan::firstOrFail()->jumlah_mahasiswa);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'status=AKTIF') && str_contains($r->url(), 'angkatan=2025') && str_contains($r->url(), 'prodiId=p1'));
    }

    public function test_mode_angkatan_tanpa_prodi_dan_angkatan_ditolak(): void
    {
        Http::fake();
        $this->actingAs($this->akademik())->post('/akademik/pesan', $this->pesan(['mode' => 'angkatan']))->assertSessionHasErrors('angkatan');
        $this->assertDatabaseCount('pesan', 0);
        Http::assertNothingSent();
    }

    public function test_daftar_nim_melewati_nim_yang_tidak_ada(): void
    {
        Http::fake([
            '*/mahasiswa/1001' => Http::response(['nim' => '1001', 'nama' => 'Ani', 'status' => 'AKTIF']),
            '*/mahasiswa/9999' => Http::response(['code' => 'MAHASISWA_NOT_FOUND'], 404),
        ]);

        $this->actingAs($this->akademik())->post('/akademik/pesan', $this->pesan(['mode' => 'nim', 'nim' => "1001, 9999\n1001"]))
            ->assertSessionHasNoErrors();

        $pesan = Pesan::firstOrFail();
        $this->assertSame(1, $pesan->jumlah_mahasiswa);
        $this->assertSame(['9999'], $pesan->target['tidak_ditemukan']);
    }

    public function test_tidak_ada_penerima_berarti_tidak_ada_pesan(): void
    {
        Http::fake(['*/beasiswa/b1/penerima*' => Http::response([])]);

        $this->actingAs($this->akademik())->post('/akademik/pesan', $this->pesan())->assertSessionHasErrors('mode');
        $this->assertDatabaseCount('pesan', 0);
    }

    public function test_siakad_mati_tidak_membuat_pesan_setengah_jadi(): void
    {
        Http::fake(['*' => Http::response(['message' => 'down'], 500)]);

        $this->actingAs($this->akademik())->post('/akademik/pesan', $this->pesan())->assertSessionHasErrors('mode');
        $this->assertDatabaseCount('pesan', 0);
    }

    public function test_estimasi_menghitung_mahasiswa_dan_akun_terhubung(): void
    {
        $this->fakePenerima();
        $this->ortu('1001');

        $this->actingAs($this->akademik())->getJson('/akademik/pesan/estimasi?mode=beasiswa&beasiswa_id=b1')
            ->assertOk()->assertJson(['mahasiswa' => 2, 'terhubung' => 1, 'belum_terhubung' => 1, 'akun' => 1]);
    }

    // --------------------------------------------------------------- validasi

    public function test_pesan_ke_mahasiswa_wajib_lewat_whatsapp(): void
    {
        $this->fakePenerima();
        $this->aktifkanWa();

        $this->actingAs($this->akademik())->post('/akademik/pesan', $this->pesan(['ke_mahasiswa' => 1, 'ke_orangtua' => 0]))->assertSessionHasErrors('kirim_wa');
    }

    public function test_whatsapp_ditolak_bila_belum_dikonfigurasi(): void
    {
        $this->fakePenerima();

        $this->actingAs($this->akademik())->post('/akademik/pesan', $this->pesan(['kirim_wa' => 1]))
            ->assertSessionHasErrors(['kirim_wa' => 'WhatsApp belum dikonfigurasi, jadi pesan hanya dapat dikirim ke inbox portal.']);
        $this->assertDatabaseCount('pesan', 0);
    }

    public function test_harus_memilih_minimal_satu_jenis_penerima(): void
    {
        $this->fakePenerima();

        $this->actingAs($this->akademik())->post('/akademik/pesan', $this->pesan(['ke_orangtua' => 0]))->assertSessionHasErrors('ke_orangtua');
    }

    public function test_judul_dan_isi_wajib(): void
    {
        $this->fakePenerima();

        $this->actingAs($this->akademik())->post('/akademik/pesan', $this->pesan(['judul' => '', 'isi' => '']))->assertSessionHasErrors(['judul', 'isi']);
    }

    // ----------------------------------------------------------- WhatsApp job

    public function test_pesan_dengan_wa_mengantre_pekerjaan_dan_berstatus_diproses(): void
    {
        $this->fakePenerima();
        $this->aktifkanWa();

        $this->actingAs($this->akademik())->post('/akademik/pesan', $this->pesan(['kirim_wa' => 1]))->assertSessionHasNoErrors();

        $pesan = Pesan::firstOrFail();
        $this->assertSame('diproses', $pesan->status);
        Queue::assertPushed(KirimWaPesanJob::class, fn ($j) => $j->pesanId === $pesan->id);
    }

    private function jalankanWa(Pesan $pesan): void
    {
        (new KirimWaPesanJob($pesan->id))->handle(app(SiakadClient::class), app(WhatsAppClient::class));
    }

    private function pesanWa(array $daftar, bool $orangtua = true, bool $mahasiswa = false): Pesan
    {
        return Pesan::create([
            'pengirim_id' => $this->akademik()->id, 'judul' => 'Batas KRS', 'isi' => 'x', 'target' => [], 'daftar_nim' => $daftar,
            'jumlah_mahasiswa' => count($daftar), 'ke_orangtua' => $orangtua, 'ke_mahasiswa' => $mahasiswa, 'kirim_wa' => true, 'status' => 'diproses',
        ]);
    }

    public function test_wa_mengirim_template_mencatat_status_dan_tidak_menduplikasi_nomor(): void
    {
        $this->aktifkanWa();
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]]),
            '*/mahasiswa/1001/orang-tua' => Http::response(['ayah' => ['nama' => 'Budi', 'noHp' => '0812-3456-7890'], 'ibu' => ['nama' => 'Siti'], 'noHpMahasiswa' => '085711112222']),
            '*/mahasiswa/1002/orang-tua' => Http::response(['ayah' => ['nama' => 'Cahyo', 'noHp' => '081234567890'], 'ibu' => ['nama' => 'Dewi', 'noHp' => 'abc']]),
        ]);
        $pesan = $this->pesanWa([['nim' => '1001', 'nama' => 'Ani'], ['nim' => '1002', 'nama' => 'Budi']]);

        $this->jalankanWa($pesan);

        $status = fn ($nim, $tipe) => $pesan->penerima()->where('nim', $nim)->where('tipe', $tipe)->first();
        $this->assertSame('terkirim', $status('1001', 'ayah')->wa_status);
        $this->assertSame('6281234567890', $status('1001', 'ayah')->no_hp);
        $this->assertSame('tanpa_nomor', $status('1001', 'ibu')->wa_status);
        $this->assertSame('duplikat', $status('1002', 'ayah')->wa_status, 'nomor yang sama tidak dikirimi dua kali');
        $this->assertSame('nomor_tidak_valid', $status('1002', 'ibu')->wa_status);
        $this->assertNull($pesan->penerima()->where('nim', '1001')->where('tipe', 'mahasiswa')->first(), 'tidak ke mahasiswa bila tidak diminta');

        Http::assertSentCount(3); // 2 panggilan kontak + 1 panggilan WhatsApp
        Http::assertSent(fn ($r) => str_contains($r->url(), 'graph.facebook.com/v22.0/123/messages')
            && $r['to'] === '6281234567890' && $r['template']['name'] === 'notifikasi_akademik'
            && $r['template']['components'][0]['parameters'][0]['text'] === 'Bapak Budi'
            && $r['template']['components'][0]['parameters'][1]['text'] === 'Batas KRS'
            && $r->hasHeader('Authorization', 'Bearer TOKEN-RAHASIA'));

        $this->assertSame('selesai', $pesan->fresh()->status);
        // Setiap hasil tercatat di log, termasuk yang dilewati: 1 terkirim + tanpa nomor + duplikat + nomor tidak valid.
        $this->assertSame(['duplikat' => 1, 'nomor_tidak_valid' => 1, 'tanpa_nomor' => 1, 'terkirim' => 1], DB::table('whatsapp_logs')->where('pesan_id', $pesan->id)->selectRaw('status, count(*) n')->groupBy('status')->orderBy('status')->pluck('n', 'status')->all());
    }

    public function test_wa_ke_mahasiswa_memakai_nomor_mahasiswa(): void
    {
        $this->aktifkanWa();
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'w']]]),
            '*/orang-tua' => Http::response(['ayah' => ['nama' => 'Budi', 'noHp' => '081234567890'], 'noHpMahasiswa' => '085711112222']),
        ]);
        $pesan = $this->pesanWa([['nim' => '1001', 'nama' => 'Ani']], orangtua: false, mahasiswa: true);

        $this->jalankanWa($pesan);

        $this->assertSame(1, $pesan->penerima()->count());
        $baris = $pesan->penerima()->first();
        $this->assertSame(['mahasiswa', '6285711112222', 'terkirim'], [$baris->tipe, $baris->no_hp, $baris->wa_status]);
    }

    public function test_wa_gagal_dicatat_tanpa_membocorkan_token(): void
    {
        $this->aktifkanWa();
        Http::fake([
            'graph.facebook.com/*' => Http::response(['error' => ['message' => '(#132001) Template tidak ditemukan']], 400),
            '*/orang-tua' => Http::response(['ayah' => ['nama' => 'Budi', 'noHp' => '081234567890']]),
        ]);
        $pesan = $this->pesanWa([['nim' => '1001', 'nama' => 'Ani']]);

        $this->jalankanWa($pesan);

        $baris = $pesan->penerima()->first();
        $this->assertSame('gagal', $baris->wa_status);
        $this->assertStringContainsString('Template tidak ditemukan', $baris->wa_error);
        $this->assertStringNotContainsString('TOKEN-RAHASIA', (string) $baris->wa_error);
        $this->assertStringNotContainsString('TOKEN-RAHASIA', (string) \DB::table('whatsapp_logs')->value('respons'));
        $this->assertSame('selesai', $pesan->fresh()->status, 'galat satu kontak tidak menghentikan yang lain');
    }

    public function test_kontak_yang_gagal_dimuat_dicatat_dan_tidak_menghentikan_pekerjaan(): void
    {
        $this->aktifkanWa();
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'w']]]),
            '*/mahasiswa/1001/orang-tua' => Http::response(['message' => 'down'], 500),
            '*/mahasiswa/1002/orang-tua' => Http::response(['ayah' => ['nama' => 'Cahyo', 'noHp' => '081234567890']]),
        ]);
        $pesan = $this->pesanWa([['nim' => '1001', 'nama' => 'Ani'], ['nim' => '1002', 'nama' => 'Budi']]);

        $this->jalankanWa($pesan);

        $this->assertSame('gagal', $pesan->penerima()->where('nim', '1001')->first()->wa_status);
        $this->assertSame('terkirim', $pesan->penerima()->where('nim', '1002')->first()->wa_status);
    }

    public function test_menjalankan_pekerjaan_dua_kali_tidak_mengirim_dua_kali(): void
    {
        $this->aktifkanWa();
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'w']]]),
            '*/orang-tua' => Http::response(['ayah' => ['nama' => 'Budi', 'noHp' => '081234567890']]),
        ]);
        $pesan = $this->pesanWa([['nim' => '1001', 'nama' => 'Ani']]);

        $this->jalankanWa($pesan);
        $this->jalankanWa($pesan);

        Http::assertSentCount(2); // satu panggilan kontak + satu WhatsApp
        $this->assertSame(1, $pesan->penerima()->count());
    }

    // ------------------------------------------------------------ tampilan

    public function test_detail_pesan_menampilkan_jangkauan_dan_yang_belum_terhubung(): void
    {
        $this->fakePenerima();
        $ortu = $this->ortu('1001');
        $staf = $this->akademik();
        $this->actingAs($staf)->post('/akademik/pesan', $this->pesan());
        $pesan = Pesan::firstOrFail();

        $this->actingAs($ortu)->get('/orangtua/pesan/'.$ortu->pesanDiterima()->firstOrFail()->id); // dibaca

        $this->actingAs($staf)->get("/akademik/pesan/{$pesan->uuid}")->assertOk()
            ->assertSee('Batas KRS')->assertSee('Ani')->assertSee('Budi')
            ->assertSee('1/1 dibaca')->assertSee('Belum terhubung');
        $this->actingAs($staf)->get('/akademik/pesan')->assertOk()->assertSee('Batas KRS')->assertSee('100% terbaca');
    }

    public function test_form_tulis_pesan_menampilkan_pilihan_dan_status_whatsapp(): void
    {
        $this->fakePenerima();

        $this->actingAs($this->akademik())->get('/akademik/pesan/create')->assertOk()
            ->assertSee('BEASISWA UJI')->assertSee('WhatsApp belum dikonfigurasi');
    }

    // ------------------------------------------------- hanya mahasiswa aktif

    public function test_penerima_beasiswa_yang_tidak_aktif_dilewati_dan_dilaporkan(): void
    {
        Http::fake(['*/beasiswa/b1/penerima*' => Http::response([
            ['nim' => '1001', 'nama' => 'Ani', 'statusMahasiswa' => 'AKTIF'],
            ['nim' => '1002', 'nama' => 'Budi', 'statusMahasiswa' => 'LULUS'],
            ['nim' => '1003', 'nama' => 'Cici', 'statusMahasiswa' => 'CUTI'],
            ['nim' => '1004', 'nama' => 'Dedi'],
        ])]);

        $this->actingAs($this->akademik())->getJson('/akademik/pesan/estimasi?mode=beasiswa&beasiswa_id=b1')
            ->assertOk()->assertJson(['mahasiswa' => 1, 'tidak_aktif' => 3]);

        $this->actingAs($this->akademik())->post('/akademik/pesan', $this->pesan())->assertSessionHasNoErrors();
        $pesan = Pesan::firstOrFail();
        $this->assertSame(['1001'], array_column($pesan->daftar_nim, 'nim'));
        $this->assertEqualsCanonicalizing(['1002', '1003', '1004'], $pesan->target['tidak_aktif']);
    }

    public function test_nim_manual_yang_tidak_aktif_dilewati(): void
    {
        Http::fake([
            '*/mahasiswa/1001' => Http::response(['nim' => '1001', 'nama' => 'Ani', 'status' => 'AKTIF']),
            '*/mahasiswa/1002' => Http::response(['nim' => '1002', 'nama' => 'Budi', 'status' => 'LULUS']),
        ]);

        $this->actingAs($this->akademik())->post('/akademik/pesan', $this->pesan(['mode' => 'nim', 'nim' => '1001 1002']))->assertSessionHasNoErrors();

        $pesan = Pesan::firstOrFail();
        $this->assertSame(['1001'], array_column($pesan->daftar_nim, 'nim'));
        $this->assertSame(['1002'], $pesan->target['tidak_aktif']);
    }

    // ------------------------------------------------------------ jenjang

    private function fakeJenjang(): void
    {
        Http::fake(function ($req) {
            $url = $req->url();
            if (str_contains($url, '/api/integrasi/prodi')) {
                return Http::response([['id' => 'pS1', 'nama' => 'Manajemen Bisnis Syariah', 'jenjang' => 'S1'], ['id' => 'pS2', 'nama' => 'Magister Ekonomi Syariah', 'jenjang' => 'S2']]);
            }
            if (str_contains($url, '/api/integrasi/mahasiswa')) {
                return Http::response(['content' => [
                    ['nim' => '2501', 'nama' => 'S1 Aktif', 'prodiId' => 'pS1', 'status' => 'AKTIF', 'angkatan' => 2025],
                    ['nim' => '2502', 'nama' => 'S2 Aktif', 'prodiId' => 'pS2', 'status' => 'AKTIF', 'angkatan' => 25],
                    ['nim' => '2503', 'nama' => 'S2 Lulus', 'prodiId' => 'pS2', 'status' => 'LULUS', 'angkatan' => 25],
                ], 'page' => 0, 'totalPages' => 1]);
            }

            return Http::response([]);
        });
    }

    public function test_s2_dengan_angkatan_empat_digit_dibaca_sebagai_dua_digit_dan_hanya_s2_yang_dituju(): void
    {
        $this->fakeJenjang();

        $this->actingAs($this->akademik())->post('/akademik/pesan', $this->pesan(['mode' => 'angkatan', 'jenjang' => 'S2', 'angkatan' => 2025]))->assertSessionHasNoErrors();

        Http::assertSent(fn ($r) => str_contains($r->url(), '/mahasiswa') && str_contains($r->url(), 'angkatan=25') && ! str_contains($r->url(), 'angkatan=2025'));
        $pesan = Pesan::firstOrFail();
        $this->assertSame(['2502'], array_column($pesan->daftar_nim, 'nim'), 'S1 dan yang lulus tidak ikut');
        $this->assertSame(1, count($pesan->target['tidak_aktif']));
        $this->assertSame('Mahasiswa aktif · S2 · Angkatan 25', $pesan->target['label']);
    }

    public function test_jenjang_saja_sudah_cukup_sebagai_lingkup_dan_nilai_tidak_valid_ditolak(): void
    {
        $this->fakeJenjang();

        $this->actingAs($this->akademik())->post('/akademik/pesan', $this->pesan(['mode' => 'angkatan', 'jenjang' => 'S1']))->assertSessionHasNoErrors();
        $this->assertSame(['2501'], array_column(Pesan::firstOrFail()->daftar_nim, 'nim'));

        $this->actingAs($this->akademik())->post('/akademik/pesan', $this->pesan(['mode' => 'angkatan', 'jenjang' => 'S9']))->assertSessionHasErrors('jenjang');
    }

    // ------------------------------------------------ rentang 4 tahun terakhir

    private function fakeRentang(): void
    {
        Http::fake(function ($req) {
            $url = $req->url();
            if (str_contains($url, '/tahun-akademik/aktif')) {
                return Http::response(['id' => 't', 'nama' => 'Ganjil 2026/2027', 'tahun' => '2026/2027', 'semester' => 'GASAL']);
            }
            if (str_contains($url, '/api/integrasi/prodi')) {
                return Http::response([['id' => 'pS1', 'nama' => 'MBS', 'jenjang' => 'S1']]);
            }
            if (str_contains($url, '/api/integrasi/mahasiswa')) {
                parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $q);
                $semua = [
                    ['nim' => '2601', 'nama' => 'Baru', 'prodiId' => 'pS1', 'status' => 'AKTIF', 'angkatan' => 2026],
                    ['nim' => '2202', 'nama' => 'Batas', 'prodiId' => 'pS1', 'status' => 'AKTIF', 'angkatan' => 2022],
                    ['nim' => '2101', 'nama' => 'Lama', 'prodiId' => 'pS1', 'status' => 'AKTIF', 'angkatan' => 2021],
                    ['nim' => '1501', 'nama' => 'SangatLama', 'prodiId' => 'pS1', 'status' => 'AKTIF', 'angkatan' => 2015],
                ];

                return Http::response(['content' => collect($semua)->when(isset($q['angkatan']), fn ($c) => $c->where('angkatan', (int) $q['angkatan']))->values()->all(), 'page' => 0, 'totalPages' => 1]);
            }

            return Http::response([]);
        });
    }

    public function test_mode_angkatan_tanpa_angkatan_hanya_empat_tahun_terakhir_dan_melaporkan_sisanya(): void
    {
        $this->fakeRentang();

        $this->actingAs($this->akademik())->getJson('/akademik/pesan/estimasi?mode=angkatan&jenjang=S1')
            ->assertOk()->assertJson(['mahasiswa' => 2, 'di_luar_rentang' => 2, 'rentang' => '2022–2026']);

        $this->actingAs($this->akademik())->post('/akademik/pesan', $this->pesan(['mode' => 'angkatan', 'jenjang' => 'S1']))->assertSessionHasNoErrors();
        $pesan = Pesan::firstOrFail();
        $this->assertEqualsCanonicalizing(['2601', '2202'], array_column($pesan->daftar_nim, 'nim'));
        $this->assertSame(2, $pesan->target['di_luar_rentang']);
    }

    public function test_menyebut_angkatan_berarti_memilih_langsung_walau_lama(): void
    {
        $this->fakeRentang();

        $this->actingAs($this->akademik())->post('/akademik/pesan', $this->pesan(['mode' => 'angkatan', 'jenjang' => 'S1', 'angkatan' => 2015]))->assertSessionHasNoErrors();

        $pesan = Pesan::firstOrFail();
        $this->assertSame(['1501'], array_column($pesan->daftar_nim, 'nim'));
        $this->assertSame(0, $pesan->target['di_luar_rentang']);
    }
}
