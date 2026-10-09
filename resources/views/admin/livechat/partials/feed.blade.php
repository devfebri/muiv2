{{--
    Ringkasan sesi live chat (antrian menunggu & percakapan aktif) dalam bentuk JSON.
    Dipakai halaman daftar & ruang chat untuk render awal sekaligus penyegaran otomatis
    (halaman mengambil ulang HTML-nya sendiri lalu membaca elemen #lc-feed).
    Variabel: $waiting, $active (koleksi ChatSession).
--}}
@php
    $sessions = $waiting->concat($active);
    $lastMessages = collect();
    if ($sessions->isNotEmpty()) {
        $lastIds = \App\Models\ChatMessage::query()
            ->whereIn('chat_session_id', $sessions->pluck('id'))
            ->where('sender_type', '!=', 'system')
            ->selectRaw('MAX(id) as id')
            ->groupBy('chat_session_id')
            ->pluck('id');
        $lastMessages = \App\Models\ChatMessage::query()->whereIn('id', $lastIds)->get()->keyBy('chat_session_id');
    }
    $me = auth()->id();
    $toFeed = function ($s) use ($lastMessages, $me) {
        $last = $lastMessages->get($s->id);

        return [
            'id' => $s->id,
            'nama' => $s->nama_pengunjung,
            'inisial' => mb_strtoupper(mb_substr(trim($s->nama_pengunjung), 0, 1)) ?: '?',
            'antrian' => $s->antrian_nomor,
            'topik' => $s->topik ?: 'Layanan Umum',
            'nohp' => $s->nohp_pengunjung,
            'email' => $s->email_pengunjung,
            'status' => $s->status,
            'operator' => $s->operator ? ($s->operator->name_gelar ?: $s->operator->name) : null,
            'mine' => $s->operator_id !== null && (int) $s->operator_id === (int) $me,
            'masuk' => $s->created_at?->toIso8601String(),
            'aktivitas' => ($s->last_activity_at ?? $s->created_at)?->toIso8601String(),
            'pratinjau' => $last ? \Illuminate\Support\Str::limit(preg_replace('/\s+/u', ' ', $last->pesan), 110) : null,
            'pengirim' => $last?->sender_type,
            'url' => route('admin.livechat.show', $s->id),
            'take_url' => route('admin.livechat.take', $s->id),
        ];
    };
    $feed = [
        'waiting' => $waiting->map($toFeed)->values(),
        'active' => $active->map($toFeed)->values(),
    ];
@endphp
<script type="application/json" id="lc-feed">@json($feed)</script>
