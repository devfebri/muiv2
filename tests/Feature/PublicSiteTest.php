<?php

namespace Tests\Feature;

use App\Models\Berita;
use App\Models\Fatwa;
use App\Models\Konsultasi;
use App\Models\Setting;
use App\Models\Surat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_unified_search_only_returns_public_content(): void
    {
        $user = User::factory()->create();

        Berita::create(['user_id' => $user->id, 'judul' => 'Sertifikasi Halal UMKM', 'kategori' => 'Halal', 'isi' => 'Isi berita halal.', 'status' => 'published', 'published_at' => now()]);
        Berita::create(['user_id' => $user->id, 'judul' => 'Draf Halal Rahasia', 'kategori' => 'Halal', 'isi' => 'Belum terbit.', 'status' => 'draft']);
        Fatwa::create(['judul' => 'Fatwa Produk Halal', 'status_fatwa' => 'aktif', 'publikasi' => true]);
        Fatwa::create(['judul' => 'Fatwa Halal Belum Publikasi', 'status_fatwa' => 'aktif', 'publikasi' => false]);
        Surat::create(['user_id' => $user->id, 'nomor_surat' => '001/MUI/X/2026', 'perihal' => 'Rekomendasi Satgas Halal', 'tanggal_surat' => '2026-10-01', 'file_surat' => 'surat.pdf']);
        Konsultasi::create(['nama' => 'Ali', 'email' => 'ali@example.com', 'usia' => 30, 'jenis_kelamin' => 'Laki-laki', 'kab_kota' => 'Jambi', 'kategori' => 'Puasa', 'pertanyaan' => 'Bagaimana label halal impor?', 'status' => 'dijawab', 'jawaban' => 'Wajib bersertifikat.', 'answered_at' => now()]);
        Konsultasi::create(['nama' => 'Budi', 'email' => 'budi@example.com', 'usia' => 30, 'jenis_kelamin' => 'Laki-laki', 'kab_kota' => 'Jambi', 'kategori' => 'Puasa', 'pertanyaan' => 'Pertanyaan halal yang belum dijawab?', 'status' => 'pending']);

        $response = $this->get(route('search', ['q' => 'halal']));

        $response->assertOk();
        $response->assertSee('Sertifikasi Halal UMKM');
        $response->assertSee('Fatwa Produk', false);
        $response->assertSee('Rekomendasi Satgas', false);
        $response->assertSee('Bagaimana label', false);
        $response->assertDontSee('Draf Halal Rahasia');
        $response->assertDontSee('Fatwa Halal Belum Publikasi');
        $response->assertDontSee('Pertanyaan halal yang belum dijawab?');
    }

    public function test_search_requires_minimum_query_length(): void
    {
        $this->get(route('search', ['q' => 'a']))
            ->assertOk()
            ->assertSee('Kata kunci minimal 2 karakter.');
    }

    public function test_published_fatwa_detail_is_public_and_counts_views(): void
    {
        $fatwa = Fatwa::create(['judul' => 'Fatwa Detail Publik', 'keterangan' => 'Ringkasan fatwa.', 'status_fatwa' => 'aktif', 'publikasi' => true, 'views' => 4]);

        $this->get(route('fatwa.detail', $fatwa))
            ->assertOk()
            ->assertSee('Fatwa Detail Publik')
            ->assertSee('Ringkasan fatwa.');

        $this->assertSame(5, $fatwa->fresh()->views);
    }

    public function test_unpublished_fatwa_detail_returns_not_found(): void
    {
        $fatwa = Fatwa::create(['judul' => 'Fatwa Tersembunyi', 'status_fatwa' => 'aktif', 'publikasi' => false]);

        $this->get(route('fatwa.detail', $fatwa))->assertNotFound();
    }

    public function test_public_question_list_masks_the_asker_name(): void
    {
        $konsultasi = Konsultasi::create(['nama' => 'Ahmad Fauzi Rahman', 'email' => 'ahmad@example.com', 'usia' => 40, 'jenis_kelamin' => 'Laki-laki', 'kab_kota' => 'Muara Bulian', 'kategori' => 'Shalat', 'pertanyaan' => 'Bagaimana hukum jamak shalat saat hujan?', 'status' => 'dijawab', 'jawaban' => 'Diperbolehkan menurut sebagian ulama.', 'answered_at' => now()]);

        $this->assertSame('Ahmad F.', $konsultasi->nama_samaran);

        $this->get(route('konsultasi.list'))
            ->assertOk()
            ->assertSee('Ahmad F.')
            ->assertDontSee('Ahmad Fauzi Rahman')
            ->assertDontSee('ahmad@example.com');
    }

    public function test_site_profile_builds_social_links_from_settings(): void
    {
        Setting::set('kontak_instagram', '@mui_batanghari', 'kontak');
        Setting::set('kontak_youtube', 'MUI Batanghari Official', 'kontak');
        Setting::set('kontak_facebook', 'https://facebook.com/muibatanghari', 'kontak');

        $site = Setting::siteProfile();

        $this->assertSame('https://www.instagram.com/mui_batanghari', $site['instagram']);
        $this->assertSame('https://www.youtube.com/results?search_query=MUI%20Batanghari%20Official', $site['youtube']);
        $this->assertSame('https://facebook.com/muibatanghari', $site['facebook']);
        $this->assertNull($site['tiktok']);
    }

    public function test_berita_summary_keeps_paragraph_spacing(): void
    {
        $berita = new Berita(['isi' => '<p>Paragraf pertama.</p><p>Paragraf kedua.</p>']);

        $this->assertSame('Paragraf pertama. Paragraf kedua.', $berita->ringkasan());
    }

    public function test_homepage_renders_redesigned_sections(): void
    {
        $user = User::factory()->create();
        Berita::create(['user_id' => $user->id, 'judul' => 'Kabar Utama Hari Ini', 'kategori' => 'Berita Utama', 'isi' => 'Isi.', 'status' => 'published', 'published_at' => now()]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Kabar Utama Hari Ini')
            ->assertSee('Jadwal Sholat')
            ->assertSee('Berita & Kegiatan', false)
            ->assertSee('Fatwa & Keputusan MUI', false);
    }

    public function test_unknown_page_uses_custom_not_found_view(): void
    {
        $this->get('/halaman-yang-tidak-ada')
            ->assertNotFound()
            ->assertSee('Halaman Tidak Ditemukan');
    }
}
