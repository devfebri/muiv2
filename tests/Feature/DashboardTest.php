<?php

namespace Tests\Feature;

use App\Models\Berita;
use App\Models\ChatSession;
use App\Models\Fatwa;
use App\Models\KategoriFatwa;
use App\Models\Konsultasi;
use App\Models\Surat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');

        $responseAdmin = $this->get('/admin/dashboard');
        $responseAdmin->assertRedirect('/login');

        $responseOp = $this->get('/operator/dashboard');
        $responseOp->assertRedirect('/login');
    }

    public function test_admin_can_access_admin_dashboard_with_all_metrics(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        Berita::create([
            'user_id' => $admin->id,
            'judul' => 'Berita Utama Hari Ini',
            'kategori' => 'Berita Utama',
            'isi' => 'Konten berita utama portal MUI.',
            'status' => 'published',
            'views' => 150,
            'published_at' => now(),
        ]);

        Konsultasi::create([
            'nama' => 'Ahmad Jamaah',
            'email' => 'ahmad@example.com',
            'usia' => 30,
            'jenis_kelamin' => 'Laki-laki',
            'kab_kota' => 'Banda Aceh',
            'kategori' => 'Muamalah',
            'pertanyaan' => 'Bagaimana hukum transaksi uang elektronik?',
            'status' => 'pending',
        ]);

        ChatSession::create([
            'session_token' => 'token-test-123',
            'nama_pengunjung' => 'Budi Santoso',
            'email_pengunjung' => 'budi@example.com',
            'topik' => 'Zakat',
            'status' => 'menunggu',
            'last_activity_at' => now(),
        ]);

        $kategoriFatwa = KategoriFatwa::create([
            'nama' => 'Ibadah',
            'slug' => 'ibadah',
            'aktif' => true,
        ]);

        Fatwa::create([
            'kategori_fatwa_id' => $kategoriFatwa->id,
            'judul' => 'Fatwa Shalat Berjamaah',
            'keterangan' => 'Keterangan fatwa ibadah',
            'publikasi' => 1,
            'status_fatwa' => 'aktif',
            'views' => 10,
        ]);

        Surat::create([
            'user_id' => $admin->id,
            'nomor_surat' => '001/MUI/X/2026',
            'perihal' => 'Undangan Rapat Pleno',
            'tanggal_surat' => '2026-10-01',
            'file_surat' => 'surat/undangan.pdf',
        ]);

        // Route /dashboard redirects to admin.dashboard
        $redirect = $this->actingAs($admin)->get('/dashboard');
        $redirect->assertRedirect(route('admin.dashboard'));

        // Direct access to /admin/dashboard
        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard');
        $response->assertSee('Dashboard Administrator');
        $response->assertSee('Berita Utama Hari Ini');
        $response->assertSee('Ahmad Jamaah');
        $response->assertSee('Budi Santoso');
        $response->assertViewHas('stats');
        $response->assertViewHas('latestBerita');
        $response->assertViewHas('pendingKonsultasi');
        $response->assertViewHas('waitingChats');
    }

    public function test_operator_is_rendered_operator_dashboard_with_permissions(): void
    {
        $operator = User::factory()->create([
            'role' => 'operator',
            'menu_permissions' => ['berita', 'livechat', 'konsultasi', 'fatwa', 'surat'],
        ]);

        Berita::create([
            'user_id' => $operator->id,
            'judul' => 'Kajian Rutin Fatwa MUI',
            'kategori' => 'Kajian',
            'isi' => 'Artikel kajian fatwa rutin.',
            'status' => 'published',
            'views' => 45,
            'published_at' => now(),
        ]);

        ChatSession::create([
            'session_token' => 'session-op-waiting',
            'nama_pengunjung' => 'Fatimah Az Zahra',
            'email_pengunjung' => 'fatimah@example.com',
            'topik' => 'Konsultasi Keluarga',
            'status' => 'menunggu',
            'last_activity_at' => now(),
        ]);

        // Route /dashboard redirects to operator.dashboard
        $redirect = $this->actingAs($operator)->get('/dashboard');
        $redirect->assertRedirect(route('operator.dashboard'));

        // Direct access to /operator/dashboard
        $response = $this->actingAs($operator)->get(route('operator.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('operator.dashboard');
        $response->assertSee('Dashboard Petugas Operator');
        $response->assertSee('Kajian Rutin Fatwa MUI');
        $response->assertSee('Fatimah Az Zahra');
        $response->assertViewHas('assignedPerms');
        $response->assertViewHas('operatorData');
    }

    public function test_notification_poll_requires_authentication(): void
    {
        $response = $this->getJson(route('notifications.poll'));
        $response->assertStatus(401);
    }

    public function test_admin_receives_all_notifications_on_poll(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        ChatSession::create([
            'session_token' => 'token-poll-1',
            'nama_pengunjung' => 'Zaid bin Tsabit',
            'topik' => 'Warisan',
            'status' => 'menunggu',
            'last_activity_at' => now(),
        ]);

        Konsultasi::create([
            'nama' => 'Maryam Ulfah',
            'email' => 'maryam@example.com',
            'usia' => 25,
            'jenis_kelamin' => 'Perempuan',
            'kab_kota' => 'Muaro Jambi',
            'kategori' => 'Pernikahan',
            'pertanyaan' => 'Bagaimana syarat rukun mahar menurut fatwa?',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->getJson(route('notifications.poll'));

        $response->assertStatus(200);
        $response->assertJsonPath('total', 2);
        $response->assertJsonPath('chat_count', 1);
        $response->assertJsonPath('konsultasi_count', 1);
        $response->assertJsonStructure([
            'total',
            'chat_count',
            'konsultasi_count',
            'items' => [
                '*' => ['id', 'type', 'title', 'badge', 'sender', 'message', 'url'],
            ],
        ]);
    }

    public function test_operator_receives_only_permitted_notifications(): void
    {
        // Operator only has permission 'konsultasi', not 'livechat'
        $operator = User::factory()->create([
            'role' => 'operator',
            'menu_permissions' => ['konsultasi'],
        ]);

        ChatSession::create([
            'session_token' => 'token-poll-chat',
            'nama_pengunjung' => 'Pengunjung Chat',
            'status' => 'menunggu',
            'last_activity_at' => now(),
        ]);

        Konsultasi::create([
            'nama' => 'Jamaah Tanya',
            'email' => 'jamaah@example.com',
            'usia' => 35,
            'jenis_kelamin' => 'Laki-laki',
            'kab_kota' => 'Batanghari',
            'kategori' => 'Zakat',
            'pertanyaan' => 'Berapa nisab zakat perak terkini?',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($operator)->getJson(route('notifications.poll'));

        $response->assertStatus(200);
        // Chat count must be 0 for this operator because no livechat permission
        $response->assertJsonPath('chat_count', 0);
        $response->assertJsonPath('konsultasi_count', 1);
        $response->assertJsonPath('total', 1);
    }
}
