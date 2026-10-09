<?php

namespace Tests\Feature;

use App\Models\Berita;
use App\Models\Kategori;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BeritaTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> Berkas di folder unggahan berita sebelum test berjalan. */
    private array $existingUploads = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->existingUploads = File::isDirectory(public_path('uploads/berita'))
            ? array_map(fn ($file) => $file->getPathname(), File::files(public_path('uploads/berita')))
            : [];
    }

    /**
     * Hapus gambar yang diunggah selama test agar folder publik tidak dipenuhi berkas uji.
     */
    protected function tearDown(): void
    {
        if (File::isDirectory(public_path('uploads/berita'))) {
            foreach (File::files(public_path('uploads/berita')) as $file) {
                if (! in_array($file->getPathname(), $this->existingUploads, true)) {
                    File::delete($file->getPathname());
                }
            }
        }

        parent::tearDown();
    }

    public function test_berita_list_page_loads(): void
    {
        $user = User::factory()->create();

        $berita = Berita::create([
            'user_id' => $user->id,
            'judul' => 'Berita Pengujian Index',
            'kategori' => 'Berita Utama',
            'isi' => 'Konten berita pengujian.',
            'status' => 'published',
            'published_at' => now(),
            'views' => 42,
        ]);

        $response = $this->get(route('berita.list'));

        $response->assertStatus(200);
        $response->assertSee('Berita Pengujian Index');
    }

    public function test_visiting_berita_detail_displays_and_increments_views(): void
    {
        $user = User::factory()->create();

        $berita = Berita::create([
            'user_id' => $user->id,
            'judul' => 'Berita Detail Views Increment',
            'kategori' => 'Berita Utama',
            'isi' => 'Konten berita yang akan dibuka detailnya.',
            'status' => 'published',
            'published_at' => now(),
            'views' => 10,
        ]);

        $this->assertEquals(10, $berita->views);

        $response = $this->get(route('berita.detail', $berita->slug ?? $berita->id));

        $response->assertStatus(200);
        $response->assertSee('Berita Detail Views Increment');
        $response->assertSee('11 kali dilihat');
        $this->assertEquals(11, $berita->fresh()->views);
    }

    public function test_berita_detail_renders_html_content_properly(): void
    {
        $user = User::factory()->create();

        $berita = Berita::create([
            'user_id' => $user->id,
            'judul' => 'Berita Dengan Format HTML',
            'kategori' => 'Berita Utama',
            'isi' => '<p><strong>Teks Tebal Berita</strong> dan penjelasan.</p>',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get(route('berita.detail', $berita->slug ?? $berita->id));

        $response->assertStatus(200);
        $response->assertSee('<strong>Teks Tebal Berita</strong>', false);
        $response->assertDontSee('&lt;p&gt;&lt;strong&gt;', false);
    }

    public function test_berita_create_page_displays_categories_from_kategoris_table(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Kategori::create([
            'nama' => 'Pendidikan Islam',
            'warna' => '#007f5f',
            'aktif' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.berita.create'));

        $response->assertStatus(200);
        $response->assertSee('Pendidikan Islam');
    }

    public function test_berita_can_be_stored_with_category_from_kategoris_table(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Kategori::create([
            'nama' => 'Dakwah Digital',
            'warna' => '#007f5f',
            'aktif' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.berita.store'), [
            'judul' => 'Pelatihan Dakwah Digital MUI',
            'kategori' => 'Dakwah Digital',
            'isi' => '<p>Konten pelatihan dakwah digital.</p>',
            'status' => 'published',
            'gambar' => UploadedFile::fake()->image('thumbnail.jpg'),
        ]);

        $response->assertRedirect(route('admin.berita.index'));
        $this->assertDatabaseHas('beritas', [
            'judul' => 'Pelatihan Dakwah Digital MUI',
            'kategori' => 'Dakwah Digital',
        ]);
    }

    public function test_storing_berita_requires_thumbnail_image(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Kategori::create([
            'nama' => 'Fatwa',
            'warna' => '#007f5f',
            'aktif' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.berita.store'), [
            'judul' => 'Berita Tanpa Gambar',
            'kategori' => 'Fatwa',
            'isi' => '<p>Konten tanpa gambar.</p>',
            'status' => 'published',
        ]);

        $response->assertSessionHasErrors('gambar');
    }

    public function test_berita_can_be_stored_as_draft_with_null_published_at(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Kategori::create([
            'nama' => 'Pendidikan',
            'warna' => '#007f5f',
            'aktif' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.berita.store'), [
            'judul' => 'Berita Dalam Bentuk Draft',
            'kategori' => 'Pendidikan',
            'isi' => '<p>Konten berita draft.</p>',
            'status' => 'draft',
            'gambar' => UploadedFile::fake()->image('thumbnail.jpg'),
        ]);

        $response->assertRedirect(route('admin.berita.index'));
        $this->assertDatabaseHas('beritas', [
            'judul' => 'Berita Dalam Bentuk Draft',
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    public function test_berita_can_be_updated_from_published_to_draft(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Kategori::create([
            'nama' => 'Pendidikan',
            'warna' => '#007f5f',
            'aktif' => true,
        ]);

        $berita = Berita::create([
            'user_id' => $admin->id,
            'judul' => 'Berita Published Awal',
            'kategori' => 'Pendidikan',
            'isi' => '<p>Konten awal.</p>',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->actingAs($admin)->put(route('admin.berita.update', $berita), [
            'judul' => 'Berita Published Diubah Ke Draft',
            'kategori' => 'Pendidikan',
            'isi' => '<p>Konten diubah.</p>',
            'status' => 'draft',
        ]);

        $response->assertRedirect(route('admin.berita.index'));
        $this->assertDatabaseHas('beritas', [
            'id' => $berita->id,
            'judul' => 'Berita Published Diubah Ke Draft',
            'status' => 'draft',
            'published_at' => null,
        ]);
    }
}
