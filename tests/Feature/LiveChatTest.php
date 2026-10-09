<?php

namespace Tests\Feature;

use App\Models\ChatFaq;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\User;
use Database\Seeders\LiveChatSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LiveChatSeeder::class);
    }

    public function test_public_user_can_initialize_livechat(): void
    {
        $response = $this->get(route('livechat.init'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'is_operational',
            'schedule_text',
            'greeting_text',
            'offline_text',
            'faqs',
        ]);
    }

    public function test_public_user_can_start_chat_session_and_enter_queue(): void
    {
        $response = $this->postJson(route('livechat.start'), [
            'nama_pengunjung' => 'Budi Santoso',
            'email_pengunjung' => 'budi@example.com',
            'nohp_pengunjung' => '08123456789',
            'topik' => 'Konsultasi Syariah',
            'pesan_awal' => 'Saya ingin bertanya tentang hukum zakat mal.',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure([
            'session' => [
                'token',
                'status',
                'nama',
                'antrian_nomor',
            ],
            'messages',
        ]);

        $this->assertDatabaseHas('chat_sessions', [
            'nama_pengunjung' => 'Budi Santoso',
            'email_pengunjung' => 'budi@example.com',
        ]);

        $this->assertDatabaseHas('chat_messages', [
            'sender_name' => 'Budi Santoso',
            'pesan' => 'Saya ingin bertanya tentang hukum zakat mal.',
        ]);
    }

    public function test_queue_position_and_estimated_time_increments_for_multiple_sessions(): void
    {
        $session1 = ChatSession::create([
            'session_token' => 'token_1',
            'nama_pengunjung' => 'Warga 1',
            'status' => 'menunggu',
            'antrian_nomor' => 1,
        ]);

        $session2 = ChatSession::create([
            'session_token' => 'token_2',
            'nama_pengunjung' => 'Warga 2',
            'status' => 'menunggu',
            'antrian_nomor' => 2,
        ]);

        $this->assertEquals(1, $session1->antrian_position);
        $this->assertEquals(2, $session2->antrian_position);
        $this->assertGreaterThanOrEqual(2, $session2->estimasi_tunggu_menit);
    }

    public function test_visitor_can_send_message_and_poll_updates(): void
    {
        $session = ChatSession::create([
            'session_token' => 'token_test_poll',
            'nama_pengunjung' => 'Ahmad',
            'status' => 'aktif',
            'antrian_nomor' => 1,
        ]);

        $response = $this->postJson(route('livechat.send'), [
            'token' => $session->session_token,
            'pesan' => 'Halo apakah ada petugas?',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('message.pesan', 'Halo apakah ada petugas?');

        $pollResponse = $this->getJson(route('livechat.poll', [
            'token' => $session->session_token,
            'last_id' => 0,
        ]));

        $pollResponse->assertStatus(200);
        $pollResponse->assertJsonPath('status', 'aktif');
        $pollResponse->assertJsonCount(1, 'messages');
    }

    public function test_chatbot_faq_auto_responds_to_queries(): void
    {
        $session = ChatSession::create([
            'session_token' => 'token_bot_test',
            'nama_pengunjung' => 'Siti',
            'status' => 'bot',
            'antrian_nomor' => 1,
        ]);

        $faq = ChatFaq::first();

        $response = $this->postJson(route('livechat.ask-faq'), [
            'token' => $session->session_token,
            'faq_id' => $faq->id,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertDatabaseHas('chat_messages', [
            'chat_session_id' => $session->id,
            'sender_type' => 'bot',
        ]);
    }

    public function test_operator_can_take_chat_with_balas_button(): void
    {
        $operator = User::factory()->create(['role' => 'operator', 'name' => 'Petugas Layanan']);

        $session = ChatSession::create([
            'session_token' => 'token_take_test',
            'nama_pengunjung' => 'Pemohon Konsultasi',
            'status' => 'menunggu',
            'antrian_nomor' => 1,
        ]);

        $response = $this->actingAs($operator)->post(route('admin.livechat.take', $session->id));

        $response->assertRedirect(route('admin.livechat.show', $session->id));
        $session->refresh();

        $this->assertEquals('aktif', $session->status);
        $this->assertEquals($operator->id, $session->operator_id);

        $this->assertDatabaseHas('chat_messages', [
            'chat_session_id' => $session->id,
            'sender_type' => 'operator',
            'sender_id' => $operator->id,
        ]);
    }

    public function test_operator_can_send_reply_and_close_session(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);

        $session = ChatSession::create([
            'session_token' => 'token_reply_test',
            'nama_pengunjung' => 'Warga',
            'status' => 'aktif',
            'operator_id' => $operator->id,
            'antrian_nomor' => 1,
        ]);

        $sendResponse = $this->actingAs($operator)->postJson(route('admin.livechat.send', $session->id), [
            'pesan' => 'Wa\'alaikumussalam, silakan sampaikan pertanyaan Anda.',
        ]);

        $sendResponse->assertStatus(200);
        $this->assertDatabaseHas('chat_messages', [
            'chat_session_id' => $session->id,
            'sender_type' => 'operator',
            'pesan' => 'Wa\'alaikumussalam, silakan sampaikan pertanyaan Anda.',
        ]);

        $closeResponse = $this->actingAs($operator)->post(route('admin.livechat.close', $session->id));
        $closeResponse->assertRedirect();

        $session->refresh();
        $this->assertEquals('selesai', $session->status);
    }

    public function test_operator_can_export_csv_and_view_transcript(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);

        $session = ChatSession::create([
            'session_token' => 'token_export_test',
            'nama_pengunjung' => 'Warga Export',
            'status' => 'selesai',
            'antrian_nomor' => 1,
        ]);

        ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender_type' => 'pengunjung',
            'sender_name' => 'Warga Export',
            'pesan' => 'Tes pesan transkrip',
        ]);

        // Export CSV
        $csvResponse = $this->actingAs($operator)->get(route('admin.livechat.export-csv'));
        $csvResponse->assertStatus(200);
        $this->assertTrue(str_contains($csvResponse->headers->get('content-type'), 'text/csv'));

        // Transcript View
        $transcriptResponse = $this->actingAs($operator)->get(route('admin.livechat.transcript', $session->id));
        $transcriptResponse->assertStatus(200);
        $transcriptResponse->assertSee('Warga Export');
        $transcriptResponse->assertSee('Tes pesan transkrip');
    }

    public function test_overview_polling_endpoint_provides_operator_notifications(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);

        ChatSession::create([
            'session_token' => 'token_notif_test',
            'nama_pengunjung' => 'Pemohon Baru',
            'status' => 'menunggu',
            'antrian_nomor' => 5,
        ]);

        $response = $this->actingAs($operator)->getJson(route('admin.livechat.poll-overview'));

        $response->assertStatus(200);
        $response->assertJson([
            'waiting_count' => 1,
            'latest_waiting_name' => 'Pemohon Baru',
        ]);
    }
}
