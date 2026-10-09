<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PengaturanTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_tentang_kami_pages_are_accessible(): void
    {
        $this->get(route('profilemui'))->assertStatus(200);
        $this->get(route('visi-misi'))->assertStatus(200);
        $this->get(route('struktur-organisasi'))->assertStatus(200);
        $this->get(route('kontak'))->assertStatus(200);
    }

    public function test_guest_cannot_access_admin_pengaturan(): void
    {
        $response = $this->get(route('admin.pengaturan.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_operator_cannot_access_admin_pengaturan(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);

        $response = $this->actingAs($operator)->get(route('admin.pengaturan.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_view_admin_pengaturan(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.pengaturan.index'));
        $response->assertStatus(200);
        $response->assertSee('Pengaturan Website — Tentang Kami');
    }

    public function test_admin_can_update_profil_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.pengaturan.update'), [
            '_section' => 'profil',
            'profil_title' => 'Profil MUI Batanghari Custom',
            'profil_subtitle' => 'Subtitle pengujian profil',
            'profil_sekilas_1' => 'Paragraf 1 pengujian',
            'profil_peran_1_title' => 'Khadimul Ummah Custom',
            'profil_peran_1_desc' => 'Deskripsi peran 1',
            'profil_tgl_berdiri' => '17 Agustus 1945',
            'profil_sifat_lembaga' => 'Lembaga Independen',
            'profil_alamat_kantor' => 'Muara Bulian',
        ]);

        $response->assertRedirect(route('admin.pengaturan.index', ['tab' => 'profil']));
        $this->assertEquals('Profil MUI Batanghari Custom', Setting::get('profil_title'));

        // Frontend check
        $frontend = $this->get(route('profilemui'));
        $frontend->assertStatus(200);
        $frontend->assertSee('Profil MUI Batanghari Custom');
        $frontend->assertSee('Subtitle pengujian profil');
    }

    public function test_admin_can_update_visi_misi_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.pengaturan.update'), [
            '_section' => 'visi_misi',
            'visi_title' => 'Visi & Misi MUI Batanghari',
            'visi_subtitle' => 'Komitmen pengabdian',
            'visi_text' => 'Teks visi pengujian yang sangat luhur.',
            'misi_list' => "Misi Pertama | Uraian misi pertama\nMisi Kedua | Uraian misi kedua",
            'wasathiyah_title' => 'Wasathiyah Batanghari',
            'wasathiyah_desc' => 'Deskripsi wasathiyah pengujian',
        ]);

        $response->assertRedirect(route('admin.pengaturan.index', ['tab' => 'visi_misi']));
        $this->assertEquals('Teks visi pengujian yang sangat luhur.', Setting::get('visi_text'));

        // Frontend check
        $frontend = $this->get(route('visi-misi'));
        $frontend->assertStatus(200);
        $frontend->assertSee('Teks visi pengujian yang sangat luhur.');
        $frontend->assertSee('Misi Pertama');
        $frontend->assertSee('Uraian misi pertama');
    }

    public function test_admin_can_update_struktur_settings_with_image(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $file = UploadedFile::fake()->image('bagan_test.png', 400, 300);

        $response = $this->actingAs($admin)->post(route('admin.pengaturan.update'), [
            '_section' => 'struktur',
            'struktur_title' => 'Struktur MUI Batanghari',
            'struktur_subtitle' => 'Susunan pengurus 2026',
            'struktur_ketua_pertimbangan' => 'KH. Ahmad Fauzi',
            'struktur_ketua_umum' => 'KH. Muhammad Arifin',
            'struktur_komisi_list' => 'Komisi Fatwa | Bertugas mengkaji fatwa',
            'struktur_bagan_gambar' => $file,
        ]);

        $response->assertRedirect(route('admin.pengaturan.index', ['tab' => 'struktur']));
        $this->assertEquals('KH. Muhammad Arifin', Setting::get('struktur_ketua_umum'));

        $uploadedName = Setting::get('struktur_bagan_gambar');
        $this->assertNotNull($uploadedName);
        $this->assertFileExists(public_path('uploads/pengaturan/'.$uploadedName));

        // Clean up test upload
        File::delete(public_path('uploads/pengaturan/'.$uploadedName));

        // Frontend check
        $frontend = $this->get(route('struktur-organisasi'));
        $frontend->assertStatus(200);
        $frontend->assertSee('KH. Muhammad Arifin');
    }

    public function test_admin_can_update_kontak_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.pengaturan.update'), [
            '_section' => 'kontak',
            'kontak_title' => 'Hubungi MUI Batanghari Terkini',
            'kontak_subtitle' => 'Layanan cepat dan ramah',
            'kontak_alamat' => 'Jl. Jenderal Sudirman No. 99 Muara Bulian',
            'kontak_telepon' => '0743-12345',
            'kontak_whatsapp' => '0812-9999-8888',
            'kontak_email' => 'admin@muibatanghari.or.id',
            'kontak_jam_layanan' => '08.00 - 15.00 WIB',
            'kontak_instagram' => '@mui_batanghari_asli',
        ]);

        $response->assertRedirect(route('admin.pengaturan.index', ['tab' => 'kontak']));
        $this->assertEquals('0812-9999-8888', Setting::get('kontak_whatsapp'));

        // Frontend check
        $frontend = $this->get(route('kontak'));
        $frontend->assertStatus(200);
        $frontend->assertSee('0812-9999-8888');
        $frontend->assertSee('@mui_batanghari_asli');
    }
}
