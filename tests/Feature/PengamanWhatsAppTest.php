<?php

namespace Tests\Feature;

use App\Jobs\KirimWaPesanJob;
use App\Models\Pesan;
use App\Models\User;
use App\Services\Siakad\SiakadClient;
use App\Services\WhatsApp\PengirimanDiblokir;
use App\Services\WhatsApp\WhatsAppClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Pengaman: di luar production, WhatsApp hanya ke nomor di WA_NOMOR_UJI. Mencegah data uji menyentuh orang tua sungguhan. */
class PengamanWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'siakad.api_key' => 'k', 'siakad.base_url' => 'https://siakad.test',
            'whatsapp.token' => 'TOKEN-RAHASIA', 'whatsapp.phone_number_id' => '123', 'whatsapp.waba_id' => '999',
            'whatsapp.template_reminder_krs' => 'reminder_telat_krs', 'whatsapp.jeda_ms' => 0, 'whatsapp.nomor_uji' => null,
        ]);
        foreach (['admin', 'akademik', 'orangtua'] as $r) {
            Role::findOrCreate($r);
        }
        Queue::fake();
    }

    private function wa(): WhatsAppClient
    {
        return app(WhatsAppClient::class);
    }

    private function fakeMeta(): void
    {
        Http::fake([
            'graph.facebook.com/*/messages' => Http::response(['messages' => [['id' => 'w']]]),
            '*/message_templates*' => Http::response(['data' => [['name' => 'reminder_telat_krs', 'status' => 'APPROVED', 'components' => []]]]),
            '*/api/integrasi/tahun-akademik/aktif' => Http::response(['id' => 't', 'nama' => 'Ganjil', 'tahun' => '2026/2027', 'semester' => 'GASAL', 'tanggalSelesaiKrs' => '2026-10-03']),
            '*/api/integrasi/prodi' => Http::response([]),
            '*/api/integrasi/krs/belum-isi*' => Http::response([['nim' => '1001', 'nama' => 'Ani', 'angkatan' => 2025, 'prodiNama' => 'MBS', 'statusMahasiswa' => 'AKTIF']]),
            '*/mahasiswa/1001/orang-tua' => Http::response(['ayah' => ['nama' => 'Budi', 'noHp' => '081234567890']]),
        ]);
    }

    public function test_di_luar_production_tanpa_daftar_tidak_ada_yang_terkirim(): void
    {
        $this->fakeMeta();

        $this->assertFalse($this->wa()->boleh('081234567890'));
        $this->expectException(PengirimanDiblokir::class);
        try {
            $this->wa()->kirimTemplate('6281234567890', 'reminder_telat_krs', ['nama' => 'A']);
        } finally {
            Http::assertNotSent(fn ($r) => str_contains($r->url(), '/messages'));
        }
    }

    public function test_hanya_nomor_di_daftar_yang_diizinkan_apa_pun_format_penulisannya(): void
    {
        config(['whatsapp.nomor_uji' => '0812-3456-7890, +62 857 1111 2222']);

        $this->assertTrue($this->wa()->boleh('6281234567890'));
        $this->assertTrue($this->wa()->boleh('0812 3456 7890'));
        $this->assertTrue($this->wa()->boleh('085711112222'));
        $this->assertFalse($this->wa()->boleh('081299998888'));
        $this->assertFalse($this->wa()->boleh('abc'));
        $this->assertSame(2, count($this->wa()->modeUji()['nomor']));
    }

    public function test_bintang_mengizinkan_semua_dan_production_tidak_dibatasi(): void
    {
        config(['whatsapp.nomor_uji' => '*']);
        $this->assertTrue($this->wa()->boleh('081299998888'));

        config(['whatsapp.nomor_uji' => null]);
        $this->assertFalse($this->wa()->boleh('081299998888'));

        $this->app['env'] = 'production';
        $this->assertTrue($this->wa()->boleh('081299998888'));
        $this->assertFalse($this->wa()->modeUji()['terbatas']);
    }

    public function test_pekerjaan_mencatat_diblokir_dan_tidak_menghubungi_whatsapp(): void
    {
        $this->fakeMeta();
        $pesan = Pesan::create([
            'pengirim_id' => User::factory()->create()->id, 'jenis' => 'reminder_krs', 'judul' => 'J', 'isi' => 'x',
            'target' => ['krs' => ['semester' => 'Ganjil', 'academic_year' => '2026/2027', 'date' => '03 Oktober 2026']],
            'daftar_nim' => [['nim' => '1001', 'nama' => 'Ani']], 'jumlah_mahasiswa' => 1, 'ke_orangtua' => true, 'kirim_wa' => true, 'status' => 'diproses',
        ]);

        (new KirimWaPesanJob($pesan->id))->handle(app(SiakadClient::class), $this->wa());

        $baris = $pesan->penerima()->where('tipe', 'ayah')->firstOrFail();
        $this->assertSame('diblokir', $baris->wa_status);
        $this->assertStringContainsString('WA_NOMOR_UJI', $baris->wa_error);
        $this->assertSame('diblokir', DB::table('whatsapp_logs')->value('status'));
        Http::assertNotSent(fn ($r) => str_contains($r->url(), '/messages'));
        $this->assertSame('selesai', $pesan->fresh()->status);
    }

    public function test_pekerjaan_hanya_mengirim_ke_nomor_yang_diizinkan(): void
    {
        $this->fakeMeta();
        config(['whatsapp.nomor_uji' => '081234567890']);
        $pesan = Pesan::create([
            'pengirim_id' => User::factory()->create()->id, 'jenis' => 'reminder_krs', 'judul' => 'J', 'isi' => 'x',
            'target' => ['krs' => ['semester' => 'Ganjil', 'academic_year' => '2026/2027', 'date' => 'x']],
            'daftar_nim' => [['nim' => '1001', 'nama' => 'Ani']], 'jumlah_mahasiswa' => 1, 'ke_orangtua' => true, 'kirim_wa' => true, 'status' => 'diproses',
        ]);

        (new KirimWaPesanJob($pesan->id))->handle(app(SiakadClient::class), $this->wa());

        $this->assertSame('terkirim', $pesan->penerima()->where('tipe', 'ayah')->value('wa_status'));
        Http::assertSent(fn ($r) => str_contains($r->url(), '/messages') && $r['to'] === '6281234567890');
    }

    public function test_formulir_menolak_kirim_whatsapp_saat_daftar_kosong_dan_tidak_membuat_pesan(): void
    {
        $this->fakeMeta();
        $staf = User::factory()->create()->assignRole('akademik');

        $this->actingAs($staf)->post('/akademik/reminder-krs/kirim', ['angkatan' => 2025, 'nim' => ['1001'], 'ke_orangtua' => 1, 'kirim_wa' => 1])
            ->assertSessionHasErrors('kirim_wa');
        $this->assertDatabaseCount('pesan', 0);
        Http::assertNotSent(fn ($r) => str_contains($r->url(), '/messages'));

        // Hanya inbox portal tetap bisa.
        $this->actingAs($staf)->post('/akademik/reminder-krs/kirim', ['angkatan' => 2025, 'nim' => ['1001'], 'ke_orangtua' => 1, 'kirim_wa' => 0])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseCount('pesan', 1);
    }

    public function test_tes_kirim_diblokir_untuk_nomor_di_luar_daftar_dan_lolos_untuk_nomor_sendiri(): void
    {
        $this->fakeMeta();
        config(['whatsapp.nomor_uji' => '081234567890']);
        $staf = User::factory()->create()->assignRole('akademik');

        $this->actingAs($staf)->post('/akademik/reminder-krs/tes', ['nomor' => '081234567890, 081299998888'])
            ->assertSessionHas('hasil_tes', fn ($h) => $h[0]['status'] === 'terkirim' && $h[1]['status'] === 'diblokir' && str_contains($h[1]['keterangan'], 'WA_NOMOR_UJI'));

        Http::assertSentCount(2); // tahun akademik aktif + satu pesan; nomor kedua tidak pernah sampai ke Meta
        $this->assertSame(1, collect(Http::recorded())->filter(fn ($p) => str_contains($p[0]->url(), '/messages'))->count());
        $this->assertSame(['diblokir' => 1, 'terkirim' => 1], DB::table('whatsapp_logs')->selectRaw('status, count(*) n')->groupBy('status')->orderBy('status')->pluck('n', 'status')->all());
    }

    public function test_halaman_menampilkan_banner_mode_uji(): void
    {
        $this->fakeMeta();
        $staf = User::factory()->create()->assignRole('akademik');

        $this->actingAs($staf)->get('/akademik/reminder-krs?angkatan=2025')->assertSee('Mode uji')->assertSee('tidak ada WhatsApp yang akan terkirim');

        config(['whatsapp.nomor_uji' => '081234567890']);
        $this->actingAs($staf)->get('/akademik/reminder-krs?angkatan=2025')->assertSee('hanya dikirim ke')->assertDontSee('tidak ada WhatsApp yang akan terkirim');

        config(['whatsapp.nomor_uji' => '*']);
        $this->actingAs($staf)->get('/akademik/reminder-krs?angkatan=2025')->assertDontSee('Mode uji');
    }
}
