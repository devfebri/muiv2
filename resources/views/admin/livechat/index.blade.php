@php
    $user = auth()->user();
    $tabs = [
        'antrian' => ['Antrian Menunggu', 'hourglass'],
        'aktif' => ['Chat Berlangsung', 'messages-square'],
        'riwayat' => ['Riwayat & Ekspor', 'history'],
        'faq' => ['FAQ Chatbot', 'bot'],
    ];
    $tab = array_key_exists($tab, $tabs) ? $tab : 'antrian';
    $chatEnabled = (string) \App\Models\Setting::get('chat_is_enabled', '1') !== '0';
    $operational = \App\Services\LiveChatService::isOperational();
    $schedule = \App\Services\LiveChatService::getOperationalScheduleText();
    $myActive = $activeSessions->where('operator_id', $user->id)->count();
    $petugas = fn ($session) => $session->operator ? ($session->operator->name_gelar ?: $session->operator->name) : null;
    $faqOld = old('_faq_form') !== null ? [
        'id' => old('_faq_form'),
        'pertanyaan' => (string) old('pertanyaan', ''),
        'kategori' => (string) old('kategori', ''),
        'urutan' => old('urutan', 0),
        'jawaban' => (string) old('jawaban', ''),
        'is_active' => (bool) old('is_active'),
    ] : null;
@endphp

<x-layouts.admin title="Live Chat" header="Layanan percakapan langsung & antrian online masyarakat">
    @include('admin.livechat.partials.feed', ['waiting' => $waitingSessions, 'active' => $activeSessions])
    @include('admin.livechat.partials.notifier')

    <div x-data="liveChatIndex({ pollUrl: @js(route('admin.livechat.poll-overview')) })">
        <x-admin.page-header eyebrow="Layanan Umat" title="Live Chat & Antrian Online" description="Respon pesan masyarakat secara real-time, kelola antrian, riwayat percakapan, dan FAQ chatbot.">
            <x-slot:actions>
                <button type="button" @click="toggleSound()" class="btn btn-outline" :aria-pressed="sound ? 'true' : 'false'">
                    <x-icon name="bell-ring" class="size-4 text-brand-600" x-show="sound" />
                    <x-icon name="bell-off" class="size-4" x-show="!sound" x-cloak />
                    <span x-text="sound ? 'Suara notifikasi aktif' : 'Aktifkan suara notifikasi'">Suara notifikasi</span>
                </button>
                @if ($user->isAdmin())
                    <a href="{{ route('admin.pengaturan.index', ['tab' => 'livechat']) }}" class="btn btn-outline"><x-icon name="clock" class="size-4" /> Jam Operasional</a>
                @endif
            </x-slot:actions>
        </x-admin.page-header>

        {{-- Status layanan --}}
        <div @class([
            'mt-6 flex flex-wrap items-center gap-x-4 gap-y-2 rounded-2xl border px-4 py-3 text-sm',
            'border-brand-200 bg-brand-50/70 text-brand-900' => $chatEnabled && $operational,
            'border-gold-200 bg-gold-50/70 text-gold-900' => ! ($chatEnabled && $operational),
        ])>
            <span class="relative flex size-2.5 shrink-0">
                <span @class(['absolute inline-flex size-full animate-ping rounded-full opacity-60', 'bg-brand-500' => $chatEnabled && $operational, 'bg-gold-500' => ! ($chatEnabled && $operational)])></span>
                <span @class(['relative inline-flex size-2.5 rounded-full', 'bg-brand-600' => $chatEnabled && $operational, 'bg-gold-500' => ! ($chatEnabled && $operational)])></span>
            </span>
            <p class="min-w-64 flex-1">
                @if (! $chatEnabled)
                    <strong>Layanan petugas dinonaktifkan.</strong> Pengunjung baru dilayani chatbot FAQ.
                @elseif ($operational)
                    <strong>Dalam jam layanan</strong> · {{ $schedule }}. Pengunjung baru langsung masuk antrian petugas.
                @else
                    <strong>Di luar jam layanan</strong> · {{ $schedule }}. Pengunjung baru dilayani chatbot FAQ terlebih dahulu.
                @endif
            </p>
            <div class="flex flex-wrap items-center gap-3 text-xs">
                <button type="button" x-show="canAskPermission" x-cloak @click="askPermission()" class="inline-flex items-center gap-1.5 font-semibold underline decoration-dotted underline-offset-4 hover:no-underline">
                    <x-icon name="monitor" class="size-3.5" /> Izinkan notifikasi desktop
                </button>
                <span class="inline-flex items-center gap-1.5 opacity-80" aria-live="polite">
                    <x-icon name="refresh-cw" class="size-3.5" ::class="refreshing && 'animate-spin'" />
                    <span x-text="online ? 'Diperbarui otomatis' : 'Koneksi terputus, mencoba lagi…'">Diperbarui otomatis</span>
                </span>
            </div>
        </div>

        {{-- Statistik --}}
        <div class="mt-6 hidden gap-4 sm:grid sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card label="Antrian menunggu" icon="hourglass" tone="gold" :value="$waitingSessions->count()" bind="feed.waiting.length" note="Belum dibalas petugas" :href="route('admin.livechat.index', ['tab' => 'antrian'])" />
            <x-stat-card label="Percakapan aktif" icon="messages-square" :value="$activeSessions->count()" bind="feed.active.length" note="Sedang berlangsung" :href="route('admin.livechat.index', ['tab' => 'aktif'])" />
            <x-stat-card label="Ditangani Anda" icon="headset" tone="blue" :value="$myActive" bind="feed.active.filter((s) => s.mine).length" note="Sesi aktif milik Anda" :href="route('admin.livechat.index', ['tab' => 'aktif'])" />
            <x-stat-card label="Riwayat tersimpan" icon="history" tone="stone" :value="$historySessions->total()" :note="filled($search) ? 'Sesuai pencarian' : 'Selesai & sesi chatbot'" :href="route('admin.livechat.index', ['tab' => 'riwayat'])" />
        </div>

        {{-- Tab --}}
        <nav class="scrollbar-none mt-8 flex gap-1 overflow-x-auto border-b border-stone-200" aria-label="Bagian live chat">
            @foreach ($tabs as $key => [$label, $icon])
                <a href="{{ route('admin.livechat.index', ['tab' => $key]) }}" @if ($tab === $key) aria-current="page" @endif @class([
                    '-mb-px inline-flex shrink-0 items-center gap-2 border-b-2 px-3 py-3 text-sm font-semibold whitespace-nowrap transition sm:px-4',
                    'border-brand-600 text-brand-800' => $tab === $key,
                    'border-transparent text-stone-500 hover:border-stone-300 hover:text-stone-800' => $tab !== $key,
                ])>
                    <x-icon :name="$icon" class="size-4" /> {{ $label }}
                    @if ($key === 'antrian')
                        <span x-show="feed.waiting.length" x-text="feed.waiting.length" class="grid min-w-5 place-items-center rounded-full bg-red-500 px-1.5 text-[11px] font-bold text-white">{{ $waitingSessions->count() ?: '' }}</span>
                    @elseif ($key === 'aktif')
                        <span x-show="feed.active.length" x-text="feed.active.length" class="grid min-w-5 place-items-center rounded-full bg-brand-100 px-1.5 text-[11px] font-bold text-brand-800">{{ $activeSessions->count() ?: '' }}</span>
                    @elseif ($key === 'faq')
                        <span class="grid min-w-5 place-items-center rounded-full bg-stone-100 px-1.5 text-[11px] font-bold text-stone-500">{{ $faqs->count() }}</span>
                    @endif
                </a>
            @endforeach
        </nav>

        {{-- TAB 1: Antrian menunggu --}}
        @if ($tab === 'antrian')
            <section class="mt-6" aria-label="Daftar antrian menunggu">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                    <h2 class="flex items-center gap-2 font-bold text-ink-900"><x-icon name="list-ordered" class="size-5 text-gold-600" /> Antrian masuk dari masyarakat</h2>
                    <p class="text-xs text-stone-500">Urut berdasarkan waktu masuk · klik <strong>Balas Chat</strong> untuk melayani</p>
                </div>
                <div class="space-y-3">
                    <template x-for="(s, i) in feed.waiting" :key="s.id">
                        <article class="card relative flex flex-col gap-4 overflow-hidden p-4 pl-5 transition sm:flex-row sm:items-center sm:p-5 sm:pl-6"
                                 :class="fresh.includes(s.id) && 'ring-2 ring-gold-400'">
                            <span class="absolute inset-y-0 left-0 w-1.5" :class="urgency(s).bar"></span>
                            <div class="flex min-w-0 flex-1 items-start gap-4">
                                <div class="grid size-16 shrink-0 content-center justify-items-center rounded-2xl bg-gold-50 text-gold-800 ring-1 ring-gold-200">
                                    <span class="text-[9px] font-bold tracking-[.18em] uppercase opacity-80">Antrian</span>
                                    <span class="text-2xl leading-none font-bold tabular-nums" x-text="'#' + s.antrian"></span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="truncate text-base font-bold text-ink-900" x-text="s.nama"></h3>
                                        <span x-show="fresh.includes(s.id)" class="badge bg-gold-400 text-brand-950">Baru</span>
                                        <span class="badge badge-gray" x-text="'Urutan ke-' + (i + 1)"></span>
                                        <span class="badge" :class="urgency(s).badge"><x-icon name="timer" class="size-3" /> <span x-text="'Menunggu ' + waitText(s)"></span></span>
                                    </div>
                                    <p class="mt-1.5 flex flex-wrap gap-x-4 gap-y-1 text-xs text-stone-500">
                                        <span class="inline-flex items-center gap-1"><x-icon name="tag" class="size-3.5 text-brand-600" /> <span x-text="s.topik"></span></span>
                                        <span class="inline-flex items-center gap-1"><x-icon name="phone" class="size-3.5 text-brand-600" /> <span x-text="s.nohp || '—'"></span></span>
                                        <span class="inline-flex items-center gap-1"><x-icon name="clock" class="size-3.5" /> <span x-text="'Masuk ' + clock(s.masuk) + ' WIB'"></span></span>
                                    </p>
                                    <div x-show="s.pratinjau" class="mt-2.5 rounded-xl bg-stone-50 px-3 py-2 ring-1 ring-stone-100"><p class="line-clamp-2 text-sm text-stone-600 italic" x-text="'“' + s.pratinjau + '”'"></p></div>
                                </div>
                            </div>
                            <form method="POST" :action="s.take_url" class="shrink-0">
                                @csrf
                                <button type="submit" class="btn btn-primary w-full sm:w-auto"><x-icon name="message-square-reply" class="size-4" /> Balas Chat</button>
                            </form>
                        </article>
                    </template>
                    <div x-show="!feed.waiting.length" @unless ($waitingSessions->isEmpty()) x-cloak @endunless>
                        <x-empty-state icon="circle-check-big" title="Tidak ada antrian yang menunggu saat ini"
                                       message="Daftar diperbarui otomatis. Bel notifikasi berbunyi saat ada pengunjung baru (pastikan suara notifikasi aktif)." />
                    </div>
                </div>
            </section>
        @endif

        {{-- TAB 2: Chat berlangsung --}}
        @if ($tab === 'aktif')
            <section class="mt-6" aria-label="Percakapan yang sedang berlangsung">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                    <h2 class="flex items-center gap-2 font-bold text-ink-900"><x-icon name="messages-square" class="size-5 text-brand-600" /> Percakapan yang sedang berlangsung</h2>
                    <p class="flex items-center gap-1.5 text-xs text-stone-500"><span class="size-2 rounded-full bg-red-500"></span> Pesan terakhir dari pengunjung, perlu dibalas</p>
                </div>
                <div class="grid grid-cols-1 gap-3 xl:grid-cols-2">
                    <template x-for="s in feed.active" :key="s.id">
                        <a :href="s.url" class="card card-hover group flex min-w-0 items-start gap-3 p-4 sm:gap-4 sm:p-5">
                            <span class="relative grid size-12 shrink-0 place-items-center rounded-2xl text-base font-bold" :class="s.mine ? 'bg-brand-700 text-gold-300' : 'bg-brand-50 text-brand-700 ring-1 ring-brand-100'">
                                <span x-text="s.inisial"></span>
                                <span class="absolute -right-0.5 -bottom-0.5 size-3.5 rounded-full border-2 border-white bg-emerald-500"></span>
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <p class="truncate font-bold text-ink-900 group-hover:text-brand-700" x-text="s.nama"></p>
                                    <span class="shrink-0 text-xs font-semibold text-gold-700" x-text="'#' + s.antrian"></span>
                                    <span class="ml-auto shrink-0 text-[11px] text-stone-400" x-text="ago(s.aktivitas)"></span>
                                </div>
                                <p class="mt-0.5 truncate text-xs text-stone-500">
                                    <span x-text="s.topik"></span> ·
                                    <span :class="s.mine ? 'font-semibold text-brand-700' : ''" x-text="s.mine ? 'Ditangani Anda' : 'Petugas: ' + (s.operator || 'Belum ada')"></span>
                                </p>
                                <p class="mt-2 flex items-center gap-2 text-sm">
                                    <span x-show="s.pengirim === 'pengunjung'" class="size-2 shrink-0 rounded-full bg-red-500" title="Perlu balasan"></span>
                                    <span class="truncate" :class="s.pengirim === 'pengunjung' ? 'font-medium text-ink-900' : 'text-stone-500'" x-text="preview(s)"></span>
                                </p>
                            </div>
                            <span class="hidden shrink-0 self-center sm:block"><span class="btn btn-outline btn-sm group-hover:border-brand-600 group-hover:text-brand-700"><x-icon name="messages-square" class="size-4" /> Buka Chat</span></span>
                        </a>
                    </template>
                </div>
                <div x-show="!feed.active.length" @unless ($activeSessions->isEmpty()) x-cloak @endunless>
                    <x-empty-state icon="messages-square" title="Belum ada sesi percakapan aktif" message="Ambil pengunjung dari tab Antrian Menunggu untuk memulai percakapan." />
                </div>
            </section>
        @endif

        {{-- TAB 3: Riwayat & ekspor --}}
        @if ($tab === 'riwayat')
            <section class="card mt-6 overflow-hidden" aria-label="Riwayat percakapan">
                <div class="flex flex-wrap items-center gap-3 border-b border-stone-100 p-4">
                    <form method="GET" action="{{ route('admin.livechat.index') }}" class="flex min-w-0 flex-1 basis-80 gap-2" role="search">
                        <input type="hidden" name="tab" value="riwayat">
                        <div class="relative min-w-0 flex-1">
                            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-stone-400" />
                            <input type="search" name="search" value="{{ $search }}" placeholder="Cari nama pengunjung, email, no. HP, atau token sesi…" aria-label="Cari riwayat percakapan" class="input pl-10">
                        </div>
                        <button type="submit" class="btn btn-primary shrink-0" aria-label="Cari"><x-icon name="search" class="size-4" /> <span class="hidden sm:inline">Cari</span></button>
                        @if (filled($search))
                            <a href="{{ route('admin.livechat.index', ['tab' => 'riwayat']) }}" class="btn btn-ghost shrink-0" aria-label="Hapus pencarian"><x-icon name="x" class="size-4" /> <span class="hidden sm:inline">Reset</span></a>
                        @endif
                    </form>
                    <a href="{{ route('admin.livechat.export-csv') }}" download class="btn btn-outline shrink-0" title="Unduh seluruh riwayat percakapan (CSV, terbuka di Excel)"><x-icon name="file-spreadsheet" class="size-4 text-brand-600" /> Ekspor CSV</a>
                </div>
                @if (filled($search))
                    <p class="border-b border-stone-100 bg-gold-50/60 px-4 py-2.5 text-xs text-stone-600">Hasil pencarian “<strong class="text-ink-900">{{ $search }}</strong>”: {{ $historySessions->total() }} sesi ditemukan.</p>
                @endif
                <div class="overflow-x-auto">
                    <table class="table-clean">
                        <thead>
                            <tr>
                                <th class="w-16">Antrian</th>
                                <th>Pengunjung</th>
                                <th class="hidden sm:table-cell">Tanggal</th>
                                <th class="hidden md:table-cell">Status</th>
                                <th class="hidden lg:table-cell">Petugas</th>
                                <th class="hidden lg:table-cell">Pesan</th>
                                <th class="hidden xl:table-cell">Durasi</th>
                                <th class="text-right">Transkrip</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($historySessions as $item)
                                @php
                                    $durasi = $item->started_at && $item->closed_at ? $item->started_at->diffForHumans($item->closed_at, \Carbon\CarbonInterface::DIFF_ABSOLUTE) : null;
                                    $statusBadge = $item->status === 'bot'
                                        ? '<span class="badge badge-purple">FAQ Bot</span>'
                                        : '<span class="badge badge-gray">Selesai</span>';
                                @endphp
                                <tr>
                                    <td><span class="text-base font-bold text-brand-800 tabular-nums">#{{ $item->antrian_nomor }}</span></td>
                                    <td class="min-w-40">
                                        <p class="font-semibold text-ink-900">{{ $item->nama_pengunjung }}</p>
                                        <p class="text-xs text-stone-500">{{ $item->nohp_pengunjung ?: ($item->email_pengunjung ?: '—') }}</p>
                                        <p class="mt-1.5 flex flex-wrap items-center gap-1.5 text-[11px] text-stone-400 md:hidden">
                                            {!! $statusBadge !!}
                                            <span class="sm:hidden">{{ $item->created_at->translatedFormat('d M Y, H:i') }}</span>
                                            <span>{{ $item->messages->count() }} pesan</span>
                                        </p>
                                    </td>
                                    <td class="hidden whitespace-nowrap sm:table-cell">
                                        <p class="font-medium text-stone-700">{{ $item->created_at->translatedFormat('d M Y') }}</p>
                                        <p class="text-xs text-stone-400">{{ $item->created_at->format('H:i') }} WIB</p>
                                    </td>
                                    <td class="hidden md:table-cell">{!! $statusBadge !!}</td>
                                    <td class="hidden lg:table-cell">
                                        @if ($petugas($item))
                                            <span class="inline-flex items-center gap-1.5 text-stone-700"><x-icon name="user-round" class="size-3.5 text-stone-400" /> {{ $petugas($item) }}</span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 text-stone-500"><x-icon name="bot" class="size-3.5 text-gold-600" /> MUI Bot</span>
                                        @endif
                                    </td>
                                    <td class="hidden whitespace-nowrap text-stone-600 tabular-nums lg:table-cell">{{ $item->messages->count() }} pesan</td>
                                    <td class="hidden whitespace-nowrap text-stone-600 xl:table-cell">{{ $durasi ?? '—' }}</td>
                                    <td class="text-right">
                                        <a href="{{ route('admin.livechat.transcript', $item->id) }}" target="_blank" rel="noopener" class="btn btn-outline btn-sm whitespace-nowrap" title="Cetak / detail transkrip" aria-label="Cetak transkrip percakapan {{ $item->nama_pengunjung }}">
                                            <x-icon name="printer" class="size-4" /> <span class="hidden sm:inline">Cetak / Detail</span>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-14 text-center">
                                        <span class="mx-auto grid size-12 place-items-center rounded-2xl bg-brand-50 text-brand-600"><x-icon name="history" class="size-6" /></span>
                                        <p class="mt-3 font-semibold text-ink-900">{{ filled($search) ? 'Tidak ada riwayat yang cocok dengan pencarian' : 'Belum ada riwayat percakapan' }}</p>
                                        <p class="mt-1 text-xs text-stone-500">Sesi yang selesai atau dilayani chatbot akan tersimpan di sini.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($historySessions->hasPages())
                    <div class="border-t border-stone-100 px-4 py-4">{{ $historySessions->links() }}</div>
                @endif
            </section>
        @endif

        {{-- TAB 4: FAQ chatbot --}}
        @if ($tab === 'faq')
            <section class="mt-6" aria-label="FAQ chatbot"
                     x-data="faqManager({ storeUrl: @js(route('admin.livechat.faq.store')), updateUrl: @js(route('admin.livechat.faq.update', ['faq' => '__ID__'])), old: @js($faqOld), errors: @js($faqOld ? $errors->getMessages() : []) })">
                <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h2 class="flex items-center gap-2 font-bold text-ink-900"><x-icon name="bot" class="size-5 text-gold-600" /> Pertanyaan umum (FAQ chatbot)</h2>
                        <p class="mt-1 max-w-2xl text-sm text-stone-500">Dijawab otomatis oleh bot saat pengunjung bertanya atau ketika di luar jam layanan. Hanya FAQ aktif yang ditampilkan kepada pengunjung.</p>
                    </div>
                    <button type="button" @click="create()" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Tambah FAQ</button>
                </div>

                <div class="card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="table-clean">
                            <thead>
                                <tr>
                                    <th class="w-20">Urutan</th>
                                    <th>Pertanyaan &amp; jawaban</th>
                                    <th class="hidden md:table-cell">Kategori</th>
                                    <th class="hidden sm:table-cell">Status</th>
                                    <th class="text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($faqs as $faq)
                                    <tr @class(['bg-stone-50/60' => ! $faq->is_active])>
                                        <td><span class="grid size-9 place-items-center rounded-xl bg-stone-100 font-bold text-stone-700 tabular-nums">{{ $faq->urutan }}</span></td>
                                        <td class="max-w-2xl min-w-64">
                                            <p @class(['font-semibold', 'text-ink-900' => $faq->is_active, 'text-stone-500' => ! $faq->is_active])>{{ $faq->pertanyaan }}</p>
                                            <p class="mt-1 line-clamp-2 text-xs leading-relaxed text-stone-500">{{ $faq->jawaban }}</p>
                                            <p class="mt-1.5 flex flex-wrap gap-1.5 md:hidden">
                                                <span class="badge badge-blue">{{ $faq->kategori ?: 'Umum' }}</span>
                                                <span @class(['badge sm:hidden', 'badge-green' => $faq->is_active, 'badge-gray' => ! $faq->is_active])>{{ $faq->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                            </p>
                                        </td>
                                        <td class="hidden md:table-cell"><span class="badge badge-blue">{{ $faq->kategori ?: 'Umum' }}</span></td>
                                        <td class="hidden sm:table-cell">
                                            <span @class(['badge', 'badge-green' => $faq->is_active, 'badge-gray' => ! $faq->is_active])>
                                                <x-icon :name="$faq->is_active ? 'circle-check-big' : 'eye-off'" class="size-3" /> {{ $faq->is_active ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="flex justify-end gap-1.5">
                                                <button type="button" @click="edit(@js($faq->only(['id', 'pertanyaan', 'kategori', 'urutan', 'jawaban', 'is_active'])))" class="grid size-8 place-items-center rounded-lg text-stone-500 hover:bg-brand-50 hover:text-brand-700" title="Ubah" aria-label="Ubah FAQ"><x-icon name="pencil" class="size-4" /></button>
                                                <form method="POST" action="{{ route('admin.livechat.faq.destroy', $faq->id) }}" data-confirm="FAQ “{{ \Illuminate\Support\Str::limit($faq->pertanyaan, 90) }}” akan dihapus permanen dan tidak lagi dijawab chatbot." data-confirm-title="Hapus FAQ chatbot?" data-confirm-button="Ya, hapus">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="grid size-8 place-items-center rounded-lg text-stone-500 hover:bg-red-50 hover:text-red-600" title="Hapus" aria-label="Hapus FAQ"><x-icon name="trash-2" class="size-4" /></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-14 text-center">
                                            <span class="mx-auto grid size-12 place-items-center rounded-2xl bg-brand-50 text-brand-600"><x-icon name="bot" class="size-6" /></span>
                                            <p class="mt-3 font-semibold text-ink-900">Belum ada FAQ chatbot</p>
                                            <p class="mt-1 text-xs text-stone-500">Tambahkan pertanyaan umum agar chatbot dapat menjawab pengunjung secara otomatis.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <x-admin.modal title="mode === 'edit' ? 'Ubah FAQ Chatbot' : 'Tambah FAQ Chatbot'" icon="bot">
                    <form method="POST" :action="mode === 'edit' ? updateUrl.replace('__ID__', data.id) : storeUrl" @submit="saving = true" class="flex min-h-0 flex-1 flex-col">
                        @csrf
                        <input type="hidden" name="_method" value="PUT" :disabled="mode !== 'edit'">
                        <input type="hidden" name="_faq_form" :value="mode === 'edit' ? data.id : 'baru'">
                        <div class="scrollbar-thin flex-1 space-y-5 overflow-y-auto p-6">
                            <div>
                                <label for="faq-pertanyaan" class="label">Pertanyaan <span class="text-red-500">*</span></label>
                                <input id="faq-pertanyaan" x-ref="first" name="pertanyaan" x-model="data.pertanyaan" type="text" maxlength="255" required
                                       placeholder="Contoh: Berapa biaya permohonan sertifikasi halal?" class="input" :class="error('pertanyaan') && 'input-error'">
                                <p class="field-error" x-show="error('pertanyaan')" x-text="error('pertanyaan')"></p>
                            </div>
                            <div class="grid gap-5 sm:grid-cols-3">
                                <div class="sm:col-span-2">
                                    <label for="faq-kategori" class="label">Kategori <span class="font-normal text-stone-400">(opsional)</span></label>
                                    <input id="faq-kategori" name="kategori" x-model="data.kategori" type="text" maxlength="100" list="faq-kategori-list"
                                           placeholder="Contoh: Konsultasi, Halal, Fatwa, Umum" class="input" :class="error('kategori') && 'input-error'">
                                    <datalist id="faq-kategori-list">
                                        @foreach ($faqs->pluck('kategori')->filter()->unique()->sort() as $kategori)
                                            <option value="{{ $kategori }}"></option>
                                        @endforeach
                                    </datalist>
                                    <p class="field-error" x-show="error('kategori')" x-text="error('kategori')"></p>
                                </div>
                                <div>
                                    <label for="faq-urutan" class="label">Urutan tampil</label>
                                    <input id="faq-urutan" name="urutan" x-model="data.urutan" type="number" class="input" :class="error('urutan') && 'input-error'">
                                    <p class="mt-1.5 text-xs text-stone-500">Angka kecil tampil lebih dulu</p>
                                </div>
                            </div>
                            <div>
                                <label for="faq-jawaban" class="label">Jawaban chatbot <span class="text-red-500">*</span></label>
                                <textarea id="faq-jawaban" name="jawaban" x-model="data.jawaban" rows="7" required placeholder="Tuliskan jawaban yang akan diberikan oleh chatbot…"
                                          class="input leading-relaxed" :class="error('jawaban') && 'input-error'"></textarea>
                                <p class="field-error" x-show="error('jawaban')" x-text="error('jawaban')"></p>
                                <p class="mt-1.5 text-xs text-stone-500">Tips: apit teks dengan <code class="rounded bg-stone-100 px-1">**</code> untuk huruf tebal; tautan https:// otomatis dapat diklik.</p>
                            </div>
                            <template x-if="mode === 'edit'">
                                <label class="flex cursor-pointer items-center justify-between gap-4 rounded-xl border border-stone-200 p-4 hover:border-brand-300">
                                    <span>
                                        <span class="block text-sm font-semibold text-ink-900">FAQ aktif</span>
                                        <span class="block text-xs text-stone-500">FAQ nonaktif tidak ditampilkan & tidak dipakai chatbot.</span>
                                    </span>
                                    <span class="relative inline-flex shrink-0">
                                        <input type="checkbox" name="is_active" value="1" x-model="data.is_active" class="peer sr-only">
                                        <span class="h-6 w-11 rounded-full bg-stone-300 transition peer-checked:bg-brand-600 peer-focus-visible:ring-4 peer-focus-visible:ring-brand-500/20"></span>
                                        <span class="absolute top-0.5 left-0.5 size-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                                    </span>
                                </label>
                            </template>
                        </div>
                        <footer class="flex shrink-0 justify-end gap-2 border-t border-stone-100 bg-stone-50/60 px-6 py-4">
                            <button type="button" @click="close()" class="btn btn-outline">Batal</button>
                            <button type="submit" class="btn btn-primary" :disabled="saving">
                                <x-icon name="loader-circle" class="size-4 animate-spin" x-show="saving" x-cloak />
                                <x-icon name="save" class="size-4" x-show="!saving" />
                                <span x-text="mode === 'edit' ? 'Simpan Perubahan' : 'Simpan FAQ'">Simpan</span>
                            </button>
                        </footer>
                    </form>
                </x-admin.modal>
            </section>
        @endif
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                const kit = window.LiveChatKit;

                Alpine.data('liveChatIndex', ({ pollUrl }) => ({
                    feed: kit.readFeed(document) ?? { waiting: [], active: [] },
                    sound: kit.enabled(),
                    online: true,
                    refreshing: false,
                    polling: false,
                    signature: '',
                    fresh: [],
                    now: Date.now(),
                    permission: 'Notification' in window ? Notification.permission : 'unsupported',
                    timers: [],
                    init() {
                        // Acuan awal = isi halaman ini, sehingga perubahan sebelum polling pertama tetap terdeteksi.
                        this.signature = kit.signature(this.feed);
                        this.poll();
                        this.timers.push(setInterval(() => this.poll(), 3000));
                        this.timers.push(setInterval(() => this.refresh(), 20000));
                        this.timers.push(setInterval(() => { this.now = Date.now(); }, 15000));
                        window.addEventListener('beforeunload', () => this.timers.forEach(clearInterval));
                    },
                    get canAskPermission() {
                        return this.permission === 'default';
                    },
                    /** Ringkasan antrian (ringan) setiap 3 detik; bila berubah, muat ulang daftar sesi. */
                    async poll() {
                        if (this.polling) return;
                        this.polling = true;
                        try {
                            const signature = kit.overviewSignature(await MUIAdmin.http(pollUrl));
                            if (signature !== this.signature) {
                                this.signature = signature;
                                this.refresh();
                            }
                            this.online = true;
                        } catch {
                            this.online = false;
                        } finally {
                            this.polling = false;
                        }
                    },
                    async refresh() {
                        if (this.refreshing) return;
                        this.refreshing = true;
                        try {
                            const feed = await kit.fetchFeed(location.href);
                            if (feed) this.apply(feed);
                            this.online = true;
                        } catch {
                            this.online = false;
                        } finally {
                            this.refreshing = false;
                            this.now = Date.now();
                        }
                    },
                    apply(feed) {
                        const known = new Set(this.feed.waiting.map((s) => s.id));
                        const baru = feed.waiting.filter((s) => !known.has(s.id));
                        this.feed = feed;
                        if (!baru.length) return;
                        const ids = baru.map((s) => s.id);
                        this.fresh = [...this.fresh, ...ids];
                        setTimeout(() => { this.fresh = this.fresh.filter((id) => !ids.includes(id)); }, 10000);
                        kit.bell();
                        kit.attention(baru.length);
                        kit.notify('Antrian Chat Baru Masuk', `Pengunjung: ${baru[0].nama || 'Masyarakat'} membutuhkan bantuan.`);
                    },
                    toggleSound() {
                        this.sound = !this.sound;
                        kit.setEnabled(this.sound);
                        if (this.sound) {
                            kit.unlock();
                            setTimeout(() => kit.bell(), 60);
                            this.askPermission();
                        }
                        MUIAdmin.toast(this.sound ? 'Suara notifikasi diaktifkan.' : 'Suara notifikasi dimatikan.', 'info');
                    },
                    async askPermission() {
                        await kit.requestPermission();
                        this.permission = 'Notification' in window ? Notification.permission : 'unsupported';
                    },
                    urgency(s) {
                        const m = kit.minutesSince(s.masuk, this.now);
                        if (m >= 10) return { bar: 'bg-red-500', badge: 'badge-red' };
                        if (m >= 5) return { bar: 'bg-orange-400', badge: 'badge-gold' };
                        return { bar: 'bg-gold-400', badge: 'badge-green' };
                    },
                    waitText(s) {
                        const m = kit.minutesSince(s.masuk, this.now);
                        if (m < 1) return '< 1 menit';
                        return m < 60 ? `${m} menit` : `${Math.floor(m / 60)} jam ${m % 60} menit`;
                    },
                    clock: (iso) => kit.clock(iso),
                    ago(iso) {
                        return kit.ago(iso, this.now);
                    },
                    preview(s) {
                        const who = { operator: 'Petugas: ', bot: 'Bot: ', pengunjung: '' }[s.pengirim] ?? '';
                        return s.pratinjau ? who + s.pratinjau : 'Belum ada pesan';
                    },
                }));

                Alpine.data('faqManager', ({ storeUrl, updateUrl, old, errors }) => ({
                    storeUrl,
                    updateUrl,
                    open: false,
                    saving: false,
                    mode: 'create',
                    errors: {},
                    defaults: { id: null, pertanyaan: '', kategori: '', urutan: 0, jawaban: '', is_active: true },
                    data: {},
                    init() {
                        this.data = { ...this.defaults };
                        // Validasi gagal: buka kembali formulir dengan isian & pesan galat sebelumnya.
                        if (old) {
                            const editing = old.id && old.id !== 'baru';
                            this.mode = editing ? 'edit' : 'create';
                            this.data = { ...this.defaults, ...old, id: editing ? old.id : null };
                            this.errors = errors ?? {};
                            this.open = true;
                        }
                    },
                    create() {
                        this.show('create', { ...this.defaults });
                    },
                    edit(faq) {
                        this.show('edit', { ...this.defaults, ...faq, kategori: faq.kategori ?? '' });
                    },
                    show(mode, data) {
                        this.mode = mode;
                        this.data = data;
                        this.errors = {};
                        this.saving = false;
                        this.open = true;
                        this.$nextTick(() => this.$refs.first?.focus());
                    },
                    close() {
                        this.open = false;
                    },
                    error(field) {
                        return this.errors?.[field]?.[0] ?? null;
                    },
                }));
            });
        </script>
    @endpush
</x-layouts.admin>
