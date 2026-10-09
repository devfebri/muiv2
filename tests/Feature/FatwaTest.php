<?php

namespace Tests\Feature;

use App\Models\Fatwa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FatwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_fatwa_datatable_with_empty_filter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Fatwa::create([
            'judul' => 'Fatwa Test 1',
            'keterangan' => 'Ket 1',
            'filepdf' => 'fatwa/test1.pdf',
            'publikasi' => true,
        ]);

        Fatwa::create([
            'judul' => 'Fatwa Test 2',
            'keterangan' => 'Ket 2',
            'filepdf' => 'fatwa/test2.pdf',
            'publikasi' => false,
        ]);

        // Request with filter_publikasi = "" (Semua)
        $responseAll = $this->actingAs($admin)->getJson(route('admin.fatwa.index', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'filter_publikasi' => '',
        ]));

        $responseAll->assertStatus(200);
        $this->assertEquals(2, $responseAll->json('recordsFiltered'));

        // Request with filter_publikasi = "1" (Aktif)
        $responseActive = $this->actingAs($admin)->getJson(route('admin.fatwa.index', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'filter_publikasi' => '1',
        ]));

        $responseActive->assertStatus(200);
        $this->assertEquals(1, $responseActive->json('recordsFiltered'));
        $this->assertEquals('Fatwa Test 1', $responseActive->json('data.0.judul'));

        // Request with filter_publikasi = "0" (Tidak Aktif)
        $responseInactive = $this->actingAs($admin)->getJson(route('admin.fatwa.index', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'filter_publikasi' => '0',
        ]));

        $responseInactive->assertStatus(200);
        $this->assertEquals(1, $responseInactive->json('recordsFiltered'));
        $this->assertEquals('Fatwa Test 2', $responseInactive->json('data.0.judul'));
    }

    public function test_fatwa_can_be_stored_and_updated_with_status_fatwa(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $fatwa = Fatwa::create([
            'judul' => 'Fatwa Keuangan 1',
            'status_fatwa' => 'aktif',
            'filepdf' => 'fatwa/keuangan1.pdf',
            'publikasi' => true,
        ]);

        $this->assertEquals('aktif', $fatwa->status_fatwa);

        $fatwa->update([
            'status_fatwa' => 'direvisi',
        ]);

        $this->assertEquals('direvisi', $fatwa->fresh()->status_fatwa);

        $fatwa->update([
            'status_fatwa' => 'digantikan',
        ]);

        $this->assertEquals('digantikan', $fatwa->fresh()->status_fatwa);
    }

    public function test_fatwa_datatable_can_filter_by_status_fatwa(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Fatwa::create([
            'judul' => 'Fatwa Aktif',
            'status_fatwa' => 'aktif',
            'publikasi' => true,
        ]);

        Fatwa::create([
            'judul' => 'Fatwa Direvisi',
            'status_fatwa' => 'direvisi',
            'publikasi' => true,
        ]);

        Fatwa::create([
            'judul' => 'Fatwa Digantikan',
            'status_fatwa' => 'digantikan',
            'publikasi' => true,
        ]);

        $response = $this->actingAs($admin)->getJson(route('admin.fatwa.index', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'filter_status_fatwa' => 'direvisi',
        ]));

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('recordsFiltered'));
        $this->assertEquals('Fatwa Direvisi', $response->json('data.0.judul'));
        $this->assertEquals('direvisi', $response->json('data.0.status_fatwa'));
    }

    public function test_public_can_view_fatwa_list_page(): void
    {
        Fatwa::create([
            'judul' => 'Fatwa Publik 1',
            'keterangan' => 'Penjelasan fatwa publik',
            'status_fatwa' => 'aktif',
            'filepdf' => 'fatwa/pub1.pdf',
            'publikasi' => true,
        ]);

        Fatwa::create([
            'judul' => 'Fatwa Rahasia Belum Publish',
            'status_fatwa' => 'aktif',
            'publikasi' => false,
        ]);

        $response = $this->get(route('fatwa'));

        $response->assertStatus(200);
        $response->assertSee('Fatwa Majelis Ulama Indonesia');
        $response->assertSee('Fatwa Publik 1');
        $response->assertDontSee('Fatwa Rahasia Belum Publish');
        $response->assertSee('Baca Fatwa');
    }

    public function test_fatwa_views_can_be_incremented(): void
    {
        $fatwa = Fatwa::create([
            'judul' => 'Fatwa Uji Views',
            'status_fatwa' => 'aktif',
            'filepdf' => 'fatwa/uji_views.pdf',
            'publikasi' => true,
            'views' => 5,
        ]);

        $response = $this->postJson(route('fatwa.increment-views', $fatwa));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'views' => 6,
        ]);

        $this->assertEquals(6, $fatwa->fresh()->views);
    }
}
