<?php

namespace App\Http\Controllers;

use App\Models\ChatFaq;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Setting;
use App\Services\LiveChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LiveChatController extends Controller
{
    /**
     * Dapatkan status awal live chat (apakah jam operasional, list FAQ, dan sesi yang tersimpan).
     */
    public function init(Request $request): JsonResponse
    {
        $token = $request->input('token');
        $session = null;
        $messages = [];

        if ($token) {
            $session = ChatSession::with(['operator'])
                ->where('session_token', $token)
                ->first();

            if ($session) {
                $messages = $session->messages()
                    ->select('id', 'sender_type', 'sender_name', 'pesan', 'created_at')
                    ->get()
                    ->map(function ($m) {
                        return [
                            'id' => $m->id,
                            'sender_type' => $m->sender_type,
                            'sender_name' => $m->sender_name,
                            'pesan' => $m->pesan,
                            'time' => $m->created_at->format('H:i'),
                        ];
                    });
            }
        }

        $isOperational = LiveChatService::isOperational();
        $faqs = ChatFaq::active()->select('id', 'pertanyaan', 'jawaban', 'kategori')->get();

        return response()->json([
            'is_operational' => $isOperational,
            'schedule_text' => LiveChatService::getOperationalScheduleText(),
            'greeting_text' => Setting::get('chat_bot_greeting', 'Assalamu\'alaikum! Selamat datang di Layanan Bantuan Online MUI Batanghari.'),
            'offline_text' => Setting::get('chat_offline_message', 'Saat ini kantor sedang di luar jam operasional. Silakan pilih FAQ atau kirim pesan.'),
            'faqs' => $faqs,
            'session' => $session ? [
                'token' => $session->session_token,
                'status' => $session->status,
                'nama' => $session->nama_pengunjung,
                'antrian_nomor' => $session->antrian_nomor,
                'antrian_position' => $session->antrian_position,
                'estimasi_tunggu' => $session->estimasi_tunggu_menit,
                'operator_name' => $session->operator ? $session->operator->name : null,
                'operator_avatar' => $session->operator ? $session->operator->foto_url : null,
            ] : null,
            'messages' => $messages,
        ]);
    }

    /**
     * Memulai sesi chat baru bagi pengunjung.
     */
    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama_pengunjung' => 'required|string|max:150',
            'email_pengunjung' => 'nullable|email|max:150',
            'nohp_pengunjung' => 'nullable|string|max:30',
            'topik' => 'nullable|string|max:150',
            'pesan_awal' => 'nullable|string|max:2000',
            'is_bot' => 'nullable|boolean',
        ]);

        $session = LiveChatService::createSession($validated, $validated['pesan_awal'] ?? null);

        $messages = $session->messages()
            ->select('id', 'sender_type', 'sender_name', 'pesan', 'created_at')
            ->get()
            ->map(function ($m) {
                return [
                    'id' => $m->id,
                    'sender_type' => $m->sender_type,
                    'sender_name' => $m->sender_name,
                    'pesan' => $m->pesan,
                    'time' => $m->created_at->format('H:i'),
                ];
            });

        return response()->json([
            'success' => true,
            'session' => [
                'token' => $session->session_token,
                'status' => $session->status,
                'nama' => $session->nama_pengunjung,
                'antrian_nomor' => $session->antrian_nomor,
                'antrian_position' => $session->antrian_position,
                'estimasi_tunggu' => $session->estimasi_tunggu_menit,
                'operator_name' => null,
                'operator_avatar' => null,
            ],
            'messages' => $messages,
        ]);
    }

    /**
     * Kirim pesan dari pengunjung ke sesi chat.
     */
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'pesan' => 'required|string|max:2000',
        ]);

        $session = ChatSession::where('session_token', $validated['token'])->first();
        if (! $session) {
            return response()->json(['error' => 'Sesi chat tidak ditemukan.'], 404);
        }

        if ($session->status === 'selesai') {
            return response()->json(['error' => 'Sesi chat telah selesai.'], 400);
        }

        $session->update(['last_activity_at' => now()]);

        $message = ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender_type' => 'pengunjung',
            'sender_name' => $session->nama_pengunjung,
            'pesan' => $validated['pesan'],
        ]);

        $botReply = null;
        if ($session->status === 'bot') {
            $botMsg = LiveChatService::processBotQuery($session, $validated['pesan']);
            $botReply = [
                'id' => $botMsg->id,
                'sender_type' => 'bot',
                'sender_name' => $botMsg->sender_name,
                'pesan' => $botMsg->pesan,
                'time' => $botMsg->created_at->format('H:i'),
            ];
        }

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $message->id,
                'sender_type' => 'pengunjung',
                'sender_name' => $message->sender_name,
                'pesan' => $message->pesan,
                'time' => $message->created_at->format('H:i'),
            ],
            'bot_reply' => $botReply,
        ]);
    }

    /**
     * Memilih pertanyaan FAQ cepat pada mode Bot.
     */
    public function askFaq(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'faq_id' => 'required|integer',
        ]);

        $session = ChatSession::where('session_token', $validated['token'])->first();
        if (! $session) {
            return response()->json(['error' => 'Sesi chat tidak ditemukan.'], 404);
        }

        $faq = ChatFaq::find($validated['faq_id']);
        if (! $faq) {
            return response()->json(['error' => 'FAQ tidak ditemukan.'], 404);
        }

        // Simpan pertanyaan pengunjung
        ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender_type' => 'pengunjung',
            'sender_name' => $session->nama_pengunjung,
            'pesan' => $faq->pertanyaan,
        ]);

        // Simpan jawaban bot
        $answerText = "📌 **{$faq->pertanyaan}**\n\n{$faq->jawaban}";
        $botMsg = ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender_type' => 'bot',
            'sender_name' => 'MUI Bot (Asisten Virtual)',
            'pesan' => $answerText,
        ]);

        return response()->json([
            'success' => true,
            'bot_reply' => [
                'id' => $botMsg->id,
                'sender_type' => 'bot',
                'sender_name' => $botMsg->sender_name,
                'pesan' => $botMsg->pesan,
                'time' => $botMsg->created_at->format('H:i'),
            ],
        ]);
    }

    /**
     * Polling real-time update untuk pengunjung.
     */
    public function poll(Request $request): JsonResponse
    {
        $token = $request->query('token');
        $lastId = (int) $request->query('last_id', 0);

        if (! $token) {
            return response()->json(['error' => 'Token wajib disertakan.'], 400);
        }

        $session = ChatSession::with(['operator'])->where('session_token', $token)->first();
        if (! $session) {
            return response()->json(['status' => 'not_found'], 404);
        }

        $session->update(['last_activity_at' => now()]);

        $newMessages = ChatMessage::where('chat_session_id', $session->id)
            ->where('id', '>', $lastId)
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($m) {
                return [
                    'id' => $m->id,
                    'sender_type' => $m->sender_type,
                    'sender_name' => $m->sender_name,
                    'pesan' => $m->pesan,
                    'time' => $m->created_at->format('H:i'),
                ];
            });

        return response()->json([
            'status' => $session->status,
            'antrian_nomor' => $session->antrian_nomor,
            'antrian_position' => $session->antrian_position,
            'estimasi_tunggu' => $session->estimasi_tunggu_menit,
            'operator_name' => $session->operator ? $session->operator->name : null,
            'operator_avatar' => $session->operator ? $session->operator->foto_url : null,
            'messages' => $newMessages,
        ]);
    }

    /**
     * Server-Sent Events (SSE) stream untuk update instan tanpa jeda polling.
     */
    public function stream(Request $request): StreamedResponse
    {
        $token = $request->query('token');
        $lastId = (int) $request->query('last_id', 0);

        return new StreamedResponse(function () use ($token, $lastId) {
            $session = ChatSession::with(['operator'])->where('session_token', $token)->first();
            if (! $session) {
                echo "event: error\ndata: ".json_encode(['message' => 'Session not found'])."\n\n";
                flush();

                return;
            }

            // Loop singkat (hingga 25 detik per koneksi sebelum auto-reconnect client)
            $startTime = time();
            $currentLastId = $lastId;

            while (time() - $startTime < 25) {
                if (connection_aborted()) {
                    break;
                }

                $session->refresh();

                $messages = ChatMessage::where('chat_session_id', $session->id)
                    ->where('id', '>', $currentLastId)
                    ->orderBy('id', 'asc')
                    ->get();

                if ($messages->isNotEmpty()) {
                    $payload = [
                        'status' => $session->status,
                        'antrian_position' => $session->antrian_position,
                        'estimasi_tunggu' => $session->estimasi_tunggu_menit,
                        'operator_name' => $session->operator ? $session->operator->name : null,
                        'operator_avatar' => $session->operator ? $session->operator->foto_url : null,
                        'messages' => $messages->map(fn ($m) => [
                            'id' => $m->id,
                            'sender_type' => $m->sender_type,
                            'sender_name' => $m->sender_name,
                            'pesan' => $m->pesan,
                            'time' => $m->created_at->format('H:i'),
                        ]),
                    ];

                    echo "event: message\ndata: ".json_encode($payload)."\n\n";
                    ob_flush();
                    flush();

                    $currentLastId = $messages->last()->id;
                } else {
                    // Send heartbeat
                    echo ": heartbeat\n\n";
                    ob_flush();
                    flush();
                }

                usleep(1500000); // 1.5 detik per tick
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Selesaikan sesi chat oleh pengunjung.
     */
    public function close(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string',
        ]);

        $session = ChatSession::where('session_token', $validated['token'])->first();
        if ($session && $session->status !== 'selesai') {
            $session->update([
                'status' => 'selesai',
                'closed_at' => now(),
            ]);

            ChatMessage::create([
                'chat_session_id' => $session->id,
                'sender_type' => 'system',
                'sender_name' => 'Sistem Antrian MUI',
                'pesan' => 'Percakapan telah diakhiri oleh pengunjung. Terima kasih telah menghubungi MUI Batanghari.',
            ]);
        }

        return response()->json(['success' => true]);
    }
}
