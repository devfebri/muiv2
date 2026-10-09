<?php

namespace Tests\Feature;

use App\Models\Surat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SuratTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_can_view_surat_list(): void
    {
        $user = User::factory()->create();

        Surat::create([
            'user_id' => $user->id,
            'nomor_surat' => '001/MUI/IX/2026',
            'perihal' => 'Undangan Rapat Pleno MUI',
            'tanggal_surat' => '2026-09-15',
            'file_surat' => 'surat/surat1.pdf',
        ]);

        $response = $this->get(route('surat'));

        $response->assertStatus(200);
        $response->assertSee('Arsip Surat Resmi MUI');
        $response->assertSee('001/MUI/IX/2026');
        $response->assertSee('Undangan Rapat Pleno MUI');
        $response->assertSee('Lihat Surat');
    }

    public function test_public_can_search_surat(): void
    {
        $user = User::factory()->create();

        Surat::create([
            'user_id' => $user->id,
            'nomor_surat' => '100/EDARAN/2026',
            'perihal' => 'Edaran Idul Fitri',
            'tanggal_surat' => '2026-04-01',
            'file_surat' => 'surat/edaran.pdf',
        ]);

        Surat::create([
            'user_id' => $user->id,
            'nomor_surat' => '200/SK/2026',
            'perihal' => 'Surat Keputusan Pengurus',
            'tanggal_surat' => '2026-05-01',
            'file_surat' => 'surat/sk.pdf',
        ]);

        $response = $this->get(route('surat', ['q' => 'Edaran']));

        $response->assertStatus(200);
        $response->assertSee('100/EDARAN/2026');
        $response->assertDontSee('200/SK/2026');
    }

    public function test_admin_can_access_surat_datatable_and_crud(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        // Store
        $file = UploadedFile::fake()->create('surat_edaran.pdf', 500, 'application/pdf');

        $storeResponse = $this->actingAs($admin)->postJson(route('admin.surat.store'), [
            'nomor_surat' => '999/MUI/2026',
            'perihal' => 'Surat Tugas Dewan Syariah',
            'tanggal_surat' => '2026-09-30',
            'file_surat' => $file,
        ]);

        $storeResponse->assertStatus(201);
        $this->assertDatabaseHas('surats', [
            'nomor_surat' => '999/MUI/2026',
            'perihal' => 'Surat Tugas Dewan Syariah',
        ]);

        // DataTable index
        $indexResponse = $this->actingAs($admin)->getJson(route('admin.surat.index', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]));

        $indexResponse->assertStatus(200);
        $this->assertEquals(1, $indexResponse->json('recordsTotal'));
        $this->assertEquals('999/MUI/2026', $indexResponse->json('data.0.nomor_surat'));

        // Delete
        $surat = Surat::first();
        $deleteResponse = $this->actingAs($admin)->deleteJson(route('admin.surat.destroy', $surat));
        $deleteResponse->assertStatus(200);
        $this->assertDatabaseMissing('surats', ['id' => $surat->id]);
    }
}
