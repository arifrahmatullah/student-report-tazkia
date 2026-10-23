<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialUser;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([User::ROLE_ADMIN, User::ROLE_AKADEMIK, User::ROLE_ORANGTUA] as $r) {
            Role::findOrCreate($r);
        }
    }

    private function aktifkan(): void
    {
        config(['services.google.client_id' => 'id-uji', 'services.google.client_secret' => 'rahasia-uji']);
    }

    private function googleMengembalikan(array $ubah = []): void
    {
        $raw = array_merge(['sub' => 'g-123', 'email' => 'ibu.siti@gmail.com', 'name' => 'Siti Aminah', 'email_verified' => true], $ubah);
        $akun = (new SocialUser)->setRaw($raw)->map(['id' => $raw['sub'], 'name' => $raw['name'], 'email' => $raw['email']]);

        $provider = \Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($akun);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_tombol_dan_rute_google_tersembunyi_bila_belum_dikonfigurasi(): void
    {
        config(['services.google.client_id' => null, 'services.google.client_secret' => null]);

        $this->get('/login')->assertOk()->assertDontSee('Masuk dengan Google');
        $this->get('/auth/google')->assertNotFound();
        $this->get('/auth/google/callback')->assertNotFound();
    }

    public function test_tombol_tampil_di_login_dan_daftar_bila_dikonfigurasi(): void
    {
        $this->aktifkan();

        $this->get('/login')->assertOk()->assertSee('Masuk dengan Google');
        $this->get('/register')->assertOk()->assertSee('Masuk dengan Google');
    }

    public function test_tombol_mengarah_ke_halaman_masuk_google(): void
    {
        $this->aktifkan();

        $res = $this->get('/auth/google');
        $res->assertRedirectContains('accounts.google.com');
        $this->assertStringContainsString('client_id=id-uji', $res->headers->get('Location'));
        $this->assertStringContainsString(urlencode('/auth/google/callback'), $res->headers->get('Location'));
    }

    public function test_orang_tua_baru_dibuatkan_akun_dengan_peran_orangtua(): void
    {
        $this->aktifkan();
        $this->googleMengembalikan();

        $this->get('/auth/google/callback')->assertRedirect(route('dashboard', absolute: false));

        $u = User::where('email', 'ibu.siti@gmail.com')->firstOrFail();
        $this->assertAuthenticatedAs($u);
        $this->assertTrue($u->hasRole(User::ROLE_ORANGTUA));
        $this->assertFalse($u->hasAnyRole([User::ROLE_ADMIN, User::ROLE_AKADEMIK]));
        $this->assertSame('g-123', $u->google_id);
        $this->assertNotNull($u->email_verified_at);
        $this->assertSame('Siti Aminah', $u->name);
    }

    public function test_akun_staf_dengan_email_sama_ditautkan_dan_tetap_berperan_staf(): void
    {
        $this->aktifkan();
        $staf = User::factory()->create(['email' => 'staf@tazkia.ac.id', 'email_verified_at' => null]);
        $staf->assignRole(User::ROLE_AKADEMIK);
        $this->googleMengembalikan(['sub' => 'g-staf', 'email' => 'staf@tazkia.ac.id']);

        $this->get('/auth/google/callback')->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($staf);
        $this->assertSame('g-staf', $staf->fresh()->google_id);
        $this->assertTrue($staf->fresh()->hasRole(User::ROLE_AKADEMIK));
        $this->assertSame(1, User::where('email', 'staf@tazkia.ac.id')->count());
    }

    public function test_akun_formulir_belum_terverifikasi_tidak_ditautkan_otomatis(): void
    {
        // Pencegahan pembajakan: penyerang bisa lebih dulu mendaftar dengan email korban.
        $this->aktifkan();
        $lama = User::factory()->create(['email' => 'korban@gmail.com', 'email_verified_at' => null]);
        $lama->assignRole(User::ROLE_ORANGTUA);
        $this->googleMengembalikan(['sub' => 'g-korban', 'email' => 'korban@gmail.com']);

        $this->get('/auth/google/callback')->assertRedirect(route('login'))->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertNull($lama->fresh()->google_id);
    }

    public function test_orang_tua_hasil_impor_bisa_masuk_dengan_google_dan_tertaut(): void
    {
        // Akun yang dibuat admin (impor) memakai email terverifikasi dan kata sandi acak yang tidak diketahui siapa pun.
        $this->aktifkan();
        $u = User::factory()->create(['email' => 'ayah.budi@gmail.com', 'email_verified_at' => now()]);
        $u->assignRole(User::ROLE_ORANGTUA);
        $this->googleMengembalikan(['sub' => 'g-ayah', 'email' => 'Ayah.Budi@Gmail.com']);

        $this->get('/auth/google/callback')->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($u);
        $this->assertSame('g-ayah', $u->fresh()->google_id);
        $this->assertSame(1, User::where('email', 'ayah.budi@gmail.com')->count());
    }

    public function test_email_google_belum_terverifikasi_ditolak(): void
    {
        $this->aktifkan();
        $this->googleMengembalikan(['email_verified' => false]);

        $this->get('/auth/google/callback')->assertRedirect(route('login'))->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'ibu.siti@gmail.com']);
    }

    public function test_akun_nonaktif_tidak_bisa_masuk_lewat_google(): void
    {
        $this->aktifkan();
        $u = User::factory()->create(['email' => 'nonaktif@tazkia.ac.id', 'is_active' => false]);
        $u->assignRole(User::ROLE_ADMIN);
        $this->googleMengembalikan(['sub' => 'g-off', 'email' => 'nonaktif@tazkia.ac.id']);

        $this->get('/auth/google/callback')->assertRedirect(route('login'))->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_akun_nonaktif_tidak_bisa_masuk_dengan_kata_sandi(): void
    {
        $u = User::factory()->create(['is_active' => false]);

        $this->post('/login', ['email' => $u->email, 'password' => 'password'])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_gagal_dari_google_kembali_ke_login_tanpa_membocorkan_galat(): void
    {
        $this->aktifkan();
        $provider = \Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andThrow(new \RuntimeException('rahasia-internal'));
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->get('/auth/google/callback')->assertRedirect(route('login'))->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_google_id_dipakai_ulang_untuk_masuk_walau_email_berubah(): void
    {
        $this->aktifkan();
        $u = User::factory()->create(['email' => 'lama@gmail.com']);
        $u->forceFill(['google_id' => 'g-sama'])->save();
        $u->assignRole(User::ROLE_ORANGTUA);
        $this->googleMengembalikan(['sub' => 'g-sama', 'email' => 'baru@gmail.com']);

        $this->get('/auth/google/callback')->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($u);
        $this->assertDatabaseMissing('users', ['email' => 'baru@gmail.com']);
    }
}
