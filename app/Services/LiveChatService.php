<?php

namespace App\Services;

use App\Models\ChatFaq;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Str;

class LiveChatService
{
    /**
     * Cek apakah saat ini berada dalam jam operasional layanan live chat.
     */
    public static function isOperational(): bool
    {
        $enabled = Setting::get('chat_is_enabled', '1');
        if ($enabled === '0' || $enabled === false) {
            return false;
        }

        $now = Carbon::now('Asia/Jakarta');

        // Cek hari operasional (1 = Senin, ..., 7 = Minggu)
        $operationalDays = explode(',', (string) Setting::get('chat_operational_days', '1,2,3,4,5'));
        $dayOfWeek = (string) $now->isoWeekday(); // 1 (Senin) s.d. 7 (Minggu)

        if (! in_array($dayOfWeek, $operationalDays, true)) {
            return false;
        }

        $startTime = Setting::get('chat_operational_start', '08:00');
        $endTime = Setting::get('chat_operational_end', '16:00');

        $start = Carbon::createFromTimeString($startTime, 'Asia/Jakarta');
        $end = Carbon::createFromTimeString($endTime, 'Asia/Jakarta');

        return $now->between($start, $end);
    }

    /**
     * Dapatkan teks jam operasional yang ramah pengguna.
     */
    public static function getOperationalScheduleText(): string
    {
        $start = Setting::get('chat_operational_start', '08:00');
        $end = Setting::get('chat_operational_end', '16:00');
        $days = self::formatOperationalDays((string) Setting::get('chat_operational_days', '1,2,3,4,5'));

        return "{$days}, {$start} – {$end} WIB";
    }

    /**
     * Ubah daftar hari ISO ("1,2,3,4,5") menjadi teks ("Senin – Jumat", "Senin, Rabu, Jumat", "Setiap hari").
     */
    public static function formatOperationalDays(string $days): string
    {
        $names = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

        $list = collect(explode(',', $days))
            ->map(fn (string $day): int => (int) trim($day))
            ->filter(fn (int $day): bool => isset($names[$day]))
            ->unique()
            ->sort()
            ->values();

        if ($list->isEmpty()) {
            return 'Tidak ada hari layanan';
        }

        if ($list->count() === 7) {
            return 'Setiap hari';
        }

        $contiguous = $list->last() - $list->first() + 1 === $list->count();

        if ($contiguous && $list->count() >= 3) {
            return $names[$list->first()].' – '.$names[$list->last()];
        }

        return $list->map(fn (int $day): string => $names[$day])->implode(', ');
    }

    /**
     * Hitung nomor antrian harian berikutnya.
     */
    public static function getNextQueueNumber(): int
    {
        $today = Carbon::today('Asia/Jakarta');

        $lastNumber = ChatSession::whereDate('created_at', $today)->max('antrian_nomor') ?? 0;

        return (int) $lastNumber + 1;
    }

    /**
     * Buat sesi chat baru untuk pengunjung.
     */
    public static function createSession(array $data, ?string $initialMessage = null): ChatSession
    {
        $isOperational = self::isOperational();
        $isBot = isset($data['is_bot']) ? (bool) $data['is_bot'] : ! $isOperational;

        $token = 'chat_'.Str::random(32);
        $queueNumber = self::getNextQueueNumber();

        $session = ChatSession::create([
            'session_token' => $token,
            'nama_pengunjung' => $data['nama_pengunjung'],
            'email_pengunjung' => $data['email_pengunjung'] ?? null,
            'nohp_pengunjung' => $data['nohp_pengunjung'] ?? null,
            'topik' => $data['topik'] ?? 'Layanan Umum',
            'status' => $isBot ? 'bot' : 'menunggu',
            'antrian_nomor' => $queueNumber,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'last_activity_at' => now(),
        ]);

        if (! empty($initialMessage)) {
            ChatMessage::create([
                'chat_session_id' => $session->id,
                'sender_type' => 'pengunjung',
                'sender_name' => $session->nama_pengunjung,
                'pesan' => $initialMessage,
            ]);
        }

        // Kirim sapaan pembuka
        if ($isBot) {
            $greeting = Setting::get(
                'chat_offline_message',
                'Mohon maaf, saat ini kantor MUI Batanghari sedang di luar jam operasional. Silakan pilih pertanyaan umum di bawah ini atau tinggalkan pesan untuk petugas kami.'
            );

            ChatMessage::create([
                'chat_session_id' => $session->id,
                'sender_type' => 'bot',
                'sender_name' => 'MUI Bot (Asisten Virtual)',
                'pesan' => $greeting,
            ]);
        } else {
            $pos = $session->antrian_position;
            $est = $session->estimasi_tunggu_menit;

            $systemMsg = "Terima kasih telah menghubungi Layanan Bantuan MUI Batanghari. Anda berada di nomor antrian #{$queueNumber} (Urutan ke-{$pos} dalam antrian). Estimasi waktu tunggu: ~{$est} menit. Mohon menunggu, petugas kami akan segera membalas.";

            ChatMessage::create([
                'chat_session_id' => $session->id,
                'sender_type' => 'system',
                'sender_name' => 'Sistem Antrian MUI',
                'pesan' => $systemMsg,
            ]);
        }

        return $session;
    }

    /**
     * Respon FAQ otomatis oleh bot.
     */
    public static function processBotQuery(ChatSession $session, string $query): ChatMessage
    {
        // Cari kecocokan FAQ
        $faq = ChatFaq::active()
            ->where(function ($q) use ($query) {
                $q->where('pertanyaan', 'LIKE', "%{$query}%")
                    ->orWhere('jawaban', 'LIKE', "%{$query}%")
                    ->orWhere('kategori', 'LIKE', "%{$query}%");
            })
            ->first();

        if ($faq) {
            $answer = "📌 **{$faq->pertanyaan}**\n\n{$faq->jawaban}";
        } else {
            $answer = 'Terima kasih atas pertanyaan Anda. Saat ini pertanyaan tersebut belum tercantum di FAQ cepat. Pesan Anda telah kami catat dalam sistem dan akan segera dibalas oleh petugas MUI Batanghari pada jam kerja operasional.';
        }

        return ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender_type' => 'bot',
            'sender_name' => 'MUI Bot (Asisten Virtual)',
            'pesan' => $answer,
        ]);
    }
}
