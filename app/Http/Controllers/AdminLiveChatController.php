<?php

namespace App\Http\Controllers;

use App\Models\ChatFaq;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminLiveChatController extends Controller
{
    /**
     * Halaman utama Live Chat Operator & Admin.
     */
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'antrian');
        $search = $request->query('search');

        // 1. Antrian Menunggu
        $waitingSessions = ChatSession::where('status', 'menunggu')
            ->orderBy('created_at', 'asc')
            ->get();

        // 2. Chat Berlangsung
        $activeSessions = ChatSession::where('status', 'aktif')
            ->with(['operator', 'messages' => fn ($q) => $q->latest()->limit(1)])
            ->orderBy('last_activity_at', 'desc')
            ->get();

        // 3. Riwayat Percakapan (Selesai / Bot)
        $historyQuery = ChatSession::with(['operator', 'messages'])
            ->whereIn('status', ['selesai', 'bot']);

        if ($search) {
            $historyQuery->where(function ($q) use ($search) {
                $q->where('nama_pengunjung', 'like', "%{$search}%")
                    ->orWhere('email_pengunjung', 'like', "%{$search}%")
                    ->orWhere('nohp_pengunjung', 'like', "%{$search}%")
                    ->orWhere('session_token', 'like', "%{$search}%");
            });
        }

        $historySessions = $historyQuery->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        // 4. FAQ Chatbot
        $faqs = ChatFaq::orderBy('urutan', 'asc')->get();

        return view('admin.livechat.index', compact(
            'tab',
            'waitingSessions',
            'activeSessions',
            'historySessions',
            'faqs',
            'search'
        ));
    }

    /**
     * Petugas mengklik tombol "Balas Chat" untuk mengambil antrian pengunjung.
     */
    public function take(ChatSession $session): RedirectResponse|JsonResponse
    {
        if ($session->status === 'selesai') {
            return back()->with('error', 'Sesi chat ini sudah selesai.');
        }

        $operator = auth()->user();

        $session->update([
            'operator_id' => $operator->id,
            'status' => 'aktif',
            'started_at' => $session->started_at ?? now(),
            'last_activity_at' => now(),
        ]);

        $displayName = $operator->name_gelar ?: $operator->name;

        // Kirim sapaan pembuka dari petugas
        ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender_type' => 'operator',
            'sender_id' => $operator->id,
            'sender_name' => $displayName,
            'pesan' => "Assalamu'alaikum Warahmatullahi Wabarakatuh. Halo {$session->nama_pengunjung}, saya {$displayName} dari MUI Batanghari siap melayani Anda. Ada yang bisa kami bantu?",
        ]);

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'session_id' => $session->id,
                'redirect_url' => route('admin.livechat.show', $session->id),
            ]);
        }

        return redirect()->route('admin.livechat.show', $session->id)
            ->with('success', "Anda berhasil tersambung dengan {$session->nama_pengunjung}. Silakan mulai percakapan.");
    }

    /**
     * Ruang percakapan interaktif petugas.
     */
    public function show(ChatSession $session): View
    {
        // Jika sesi masih menunggu dan dibuka langsung oleh petugas, aktifkan dan tugaskan ke petugas ini
        if ($session->status === 'menunggu') {
            $session->update([
                'operator_id' => auth()->id(),
                'status' => 'aktif',
                'started_at' => $session->started_at ?? now(),
                'last_activity_at' => now(),
            ]);
        }

        $session->load(['operator', 'messages']);

        return view('admin.livechat.show', compact('session'));
    }

    /**
     * Operator mengirim balasan chat.
     */
    public function sendMessage(Request $request, ChatSession $session): JsonResponse
    {
        $validated = $request->validate([
            'pesan' => 'required|string|max:2500',
        ]);

        $operator = auth()->user();
        $displayName = $operator->name_gelar ?: $operator->name;

        $msg = ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender_type' => 'operator',
            'sender_id' => $operator->id,
            'sender_name' => $displayName,
            'pesan' => $validated['pesan'],
        ]);

        $session->update([
            'last_activity_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $msg->id,
                'sender_type' => 'operator',
                'sender_name' => $msg->sender_name,
                'pesan' => $msg->pesan,
                'time' => $msg->created_at->format('H:i'),
            ],
        ]);
    }

    /**
     * Polling percakapan pada ruang chat petugas.
     */
    public function pollSession(Request $request, ChatSession $session): JsonResponse
    {
        $lastId = (int) $request->query('last_id', 0);

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
            'messages' => $newMessages,
        ]);
    }

    /**
     * Polling ringkasan antrian & notifikasi petugas untuk topbar / audio alert.
     */
    public function pollOverview(): JsonResponse
    {
        $waitingCount = ChatSession::where('status', 'menunggu')->count();
        $myActiveCount = ChatSession::where('status', 'aktif')
            ->where('operator_id', auth()->id())
            ->count();

        $latestWaiting = ChatSession::where('status', 'menunggu')->latest()->first();

        return response()->json([
            'waiting_count' => $waitingCount,
            'my_active_count' => $myActiveCount,
            'latest_waiting_name' => $latestWaiting ? $latestWaiting->nama_pengunjung : null,
            'latest_waiting_id' => $latestWaiting ? $latestWaiting->id : null,
        ]);
    }

    /**
     * Selesaikan sesi chat oleh petugas.
     */
    public function close(ChatSession $session): RedirectResponse|JsonResponse
    {
        $session->update([
            'status' => 'selesai',
            'closed_at' => now(),
        ]);

        ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender_type' => 'system',
            'sender_name' => 'Sistem Antrian MUI',
            'pesan' => 'Sesi percakapan telah ditandai selesai oleh petugas. Terima kasih atas partisipasi Anda.',
        ]);

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('admin.livechat.index', ['tab' => 'riwayat'])
            ->with('success', "Sesi percakapan dengan {$session->nama_pengunjung} berhasil diselesaikan.");
    }

    /**
     * Ekspor riwayat percakapan ke format CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $filename = 'riwayat-livechat-mui-batanghari-'.date('Ymd-His').'.csv';

        $sessions = ChatSession::with(['operator', 'messages'])
            ->orderBy('created_at', 'desc')
            ->get();

        return new StreamedResponse(function () use ($sessions) {
            $handle = fopen('php://output', 'w');

            // Tambahkan BOM untuk UTF-8 compatibility di Excel
            fwrite($handle, "\xEF\xBB\xBF");

            // Header kolom
            fputcsv($handle, [
                'ID Sesi',
                'Nomor Antrian',
                'Tanggal Masuk',
                'Nama Pengunjung',
                'Email',
                'No. Telepon / WA',
                'Status',
                'Nama Petugas',
                'Waktu Mulai',
                'Waktu Selesai',
                'Total Pesan',
                'Ringkasan Percakapan',
            ], ';');

            foreach ($sessions as $s) {
                $transcriptSummary = $s->messages->map(function ($m) {
                    return "[{$m->created_at->format('H:i')}] {$m->sender_name}: {$m->pesan}";
                })->implode(" | \n");

                fputcsv($handle, [
                    $s->id,
                    '#'.$s->antrian_nomor,
                    $s->created_at->format('d/m/Y H:i:s'),
                    $s->nama_pengunjung,
                    $s->email_pengunjung ?: '-',
                    $s->nohp_pengunjung ?: '-',
                    strtoupper($s->status),
                    $s->operator ? ($s->operator->name_gelar ?: $s->operator->name) : 'Bot / Belum Ada',
                    $s->started_at ? $s->started_at->format('d/m/Y H:i:s') : '-',
                    $s->closed_at ? $s->closed_at->format('d/m/Y H:i:s') : '-',
                    $s->messages->count(),
                    $transcriptSummary,
                ], ';');
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Tampilan transkrip percakapan cetak / detail.
     */
    public function transcript(ChatSession $session): View
    {
        $session->load(['operator', 'messages']);

        return view('admin.livechat.transcript', compact('session'));
    }

    /**
     * Tambah FAQ Chatbot baru.
     */
    public function storeFaq(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'pertanyaan' => 'required|string|max:255',
            'jawaban' => 'required|string',
            'kategori' => 'nullable|string|max:100',
            'urutan' => 'nullable|integer',
        ]);

        $validated['urutan'] = $validated['urutan'] ?? 0;
        $validated['is_active'] = true;

        ChatFaq::create($validated);

        return redirect()->route('admin.livechat.index', ['tab' => 'faq'])
            ->with('success', 'FAQ Chatbot baru berhasil ditambahkan.');
    }

    /**
     * Update FAQ Chatbot.
     */
    public function updateFaq(Request $request, ChatFaq $faq): RedirectResponse
    {
        $validated = $request->validate([
            'pertanyaan' => 'required|string|max:255',
            'jawaban' => 'required|string',
            'kategori' => 'nullable|string|max:100',
            'urutan' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $faq->update($validated);

        return redirect()->route('admin.livechat.index', ['tab' => 'faq'])
            ->with('success', 'FAQ Chatbot berhasil diperbarui.');
    }

    /**
     * Hapus FAQ Chatbot.
     */
    public function deleteFaq(ChatFaq $faq): RedirectResponse
    {
        $faq->delete();

        return redirect()->route('admin.livechat.index', ['tab' => 'faq'])
            ->with('success', 'FAQ Chatbot berhasil dihapus.');
    }
}
