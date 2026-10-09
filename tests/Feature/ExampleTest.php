<?php

namespace Tests\Feature;

use App\Models\Berita;
use App\Models\Fatwa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('MUI Batanghari');
    }

    public function test_welcome_page_displays_real_data_from_database(): void
    {
        $user = User::factory()->create();

        Berita::create([
            'user_id' => $user->id,
            'judul' => 'Berita Real Unggulan MUI Batanghari',
            'kategori' => 'Berita Utama',
            'isi' => 'Konten berita lengkap dari database.',
            'status' => 'published',
            'published_at' => now(),
        ]);

        Fatwa::create([
            'judul' => 'Fatwa Real Tentang Investasi Digital',
            'status_fatwa' => 'aktif',
            'filepdf' => 'real_fatwa.pdf',
            'publikasi' => true,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Berita Real Unggulan MUI Batanghari');
        $response->assertSee('Fatwa Real Tentang Investasi Digital');
        $response->assertSee('Fatwa MUI');
        $response->assertSee('Surat Resmi');
    }
}
