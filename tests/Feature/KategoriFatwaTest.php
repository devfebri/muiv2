<?php

namespace Tests\Feature;

use App\Models\Fatwa;
use App\Models\KategoriFatwa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KategoriFatwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_kategori_fatwa_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.kategori-fatwa.index'));

        $response->assertStatus(200);
        $response->assertSee('Kategori Fatwa');
    }

    public function test_operator_can_view_kategori_fatwa_page(): void
    {
        $operator = User::factory()->create([
            'role' => 'operator',
            'menu_permissions' => ['kategori-fatwa'],
        ]);

        $response = $this->actingAs($operator)->get(route('operator.kategori-fatwa.index'));

        $response->assertStatus(200);
        $response->assertSee('Kategori Fatwa');
    }

    public function test_can_create_kategori_fatwa(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->postJson(route('admin.kategori-fatwa.store'), [
            'nama' => 'Hukum & Ibadah',
            'deskripsi' => 'Fatwa terkait masalah hukum dan pelaksanaan ibadah harian.',
            'aktif' => 1,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('message', 'Kategori fatwa berhasil ditambahkan.');

        $this->assertDatabaseHas('kategori_fatwas', [
            'nama' => 'Hukum & Ibadah',
            'slug' => 'hukum-ibadah',
            'aktif' => 1,
        ]);
    }

    public function test_can_update_kategori_fatwa(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $kategori = KategoriFatwa::create([
            'nama' => 'Produk Halal',
            'slug' => 'produk-halal',
            'deskripsi' => 'Deskripsi lama',
            'aktif' => true,
        ]);

        $response = $this->actingAs($admin)->putJson(route('admin.kategori-fatwa.update', $kategori), [
            'nama' => 'Standar Produk & Halal',
            'deskripsi' => 'Deskripsi diperbarui',
            'aktif' => 1,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Kategori fatwa berhasil diperbarui.');

        $this->assertDatabaseHas('kategori_fatwas', [
            'id' => $kategori->id,
            'nama' => 'Standar Produk & Halal',
            'slug' => 'standar-produk-halal',
        ]);
    }

    public function test_can_toggle_kategori_fatwa_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $kategori = KategoriFatwa::create([
            'nama' => 'Muamalah Kontemporer',
            'slug' => 'muamalah-kontemporer',
            'aktif' => true,
        ]);

        $response = $this->actingAs($admin)->patchJson(route('admin.kategori-fatwa.toggleStatus', $kategori));

        $response->assertStatus(200);
        $this->assertDatabaseHas('kategori_fatwas', [
            'id' => $kategori->id,
            'aktif' => 0,
        ]);
    }

    public function test_can_delete_kategori_fatwa(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $kategori = KategoriFatwa::create([
            'nama' => 'Kategori Sementara',
            'slug' => 'kategori-sementara',
            'aktif' => true,
        ]);

        $response = $this->actingAs($admin)->deleteJson(route('admin.kategori-fatwa.destroy', $kategori));

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Kategori fatwa berhasil dihapus.');

        $this->assertDatabaseMissing('kategori_fatwas', [
            'id' => $kategori->id,
        ]);
    }

    public function test_fatwa_belongs_to_kategori_fatwa_relationship(): void
    {
        $kategori = KategoriFatwa::create([
            'nama' => 'Ekonomi Syariah',
            'slug' => 'ekonomi-syariah',
            'aktif' => true,
        ]);

        $fatwa = Fatwa::create([
            'kategori_fatwa_id' => $kategori->id,
            'judul' => 'Fatwa Fintech Syariah',
            'publikasi' => true,
        ]);

        $this->assertEquals($kategori->id, $fatwa->kategori->id);
        $this->assertTrue($kategori->fatwas->contains($fatwa));
    }
}
