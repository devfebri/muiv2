<?php

namespace Tests\Feature;

use App\Models\Konsultasi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KonsultasiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test tanya ulama page can be rendered.
     */
    public function test_tanya_ulama_page_can_be_rendered(): void
    {
        $response = $this->get(route('tanya-ulama'));

        $response->assertStatus(200);
        $response->assertSee('Tanya Ulama');
        $response->assertSee('Formulir Tanya Ulama');
    }

    /**
     * Test submitting consultation form saves to database and redirects.
     */
    public function test_can_submit_konsultasi_form(): void
    {
        $payload = [
            'nama' => 'Ahmad Fulan',
            'email' => 'ahmad@example.com',
            'usia' => 28,
            'jenis_kelamin' => 'Laki-laki',
            'kab_kota' => 'Kota Banda Aceh',
            'kategori' => 'Shalat',
            'pertanyaan' => 'Bagaimana hukum shalat di atas kendaraan ketika dalam perjalanan jauh?',
        ];

        $response = $this->post(route('tanya-ulama.store'), $payload);

        $response->assertSessionHas('success');
        $response->assertRedirect();

        $this->assertDatabaseHas('konsultasis', [
            'nama' => 'Ahmad Fulan',
            'email' => 'ahmad@example.com',
            'kategori' => 'Shalat',
            'status' => 'pending',
        ]);
    }

    /**
     * Test admin can view konsultasi list page.
     */
    public function test_admin_can_view_konsultasi_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.konsultasi.index'));

        $response->assertStatus(200);
        $response->assertSee('Konsultasi Syariah');
        $response->assertSee('Mode Lihat Saja');
    }

    /**
     * Test operator can view konsultasi list page.
     */
    public function test_operator_can_view_konsultasi_page(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);

        $response = $this->actingAs($operator)->get(route('operator.konsultasi.index'));

        $response->assertStatus(200);
        $response->assertSee('Konsultasi Syariah');
        $response->assertSee('Akses Penuh');
    }

    /**
     * Test admin cannot reply to a consultation.
     */
    public function test_admin_cannot_reply_konsultasi(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $konsultasi = Konsultasi::create([
            'nama' => 'Fulan',
            'email' => 'fulan@example.com',
            'usia' => 30,
            'jenis_kelamin' => 'Laki-laki',
            'kab_kota' => 'Jakarta',
            'kategori' => 'Akidah dan Kepercayaan',
            'pertanyaan' => 'Pertanyaan mengenai akidah',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->postJson(route('operator.konsultasi.jawab', $konsultasi), [
            'jawaban' => 'Jawaban dari admin',
            'status' => 'dijawab',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test operator can reply to a consultation.
     */
    public function test_operator_can_reply_konsultasi(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $konsultasi = Konsultasi::create([
            'nama' => 'Fatimah',
            'email' => 'fatimah@example.com',
            'usia' => 25,
            'jenis_kelamin' => 'Perempuan',
            'kab_kota' => 'Surabaya',
            'kategori' => 'Zakat',
            'pertanyaan' => 'Bagaimana nisab zakat tabungan emas?',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($operator)->postJson(route('operator.konsultasi.jawab', $konsultasi), [
            'jawaban' => 'Nisab zakat emas adalah setara dengan 85 gram emas murni...',
            'status' => 'dijawab',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Jawaban konsultasi berhasil disimpan.');

        $this->assertDatabaseHas('konsultasis', [
            'id' => $konsultasi->id,
            'status' => 'dijawab',
            'penjawab_id' => $operator->id,
        ]);
    }

    public function test_konsultasi_show_returns_json_for_ajax_and_redirects_for_browser(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $konsultasi = Konsultasi::create([
            'nama' => 'Ahmad Test',
            'email' => 'ahmad@example.com',
            'usia' => 30,
            'jenis_kelamin' => 'Laki-laki',
            'kab_kota' => 'Banda Aceh',
            'kategori' => 'Muamalah',
            'pertanyaan' => 'Pertanyaan uji show',
            'status' => 'pending',
        ]);

        // 1. AJAX request returns JSON
        $ajaxResponse = $this->actingAs($admin)->getJson(route('admin.konsultasi.show', $konsultasi));
        $ajaxResponse->assertStatus(200);
        $ajaxResponse->assertJsonPath('id', $konsultasi->id);
        $ajaxResponse->assertJsonPath('nama', 'Ahmad Test');

        // 2. Direct browser GET request redirects to index view with detail_id
        $browserResponse = $this->actingAs($admin)->get(route('admin.konsultasi.show', $konsultasi));
        $browserResponse->assertRedirect(route('admin.konsultasi.index', ['detail_id' => $konsultasi->id]));
    }

    /**
     * Test public can view konsultasi list page.
     */
    public function test_public_can_view_konsultasi_list_page(): void
    {
        Konsultasi::create([
            'nama' => 'Hasan',
            'email' => 'hasan@example.com',
            'usia' => 35,
            'jenis_kelamin' => 'Laki-laki',
            'kab_kota' => 'Medan',
            'kategori' => 'Puasa',
            'pertanyaan' => 'Apakah membatalkan puasa jika menelan dahak?',
            'status' => 'dijawab',
            'jawaban' => 'Menurut mayoritas ulama Syafi\'iyyah...',
        ]);

        $response = $this->get(route('konsultasi.list'));

        $response->assertStatus(200);
        $response->assertSee('Daftar Konsultasi Syariah');
        $response->assertSee('Apakah membatalkan puasa jika menelan dahak?');
    }

    /**
     * Test public can view konsultasi detail page.
     */
    public function test_public_can_view_konsultasi_detail_page(): void
    {
        $konsultasi = Konsultasi::create([
            'nama' => 'Zainab',
            'email' => 'zainab@example.com',
            'usia' => 22,
            'jenis_kelamin' => 'Perempuan',
            'kab_kota' => 'Bandung',
            'kategori' => 'Shalat',
            'pertanyaan' => 'Bagaimana ketentuan shalat jamak qashar?',
            'status' => 'dijawab',
            'jawaban' => 'Shalat jamak qashar diperbolehkan bagi musafir...',
        ]);

        $response = $this->get(route('konsultasi.detail', $konsultasi));

        $response->assertStatus(200);
        $response->assertSee('Bagaimana ketentuan shalat jamak qashar?');
        $response->assertSee('Shalat jamak qashar diperbolehkan bagi musafir');
    }
}
