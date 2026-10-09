@php
    $tz = 'Asia/Jakarta';
    $user = auth()->user();
    $now = now($tz);
    $nama = $user->name_gelar ?: $user->name;
    $sapaan = match (true) {
        $now->hour < 11 => 'Selamat pagi',
        $now->hour < 15 => 'Selamat siang',
        $now->hour < 18 => 'Selamat sore',
        default => 'Selamat malam',
    };

    // Tanggal Hijriah (kalender Umm al-Qura) — disembunyikan bila ekstensi intl tidak tersedia.
    $hijri = null;
    try {
        $bulanHijri = ['Muharram', 'Safar', 'Rabiul Awal', 'Rabiul Akhir', 'Jumadil Awal', 'Jumadil Akhir', 'Rajab', "Sya'ban", 'Ramadhan', 'Syawal', "Dzulqa'dah", 'Dzulhijjah'];
        $fmtHijri = \IntlDateFormatter::create('en_US@calendar=islamic-umalqura', \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, $tz, \IntlDateFormatter::TRADITIONAL, 'd/M/y');
        [$hd, $hm, $hy] = array_map('intval', explode('/', (string) $fmtHijri?->format($now->getTimestamp())) + [0, 0, 0]);
        $hijri = isset($bulanHijri[$hm - 1]) && $hd > 0 && $hy > 0 ? "{$hd} {$bulanHijri[$hm - 1]} {$hy} H" : null;
    } catch (\Throwable) {
        $hijri = null;
    }

    $angka = fn ($n) => number_format((int) $n, 0, ',', '.');
    $tgl = fn ($date, string $format = 'j M Y') => $date ? $date->copy()->setTimezone($tz)->translatedFormat($format) : '—';
    $persen = fn ($bagian, $semua) => $semua > 0 ? (int) round($bagian / $semua * 100) : 0;
    $inisial = fn (string $nama) => collect(preg_split('/\s+/', trim($nama)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');

    $perluRespon = $stats['chat_menunggu'] + $stats['konsultasi_pending'];
    $statusBerita = ['published' => ['badge-green', 'Terbit'], 'draft' => ['badge-gold', 'Draft'], 'archived' => ['badge-gray', 'Diarsipkan']];
    $nada = [
        'brand' => 'bg-brand-50 text-brand-700 ring-brand-100',
        'gold' => 'bg-gold-50 text-gold-700 ring-gold-100',
        'blue' => 'bg-sky-50 text-sky-700 ring-sky-100',
        'red' => 'bg-red-50 text-red-600 ring-red-100',
        'purple' => 'bg-violet-50 text-violet-700 ring-violet-100',
        'stone' => 'bg-stone-100 text-stone-600 ring-stone-200',
    ];

    // Grafik aktivitas bulanan (warna tervalidasi untuk buta warna; urutan tetap per entitas).
    $warnaSeri = ['berita' => '#0284c7', 'konsultasi' => '#177a53', 'fatwa' => '#c9951f', 'surat' => '#7c3aed'];
    $bulan = $activity['months'];
    $seri = array_map(fn ($s) => $s + ['color' => $warnaSeri[$s['key']] ?? '#78716c'], $activity['series']);
    $totalBulan = array_map(fn ($i) => array_sum(array_column(array_column($seri, 'data'), $i)), array_keys($bulan));
    $puncak = max([0, ...$totalBulan]);
    $kasar = max($puncak, 4) / 5;
    $mag = 10 ** floor(log10($kasar));
    $langkah = max(1, (int) collect([1, 2, 5, 10])->map(fn ($m) => $m * $mag)->first(fn ($s) => $s >= $kasar));
    $sumbuMax = max((int) (ceil(max($puncak, 1) / $langkah) * $langkah), $langkah * 2);
    $ticks = range(0, $sumbuMax, $langkah);
    $grandTotal = array_sum($totalBulan);

    $pintasan = [
        ['Tulis Berita', 'Buat artikel baru', 'pen-line', route('admin.berita.create'), 'brand'],
        ['Live Chat', 'Layani pengunjung', 'messages-square', route('admin.livechat.index'), 'red'],
        ['Unggah Fatwa', 'Kelola dokumen fatwa', 'scale', route('admin.fatwa.index'), 'gold'],
        ['Kelola Pengguna', 'Akun admin & operator', 'users', route('admin.users.index'), 'blue'],
        ['Hak Akses Operator', 'Bagi tugas & menu', 'shield-check', route('admin.operator-permissions.index'), 'purple'],
        ['Pengaturan Web', 'Profil & kontak situs', 'settings', route('admin.pengaturan.index'), 'stone'],
    ];
    $meter = [
        ['Konsultasi terjawab', $stats['konsultasi_dijawab'], $stats['total_konsultasi'], route('admin.konsultasi.index')],
        ['Berita terbit', $stats['berita_published'], $stats['total_berita'], route('admin.berita.index')],
        ['Fatwa terpublikasi', $stats['fatwa_published'], $stats['total_fatwa'], route('admin.fatwa.index')],
    ];
@endphp

<x-layouts.admin title="Dashboard" :header="'Ringkasan aktivitas sistem informasi '.$site['site_short']">
    {{-- ===== Sambutan ===== --}}
    <section class="bg-gradient-brand relative overflow-hidden rounded-3xl text-white shadow-[var(--shadow-lift)]">
        <div class="pattern-islamic absolute inset-0"></div>
        <div class="absolute -top-24 -right-20 size-80 rounded-full bg-gold-400/20 blur-3xl"></div>
        <div class="absolute -bottom-32 left-1/3 size-80 rounded-full bg-brand-400/20 blur-3xl"></div>
        <div class="relative grid gap-6 p-6 sm:p-8 lg:grid-cols-[minmax(0,1fr)_18rem] lg:items-center lg:gap-10">
            <div class="min-w-0">
                <p class="eyebrow text-gold-300!">Dashboard Administrator</p>
                <h2 class="mt-3 font-display text-[1.75rem] leading-tight font-semibold text-balance text-white sm:text-4xl">Assalamu'alaikum, {{ $nama }}</h2>
                <p class="mt-3 max-w-2xl text-sm leading-relaxed text-pretty text-white/75 sm:text-[15px]">{{ $sapaan }}. Pantau layanan umat, publikasi, dan arsip digital {{ $site['site_short'] }} dari satu tempat.</p>
                <div class="mt-5 flex flex-wrap items-center gap-2 text-xs font-semibold">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 ring-1 ring-white/15"><x-icon name="shield-check" class="size-3.5 text-gold-300" /> Administrator · akses penuh</span>
                    @if ($perluRespon > 0)
                        <a href="#perlu-tindakan" class="inline-flex items-center gap-2 rounded-full bg-gold-300 px-3 py-1.5 text-brand-950 transition hover:bg-gold-200">
                            <span class="relative flex size-2"><span class="absolute inline-flex size-full animate-ping rounded-full bg-red-500 opacity-75"></span><span class="relative inline-flex size-2 rounded-full bg-red-600"></span></span>
                            {{ $perluRespon }} permintaan menunggu tanggapan
                        </a>
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-brand-100 ring-1 ring-white/15"><x-icon name="circle-check-big" class="size-3.5 text-brand-300" /> Semua layanan tertangani</span>
                    @endif
                </div>
                <div class="mt-6 grid grid-cols-2 gap-2.5 sm:flex sm:flex-wrap">
                    <a href="{{ route('admin.berita.create') }}" class="btn btn-gold col-span-2"><x-icon name="pen-line" class="size-4" /> Tulis Berita</a>
                    <a href="{{ route('admin.livechat.index') }}" class="btn btn-glass px-3 sm:px-5"><x-icon name="messages-square" class="size-4" /> Live Chat</a>
                    <a href="{{ route('home.public') }}" target="_blank" rel="noopener" class="btn btn-glass px-3 sm:px-5"><x-icon name="external-link" class="size-4" /> Lihat Website</a>
                </div>
            </div>

            <div class="rounded-2xl border border-white/15 bg-white/[.07] p-5 shadow-2xl backdrop-blur-md">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-[11px] font-bold tracking-[.2em] text-gold-300 uppercase">Hari ini</p>
                    <x-icon name="moon-star" class="size-4 text-gold-300" />
                </div>
                @if ($hijri)
                    <p class="mt-3 font-display text-2xl leading-snug font-semibold text-white">{{ $hijri }}</p>
                @endif
                <p class="mt-1 text-sm text-white/75">{{ $now->translatedFormat('l, j F Y') }}</p>
                <div class="mt-4 flex items-end justify-between gap-3 border-t border-white/10 pt-4">
                    <span class="text-xs text-white/60">Waktu Batanghari</span>
                    <span class="font-display text-2xl leading-none font-semibold text-white"><span x-data="wibClock" x-text="text">{{ $now->format('H:i') }}</span> <span class="font-sans text-xs font-bold text-gold-300">WIB</span></span>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== Statistik utama ===== --}}
    {{-- Ponsel: baris geser (snap) agar ringkas; tablet ke atas: grid. --}}
    <div class="scrollbar-none -mx-4 mt-5 flex snap-x snap-mandatory scroll-px-4 gap-3 overflow-x-auto px-4 pt-1 pb-3 sm:mx-0 sm:mt-6 sm:grid sm:snap-none sm:grid-cols-2 sm:gap-4 sm:overflow-visible sm:p-0 xl:grid-cols-4" aria-label="Statistik utama">
        <x-stat-card class="w-[82%] shrink-0 snap-start sm:w-auto" label="Layanan perlu respons" icon="bell-ring" :tone="$perluRespon > 0 ? 'red' : 'brand'" :value="$angka($perluRespon)"
                     :note="$stats['chat_menunggu'].' chat · '.$stats['konsultasi_pending'].' konsultasi'"
                     :href="$stats['chat_menunggu'] > 0 ? route('admin.livechat.index') : route('admin.konsultasi.index')" />
        <x-stat-card class="w-[82%] shrink-0 snap-start sm:w-auto" label="Berita & artikel" icon="newspaper" tone="blue" :value="$angka($stats['total_berita'])"
                     :note="$angka($stats['total_views_berita']).' total pembaca'" :href="route('admin.berita.index')" />
        <x-stat-card class="w-[82%] shrink-0 snap-start sm:w-auto" label="Dokumen fatwa" icon="scale" tone="gold" :value="$angka($stats['total_fatwa'])"
                     :note="$angka($stats['total_surat']).' arsip surat tersimpan'" :href="route('admin.fatwa.index')" />
        <x-stat-card class="w-[82%] shrink-0 snap-start sm:w-auto" label="Operator bertugas" icon="users" tone="purple" :value="$angka($stats['total_operator'])"
                     note="Kelola hak akses operator" :href="route('admin.operator-permissions.index')" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="min-w-0 space-y-6 xl:col-span-2">
            {{-- ===== Perlu tindakan ===== --}}
            <section id="perlu-tindakan" class="card scroll-mt-24 overflow-hidden">
                <header class="flex flex-wrap items-center justify-between gap-3 border-b border-stone-100 px-5 py-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-gold-50 text-gold-700 ring-1 ring-gold-100"><x-icon name="bell-ring" class="size-5" /></span>
                        <div class="min-w-0">
                            <h3 class="text-[15px] font-bold text-ink-900">Perlu tindakan</h3>
                            <p class="text-xs text-stone-500">{{ $stats['chat_menunggu'] }} live chat & {{ $stats['konsultasi_pending'] }} pertanyaan Tanya Ulama menunggu tanggapan</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('admin.livechat.index') }}" class="btn btn-outline btn-sm"><x-icon name="messages-square" class="size-4" /> Live Chat</a>
                        <a href="{{ route('admin.konsultasi.index') }}" class="btn btn-outline btn-sm"><x-icon name="message-circle-question" class="size-4" /> Konsultasi</a>
                    </div>
                </header>

                @if ($waitingChats->isEmpty() && $pendingKonsultasi->isEmpty())
                    <div class="flex flex-col items-center px-6 py-10 text-center">
                        <span class="grid size-12 place-items-center rounded-2xl bg-brand-50 text-brand-600 ring-8 ring-brand-50/50"><x-icon name="circle-check-big" class="size-6" /></span>
                        <p class="mt-4 font-semibold text-ink-900">Alhamdulillah, semua sudah tertangani</p>
                        <p class="mt-1 max-w-md text-sm text-stone-500">Seluruh antrian live chat dan pertanyaan konsultasi saat ini telah tertangani.</p>
                    </div>
                @else
                    <ul class="divide-y divide-stone-100">
                        @foreach ($waitingChats as $chat)
                            <li class="flex items-center gap-3 px-5 py-3.5 transition hover:bg-brand-50/40 sm:gap-4">
                                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-red-50 text-red-600 ring-1 ring-red-100"><x-icon name="messages-square" class="size-[18px]" /></span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                        <p class="truncate font-semibold text-ink-900">{{ $chat->nama_pengunjung }}</p>
                                        <span class="badge badge-red">Live chat · Antrian #{{ $chat->antrian_nomor }}</span>
                                    </div>
                                    <p class="mt-0.5 line-clamp-1 text-sm text-stone-600">{{ $chat->topik ?: 'Layanan umum' }}</p>
                                    <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs text-stone-500">
                                        <span class="inline-flex items-center gap-1 whitespace-nowrap"><x-icon name="clock" class="size-3.5" /> {{ $chat->created_at->diffForHumans() }}</span>
                                        <span class="font-medium whitespace-nowrap text-red-600">Menunggu respons</span>
                                    </p>
                                </div>
                                <a href="{{ route('admin.livechat.show', $chat->id) }}" class="btn btn-primary btn-sm shrink-0" aria-label="Respon chat {{ $chat->nama_pengunjung }}"><x-icon name="reply" class="size-4" /> <span class="hidden sm:inline">Respon</span></a>
                            </li>
                        @endforeach
                        @foreach ($pendingKonsultasi as $k)
                            <li class="flex items-center gap-3 px-5 py-3.5 transition hover:bg-brand-50/40 sm:gap-4">
                                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-gold-50 text-gold-700 ring-1 ring-gold-100"><x-icon name="message-circle-question" class="size-[18px]" /></span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                        <p class="truncate font-semibold text-ink-900">{{ $k->nama }}</p>
                                        <span class="badge badge-gold">Tanya Ulama{{ $k->kategori ? ' · '.$k->kategori : '' }}</span>
                                    </div>
                                    <p class="mt-0.5 line-clamp-1 text-sm text-stone-600">{{ Str::limit(strip_tags($k->pertanyaan), 140) }}</p>
                                    <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs text-stone-500">
                                        <span class="inline-flex items-center gap-1 whitespace-nowrap"><x-icon name="clock" class="size-3.5" /> {{ $k->created_at->diffForHumans() }}</span>
                                        <span class="font-medium whitespace-nowrap text-gold-700">Menunggu jawaban</span>
                                    </p>
                                </div>
                                <a href="{{ route('admin.konsultasi.show', $k->id) }}" class="btn btn-outline btn-sm shrink-0" aria-label="Lihat konsultasi {{ $k->nama }}"><x-icon name="eye" class="size-4" /> <span class="hidden sm:inline">Lihat</span></a>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($activeChats->isNotEmpty())
                    <div class="border-t border-stone-100 bg-stone-50/60 px-5 py-4">
                        <p class="text-[11px] font-bold tracking-wider text-stone-500 uppercase">Chat sedang berlangsung</p>
                        <ul class="mt-3 grid gap-2 sm:grid-cols-2">
                            @foreach ($activeChats as $chat)
                                <li>
                                    <a href="{{ route('admin.livechat.show', $chat->id) }}" class="flex items-center gap-3 rounded-xl border border-stone-200 bg-white px-3 py-2.5 transition hover:border-brand-300">
                                        <span class="relative flex size-2.5 shrink-0"><span class="absolute inline-flex size-full animate-ping rounded-full bg-brand-400 opacity-60"></span><span class="relative inline-flex size-2.5 rounded-full bg-brand-500"></span></span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-semibold text-ink-900">{{ $chat->nama_pengunjung }}</span>
                                            <span class="block truncate text-xs text-stone-500">Dilayani {{ $chat->operator?->name_gelar ?: ($chat->operator?->name ?? 'petugas') }}</span>
                                        </span>
                                        <x-icon name="arrow-up-right" class="size-4 text-stone-400" />
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </section>

            {{-- ===== Grafik aktivitas ===== --}}
            <section class="card" x-data="dashChart(@js(['months' => $bulan, 'series' => $seri]))">
                <header class="flex flex-wrap items-center justify-between gap-3 border-b border-stone-100 px-5 py-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100"><x-icon name="chart-column-stacked" class="size-5" /></span>
                        <div class="min-w-0">
                            <h3 class="text-[15px] font-bold text-ink-900">Aktivitas 6 bulan terakhir</h3>
                            <p class="text-xs text-stone-500">Konten & layanan per bulan, {{ $bulan[0]['long'] }} – {{ last($bulan)['long'] }}</p>
                        </div>
                    </div>
                    <div class="flex rounded-xl bg-stone-100 p-1 text-xs font-semibold" role="group" aria-label="Pilih tampilan data">
                        <button type="button" @click="table = false" :aria-pressed="!table" class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 transition" :class="!table ? 'bg-white text-brand-700 shadow-sm' : 'text-stone-500 hover:text-stone-700'"><x-icon name="chart-column" class="size-3.5" /> Grafik</button>
                        <button type="button" @click="table = true" :aria-pressed="table" class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 transition" :class="table ? 'bg-white text-brand-700 shadow-sm' : 'text-stone-500 hover:text-stone-700'"><x-icon name="table" class="size-3.5" /> Tabel</button>
                    </div>
                </header>

                <div class="p-5">
                    {{-- Legenda + pembacaan nilai (total periode, atau bulan yang disorot) --}}
                    <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1" aria-live="polite">
                        <p class="text-xs font-semibold tracking-wide text-stone-500 uppercase" x-text="heading">Total 6 bulan terakhir</p>
                        <p class="text-xs text-stone-500">Jumlah <b class="text-sm text-ink-900 tabular-nums" x-text="grandTotal">{{ $grandTotal }}</b></p>
                    </div>
                    <ul class="mt-2.5 flex flex-wrap gap-x-5 gap-y-2">
                        @foreach ($seri as $s)
                            <li class="flex items-center gap-2 text-sm text-stone-600">
                                <span class="size-2.5 shrink-0 rounded-[3px]" style="background: {{ $s['color'] }}"></span>
                                {{ $s['label'] }}
                                <b class="font-semibold text-ink-900 tabular-nums" x-text="value({{ $loop->index }})">{{ $s['total'] }}</b>
                            </li>
                        @endforeach
                    </ul>

                    <div x-show="!table">
                        <div class="mt-7 flex gap-3" @mouseleave="active = null">
                            <div class="relative h-56 w-7 shrink-0 text-right text-[11px] text-stone-500 tabular-nums" aria-hidden="true">
                                @foreach ($ticks as $t)
                                    <span class="absolute right-0 translate-y-1/2 leading-none" style="bottom: {{ $t / $sumbuMax * 100 }}%">{{ $t }}</span>
                                @endforeach
                            </div>
                            <div class="relative h-56 min-w-0 flex-1">
                                @foreach ($ticks as $t)
                                    <div @class(['absolute inset-x-0 border-t', 'border-stone-300' => $t === 0, 'border-stone-100' => $t !== 0]) style="bottom: {{ $t / $sumbuMax * 100 }}%"></div>
                                @endforeach
                                <div class="absolute inset-0 grid" style="grid-template-columns: repeat({{ count($bulan) }}, minmax(0, 1fr))" role="list" aria-label="Grafik batang bertumpuk aktivitas per bulan">
                                    @foreach ($bulan as $i => $b)
                                        @php
                                            $tinggi = $totalBulan[$i] / $sumbuMax * 100;
                                            $isi = array_values(array_filter($seri, fn ($s) => $s['data'][$i] > 0));
                                        @endphp
                                        <div role="listitem" tabindex="0" class="relative h-full rounded-lg transition"
                                             :class="active === {{ $i }} && 'bg-brand-50/80'"
                                             @mouseenter="active = {{ $i }}" @focus="active = {{ $i }}" @blur="active = null" @click="active = {{ $i }}"
                                             aria-label="{{ $b['long'] }}: {{ collect($seri)->map(fn ($s) => $s['label'].' '.$s['data'][$i])->implode(', ') }}. Jumlah {{ $totalBulan[$i] }}.">
                                            <div class="absolute bottom-0 left-1/2 flex w-6 -translate-x-1/2 flex-col-reverse transition-opacity" style="height: {{ $tinggi }}%"
                                                 :class="active !== null && active !== {{ $i }} && 'opacity-35'">
                                                @foreach ($isi as $j => $s)
                                                    <span @class(['block w-full min-h-[3px]', 'rounded-t-[4px]' => $loop->last])
                                                          style="flex: {{ $s['data'][$i] }} 1 0%; background: {{ $s['color'] }};{{ $j > 0 ? ' box-shadow: inset 0 -2px 0 #fff;' : '' }}"></span>
                                                @endforeach
                                            </div>
                                            @if ($totalBulan[$i] > 0)
                                                <span class="absolute left-1/2 -translate-x-1/2 text-xs font-semibold text-stone-700 tabular-nums" style="bottom: calc({{ $tinggi }}% + 4px)">{{ $totalBulan[$i] }}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                                @if ($grandTotal === 0)
                                    <div class="pointer-events-none absolute inset-0 grid place-items-center">
                                        <p class="rounded-full bg-white px-4 py-2 text-sm text-stone-500 ring-1 ring-stone-200">Belum ada aktivitas dalam 6 bulan terakhir</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="mt-2 flex gap-3" aria-hidden="true">
                            <div class="w-7 shrink-0"></div>
                            <div class="grid flex-1 text-center text-xs text-stone-500" style="grid-template-columns: repeat({{ count($bulan) }}, minmax(0, 1fr))">
                                @foreach ($bulan as $b)
                                    <span @class(['font-semibold text-ink-900' => $loop->last])>{{ $b['short'] }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div x-show="table" x-cloak class="mt-5 overflow-x-auto rounded-xl ring-1 ring-stone-200">
                        <table class="table-clean">
                            <caption class="sr-only">Aktivitas per bulan selama 6 bulan terakhir</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Bulan</th>
                                    @foreach ($seri as $s)
                                        <th scope="col" class="text-right">{{ $s['label'] }}</th>
                                    @endforeach
                                    <th scope="col" class="text-right">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($bulan as $i => $b)
                                    <tr>
                                        <th scope="row" class="px-4 py-3 text-left font-medium whitespace-nowrap text-ink-900">{{ $b['long'] }}</th>
                                        @foreach ($seri as $s)
                                            <td class="text-right tabular-nums">{{ $s['data'][$i] }}</td>
                                        @endforeach
                                        <td class="text-right font-semibold text-ink-900 tabular-nums">{{ $totalBulan[$i] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-stone-50/80 text-ink-900">
                                    <th scope="row" class="px-4 py-3 text-left">Total</th>
                                    @foreach ($seri as $s)
                                        <td class="px-4 py-3 text-right font-semibold tabular-nums">{{ $s['total'] }}</td>
                                    @endforeach
                                    <td class="px-4 py-3 text-right font-bold tabular-nums">{{ $grandTotal }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <p class="mt-5 flex items-start gap-1.5 text-xs leading-relaxed text-stone-500">
                        <x-icon name="info" class="mt-0.5 size-3.5" />
                        <span>Berita dihitung menurut tanggal terbit, arsip surat menurut tanggal surat, fatwa & konsultasi menurut tanggal masuk (WIB). Arahkan kursor atau fokus ke batang untuk melihat rincian per bulan.</span>
                    </p>
                </div>
            </section>

            {{-- ===== Berita terbaru ===== --}}
            <section class="card overflow-hidden">
                <header class="flex flex-wrap items-center justify-between gap-3 border-b border-stone-100 px-5 py-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-sky-50 text-sky-700 ring-1 ring-sky-100"><x-icon name="newspaper" class="size-5" /></span>
                        <div class="min-w-0">
                            <h3 class="text-[15px] font-bold text-ink-900">Publikasi berita & artikel terkini</h3>
                            <p class="text-xs text-stone-500">{{ $angka($stats['berita_published']) }} dari {{ $angka($stats['total_berita']) }} berita telah terbit</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('admin.berita.index') }}" class="btn btn-ghost btn-sm">Lihat semua <x-icon name="arrow-right" class="size-4" /></a>
                        <a href="{{ route('admin.berita.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> Tulis</a>
                    </div>
                </header>

                @if ($latestBerita->isEmpty())
                    <div class="p-5">
                        <x-empty-state icon="newspaper" title="Belum ada data berita" message="Tulis berita pertama agar tampil di halaman publik website." />
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="table-clean">
                            <thead>
                                <tr>
                                    <th>Judul berita & penulis</th>
                                    <th class="hidden md:table-cell">Kategori</th>
                                    <th class="hidden sm:table-cell">Status</th>
                                    <th class="hidden text-right md:table-cell">Dibaca</th>
                                    <th class="text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($latestBerita as $berita)
                                    @php
                                        [$badgeStatus, $labelStatus] = $statusBerita[$berita->status] ?? ['badge-gray', ucfirst((string) $berita->status)];
                                        $kategori = $berita->kategori ?: 'Umum';
                                    @endphp
                                    <tr>
                                        <td class="align-middle">
                                            <div class="flex min-w-0 items-center gap-3">
                                                @if ($berita->gambar_url)
                                                    <img src="{{ $berita->gambar_url }}" alt="" loading="lazy" class="size-12 shrink-0 rounded-xl object-cover ring-1 ring-stone-200">
                                                @else
                                                    <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-sky-50 text-sky-700"><x-icon name="newspaper" class="size-5" /></span>
                                                @endif
                                                <div class="min-w-0">
                                                    <a href="{{ route('admin.berita.edit', $berita->id) }}" class="line-clamp-2 font-semibold text-ink-900 hover:text-brand-700">{{ $berita->judul }}</a>
                                                    <p class="mt-0.5 text-xs text-stone-500">
                                                        {{ $tgl($berita->tanggal_terbit) }}<span class="hidden sm:inline"> · {{ $berita->user?->name ?: 'Admin' }}</span><span class="md:hidden"> · {{ $kategori }}</span><span class="hidden sm:inline md:hidden"> · {{ $angka($berita->views) }} dibaca</span><span class="sm:hidden"> · {{ $labelStatus }}</span>
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="hidden align-middle md:table-cell">
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-stone-50 px-2.5 py-1 text-xs font-medium whitespace-nowrap text-stone-700 ring-1 ring-stone-200">
                                                <span class="size-2 rounded-full" style="background: {{ $kategoriWarna[$berita->kategori] ?? '#a8a29e' }}"></span>{{ $kategori }}
                                            </span>
                                        </td>
                                        <td class="hidden align-middle sm:table-cell"><span class="badge {{ $badgeStatus }}">{{ $labelStatus }}</span></td>
                                        <td class="hidden text-right align-middle whitespace-nowrap text-stone-600 tabular-nums md:table-cell"><x-icon name="eye" class="mr-1 inline size-3.5 text-stone-400" />{{ $angka($berita->views) }}</td>
                                        <td class="align-middle">
                                            <div class="flex justify-end gap-1">
                                                @if ($berita->status === 'published' && $berita->slug)
                                                    <a href="{{ route('berita.detail', $berita->slug) }}" target="_blank" rel="noopener" class="grid size-8 place-items-center rounded-lg text-stone-500 hover:bg-sky-50 hover:text-sky-700" title="Lihat di website" aria-label="Lihat berita di website"><x-icon name="external-link" class="size-4" /></a>
                                                @endif
                                                <a href="{{ route('admin.berita.edit', $berita->id) }}" class="grid size-8 place-items-center rounded-lg text-stone-500 hover:bg-brand-50 hover:text-brand-700" title="Ubah berita" aria-label="Ubah berita"><x-icon name="pencil" class="size-4" /></a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>

        {{-- ===== Kolom samping ===== --}}
        <aside class="grid content-start gap-6 sm:grid-cols-2 xl:grid-cols-1" aria-label="Pintasan & ringkasan">
            <section class="card p-5">
                <h3 class="flex items-center gap-2 text-[15px] font-bold text-ink-900"><x-icon name="zap" class="size-[18px] text-gold-500" /> Pintasan cepat</h3>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    @foreach ($pintasan as [$label, $hint, $icon, $url, $tone])
                        <a href="{{ $url }}" class="group relative flex flex-col rounded-2xl border border-stone-200 bg-white p-3.5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-[var(--shadow-soft)]">
                            <x-icon name="arrow-up-right" class="absolute top-3 right-3 size-3.5 text-stone-300 transition group-hover:text-brand-600" />
                            <span class="grid size-10 place-items-center rounded-xl ring-1 {{ $nada[$tone] }}"><x-icon :name="$icon" class="size-5" /></span>
                            <span class="mt-3 text-sm leading-snug font-semibold text-ink-900">{{ $label }}</span>
                            <span class="mt-0.5 text-xs leading-snug text-stone-500">{{ $hint }}</span>
                        </a>
                    @endforeach
                </div>
            </section>

            <section class="card p-5">
                <h3 class="flex items-center gap-2 text-[15px] font-bold text-ink-900"><x-icon name="activity" class="size-[18px] text-brand-600" /> Kinerja layanan & publikasi</h3>
                <dl class="mt-4 space-y-4">
                    @foreach ($meter as [$label, $bagian, $semua, $url])
                        @php $p = $persen($bagian, $semua); @endphp
                        <div>
                            <div class="flex items-baseline justify-between gap-3 text-sm">
                                <dt><a href="{{ $url }}" class="text-stone-600 hover:text-brand-700">{{ $label }}</a></dt>
                                <dd class="text-stone-500"><b class="font-semibold text-ink-900 tabular-nums">{{ $angka($bagian) }}</b>/{{ $angka($semua) }} · <b class="font-semibold text-brand-700 tabular-nums">{{ $semua > 0 ? $p.'%' : '–' }}</b></dd>
                            </div>
                            <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-brand-100" role="progressbar" aria-label="{{ $label }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $p }}">
                                <div class="h-full rounded-full bg-brand-600" style="width: {{ $p }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </dl>
                <div class="mt-5 border-t border-stone-100 pt-4">
                    <p class="flex items-center justify-between text-xs font-semibold text-stone-500"><span>Sesi live chat</span><span>{{ $angka($stats['total_chat']) }} total</span></p>
                    <div class="mt-2 grid grid-cols-3 gap-2 text-center">
                        @foreach ([['Menunggu', $stats['chat_menunggu'], 'text-red-600'], ['Berlangsung', $stats['chat_aktif'], 'text-brand-700'], ['Selesai', $stats['chat_selesai'], 'text-stone-700']] as [$label, $jumlah, $warna])
                            <div class="rounded-xl bg-stone-50 px-2 py-2.5 ring-1 ring-stone-100">
                                <p class="text-lg leading-none font-bold {{ $jumlah > 0 ? $warna : 'text-stone-400' }}">{{ $angka($jumlah) }}</p>
                                <p class="mt-1 text-[11px] text-stone-500">{{ $label }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="card p-5">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="flex items-center gap-2 text-[15px] font-bold text-ink-900"><x-icon name="user-cog" class="size-[18px] text-violet-600" /> Operator & tugas</h3>
                    <a href="{{ route('admin.operator-permissions.index') }}" class="flex items-center gap-0.5 text-xs font-semibold text-brand-700 hover:text-brand-900">Atur <x-icon name="chevron-right" class="size-3.5" /></a>
                </div>
                <p class="mt-1 text-xs text-stone-500">{{ $angka($stats['total_operator']) }} operator · {{ $angka($stats['total_admin']) }} administrator terdaftar</p>
                <ul class="mt-3 space-y-0.5">
                    @forelse ($operators as $op)
                        @php $jumlahTugas = count($op->getAssignedPermissions()); @endphp
                        <li>
                            <a href="{{ route('admin.operator-permissions.edit', $op->id) }}" class="-mx-2 flex items-center gap-3 rounded-xl px-2 py-2 transition hover:bg-stone-50" title="Atur hak akses {{ $op->name }}">
                                @if ($op->foto_url)
                                    <img src="{{ $op->foto_url }}" alt="" class="size-9 shrink-0 rounded-lg object-cover">
                                @else
                                    <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-gradient-to-br from-gold-200 to-gold-400 text-xs font-bold text-brand-950">{{ $inisial($op->name) }}</span>
                                @endif
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold text-ink-900">{{ $op->name_gelar ?: $op->name }}</span>
                                    <span class="block text-xs text-stone-500">{{ $jumlahTugas }} menu tugas aktif</span>
                                </span>
                                <span class="badge {{ $jumlahTugas > 0 ? 'badge-green' : 'badge-red' }}">{{ $jumlahTugas > 0 ? 'Aktif' : 'Nol tugas' }}</span>
                            </a>
                        </li>
                    @empty
                        <li class="rounded-xl bg-stone-50 px-4 py-6 text-center text-sm text-stone-500">
                            Belum ada akun operator.
                            <a href="{{ route('admin.users.index') }}" class="mt-1 block font-semibold text-brand-700 hover:text-brand-900">Tambah pengguna</a>
                        </li>
                    @endforelse
                </ul>
                @if ($stats['total_operator'] > $operators->count())
                    <a href="{{ route('admin.operator-permissions.index') }}" class="mt-3 block text-center text-xs font-semibold text-brand-700 hover:text-brand-900">Lihat semua {{ $stats['total_operator'] }} operator</a>
                @endif
            </section>

            <section class="card p-5">
                <h3 class="flex items-center gap-2 text-[15px] font-bold text-ink-900"><x-icon name="server" class="size-[18px] text-sky-600" /> Status sesi & sistem</h3>
                <dl class="mt-3 divide-y divide-stone-100 text-sm">
                    <div class="flex items-center justify-between gap-3 py-2.5">
                        <dt class="text-stone-500">Akun login</dt>
                        <dd class="truncate font-semibold text-ink-900">{{ $user->username }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 py-2.5">
                        <dt class="text-stone-500">Peran</dt>
                        <dd><span class="badge badge-green"><x-icon name="shield-check" class="size-3" /> Administrator</span></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 py-2.5">
                        <dt class="text-stone-500">Basis data</dt>
                        <dd class="flex items-center gap-1.5 font-semibold text-brand-700"><span class="size-2 rounded-full bg-brand-500 ring-4 ring-brand-100"></span> Terhubung</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 py-2.5">
                        <dt class="text-stone-500">Zona waktu aplikasi</dt>
                        <dd class="font-semibold text-ink-900">{{ config('app.timezone') }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 py-2.5">
                        <dt class="text-stone-500">Waktu Batanghari</dt>
                        <dd class="font-semibold text-ink-900 tabular-nums"><span x-data="wibClock(true)" x-text="text">{{ $now->format('H:i:s') }}</span> WIB</dd>
                    </div>
                </dl>
            </section>
        </aside>
    </div>

    {{-- ===== Fatwa & arsip surat terbaru ===== --}}
    <div class="mt-6 grid gap-6 md:grid-cols-2">
        <section class="card overflow-hidden">
            <header class="flex items-center justify-between gap-3 border-b border-stone-100 px-5 py-4">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-gold-50 text-gold-700 ring-1 ring-gold-100"><x-icon name="scale" class="size-[18px]" /></span>
                    <div class="min-w-0">
                        <h3 class="text-[15px] font-bold text-ink-900">Fatwa terbaru</h3>
                        <p class="text-xs text-stone-500">{{ $angka($stats['fatwa_published']) }} terpublikasi · {{ $angka($stats['total_views_fatwa']) }} kali dibaca</p>
                    </div>
                </div>
                <a href="{{ route('admin.fatwa.index') }}" class="btn btn-ghost btn-sm shrink-0">Kelola <x-icon name="chevron-right" class="size-4" /></a>
            </header>
            <ul class="divide-y divide-stone-100">
                @forelse ($latestFatwa as $f)
                    <li class="flex items-start gap-3 px-5 py-3.5">
                        <div class="min-w-0 flex-1">
                            <p class="line-clamp-2 text-sm leading-snug font-semibold text-ink-900">{{ $f->judul }}</p>
                            <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-stone-500">
                                <span>{{ $f->kategoriFatwa?->nama ?: 'Umum' }}</span><span aria-hidden="true">·</span><span>{{ $tgl($f->created_at) }}</span>
                                @unless ($f->publikasi)<span class="badge badge-gray">Draf</span>@endunless
                            </p>
                        </div>
                        @if ($f->file_url)
                            <a href="{{ $f->file_url }}" target="_blank" rel="noopener" class="grid size-8 shrink-0 place-items-center rounded-lg text-stone-500 hover:bg-red-50 hover:text-red-600" title="Buka PDF fatwa" aria-label="Buka PDF fatwa {{ Str::limit($f->judul, 40) }}"><x-icon name="file-text" class="size-4" /></a>
                        @endif
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-stone-500">Belum ada fatwa.</li>
                @endforelse
            </ul>
        </section>

        <section class="card overflow-hidden">
            <header class="flex items-center justify-between gap-3 border-b border-stone-100 px-5 py-4">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-violet-50 text-violet-700 ring-1 ring-violet-100"><x-icon name="folder-archive" class="size-[18px]" /></span>
                    <div class="min-w-0">
                        <h3 class="text-[15px] font-bold text-ink-900">Arsip surat terbaru</h3>
                        <p class="text-xs text-stone-500">{{ $angka($stats['total_surat']) }} surat tersimpan</p>
                    </div>
                </div>
                <a href="{{ route('admin.surat.index') }}" class="btn btn-ghost btn-sm shrink-0">Kelola <x-icon name="chevron-right" class="size-4" /></a>
            </header>
            <ul class="divide-y divide-stone-100">
                @forelse ($latestSurat as $s)
                    <li class="flex items-start gap-3 px-5 py-3.5">
                        <div class="min-w-0 flex-1">
                            <p class="line-clamp-2 text-sm leading-snug font-semibold text-ink-900">{{ $s->perihal ?: $s->nomor_surat }}</p>
                            <p class="mt-1 text-xs text-stone-500"><span class="font-mono text-[11px]">{{ $s->nomor_surat }}</span>@if ($s->tanggal_surat) · {{ $s->tanggal_surat->translatedFormat('j M Y') }}@endif</p>
                        </div>
                        @if ($s->file_url)
                            <a href="{{ $s->file_url }}" target="_blank" rel="noopener" class="grid size-8 shrink-0 place-items-center rounded-lg text-stone-500 hover:bg-violet-50 hover:text-violet-700" title="Buka berkas surat" aria-label="Buka berkas surat {{ $s->nomor_surat }}"><x-icon name="file-down" class="size-4" /></a>
                        @endif
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-stone-500">Belum ada arsip surat.</li>
                @endforelse
            </ul>
        </section>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                // Jam WIB (Asia/Jakarta) — tidak bergantung pada zona waktu perangkat.
                Alpine.data('wibClock', (seconds = false) => ({
                    text: '',
                    timer: null,
                    init() {
                        const opts = { timeZone: 'Asia/Jakarta', hour: '2-digit', minute: '2-digit', hour12: false, ...(seconds ? { second: '2-digit' } : {}) };
                        const tick = () => { this.text = new Date().toLocaleTimeString('id-ID', opts).replace(/\./g, ':'); };
                        tick();
                        this.timer = setInterval(tick, seconds ? 1000 : 15000);
                    },
                    destroy() {
                        clearInterval(this.timer);
                    },
                }));

                // Grafik aktivitas: legenda menampilkan total periode, atau rincian bulan yang disorot/difokus.
                Alpine.data('dashChart', ({ months, series }) => ({
                    months,
                    series,
                    active: null,
                    table: false,
                    get heading() {
                        return this.active === null ? `Total ${this.months.length} bulan terakhir` : this.months[this.active].long;
                    },
                    value(i) {
                        const s = this.series[i];
                        return this.active === null ? s.total : s.data[this.active];
                    },
                    get grandTotal() {
                        return this.series.reduce((sum, s, i) => sum + this.value(i), 0);
                    },
                }));
            });
        </script>
    @endpush
</x-layouts.admin>
