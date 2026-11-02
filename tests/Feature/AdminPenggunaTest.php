<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminPenggunaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([User::ROLE_ADMIN, User::ROLE_AKADEMIK, User::ROLE_ORANGTUA] as $r) {
            Role::findOrCreate($r);
        }
    }

    private function user(string $peran, array $atribut = []): User
    {
        $u = User::factory()->create($atribut + ['is_active' => true]);
        $u->assignRole($peran);

        return $u;
    }

    public function test_hanya_admin_yang_bisa_membuka_halaman_pengguna(): void
    {
        $this->actingAs($this->user(User::ROLE_AKADEMIK))->get('/admin/pengguna')->assertForbidden();
        $this->actingAs($this->user(User::ROLE_ORANGTUA))->get('/admin/pengguna')->assertForbidden();
        $this->actingAs($this->user(User::ROLE_ADMIN))->get('/admin/pengguna')->assertOk();
    }

    public function test_daftar_menampilkan_staf_dan_alamat_ubah_memakai_uuid(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, ['name' => 'Admin Satu']);
        $ortu = $this->user(User::ROLE_ORANGTUA, ['name' => 'Ibu Budi']);

        $r = $this->actingAs($admin)->get('/admin/pengguna')->assertOk()->assertSee($admin->email)->assertDontSee('Ibu Budi');
        $r->assertSee('/admin/pengguna/'.$admin->uuid.'/edit', false);
        $this->actingAs($admin)->get('/admin/pengguna?peran=orangtua')->assertSee('Ibu Budi')->assertDontSee('/admin/pengguna/'.$admin->uuid.'/edit', false);
        $this->actingAs($admin)->get('/admin/pengguna?q=Budi&peran=semua')->assertSee('Ibu Budi');
    }

    public function test_admin_membuat_staf_tanpa_kata_sandi_yang_diketahui(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);

        $this->actingAs($admin)->post('/admin/pengguna', ['name' => 'Staf Baru', 'email' => 'Staf.Baru@Gmail.com', 'peran' => 'akademik'])
            ->assertRedirect('/admin/pengguna');

        $staf = User::where('email', 'staf.baru@gmail.com')->firstOrFail();
        $this->assertTrue($staf->hasRole(User::ROLE_AKADEMIK));
        $this->assertTrue($staf->is_active);
        $this->assertNotNull($staf->uuid);
        $this->assertNull($staf->google_id);
        $this->assertGreaterThan(40, strlen($staf->password)); // hash acak; tidak ada kata sandi yang bisa dipakai
    }

    public function test_email_yang_sudah_ada_atau_peran_tak_sah_ditolak(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $ortu = $this->user(User::ROLE_ORANGTUA, ['email' => 'ortu@gmail.com']);

        $this->actingAs($admin)->post('/admin/pengguna', ['name' => 'X', 'email' => 'ortu@gmail.com', 'peran' => 'admin'])->assertSessionHasErrors('email');
        $this->actingAs($admin)->post('/admin/pengguna', ['name' => 'X', 'email' => 'baru@gmail.com', 'peran' => 'orangtua'])->assertSessionHasErrors('peran');
        $this->assertFalse($ortu->fresh()->hasRole(User::ROLE_ADMIN));
        $this->assertNull(User::where('email', 'baru@gmail.com')->first());
    }

    public function test_admin_tidak_bisa_menonaktifkan_atau_menurunkan_diri_sendiri(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $this->user(User::ROLE_ADMIN); // admin lain ada, tapi tetap tidak boleh untuk diri sendiri

        $this->actingAs($admin)->put("/admin/pengguna/{$admin->uuid}", ['name' => $admin->name, 'peran' => 'akademik', 'is_active' => 1])->assertSessionHasErrors('peran');
        $this->actingAs($admin)->put("/admin/pengguna/{$admin->uuid}", ['name' => $admin->name, 'peran' => 'admin'])->assertSessionHasErrors('peran');

        $this->assertTrue($admin->fresh()->hasRole(User::ROLE_ADMIN));
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_admin_lain_bisa_diturunkan_atau_dinonaktifkan_hanya_bila_ada_admin_aktif_lain(): void
    {
        $a = $this->user(User::ROLE_ADMIN);
        $b = $this->user(User::ROLE_ADMIN);

        $this->actingAs($a)->put("/admin/pengguna/{$b->uuid}", ['name' => $b->name, 'peran' => 'akademik', 'is_active' => 1])->assertRedirect('/admin/pengguna');
        $this->assertTrue($b->fresh()->hasRole(User::ROLE_AKADEMIK));
        $this->assertFalse($b->fresh()->hasRole(User::ROLE_ADMIN));

        // a kini satu-satunya admin aktif: admin lain tidak ada yang bisa diturunkan, dan a tak bisa diturunkan oleh dirinya
        $c = $this->user(User::ROLE_ADMIN, ['is_active' => false]);
        $this->actingAs($a)->put("/admin/pengguna/{$c->uuid}", ['name' => $c->name, 'peran' => 'akademik', 'is_active' => 1])->assertRedirect('/admin/pengguna');
        $this->assertTrue($c->fresh()->hasRole(User::ROLE_AKADEMIK));
    }

    public function test_admin_aktif_terakhir_tidak_bisa_dinonaktifkan_atau_diturunkan(): void
    {
        $terakhir = $this->user(User::ROLE_ADMIN);
        $pelaku = $this->user(User::ROLE_ADMIN, ['is_active' => false]); // pengaman tetap berlaku walau pelaku bukan admin aktif

        $this->actingAs($pelaku)->put("/admin/pengguna/{$terakhir->uuid}", ['name' => $terakhir->name, 'peran' => 'admin'])->assertSessionHasErrors('peran');
        $this->actingAs($pelaku)->put("/admin/pengguna/{$terakhir->uuid}", ['name' => $terakhir->name, 'peran' => 'akademik', 'is_active' => 1])->assertSessionHasErrors('peran');

        $this->assertTrue($terakhir->fresh()->is_active);
        $this->assertTrue($terakhir->fresh()->hasRole(User::ROLE_ADMIN));
    }

    public function test_menonaktifkan_akun_menghapus_sesi_aktifnya(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $staf = $this->user(User::ROLE_AKADEMIK);
        DB::table('sessions')->insert(['id' => 'sesi-staf', 'user_id' => $staf->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'uji', 'payload' => '', 'last_activity' => time()]);

        $this->actingAs($admin)->put("/admin/pengguna/{$staf->uuid}", ['name' => $staf->name, 'peran' => 'akademik'])->assertRedirect('/admin/pengguna');

        $this->assertFalse($staf->fresh()->is_active);
        $this->assertSame(0, DB::table('sessions')->where('user_id', $staf->id)->count());
    }

    public function test_akun_orang_tua_hanya_bisa_diaktifkan_atau_dinonaktifkan(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $ortu = $this->user(User::ROLE_ORANGTUA, ['name' => 'Ibu Asli']);

        $this->actingAs($admin)->put("/admin/pengguna/{$ortu->uuid}", ['name' => 'Diganti', 'peran' => 'admin'])->assertRedirect('/admin/pengguna');

        $ortu = $ortu->fresh();
        $this->assertTrue($ortu->hasRole(User::ROLE_ORANGTUA));
        $this->assertFalse($ortu->hasRole(User::ROLE_ADMIN));
        $this->assertSame('Ibu Asli', $ortu->name);
        $this->assertFalse($ortu->is_active);
    }

    public function test_alamat_dengan_nomor_urut_tidak_dikenali(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $this->actingAs($admin)->get("/admin/pengguna/{$admin->id}/edit")->assertNotFound();
        $this->actingAs($admin)->get("/admin/pengguna/{$admin->uuid}/edit")->assertOk();
    }
}
