@php
    $user = auth()->user();
    $me = $user->name_gelar ?: $user->name;
    $operatorName = $session->operator ? ($session->operator->name_gelar ?: $session->operator->name) : null;
    $mine = $session->operator_id !== null && (int) $session->operator_id === (int) $user->id;
    $initial = mb_strtoupper(mb_substr(trim($session->nama_pengunjung), 0, 1)) ?: '?';
    $messages = $session->messages->map(fn ($m) => [
        'id' => $m->id,
        'sender_type' => $m->sender_type,
        'sender_name' => $m->sender_name,
        'pesan' => $m->pesan,
        'time' => $m->created_at->format('H:i'),
        'date' => $m->created_at->format('Y-m-d'),
    ])->values();

    // Nomor WhatsApp → tautan wa.me (format internasional 62…)
    $wa = preg_replace('/\D+/', '', (string) $session->nohp_pengunjung);
    $wa = $wa === '' ? null : (str_starts_with($wa, '0') ? '62'.substr($wa, 1) : (str_starts_with($wa, '8') ? '62'.$wa : $wa));

    // Ringkasan perangkat pengunjung dari user agent
    $ua = (string) $session->user_agent;
    $browser = collect(['Edg/' => 'Edge', 'OPR/' => 'Opera', 'Firefox/' => 'Firefox', 'Chrome/' => 'Chrome', 'Safari/' => 'Safari'])->first(fn ($label, $needle) => str_contains($ua, $needle));
    $os = collect(['Android' => 'Android', 'iPhone' => 'iOS', 'iPad' => 'iPadOS', 'Windows' => 'Windows', 'Mac OS X' => 'macOS', 'Linux' => 'Linux'])->first(fn ($label, $needle) => str_contains($ua, $needle));
    $device = collect([$browser, $os])->filter()->implode(' · ');

    $quickReplies = [
        ['Sapaan Pembuka', "Assalamu'alaikum Warahmatullahi Wabarakatuh. Ada yang bisa kami bantu?"],
        ['Sedang Dicek', 'Baik bapak/ibu, mohon ditunggu sebentar sedang kami konfirmasikan ke komisi terkait.'],
        ['Syarat Berkas', 'Silakan melampirkan berkas persyaratan atau mengajukan melalui menu Layanan di portal MUI.'],
        ['Konfirmasi Akhir', 'Apakah ada hal lain yang ingin bapak/ibu tanyakan sebelum sesi kami akhiri?'],
        ['Penutup Salam', "Sama-sama bapak/ibu. Semoga berkah dan sehat selalu. Wassalamu'alaikum Warahmatullahi Wabarakatuh."],
    ];

    $statusText = match ($session->status) {
        'selesai' => 'Sesi telah selesai',
        'bot' => 'Dilayani chatbot',
        'menunggu' => 'Menunggu di antrian',
        default => $mine ? 'Sedang Anda layani' : 'Dilayani '.($operatorName ?? 'petugas'),
    };

    // Daftar percakapan di panel kiri (antrian & sesi aktif)
    $waitingList = \App\Models\ChatSession::query()->where('status', 'menunggu')->orderBy('created_at')->get();
    $activeList = \App\Models\ChatSession::query()->where('status', 'aktif')->with('operator')->orderByDesc('last_activity_at')->get();

    $roomConfig = [
        'id' => $session->id,
        'status' => $session->status,
        'mine' => $mine,
        'me' => $me,
        'visitor' => $session->nama_pengunjung,
        'operatorName' => $operatorName,
        'createdAt' => $session->created_at?->toIso8601String(),
        'startedAt' => $session->started_at?->toIso8601String(),
        'closedAt' => $session->closed_at?->toIso8601String(),
        'messages' => $messages,
        'pollUrl' => route('admin.livechat.poll-session', $session->id),
        'sendUrl' => route('admin.livechat.send', $session->id),
        'overviewUrl' => route('admin.livechat.poll-overview'),
        'feedUrl' => route('admin.livechat.show', $session->id),
    ];
@endphp

<x-layouts.admin :title="'Live Chat — '.$session->nama_pengunjung" header="Ruang percakapan petugas · Antrian #{{ $session->antrian_nomor }}">
    @include('admin.livechat.partials.feed', ['waiting' => $waitingList, 'active' => $activeList])
    @include('admin.livechat.partials.notifier')

    <div x-data="chatRoom(@js($roomConfig))"
         class="card flex h-[calc(100dvh-72px-2rem)] min-h-[540px] overflow-hidden sm:h-[calc(100dvh-72px-3rem)] lg:h-[calc(100dvh-72px-4rem)]">

        {{-- Panel kiri: daftar percakapan --}}
        <aside class="hidden w-72 shrink-0 flex-col border-r border-stone-200 bg-white lg:flex" aria-label="Daftar percakapan">
            <div class="border-b border-stone-100 px-4 pt-4 pb-3">
                <div class="flex items-center justify-between">
                    <h2 class="font-display text-lg font-semibold text-ink-900">Percakapan</h2>
                    <a href="{{ route('admin.livechat.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-brand-700 hover:text-brand-900">Kelola <x-icon name="arrow-up-right" class="size-3.5" /></a>
                </div>
                <div class="mt-3 grid grid-cols-2 gap-1 rounded-xl bg-stone-100 p-1" role="group" aria-label="Jenis percakapan">
                    <button type="button" @click="listTab = 'active'" :aria-pressed="listTab === 'active' ? 'true' : 'false'"
                            class="inline-flex items-center justify-center gap-1.5 rounded-lg px-2 py-1.5 text-xs font-semibold transition"
                            :class="listTab === 'active' ? 'bg-white text-brand-800 shadow-sm' : 'text-stone-500 hover:text-stone-800'">
                        Berlangsung <span class="rounded-full bg-brand-100 px-1.5 text-[10px] font-bold text-brand-800 tabular-nums" x-text="feed.active.length"></span>
                    </button>
                    <button type="button" @click="listTab = 'waiting'" :aria-pressed="listTab === 'waiting' ? 'true' : 'false'"
                            class="inline-flex items-center justify-center gap-1.5 rounded-lg px-2 py-1.5 text-xs font-semibold transition"
                            :class="listTab === 'waiting' ? 'bg-white text-brand-800 shadow-sm' : 'text-stone-500 hover:text-stone-800'">
                        Antrian <span class="rounded-full px-1.5 text-[10px] font-bold tabular-nums" :class="feed.waiting.length ? 'bg-red-500 text-white' : 'bg-stone-200 text-stone-500'" x-text="feed.waiting.length"></span>
                    </button>
                </div>
            </div>

            <div class="scrollbar-thin min-h-0 flex-1 overflow-y-auto">
                <ul x-show="listTab === 'active'" class="divide-y divide-stone-100">
                    <template x-for="s in feed.active" :key="s.id">
                        <li>
                            <a :href="s.url" class="relative flex items-start gap-3 px-4 py-3 transition hover:bg-brand-50/50" :class="s.id === sessionId && 'bg-brand-50/80'" :aria-current="s.id === sessionId ? 'page' : null">
                                <span x-show="s.id === sessionId" class="absolute inset-y-2 left-0 w-1 rounded-r-full bg-brand-600"></span>
                                <span class="relative grid size-10 shrink-0 place-items-center rounded-full text-sm font-bold" :class="s.mine ? 'bg-brand-700 text-gold-300' : 'bg-brand-100 text-brand-800'">
                                    <span x-text="s.inisial"></span>
                                    <span class="absolute -right-0.5 -bottom-0.5 size-3 rounded-full border-2 border-white bg-emerald-500"></span>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="flex items-center justify-between gap-2">
                                        <span class="truncate text-sm font-semibold text-ink-900" x-text="s.nama"></span>
                                        <span class="shrink-0 text-[10px] text-stone-400" x-text="ago(s.aktivitas)"></span>
                                    </span>
                                    <span class="block truncate text-[11px] font-medium" :class="s.mine ? 'text-brand-700' : 'text-stone-400'" x-text="(s.mine ? 'Ditangani Anda' : 'Petugas: ' + (s.operator || '—')) + ' · #' + s.antrian"></span>
                                    <span class="mt-0.5 flex items-center gap-1.5">
                                        <span class="flex-1 truncate text-xs" :class="s.pengirim === 'pengunjung' && s.id !== sessionId ? 'font-medium text-ink-900' : 'text-stone-500'" x-text="preview(s)"></span>
                                        <span x-show="s.pengirim === 'pengunjung' && s.id !== sessionId" class="size-2 shrink-0 rounded-full bg-red-500" title="Perlu balasan"></span>
                                    </span>
                                </span>
                            </a>
                        </li>
                    </template>
                    <li x-show="!feed.active.length" class="px-6 py-12 text-center">
                        <x-icon name="messages-square" class="mx-auto size-7 text-stone-300" />
                        <p class="mt-2 text-xs text-stone-500">Belum ada percakapan aktif.</p>
                    </li>
                </ul>

                <ul x-show="listTab === 'waiting'" x-cloak class="divide-y divide-stone-100">
                    <template x-for="s in feed.waiting" :key="s.id">
                        <li class="px-4 py-3">
                            <div class="flex items-start gap-3">
                                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-gold-50 text-xs font-bold text-gold-800 ring-1 ring-gold-200" x-text="'#' + s.antrian"></span>
                                <div class="min-w-0 flex-1">
                                    <p class="flex items-center justify-between gap-2">
                                        <span class="truncate text-sm font-semibold text-ink-900" x-text="s.nama"></span>
                                        <span class="shrink-0 text-[10px] text-stone-400" x-text="ago(s.masuk)"></span>
                                    </p>
                                    <p class="truncate text-[11px] text-stone-500" x-text="s.topik"></p>
                                    <p x-show="s.pratinjau" class="mt-0.5 truncate text-xs text-stone-500 italic" x-text="'“' + s.pratinjau + '”'"></p>
                                </div>
                            </div>
                            <form method="POST" :action="s.take_url" class="mt-2.5 pl-13">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-sm w-full"><x-icon name="message-square-reply" class="size-4" /> Balas Chat</button>
                            </form>
                        </li>
                    </template>
                    <li x-show="!feed.waiting.length" class="px-6 py-12 text-center">
                        <x-icon name="circle-check-big" class="mx-auto size-7 text-stone-300" />
                        <p class="mt-2 text-xs text-stone-500">Tidak ada antrian menunggu.</p>
                    </li>
                </ul>
            </div>

            <div class="flex items-center justify-between gap-2 border-t border-stone-100 bg-stone-50/70 px-4 py-2.5 text-[11px] text-stone-500">
                <span class="flex items-center gap-1.5" aria-live="polite">
                    <span class="size-1.5 rounded-full" :class="online ? 'bg-emerald-500' : 'bg-red-500'"></span>
                    <span x-text="online ? 'Tersambung' : 'Terputus, mencoba lagi…'">Tersambung</span>
                </span>
                <button type="button" @click="toggleSound()" class="inline-flex items-center gap-1 font-semibold hover:text-brand-700" :aria-pressed="sound ? 'true' : 'false'">
                    <x-icon name="volume-2" class="size-3.5" x-show="sound" />
                    <x-icon name="volume-x" class="size-3.5" x-show="!sound" x-cloak />
                    <span x-text="sound ? 'Suara aktif' : 'Suara mati'"></span>
                </button>
            </div>
        </aside>

        {{-- Panel tengah: percakapan --}}
        <section class="flex min-w-0 flex-1 flex-col bg-sand-50" aria-label="Percakapan dengan {{ $session->nama_pengunjung }}">
            <header class="flex shrink-0 items-center gap-3 border-b border-stone-200 bg-white px-3 py-3 sm:px-5">
                <a href="{{ route('admin.livechat.index', ['tab' => 'aktif']) }}" class="grid size-9 shrink-0 place-items-center rounded-lg text-stone-500 hover:bg-stone-100 lg:hidden" aria-label="Kembali ke daftar percakapan"><x-icon name="arrow-left" class="size-5" /></a>
                <span class="relative grid size-11 shrink-0 place-items-center rounded-full bg-brand-700 text-base font-bold text-gold-300">
                    {{ $initial }}
                    <span class="absolute -right-0.5 -bottom-0.5 size-3.5 rounded-full border-2 border-white" :class="statusInfo.dot"></span>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="flex min-w-0 items-center gap-2">
                        <span class="truncate font-bold text-ink-900">{{ $session->nama_pengunjung }}</span>
                        <span class="badge badge-gold hidden shrink-0 sm:inline-flex">#{{ $session->antrian_nomor }}</span>
                    </p>
                    <p class="truncate text-xs text-stone-500"><span x-text="statusText">{{ $statusText }}</span> · {{ $session->topik ?: 'Layanan Umum' }}</p>
                </div>
                <div class="flex shrink-0 items-center gap-0.5 sm:gap-1">
                    <button type="button" @click="toggleSound()" class="hidden size-9 place-items-center rounded-lg text-stone-500 hover:bg-stone-100 hover:text-brand-700 sm:grid lg:hidden"
                            :title="sound ? 'Matikan suara notifikasi' : 'Aktifkan suara notifikasi'" :aria-label="sound ? 'Matikan suara notifikasi' : 'Aktifkan suara notifikasi'">
                        <x-icon name="volume-2" class="size-[18px]" x-show="sound" />
                        <x-icon name="volume-x" class="size-[18px]" x-show="!sound" x-cloak />
                    </button>
                    <a href="{{ route('admin.livechat.transcript', $session->id) }}" target="_blank" rel="noopener" class="hidden size-9 place-items-center rounded-lg text-stone-500 hover:bg-stone-100 hover:text-brand-700 sm:grid" title="Cetak transkrip" aria-label="Cetak transkrip"><x-icon name="printer" class="size-[18px]" /></a>
                    <button type="button" @click="info = true" class="grid size-9 place-items-center rounded-lg text-stone-500 hover:bg-stone-100 hover:text-brand-700 2xl:hidden" title="Info pengunjung" aria-label="Info pengunjung"><x-icon name="panel-right-open" class="size-[18px]" /></button>
                    @if ($session->status !== 'selesai')
                        <form method="POST" action="{{ route('admin.livechat.close', $session->id) }}" x-show="!closed" class="ml-1"
                              data-confirm="Apakah Anda yakin ingin menyelesaikan sesi ini? Pengunjung tidak dapat mengirim pesan lagi pada sesi ini." data-confirm-title="Selesaikan percakapan?" data-confirm-button="Ya, selesaikan">
                            @csrf
                            <button type="submit" class="btn btn-sm border border-red-200 bg-red-50 px-2.5 text-red-700 hover:border-red-300 hover:bg-red-100 sm:px-3.5" aria-label="Selesaikan chat">
                                <x-icon name="circle-check-big" class="size-4" /> <span class="hidden sm:inline">Selesaikan</span>
                            </button>
                        </form>
                    @endif
                </div>
            </header>

            @if (in_array($session->status, ['aktif', 'bot'], true) && ! $mine)
                <div x-show="!closed" class="flex shrink-0 flex-wrap items-center gap-x-3 gap-y-2 border-b border-sky-100 bg-sky-50 px-4 py-2.5 text-xs text-sky-900 sm:px-5">
                    <x-icon name="info" class="size-4 text-sky-600" />
                    <p class="min-w-0 flex-1">
                        @if ($session->status === 'bot')
                            Percakapan ini sedang dilayani <strong>chatbot</strong>. Ambil alih agar Anda tercatat sebagai petugas.
                        @else
                            Percakapan ini ditangani <strong>{{ $operatorName ?? 'petugas lain' }}</strong>. Anda tetap dapat membantu membalas.
                        @endif
                    </p>
                    <form method="POST" action="{{ route('admin.livechat.take', $session->id) }}" data-confirm="Ambil alih percakapan ini? Pengunjung akan menerima salam pembuka dari Anda." data-confirm-title="Ambil alih percakapan?" data-confirm-button="Ya, ambil alih" data-confirm-tone="primary">
                        @csrf
                        <button type="submit" class="btn btn-outline btn-sm py-1"><x-icon name="headset" class="size-3.5" /> Ambil alih</button>
                    </form>
                </div>
            @endif

            <div class="relative min-h-0 flex-1">
                <div x-ref="scroller" @scroll.passive="onScroll()" class="scrollbar-thin pattern-islamic-dark absolute inset-0 overflow-y-auto px-3 py-5 sm:px-6" aria-live="polite" aria-label="Isi percakapan">
                    <div class="mx-auto max-w-3xl">
                        <p class="mb-5 text-center">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1 text-[11px] text-stone-500 ring-1 ring-stone-200">
                                <x-icon name="calendar-days" class="size-3.5 text-gold-600" /> Sesi dimulai {{ $session->created_at->translatedFormat('l, d F Y · H:i') }} WIB
                            </span>
                        </p>

                        <template x-for="(m, i) in messages" :key="m.id">
                            <div>
                                <div x-show="newDay(i)" class="my-5 flex items-center gap-3 text-[11px] font-semibold text-stone-400">
                                    <span class="h-px flex-1 bg-stone-200"></span><span x-text="dayLabel(m.date)"></span><span class="h-px flex-1 bg-stone-200"></span>
                                </div>
                                <template x-if="m.sender_type === 'system'">
                                    <div class="my-3 flex justify-center">
                                        <p class="max-w-[92%] rounded-2xl bg-white/90 px-3.5 py-2 text-center text-xs leading-relaxed text-stone-500 ring-1 ring-stone-200">
                                            <span x-html="m.html"></span> <span class="ml-1 whitespace-nowrap text-stone-400" x-text="m.time"></span>
                                        </p>
                                    </div>
                                </template>
                                <template x-if="m.sender_type !== 'system'">
                                    <div class="flex items-end gap-2" :class="[m.sender_type === 'operator' ? 'flex-row-reverse' : '', groupStart(i) ? 'mt-4' : 'mt-1']">
                                        <span x-show="m.sender_type !== 'operator'" class="grid size-8 shrink-0 place-items-center rounded-full text-[11px] font-bold" :class="[avatarClass(m), groupEnd(i) ? 'mb-5' : 'invisible']" x-text="avatarText(m)"></span>
                                        <div class="flex max-w-[85%] min-w-0 flex-col sm:max-w-[72%]" :class="m.sender_type === 'operator' ? 'items-end' : 'items-start'">
                                            <p x-show="groupStart(i)" class="mb-1 flex items-center gap-1 px-1 text-[11px] font-semibold text-stone-500">
                                                <x-icon name="bot" class="size-3.5 text-gold-600" x-show="m.sender_type === 'bot'" />
                                                <span x-text="m.sender_type === 'operator' && m.sender_name === me ? 'Anda' : m.sender_name"></span>
                                            </p>
                                            <div class="rounded-2xl px-3.5 py-2.5 text-[14px] leading-relaxed shadow-sm [overflow-wrap:anywhere]" :class="bubbleClass(m)" x-html="m.html"></div>
                                            <p x-show="groupEnd(i)" class="mt-1 flex items-center gap-1 px-1 text-[10.5px] text-stone-400">
                                                <span x-text="m.time"></span>
                                                <x-icon name="clock" class="size-3" x-show="m.pending" />
                                                <x-icon name="check-check" class="size-3.5 text-brand-500" x-show="m.sender_type === 'operator' && !m.pending" />
                                            </p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <div x-show="closed" x-cloak class="mx-auto mt-6 max-w-sm rounded-2xl bg-white p-5 text-center ring-1 ring-stone-200">
                            <span class="mx-auto grid size-11 place-items-center rounded-full bg-brand-50 text-brand-700"><x-icon name="circle-check-big" class="size-5" /></span>
                            <p class="mt-2 text-sm font-semibold text-ink-900">Sesi percakapan telah selesai</p>
                            <p class="mt-0.5 text-xs text-stone-500">Transkrip tersimpan di riwayat & dapat dicetak kapan saja.</p>
                        </div>
                    </div>
                </div>

                <button type="button" x-show="unseen > 0" x-cloak x-transition @click="scrollBottom()"
                        class="absolute bottom-4 left-1/2 inline-flex -translate-x-1/2 items-center gap-1.5 rounded-full bg-brand-700 px-4 py-2 text-xs font-semibold text-white shadow-lg ring-4 ring-white/60 hover:bg-brand-800">
                    <x-icon name="arrow-down" class="size-3.5" /> <span x-text="unseen + ' pesan baru'"></span>
                </button>
            </div>

            <footer class="shrink-0 border-t border-stone-200 bg-white px-3 pt-2.5 pb-3 sm:px-5">
                <div x-show="closed" @if ($session->status !== 'selesai') x-cloak @endif class="flex flex-col items-center gap-3 py-1.5 text-center sm:flex-row sm:justify-between sm:text-left">
                    <p class="flex items-center gap-2 text-sm text-stone-600"><x-icon name="lock" class="size-4 text-stone-400" /> Sesi percakapan telah diselesaikan.</p>
                    <div class="flex gap-2">
                        <a href="{{ route('admin.livechat.transcript', $session->id) }}" target="_blank" rel="noopener" class="btn btn-outline btn-sm"><x-icon name="printer" class="size-4" /> Transkrip</a>
                        <a href="{{ route('admin.livechat.index', ['tab' => 'antrian']) }}" class="btn btn-primary btn-sm"><x-icon name="list-ordered" class="size-4" /> Ke antrian</a>
                    </div>
                </div>

                <div x-show="!closed" @if ($session->status === 'selesai') x-cloak @endif>
                    <div class="scrollbar-none -mx-1 mb-2 flex gap-1.5 overflow-x-auto px-1 pb-0.5" role="group" aria-label="Template balasan cepat">
                        @foreach ($quickReplies as [$label, $text])
                            <button type="button" @click="useTemplate(@js($text))" title="{{ $text }}"
                                    class="shrink-0 rounded-full border border-brand-200 bg-brand-50 px-3 py-1 text-[11.5px] font-semibold text-brand-800 transition hover:border-brand-700 hover:bg-brand-700 hover:text-white">{{ $label }}</button>
                        @endforeach
                    </div>
                    <form @submit.prevent="send()" class="flex items-end gap-2">
                        <label for="lc-reply" class="sr-only">Balasan untuk {{ $session->nama_pengunjung }}</label>
                        <textarea id="lc-reply" x-ref="input" x-model="input" rows="1" maxlength="2500" autocomplete="off"
                                  placeholder="Tulis balasan…"
                                  @keydown.enter="if (!$event.shiftKey && !$event.isComposing) { $event.preventDefault(); send(); }"
                                  @input="autosize()"
                                  class="input max-h-40 min-h-[46px] flex-1 resize-none py-3 leading-snug"></textarea>
                        <button type="submit" class="btn btn-primary h-[46px] shrink-0 px-4" :disabled="sending || !input.trim()" aria-label="Kirim balasan">
                            <x-icon name="loader-circle" class="size-4 animate-spin" x-show="sending" x-cloak />
                            <x-icon name="send" class="size-4" x-show="!sending" />
                            <span class="hidden sm:inline">Kirim</span>
                        </button>
                    </form>
                    <p class="mt-1.5 hidden justify-between gap-3 px-1 text-[11px] text-stone-400 sm:flex">
                        <span>Enter untuk mengirim · Shift + Enter untuk baris baru</span>
                        <span x-show="input.length > 2000" :class="input.length >= 2500 && 'font-semibold text-red-600'" x-text="input.length + ' / 2500'"></span>
                    </p>
                </div>
            </footer>
        </section>

        {{-- Panel kanan: info pengunjung (laci di layar kecil) --}}
        <div x-show="info" x-transition.opacity x-cloak class="fixed inset-0 z-[60] bg-brand-950/50 backdrop-blur-sm 2xl:hidden" @click="info = false" aria-hidden="true"></div>
        <aside class="invisible fixed inset-y-0 right-0 z-[61] flex w-[min(21rem,92vw)] translate-x-full flex-col bg-white shadow-2xl transition-[transform,visibility] duration-300 2xl:visible 2xl:static 2xl:z-auto 2xl:w-80 2xl:shrink-0 2xl:translate-x-0 2xl:border-l 2xl:border-stone-200 2xl:shadow-none"
               :class="info && 'visible! translate-x-0!'" @keydown.escape.window="info = false" aria-label="Informasi pengunjung">
            <div class="flex items-center justify-between border-b border-stone-100 px-5 py-3.5 2xl:hidden">
                <p class="font-semibold text-ink-900">Info pengunjung</p>
                <button type="button" @click="info = false" class="grid size-9 place-items-center rounded-lg text-stone-500 hover:bg-stone-100" aria-label="Tutup info pengunjung"><x-icon name="x" class="size-5" /></button>
            </div>

            <div class="scrollbar-thin min-h-0 flex-1 overflow-y-auto p-5">
                <div class="text-center">
                    <span class="mx-auto grid size-14 place-items-center rounded-full bg-brand-50 font-display text-xl font-semibold text-brand-700 ring-8 ring-brand-50/50">{{ $initial }}</span>
                    <p class="mt-3 font-bold text-ink-900">{{ $session->nama_pengunjung }}</p>
                    <p class="text-xs text-stone-500">Pengunjung live chat</p>
                    <span class="badge mt-2" :class="statusInfo.badge" x-text="statusInfo.label"></span>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-2">
                    <div class="rounded-xl bg-gold-50 p-3 text-center ring-1 ring-gold-200">
                        <p class="text-2xl leading-tight font-bold text-gold-800 tabular-nums">#{{ $session->antrian_nomor }}</p>
                        <p class="text-[10px] font-bold tracking-wider text-gold-700/80 uppercase">No. antrian</p>
                    </div>
                    <div class="rounded-xl bg-brand-50 p-3 text-center ring-1 ring-brand-100">
                        <p class="text-2xl leading-tight font-bold text-brand-800 tabular-nums" x-text="messageCount">{{ $messages->count() }}</p>
                        <p class="text-[10px] font-bold tracking-wider text-brand-700/80 uppercase">Pesan</p>
                    </div>
                </div>

                <dl class="mt-5 space-y-3.5 text-sm">
                    <div>
                        <dt class="flex items-center gap-1.5 text-[11px] font-bold tracking-wider text-stone-400 uppercase"><x-icon name="tag" class="size-3.5" /> Topik layanan</dt>
                        <dd class="mt-0.5 font-medium text-stone-800">{{ $session->topik ?: 'Layanan Umum' }}</dd>
                    </div>
                    <div>
                        <dt class="flex items-center gap-1.5 text-[11px] font-bold tracking-wider text-stone-400 uppercase"><x-icon name="phone" class="size-3.5" /> No. WhatsApp</dt>
                        <dd class="mt-0.5">
                            @if ($wa)
                                <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 font-medium text-brand-700 hover:underline">{{ $session->nohp_pengunjung }} <x-icon name="external-link" class="size-3" /></a>
                            @else
                                <span class="text-stone-500">—</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="flex items-center gap-1.5 text-[11px] font-bold tracking-wider text-stone-400 uppercase"><x-icon name="mail" class="size-3.5" /> Email</dt>
                        <dd class="mt-0.5 break-all">
                            @if ($session->email_pengunjung)
                                <a href="mailto:{{ $session->email_pengunjung }}" class="font-medium text-brand-700 hover:underline">{{ $session->email_pengunjung }}</a>
                            @else
                                <span class="text-stone-500">—</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="flex items-center gap-1.5 text-[11px] font-bold tracking-wider text-stone-400 uppercase"><x-icon name="clock" class="size-3.5" /> Waktu masuk</dt>
                        <dd class="mt-0.5 text-stone-800">{{ $session->created_at->translatedFormat('d M Y, H:i') }} WIB</dd>
                    </div>
                    <div>
                        <dt class="flex items-center gap-1.5 text-[11px] font-bold tracking-wider text-stone-400 uppercase"><x-icon name="headset" class="size-3.5" /> Petugas</dt>
                        <dd class="mt-0.5 text-stone-800">
                            {{ $operatorName ?? 'Belum ada' }} @if ($mine)<span class="badge badge-green ml-1">Anda</span>@endif
                            @if ($session->started_at)<span class="block text-xs text-stone-500">Mulai dilayani {{ $session->started_at->format('H:i') }} WIB</span>@endif
                        </dd>
                    </div>
                    <div>
                        <dt class="flex items-center gap-1.5 text-[11px] font-bold tracking-wider text-stone-400 uppercase"><x-icon name="timer" class="size-3.5" /> Durasi</dt>
                        <dd class="mt-0.5 text-stone-800" x-text="duration">—</dd>
                    </div>
                    @if ($device || $session->ip_address)
                        <div>
                            <dt class="flex items-center gap-1.5 text-[11px] font-bold tracking-wider text-stone-400 uppercase"><x-icon name="monitor" class="size-3.5" /> Perangkat</dt>
                            <dd class="mt-0.5 text-stone-800">{{ $device ?: 'Tidak diketahui' }}@if ($session->ip_address)<span class="block font-mono text-xs text-stone-500">IP {{ $session->ip_address }}</span>@endif</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <div class="space-y-1.5 border-t border-stone-100 p-4">
                <div class="flex gap-2">
                    @if ($session->status !== 'selesai')
                        <form method="POST" action="{{ route('admin.livechat.close', $session->id) }}" x-show="!closed" class="flex-1"
                              data-confirm="Apakah Anda yakin ingin menyelesaikan sesi ini? Pengunjung tidak dapat mengirim pesan lagi pada sesi ini." data-confirm-title="Selesaikan percakapan?" data-confirm-button="Ya, selesaikan">
                            @csrf
                            <button type="submit" class="btn btn-danger btn-sm w-full"><x-icon name="circle-check-big" class="size-4" /> Selesaikan</button>
                        </form>
                    @endif
                    <a href="{{ route('admin.livechat.transcript', $session->id) }}" target="_blank" rel="noopener" class="btn btn-outline btn-sm flex-1"><x-icon name="printer" class="size-4" /> Transkrip</a>
                </div>
                <a href="{{ route('admin.livechat.index') }}" class="btn btn-ghost btn-sm w-full"><x-icon name="arrow-left" class="size-4" /> Kembali ke Antrian</a>
            </div>
        </aside>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                const kit = window.LiveChatKit;
                const pad = (n) => String(n).padStart(2, '0');
                const today = () => { const d = new Date(); return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`; };
                const nowTime = () => { const d = new Date(); return `${pad(d.getHours())}:${pad(d.getMinutes())}`; };
                const STATUS = {
                    menunggu: { label: 'Menunggu', badge: 'badge-gold', dot: 'bg-gold-400' },
                    aktif: { label: 'Berlangsung', badge: 'badge-green', dot: 'bg-emerald-500' },
                    bot: { label: 'Chatbot', badge: 'badge-purple', dot: 'bg-violet-400' },
                    selesai: { label: 'Selesai', badge: 'badge-gray', dot: 'bg-stone-400' },
                };

                Alpine.data('chatRoom', (cfg) => ({
                    sessionId: cfg.id,
                    me: cfg.me,
                    status: cfg.status,
                    closedAt: cfg.closedAt ? new Date(cfg.closedAt).getTime() : null,
                    messages: [],
                    lastId: 0,
                    input: '',
                    sending: false,
                    polling: false,
                    stopped: false,
                    failures: 0,
                    online: true,
                    atBottom: true,
                    unseen: 0,
                    info: false,
                    listTab: 'active',
                    sound: kit.enabled(),
                    feed: kit.readFeed(document) ?? { waiting: [], active: [] },
                    overview: '',
                    now: Date.now(),
                    timers: {},

                    init() {
                        cfg.messages.forEach((m) => this.push(m, { silent: true }));
                        this.$nextTick(() => this.scrollBottom(false));
                        this.schedulePoll(1200);
                        // Acuan awal = feed halaman ini, sehingga antrian yang masuk sebelum polling pertama tetap terdeteksi.
                        this.overview = kit.signature(this.feed);
                        this.pollOverview();
                        this.timers.overview = setInterval(() => this.pollOverview(), 5000);
                        this.timers.feed = setInterval(() => this.refreshFeed(), 20000);
                        this.timers.clock = setInterval(() => { this.now = Date.now(); }, 30000);
                        window.addEventListener('beforeunload', () => this.stop());
                        document.addEventListener('visibilitychange', () => { if (!document.hidden) this.schedulePoll(150); });
                        this.$watch('info', (open) => document.documentElement.classList.toggle('overflow-hidden', open && innerWidth < 1536));
                    },

                    get closed() {
                        return this.status === 'selesai';
                    },
                    get statusInfo() {
                        return STATUS[this.status] ?? STATUS.aktif;
                    },
                    get statusText() {
                        if (this.status === 'selesai') return 'Sesi telah selesai';
                        if (this.status === 'bot') return 'Dilayani chatbot';
                        if (this.status === 'menunggu') return 'Menunggu di antrian';
                        return cfg.mine ? 'Sedang Anda layani' : `Dilayani ${cfg.operatorName || 'petugas'}`;
                    },
                    get messageCount() {
                        return this.messages.filter((m) => !m.pending).length;
                    },
                    get duration() {
                        const start = cfg.startedAt || cfg.createdAt;
                        if (!start) return '—';
                        const end = this.closed && this.closedAt ? this.closedAt : this.now;
                        const min = Math.max(0, Math.floor((end - new Date(start).getTime()) / 60000));
                        if (min < 1) return 'Kurang dari 1 menit';
                        return min < 60 ? `${min} menit` : `${Math.floor(min / 60)} jam ${min % 60} menit`;
                    },

                    /* ---------- Pesan ---------- */
                    push(raw, { silent = false } = {}) {
                        if (this.messages.some((m) => m.id === raw.id)) return false;
                        const entry = { ...raw, date: raw.date ?? today(), html: MUIAdmin.formatMessage(raw.pesan ?? ''), pending: !!raw.pending };
                        // Balasan dari halaman ini bisa tiba lewat polling sebelum respons kirim: gabungkan dengan pesan sementara.
                        if (raw.sender_type === 'operator' && Number.isInteger(raw.id)) {
                            const temp = this.messages.find((m) => m.pending && m.pesan === raw.pesan);
                            if (temp) {
                                Object.assign(temp, entry, { pending: false });
                                this.track(raw.id);
                                return false;
                            }
                        }
                        this.messages.push(entry);
                        this.track(raw.id);
                        if (!silent) {
                            if (this.atBottom || entry.pending) this.scrollBottom();
                            else this.unseen++;
                        }
                        return true;
                    },
                    track(id) {
                        if (Number.isInteger(id)) this.lastId = Math.max(this.lastId, id);
                    },
                    confirmSent(tempId, saved) {
                        const temp = this.messages.find((m) => m.id === tempId);
                        if (!saved) return;
                        if (this.messages.some((m) => m.id === saved.id && m !== temp)) {
                            this.messages = this.messages.filter((m) => m.id !== tempId);
                        } else if (temp) {
                            Object.assign(temp, { id: saved.id, time: saved.time ?? temp.time, pending: false });
                        }
                        this.track(saved.id);
                    },
                    async send() {
                        const pesan = this.input.trim();
                        if (!pesan || this.sending || this.closed) return;
                        this.sending = true;
                        this.input = '';
                        this.$nextTick(() => this.autosize());
                        const tempId = `tmp-${Date.now()}`;
                        this.push({ id: tempId, sender_type: 'operator', sender_name: this.me, pesan, time: nowTime(), pending: true });
                        try {
                            const res = await MUIAdmin.http(cfg.sendUrl, { method: 'POST', body: { pesan } });
                            this.confirmSent(tempId, res.message);
                        } catch (e) {
                            this.messages = this.messages.filter((m) => m.id !== tempId);
                            if (!this.input) this.input = pesan;
                            this.$nextTick(() => this.autosize());
                            MUIAdmin.toast(e.message || 'Gagal mengirim pesan ke pengunjung. Silakan periksa koneksi Anda.', 'error');
                        } finally {
                            this.sending = false;
                            this.$nextTick(() => this.$refs.input?.focus());
                        }
                    },
                    useTemplate(text) {
                        this.input = text;
                        this.$nextTick(() => {
                            this.autosize();
                            const el = this.$refs.input;
                            el?.focus();
                            el?.setSelectionRange(text.length, text.length);
                        });
                    },
                    autosize() {
                        const el = this.$refs.input;
                        if (!el) return;
                        el.style.height = 'auto';
                        el.style.height = `${Math.min(el.scrollHeight + 2, 160)}px`;
                    },

                    /* ---------- Polling percakapan ---------- */
                    schedulePoll(delay) {
                        clearTimeout(this.timers.poll);
                        if (this.stopped || this.closed) return;
                        const wait = delay ?? (document.hidden ? 5000 : (this.failures ? Math.min(15000, 1500 * 2 ** this.failures) : 1500));
                        this.timers.poll = setTimeout(() => this.poll(), wait);
                    },
                    async poll() {
                        if (this.polling || this.stopped) return;
                        this.polling = true;
                        try {
                            const res = await MUIAdmin.http(`${cfg.pollUrl}?last_id=${this.lastId}`);
                            this.failures = 0;
                            this.online = true;
                            let fromVisitor = 0;
                            (res.messages ?? []).forEach((m) => { if (this.push(m) && m.sender_type === 'pengunjung') fromVisitor++; });
                            if (fromVisitor) this.alertVisitor(fromVisitor);
                            if (res.status && res.status !== this.status) this.changeStatus(res.status);
                        } catch (e) {
                            if (e.status === 404) {
                                this.stop();
                                MUIAdmin.toast('Sesi chat ini sudah tidak tersedia.', 'error');
                                return;
                            }
                            this.failures++;
                            this.online = false;
                        } finally {
                            this.polling = false;
                            this.schedulePoll();
                        }
                    },
                    alertVisitor(count) {
                        kit.chime();
                        kit.attention(count);
                        if (document.hidden) kit.notify(`Pesan baru dari ${cfg.visitor}`, String(this.messages.at(-1)?.pesan ?? '').slice(0, 140));
                    },
                    changeStatus(next) {
                        this.status = next;
                        if (next === 'selesai') {
                            this.closedAt = Date.now();
                            clearTimeout(this.timers.poll);
                            MUIAdmin.toast('Sesi percakapan ini telah diselesaikan.', 'info');
                            this.scrollBottom();
                        }
                    },

                    /* ---------- Daftar percakapan (panel kiri) ---------- */
                    async pollOverview() {
                        try {
                            const signature = kit.overviewSignature(await MUIAdmin.http(cfg.overviewUrl));
                            if (signature !== this.overview) {
                                this.overview = signature;
                                this.refreshFeed();
                            }
                        } catch { /* abaikan */ }
                    },
                    async refreshFeed() {
                        if (this.stopped) return;
                        try {
                            const feed = await kit.fetchFeed(cfg.feedUrl);
                            if (!feed) return;
                            const known = new Set(this.feed.waiting.map((s) => s.id));
                            const baru = feed.waiting.filter((s) => !known.has(s.id));
                            this.feed = feed;
                            this.now = Date.now();
                            if (baru.length) {
                                kit.bell();
                                kit.attention(baru.length);
                                kit.notify('Antrian Chat Baru Masuk', `Pengunjung: ${baru[0].nama || 'Masyarakat'} membutuhkan bantuan.`);
                            }
                        } catch { /* abaikan */ }
                    },

                    /* ---------- Tampilan ---------- */
                    onScroll() {
                        const el = this.$refs.scroller;
                        if (!el) return;
                        this.atBottom = el.scrollHeight - el.scrollTop - el.clientHeight < 96;
                        if (this.atBottom) this.unseen = 0;
                    },
                    scrollBottom(smooth = true) {
                        this.$nextTick(() => {
                            const el = this.$refs.scroller;
                            if (!el) return;
                            el.scrollTo({ top: el.scrollHeight, behavior: smooth ? 'smooth' : 'auto' });
                            this.atBottom = true;
                            this.unseen = 0;
                        });
                    },
                    sameGroup(a, b) {
                        return !!(a && b && a.sender_type !== 'system' && b.sender_type !== 'system'
                            && a.sender_type === b.sender_type && a.sender_name === b.sender_name && a.date === b.date);
                    },
                    groupStart(i) {
                        return !this.sameGroup(this.messages[i - 1], this.messages[i]);
                    },
                    groupEnd(i) {
                        return !this.sameGroup(this.messages[i], this.messages[i + 1]);
                    },
                    newDay(i) {
                        return i > 0 && this.messages[i].date !== this.messages[i - 1].date;
                    },
                    dayLabel(date) {
                        if (date === today()) return 'Hari ini';
                        const d = new Date(`${date}T00:00:00`);
                        const kemarin = new Date();
                        kemarin.setDate(kemarin.getDate() - 1);
                        if (d.toDateString() === kemarin.toDateString()) return 'Kemarin';
                        return d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
                    },
                    bubbleClass(m) {
                        if (m.sender_type === 'operator') return `rounded-br-md bg-brand-700 text-white${m.pending ? ' opacity-70' : ''}`;
                        if (m.sender_type === 'bot') return 'rounded-bl-md bg-gold-50 text-stone-800 ring-1 ring-gold-200';
                        return 'rounded-bl-md bg-white text-stone-800 ring-1 ring-stone-200';
                    },
                    avatarClass(m) {
                        return m.sender_type === 'bot' ? 'bg-gold-100 text-gold-800' : 'bg-brand-100 text-brand-800';
                    },
                    avatarText(m) {
                        return m.sender_type === 'bot' ? 'AI' : String(m.sender_name || '?').trim().charAt(0).toUpperCase();
                    },
                    preview(s) {
                        const who = { operator: 'Petugas: ', bot: 'Bot: ', pengunjung: '' }[s.pengirim] ?? '';
                        return s.pratinjau ? who + s.pratinjau : 'Belum ada pesan';
                    },
                    ago(iso) {
                        return kit.ago(iso, this.now);
                    },
                    toggleSound() {
                        this.sound = !this.sound;
                        kit.setEnabled(this.sound);
                        if (this.sound) {
                            kit.unlock();
                            setTimeout(() => kit.chime(), 60);
                            kit.requestPermission();
                        }
                        MUIAdmin.toast(this.sound ? 'Suara notifikasi diaktifkan.' : 'Suara notifikasi dimatikan.', 'info');
                    },
                    stop() {
                        this.stopped = true;
                        clearTimeout(this.timers.poll);
                        clearInterval(this.timers.overview);
                        clearInterval(this.timers.feed);
                        clearInterval(this.timers.clock);
                    },
                }));
            });
        </script>
    @endpush
</x-layouts.admin>
