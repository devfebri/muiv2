<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use App\Models\Konsultasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NotificationController extends Controller
{
    /**
     * Endpoint polling notifikasi sistem secara realtime untuk topbar header.
     */
    public function poll(Request $request): JsonResponse
    {
        $user = $request->user();

        $items = [];
        $chatCount = 0;
        $konsultasiCount = 0;
        $latestWaitingChatId = null;

        // 1. Notifikasi Live Chat Menunggu Respon
        if ($user->isAdmin() || $user->hasMenuPermission('livechat')) {
            $chatCount = ChatSession::where('status', 'menunggu')->count();
            $waitingChats = ChatSession::where('status', 'menunggu')
                ->latest()
                ->take(5)
                ->get();

            if ($waitingChats->isNotEmpty()) {
                $latestWaitingChatId = $waitingChats->first()->id;
            }

            foreach ($waitingChats as $chat) {
                $items[] = [
                    'id' => 'chat-'.$chat->id,
                    'type' => 'livechat',
                    'title' => 'Live Chat Menunggu',
                    'badge' => 'Chat Antrian',
                    'badge_class' => 'badge-danger',
                    'icon' => 'mdi mdi-chat-processing',
                    'icon_class' => 'text-danger',
                    'bg_class' => 'bg-soft-danger',
                    'sender' => $chat->nama_pengunjung,
                    'message' => 'Menunggu respon di antrian #'.$chat->antrian_nomor.($chat->topik ? ' ('.$chat->topik.')' : ''),
                    'time' => $chat->created_at->diffForHumans(),
                    'created_timestamp' => $chat->created_at->timestamp,
                    'url' => route('admin.livechat.show', $chat->id),
                ];
            }
        }

        // 2. Notifikasi Konsultasi Syariah / Tanya Ulama Baru
        if ($user->isAdmin() || $user->hasMenuPermission('konsultasi')) {
            $konsultasiCount = Konsultasi::where('status', 'pending')->count();
            $pendingKonsultasi = Konsultasi::where('status', 'pending')
                ->latest()
                ->take(5)
                ->get();

            foreach ($pendingKonsultasi as $k) {
                $targetRoute = $user->isOperator() ? 'operator.konsultasi.index' : 'admin.konsultasi.index';
                $url = route($targetRoute, ['detail_id' => $k->id]);

                $items[] = [
                    'id' => 'kon-'.$k->id,
                    'type' => 'konsultasi',
                    'title' => 'Tanya Ulama Masuk',
                    'badge' => 'Konsultasi',
                    'badge_class' => 'badge-warning',
                    'icon' => 'mdi mdi-forum',
                    'icon_class' => 'text-warning',
                    'bg_class' => 'bg-soft-warning',
                    'sender' => $k->nama,
                    'message' => Str::limit($k->pertanyaan, 55),
                    'time' => $k->created_at->diffForHumans(),
                    'created_timestamp' => $k->created_at->timestamp,
                    'url' => $url,
                ];
            }
        }

        // Urutkan notifikasi berdasarkan waktu terbaru
        usort($items, function ($a, $b) {
            return $b['created_timestamp'] <=> $a['created_timestamp'];
        });

        // Batasi maksimal 6 item di dropdown
        $items = array_slice($items, 0, 6);
        $total = $chatCount + $konsultasiCount;

        return response()->json([
            'total' => $total,
            'chat_count' => $chatCount,
            'konsultasi_count' => $konsultasiCount,
            'latest_chat_id' => $latestWaitingChatId,
            'items' => $items,
        ]);
    }
}
