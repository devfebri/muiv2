<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KategoriBeritaTest extends TestCase
{
    use RefreshDatabase;

    public function test_name_producing_an_existing_slug_returns_validation_error(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Kategori::create(['nama' => 'Halal', 'slug' => 'halal', 'warna' => '#177a53', 'aktif' => true, 'urutan' => 1]);

        $this->actingAs($admin)
            ->postJson(route('admin.kategori.store'), ['nama' => 'Halal!', 'warna' => '#177a53', 'aktif' => 1])
            ->assertStatus(422)
            ->assertJsonValidationErrors('nama');

        $this->assertSame(1, Kategori::count());
    }

    public function test_datatable_can_filter_by_status_and_returns_stats(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Kategori::create(['nama' => 'Aktif Satu', 'slug' => 'aktif-satu', 'warna' => '#177a53', 'aktif' => true, 'urutan' => 1]);
        Kategori::create(['nama' => 'Nonaktif Satu', 'slug' => 'nonaktif-satu', 'warna' => '#177a53', 'aktif' => false, 'urutan' => 2]);

        $response = $this->actingAs($admin)
            ->getJson(route('admin.kategori.index', ['draw' => 1, 'start' => 0, 'length' => 10, 'filter_aktif' => '0']), ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.nama', 'Nonaktif Satu')
            ->assertJsonPath('stats.total', 2)
            ->assertJsonPath('stats.aktif', 1)
            ->assertJsonPath('stats.nonaktif', 1);
    }
}
