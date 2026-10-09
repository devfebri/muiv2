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
    $inisial = fn (?string $teks) => collect(preg_split('/\s+/', trim((string) $teks)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') ?: '?';

    $bisaChat = $user->hasMenuPermission('livechat');
    $bisaKonsultasi = $user->hasMenuPermission('konsultasi');
    $bisaBerita = $user->hasMenuPermission('berita');
    $bisaFatwa = $user->hasMenuPermission('fatwa');
    $bisaSurat = $user->hasMenuPermission('surat');

    $waitingChats = $operatorData['waiting_chats'] ?? collect();
    $myActiveChats = $operatorData['my_active_chats'] ?? collect();
    $pendingKonsultasi = $operatorData['pending_konsultasi'] ?? collect();
    $myAnswered = $operatorData['my_answered_konsultasi'] ?? collect();
    $myBerita = $operatorData['my_berita'] ?? collect();
    $latestBerita = $operatorData['latest_berita'] ?? collect();
    $latestFatwa = $operatorData['latest_fatwa'] ?? collect();
    $latestSurat = $operatorData['latest_surat'] ?? collect();

    $perluRespon = ($bisaChat ? $stats['chat_menunggu'] : 0) + ($bisaKonsultasi ? $stats['konsultasi_pending'] : 0);
    // Tanpa antrian layanan & berita, kolom utama pendek → fatwa/surat ditaruh di kolom utama (bukan baris penuh di bawah).
    $fatwaSuratDiUtama = ! ($bisaChat || $bisaKonsultasi || $bisaBerita);
    $statusBerita = ['published' => ['badge-green', 'Terbit'], 'draft' => ['badge-gold', 'Draft'], 'archived' => ['badge-gray', 'Diarsipkan']];
    $nada = [
        'brand' => 'bg-brand-50 text-brand-700 ring-brand-100',
        'gold' => 'bg-gold-50 text-gold-700 ring-gold-100',
        'blue' => 'bg-sky-50 text-sky-700 ring-sky-100',
        'red' => 'bg-red-50 text-red-600 ring-red-100',
        'purple' => 'bg-violet-50 text-violet-700 ring-violet-100',
        'stone' => 'bg-stone-100 text-stone-600 ring-stone-200',
    ];
    // Ikon & warna tiap menu tugas (selaras dengan menu samping panel).
    $ikonIzin = ['berita' => 'newspaper', 'kategori' => 'tag', 'surat' => 'folder-archive', 'fatwa' => 'scale', 'kategori-fatwa' => 'tags', 'livechat' => 'messages-square', 'konsultasi' => 'message-circle-question'];
    $nadaIzin = ['berita' => 'blue', 'kategori' => 'brand', 'surat' => 'purple', 'fatwa' => 'gold', 'kategori-fatwa' => 'gold', 'livechat' => 'red', 'konsultasi' => 'brand'];
    $menuSaya = collect(\App\Models\User::OPERATOR_PERMISSIONS)->filter(fn ($perm, $key) => $user->hasMenuPermission($key));

    // Kartu statistik sesuai wewenang menu.
    $kartu = [];
    if ($bisaChat) {
        $kartu[] = ['Chat menunggu', 'messages-square', $stats['chat_menunggu'] > 0 ? 'red' : 'blue', $angka($stats['chat_menunggu']), $stats['chat_menunggu'] > 0 ? 'Butuh respons segera' : 'Tidak ada antrian', route('admin.livechat.index')];
        $kartu[] = ['Chat aktif saya', 'headset', 'brand', $angka($operatorData['my_active_chats_count'] ?? 0), 'Sedang Anda tangani', route('admin.livechat.index')];
    }
    if ($bisaKonsultasi) {
        $kartu[] = ['Konsultasi baru', 'message-circle-question', $stats['konsultasi_pending'] > 0 ? 'gold' : 'brand', $angka($stats['konsultasi_pending']), 'Menunggu jawaban', route('operator.konsultasi.index')];
        $kartu[] = ['Jawaban saya', 'check-check', 'purple', $angka($operatorData['my_answered_count'] ?? 0), 'Pertanyaan telah Anda jawab', route('operator.konsultasi.index')];
    }
    if ($bisaBerita) {
        $kartu[] = ['Artikel ditulis saya', 'feather', 'brand', $angka($operatorData['my_berita_count'] ?? 0), 'Kontributor berita', route('operator.berita.index')];
        $kartu[] = ['Total pembaca portal', 'eye', 'blue', $angka($stats['total_views_berita']), 'Publikasi terbaca', route('operator.berita.index')];
    }
    if ($bisaFatwa) {
        $kartu[] = ['Dokumen fatwa', 'scale', 'gold', $angka($stats['total_fatwa']), $angka($stats['fatwa_published']).' terpublikasi', route('operator.fatwa.index')];
    }
    if ($bisaSurat) {
        $kartu[] = ['Arsip surat', 'folder-archive', 'purple', $angka($stats['total_surat']), 'Surat masuk & keluar', route('operator.surat.index')];
    }
    $kolomKartu = [1 => 'sm:grid-cols-1', 2 => 'sm:grid-cols-2', 3 => 'sm:grid-cols-2 lg:grid-cols-3', 4 => 'sm:grid-cols-2 xl:grid-cols-4', 5 => 'sm:grid-cols-2 lg:grid-cols-3', 6 => 'sm:grid-cols-2 lg:grid-cols-3', 7 => 'sm:grid-cols-2 xl:grid-cols-4', 8 => 'sm:grid-cols-2 xl:grid-cols-4'][count($kartu)] ?? 'sm:grid-cols-2';

    // Grafik aktivitas bulanan (hanya seri yang menjadi wewenang operator; warna tetap per entitas).
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
    $catatanSeri = collect([
        'berita' => 'berita menurut tanggal terbit',
        'surat' => 'arsip surat menurut tanggal surat',
        'fatwa' => 'fatwa menurut tanggal masuk',
        'konsultasi' => 'konsultasi menurut tanggal masuk',
    ])->only(array_column($seri, 'key'))->implode(', ');
@endphp

<x-layouts.admin title="Dashboard" header="Ringkasan tugas & layanan yang menjadi wewenang Anda">
    {{-- ===== Sambutan ===== --}}
    <section class="bg-gradient-brand relative overflow-hidden rounded-3xl text-white shadow-[var(--shadow-lift)]">
        <div class="pattern-islamic absolute inset-0"></div>
        <div class="absolute -top-24 -right-20 size-80 rounded-full bg-gold-400/20 blur-3xl"></div>
        <div class="absolute -bottom-32 left-1/3 size-80 rounded-full bg-brand-400/20 blur-3xl"></div>
        <div class="relative grid gap-6 p-6 sm:p-8 lg:grid-cols-[minmax(0,1fr)_18rem] lg:items-center lg:gap-10">
            <div class="min-w-0">
                <p class="eyebrow text-gold-300!">Dashboard Petugas Operator</p>
                <h2 class="mt-3 font-display text-[1.75rem] leading-tight font-semibold text-balance text-white sm:text-4xl">Selamat bertugas, {{ $nama }}</h2>
                <p class="mt-3 max-w-2xl text-sm leading-relaxed text-pretty text-white/75 sm:text-[15px]">Assalamu'alaikum, {{ Str::lower($sapaan) }}. Berikut antrian layanan dan tugas yang menjadi wewenang Anda di {{ $site['site_short'] }}.</p>
                <div class="mt-5 flex flex-wrap items-center gap-2 text-xs font-semibold">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 ring-1 ring-white/15"><x-icon name="shield-check" class="size-3.5 text-gold-300" /> Operator</span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-black/15 px-3 py-1.5 ring-1 ring-white/15"><x-icon name="badge-check" class="size-3.5 text-gold-300" /> {{ count($assignedPerms) }} tugas ditetapkan</span>
                    @if ($perluRespon > 0)
                        <a href="#perlu-tindakan" class="inline-flex items-center gap-2 rounded-full bg-gold-300 px-3 py-1.5 text-brand-950 transition hover:bg-gold-200">
                            <span class="relative flex size-2"><span class="absolute inline-flex size-full animate-ping rounded-full bg-red-500 opacity-75"></span><span class="relative inline-flex size-2 rounded-full bg-red-600"></span></span>
                            {{ $perluRespon }} menunggu tanggapan
                        </a>
                    @elseif ($bisaChat || $bisaKonsultasi)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-brand-100 ring-1 ring-white/15"><x-icon name="circle-check-big" class="size-3.5 text-brand-300" /> Antrian layanan kosong</span>
                    @endif
                </div>
                <div class="mt-6 grid grid-cols-2 gap-2.5 sm:flex sm:flex-wrap">
                    @if ($bisaBerita)
                        <a href="{{ route('operator.berita.create') }}" class="btn btn-gold col-span-2"><x-icon name="pen-line" class="size-4" /> Tulis Berita</a>
                    @endif
                    @if ($bisaChat)
                        <a href="{{ route('admin.livechat.index') }}" class="btn btn-glass px-3 sm:px-5"><x-icon name="messages-square" class="size-4" /> Live Chat</a>
                    @endif
                    @if ($bisaKonsultasi)
                        <a href="{{ route('operator.konsultasi.index') }}" class="btn btn-glass px-3 sm:px-5"><x-icon name="message-circle-question" class="size-4" /> Konsultasi</a>
                    @endif
                    <a href="{{ route('home.public') }}" target="_blank" rel="noopener" @class(['btn btn-glass px-3 sm:px-5', 'col-span-2' => ((int) $bisaChat + (int) $bisaKonsultasi) % 2 === 0])><x-icon name="external-link" class="size-4" /> Lihat Website</a>
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

    {{-- ===== Peringatan bila belum ada wewenang ===== --}}
    @if (count($assignedPerms) === 0)
        <div class="mt-6 flex items-start gap-4 rounded-2xl border border-gold-200 bg-gold-50 p-5" role="alert">
            <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-gold-100 text-gold-700"><x-icon name="triangle-alert" class="size-5" /></span>
            <div>
                <p class="font-semibold text-ink-900">Belum ada wewenang tugas yang ditetapkan</p>
                <p class="mt-1 text-sm leading-relaxed text-stone-600">Administrator belum mengaktifkan hak akses menu tugas untuk akun Anda. Silakan hubungi Administrator agar dapat membuka menu dan menangani tugas operasional.</p>
            </div>
        </div>
    @endif

    {{-- ===== Statistik sesuai wewenang ===== --}}
    @if ($kartu)
        {{-- Ponsel: baris geser (snap) agar ringkas; tablet ke atas: grid. --}}
        <div class="scrollbar-none -mx-4 mt-5 flex snap-x snap-mandatory scroll-px-4 gap-3 overflow-x-auto px-4 pt-1 pb-3 sm:mx-0 sm:mt-6 sm:grid sm:snap-none sm:gap-4 sm:overflow-visible sm:p-0 {{ $kolomKartu }}" aria-label="Statistik tugas">
            @foreach ($kartu as [$label, $icon, $tone, $value, $note, $href])
                <x-stat-card @class(['shrink-0 snap-start sm:w-auto', 'w-[82%]' => count($kartu) > 1, 'w-full' => count($kartu) === 1]) :label="$label" :icon="$icon" :tone="$tone" :value="$value" :note="$note" :href="$href" />
            @endforeach
        </div>
    @endif

    {{-- Fatwa & arsip surat: didefinisikan sekali, ditampilkan di kolom utama (bila kolom utama pendek) atau sebagai baris penuh. --}}
    @if ($bisaFatwa || $bisaSurat)
        @section('dasbor-fatwa-surat')
        <div @class(['grid gap-6', 'md:grid-cols-2' => $bisaFatwa && $bisaSurat])>
            @if ($bisaFatwa)
                <section class="card overflow-hidden">
                    <header class="flex items-center justify-between gap-3 border-b border-stone-100 px-5 py-4">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-gold-50 text-gold-700 ring-1 ring-gold-100"><x-icon name="scale" class="size-[18px]" /></span>
                            <div class="min-w-0">
                                <h3 class="text-[15px] font-bold text-ink-900">Fatwa terkini</h3>
                                <p class="text-xs text-stone-500">{{ $angka($stats['fatwa_published']) }} dari {{ $angka($stats['total_fatwa']) }} terpublikasi</p>
                            </div>
                        </div>
                        <a href="{{ route('operator.fatwa.index') }}" class="btn btn-ghost btn-sm shrink-0">Kelola <x-icon name="chevron-right" class="size-4" /></a>
                    </header>
                    <ul class="divide-y divide-stone-100">
                        @forelse ($latestFatwa as $f)
                            <li class="flex items-start gap-3 px-5 py-3.5">
                                <div class="min-w-0 flex-1">
                                    <p class="line-clamp-2 text-sm leading-snug font-semibold text-ink-900">{{ $f->judul }}</p>
                                    <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-stone-500">
                                        <span>{{ $f->kategoriFatwa?->nama ?: 'Fatwa' }}</span><span aria-hidden="true">·</span><span>{{ $tgl($f->created_at) }}</span>
                                        @unless ($f->publikasi)<span class="badge badge-gray">Draf</span>@endunless
                                    </p>
                                </div>
                                @if ($f->file_url)
                                    <a href="{{ $f->file_url }}" target="_blank" rel="noopener" class="grid size-8 shrink-0 place-items-center rounded-lg text-stone-500 hover:bg-red-50 hover:text-red-600" title="Unduh PDF fatwa" aria-label="Unduh PDF fatwa {{ Str::limit($f->judul, 40) }}"><x-icon name="file-text" class="size-4" /></a>
                                @endif
                            </li>
                        @empty
                            <li class="px-5 py-8 text-center text-sm text-stone-500">Belum ada data fatwa.</li>
                        @endforelse
                    </ul>
                </section>
            @endif

            @if ($bisaSurat)
                <section class="card overflow-hidden">
                    <header class="flex items-center justify-between gap-3 border-b border-stone-100 px-5 py-4">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-violet-50 text-violet-700 ring-1 ring-violet-100"><x-icon name="folder-archive" class="size-[18px]" /></span>
                            <div class="min-w-0">
                                <h3 class="text-[15px] font-bold text-ink-900">Arsip surat terkini</h3>
                                <p class="text-xs text-stone-500">{{ $angka($stats['total_surat']) }} surat tersimpan</p>
                            </div>
                        </div>
                        <a href="{{ route('operator.surat.index') }}" class="btn btn-ghost btn-sm shrink-0">Kelola <x-icon name="chevron-right" class="size-4" /></a>
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
            @endif
        </div>
        @endsection
    @endif

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="min-w-0 space-y-6 xl:col-span-2">
            {{-- ===== Perlu tindakan ===== --}}
            @if ($bisaChat || $bisaKonsultasi)
                <section id="perlu-tindakan" class="card scroll-mt-24 overflow-hidden">
                    <header class="flex flex-wrap items-center justify-between gap-3 border-b border-stone-100 px-5 py-4">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-gold-50 text-gold-700 ring-1 ring-gold-100"><x-icon name="bell-ring" class="size-5" /></span>
                            <div class="min-w-0">
                                <h3 class="text-[15px] font-bold text-ink-900">Perlu tindakan</h3>
                                <p class="text-xs text-stone-500">Antrian layanan umat yang menunggu tanggapan Anda</p>
                            </div>
                        </div>
                        @if ($perluRespon > 0)
                            <span class="badge badge-red">{{ $perluRespon }} menunggu</span>
                        @endif
                    </header>

                    @if ($bisaChat)
                        <div>
                            <div class="flex flex-wrap items-center justify-between gap-2 bg-stone-50/70 px-5 py-2.5">
                                <p class="flex items-center gap-2 text-xs font-bold tracking-wider text-stone-600 uppercase"><x-icon name="messages-square" class="size-4 text-red-600" /> Antrian live chat <span class="badge badge-gray normal-case">{{ $stats['chat_menunggu'] }}</span></p>
                                <a href="{{ route('admin.livechat.index') }}" class="flex items-center gap-1 text-xs font-semibold text-brand-700 hover:text-brand-900">Buka panel live chat <x-icon name="arrow-right" class="size-3.5" /></a>
                            </div>
                            @if ($waitingChats->isEmpty())
                                <p class="flex items-center gap-2 px-5 py-4 text-sm text-stone-500"><x-icon name="circle-check-big" class="size-4 text-brand-600" /> Tidak ada antrian chat saat ini. Semua obrolan masyarakat telah ditangani.</p>
                            @else
                                <ul class="divide-y divide-stone-100">
                                    @foreach ($waitingChats as $chat)
                                        <li class="flex items-center gap-3 px-5 py-3.5 transition hover:bg-brand-50/40 sm:gap-4">
                                            <span class="grid size-10 shrink-0 place-items-center rounded-full bg-red-50 text-sm font-bold text-red-600 ring-1 ring-red-100">{{ $inisial($chat->nama_pengunjung) }}</span>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                                    <p class="truncate font-semibold text-ink-900">{{ $chat->nama_pengunjung }}</p>
                                                    <span class="badge badge-red">Antrian #{{ $chat->antrian_nomor }}</span>
                                                </div>
                                                <p class="mt-0.5 line-clamp-1 text-sm text-stone-600">Topik: {{ $chat->topik ?: 'Layanan umum' }}</p>
                                                <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs text-stone-500">
                                                    <span class="inline-flex items-center gap-1 whitespace-nowrap font-medium text-red-600"><x-icon name="clock" class="size-3.5" /> {{ $chat->created_at->diffForHumans() }}</span>
                                                    @if ($chat->email_pengunjung)<span class="inline-flex min-w-0 items-center gap-1"><x-icon name="mail" class="size-3.5" /> <span class="truncate">{{ $chat->email_pengunjung }}</span></span>@endif
                                                    @if ($chat->nohp_pengunjung)<span class="inline-flex items-center gap-1 whitespace-nowrap"><x-icon name="phone" class="size-3.5" /> {{ $chat->nohp_pengunjung }}</span>@endif
                                                </p>
                                            </div>
                                            <a href="{{ route('admin.livechat.show', $chat->id) }}" class="btn btn-primary btn-sm shrink-0" aria-label="Balas chat {{ $chat->nama_pengunjung }}"><x-icon name="reply" class="size-4" /> <span class="hidden sm:inline">Balas chat</span></a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            @if ($myActiveChats->isNotEmpty())
                                <div class="border-t border-stone-100 px-5 py-4">
                                    <p class="flex items-center gap-2 text-[11px] font-bold tracking-wider text-stone-500 uppercase"><x-icon name="headset" class="size-3.5 text-brand-600" /> Obrolan yang sedang Anda tangani</p>
                                    <ul class="mt-3 grid gap-2 sm:grid-cols-2">
                                        @foreach ($myActiveChats as $chat)
                                            <li>
                                                <a href="{{ route('admin.livechat.show', $chat->id) }}" class="flex items-center gap-3 rounded-xl border border-stone-200 bg-white px-3 py-2.5 transition hover:border-brand-300">
                                                    <span class="relative flex size-2.5 shrink-0"><span class="absolute inline-flex size-full animate-ping rounded-full bg-brand-400 opacity-60"></span><span class="relative inline-flex size-2.5 rounded-full bg-brand-500"></span></span>
                                                    <span class="min-w-0 flex-1">
                                                        <span class="block truncate text-sm font-semibold text-ink-900">{{ $chat->nama_pengunjung }}</span>
                                                        <span class="block truncate text-xs text-stone-500">Terhubung sejak {{ $tgl($chat->started_at ?? $chat->created_at, 'H:i') }} WIB{{ $chat->email_pengunjung ? ' · '.$chat->email_pengunjung : '' }}</span>
                                                    </span>
                                                    <span class="text-xs font-semibold whitespace-nowrap text-brand-700">Buka obrolan</span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    @endif

                    @if ($bisaKonsultasi)
                        <div @class(['border-t border-stone-100' => $bisaChat])>
                            <div class="flex flex-wrap items-center justify-between gap-2 bg-stone-50/70 px-5 py-2.5">
                                <p class="flex items-center gap-2 text-xs font-bold tracking-wider text-stone-600 uppercase"><x-icon name="message-circle-question" class="size-4 text-gold-600" /> Konsultasi menunggu jawaban <span class="badge badge-gray normal-case">{{ $stats['konsultasi_pending'] }}</span></p>
                                <a href="{{ route('operator.konsultasi.index') }}" class="flex items-center gap-1 text-xs font-semibold text-brand-700 hover:text-brand-900">Semua konsultasi <x-icon name="arrow-right" class="size-3.5" /></a>
                            </div>
                            @if ($pendingKonsultasi->isEmpty())
                                <p class="flex items-center gap-2 px-5 py-4 text-sm text-stone-500"><x-icon name="check-check" class="size-4 text-brand-600" /> Tidak ada pertanyaan konsultasi yang menunggu. Semua pertanyaan masyarakat telah dijawab.</p>
                            @else
                                <ul class="divide-y divide-stone-100">
                                    @foreach ($pendingKonsultasi as $kon)
                                        <li class="flex items-center gap-3 px-5 py-3.5 transition hover:bg-brand-50/40 sm:gap-4">
                                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-gold-50 text-gold-700 ring-1 ring-gold-100"><x-icon name="message-circle-question" class="size-[18px]" /></span>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                                    <p class="truncate font-semibold text-ink-900">{{ $kon->nama }}</p>
                                                    <span class="badge badge-gold">{{ $kon->kategori ?: 'Pertanyaan syariah' }}</span>
                                                </div>
                                                <p class="mt-0.5 line-clamp-1 text-sm text-stone-600">{{ Str::limit(strip_tags($kon->pertanyaan), 140) }}</p>
                                                <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs text-stone-500">
                                                    <span class="inline-flex items-center gap-1 whitespace-nowrap"><x-icon name="calendar-clock" class="size-3.5" /> {{ $tgl($kon->created_at, 'j M Y, H:i') }} WIB</span>
                                                    @if ($kon->email)<span class="inline-flex min-w-0 items-center gap-1"><x-icon name="mail" class="size-3.5" /> <span class="truncate">{{ $kon->email }}</span></span>@endif
                                                </p>
                                            </div>
                                            <a href="{{ route('operator.konsultasi.show', $kon->id) }}" class="btn btn-gold btn-sm shrink-0" aria-label="Jawab konsultasi {{ $kon->nama }}"><x-icon name="reply" class="size-4" /> <span class="hidden sm:inline">Jawab</span></a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endif
                </section>
            @endif

            {{-- ===== Berita & artikel ===== --}}
            @if ($bisaBerita)
                <section class="card overflow-hidden" x-data="{ tab: @js($myBerita->isNotEmpty() ? 'saya' : 'portal') }">
                    <header class="flex flex-wrap items-center justify-between gap-3 border-b border-stone-100 px-5 py-4">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-sky-50 text-sky-700 ring-1 ring-sky-100"><x-icon name="newspaper" class="size-5" /></span>
                            <div class="min-w-0">
                                <h3 class="text-[15px] font-bold text-ink-900">Berita & artikel</h3>
                                <p class="text-xs text-stone-500">{{ $angka($operatorData['my_berita_count'] ?? 0) }} tulisan Anda · {{ $angka($stats['total_berita']) }} di portal</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('operator.berita.index') }}" class="btn btn-ghost btn-sm">Kelola semua <x-icon name="arrow-right" class="size-4" /></a>
                            <a href="{{ route('operator.berita.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> Tulis Berita</a>
                        </div>
                    </header>
                    <div class="flex gap-1 border-b border-stone-100 px-5" role="tablist" aria-label="Daftar berita">
                        @foreach (['saya' => ['Ditulis saya', $myBerita->count()], 'portal' => ['Terbaru di portal', $latestBerita->count()]] as $kunci => [$label, $jumlah])
                            <button type="button" role="tab" id="tab-berita-{{ $kunci }}" aria-controls="panel-berita-{{ $kunci }}" :aria-selected="tab === '{{ $kunci }}'" @click="tab = '{{ $kunci }}'"
                                    class="-mb-px flex items-center gap-2 border-b-2 px-1 py-3 text-sm font-semibold transition sm:px-2"
                                    :class="tab === '{{ $kunci }}' ? 'border-brand-600 text-brand-700' : 'border-transparent text-stone-500 hover:text-stone-700'">
                                {{ $label }} <span class="rounded-full bg-stone-100 px-1.5 py-0.5 text-[11px] font-bold text-stone-600 tabular-nums">{{ $jumlah }}</span>
                            </button>
                        @endforeach
                    </div>

                    @foreach (['saya' => $myBerita, 'portal' => $latestBerita] as $kunci => $daftar)
                        <div role="tabpanel" id="panel-berita-{{ $kunci }}" aria-labelledby="tab-berita-{{ $kunci }}" x-show="tab === '{{ $kunci }}'" @if (($myBerita->isNotEmpty() ? 'saya' : 'portal') !== $kunci) x-cloak @endif>
                            @if ($daftar->isEmpty())
                                <div class="p-5">
                                    @if ($kunci === 'saya')
                                        <x-empty-state icon="file-pen-line" title="Anda belum mempublikasikan artikel berita" message="Klik tombol “Tulis Berita” untuk mulai menulis konten berita portal.">
                                            <a href="{{ route('operator.berita.create') }}" class="btn btn-primary btn-sm mt-5"><x-icon name="pen-line" class="size-4" /> Tulis Berita</a>
                                        </x-empty-state>
                                    @else
                                        <x-empty-state icon="newspaper" title="Belum ada data berita" message="Berita yang ditulis petugas akan tampil di sini." />
                                    @endif
                                </div>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="table-clean">
                                        <thead>
                                            <tr>
                                                <th>Judul berita</th>
                                                <th class="hidden md:table-cell">Kategori</th>
                                                <th class="hidden sm:table-cell">Status</th>
                                                <th class="hidden text-right md:table-cell">Pembaca</th>
                                                <th class="text-right">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($daftar as $berita)
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
                                                                <a href="{{ route('operator.berita.edit', $berita->id) }}" class="line-clamp-2 font-semibold text-ink-900 hover:text-brand-700">{{ $berita->judul }}</a>
                                                                <p class="mt-0.5 text-xs text-stone-500">
                                                                    {{ $tgl($berita->tanggal_terbit) }}<span class="md:hidden"> · {{ $kategori }}</span><span class="hidden sm:inline md:hidden"> · {{ $angka($berita->views) }} dibaca</span><span class="sm:hidden"> · {{ $labelStatus }}</span>
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
                                                            <a href="{{ route('operator.berita.edit', $berita->id) }}" class="grid size-8 place-items-center rounded-lg text-stone-500 hover:bg-brand-50 hover:text-brand-700" title="Ubah berita" aria-label="Ubah berita"><x-icon name="pencil" class="size-4" /></a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </section>
            @endif

            {{-- ===== Grafik aktivitas ===== --}}
            @if ($seri)
                <section class="card" x-data="dashChart(@js(['months' => $bulan, 'series' => $seri]))">
                    <header class="flex flex-wrap items-center justify-between gap-3 border-b border-stone-100 px-5 py-4">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100"><x-icon name="chart-column-stacked" class="size-5" /></span>
                            <div class="min-w-0">
                                <h3 class="text-[15px] font-bold text-ink-900">Aktivitas portal 6 bulan terakhir</h3>
                                <p class="text-xs text-stone-500">Sesuai menu tugas Anda, {{ $bulan[0]['long'] }} – {{ last($bulan)['long'] }}</p>
                            </div>
                        </div>
                        <div class="flex rounded-xl bg-stone-100 p-1 text-xs font-semibold" role="group" aria-label="Pilih tampilan data">
                            <button type="button" @click="table = false" :aria-pressed="!table" class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 transition" :class="!table ? 'bg-white text-brand-700 shadow-sm' : 'text-stone-500 hover:text-stone-700'"><x-icon name="chart-column" class="size-3.5" /> Grafik</button>
                            <button type="button" @click="table = true" :aria-pressed="table" class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 transition" :class="table ? 'bg-white text-brand-700 shadow-sm' : 'text-stone-500 hover:text-stone-700'"><x-icon name="table" class="size-3.5" /> Tabel</button>
                        </div>
                    </header>

                    <div class="p-5">
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
                            <span>Dihitung per bulan (WIB): {{ $catatanSeri }}. Arahkan kursor atau fokus ke batang untuk melihat rincian per bulan.</span>
                        </p>
                    </div>
                </section>
            @endif

            @if (($bisaFatwa || $bisaSurat) && $fatwaSuratDiUtama)
                @yield('dasbor-fatwa-surat')
            @endif

            @if (! $bisaChat && ! $bisaKonsultasi && ! $bisaBerita && ! $seri && ! $bisaFatwa && ! $bisaSurat)
                @if ($menuSaya->isEmpty())
                    <x-empty-state icon="lock" title="Belum ada tugas untuk ditampilkan" message="Ringkasan antrian layanan dan konten akan muncul di sini setelah Administrator memberikan wewenang menu kepada akun Anda." />
                @else
                    <x-empty-state icon="layout-grid" title="Tidak ada ringkasan untuk menu Anda" message="Menu tugas Anda dapat dibuka langsung melalui daftar “Menu operasional saya”." />
                @endif
            @endif
        </div>

        {{-- ===== Kolom samping ===== --}}
        <aside class="grid content-start gap-6 sm:grid-cols-2 xl:grid-cols-1" aria-label="Profil & pintasan tugas">
            <section class="card overflow-hidden">
                <div class="bg-gradient-brand relative h-20">
                    <div class="pattern-islamic absolute inset-0"></div>
                </div>
                <div class="-mt-10 px-5 pb-5 text-center">
                    @if ($user->foto_url)
                        <img src="{{ $user->foto_url }}" alt="Foto {{ $user->name }}" class="relative mx-auto size-20 rounded-2xl object-cover ring-4 ring-white">
                    @else
                        <span class="relative mx-auto grid size-20 place-items-center rounded-2xl bg-gradient-to-br from-brand-600 to-brand-900 font-display text-2xl font-semibold text-gold-300 ring-4 ring-white">{{ $inisial($user->name) }}</span>
                    @endif
                    <h3 class="mt-3 font-display text-lg leading-snug font-semibold text-ink-900">{{ $nama }}</h3>
                    <p class="mt-0.5 text-xs break-all text-stone-500">{{ '@'.$user->username }} · {{ $user->email }}</p>
                    @if ($user->nohp)
                        <p class="mt-1.5 inline-flex items-center gap-1.5 text-xs font-semibold text-brand-700"><x-icon name="whatsapp" class="size-3.5" /> {{ $user->nohp }}</p>
                    @endif
                    <p class="mt-3"><span class="badge badge-green"><span class="size-1.5 rounded-full bg-brand-500"></span> Petugas aktif</span></p>
                </div>
                <div class="border-t border-stone-100 px-5 py-4">
                    <p class="flex items-center gap-1.5 text-[11px] font-bold tracking-wider text-stone-500 uppercase"><x-icon name="shield-check" class="size-3.5 text-brand-600" /> Wewenang menu aktif</p>
                    <div class="mt-3 flex flex-wrap gap-1.5">
                        @forelse ($menuSaya as $key => $perm)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-800 ring-1 ring-brand-600/15"><x-icon :name="$ikonIzin[$key] ?? 'circle-check'" class="size-3.5" /> {{ $perm['label'] }}</span>
                        @empty
                            <p class="text-sm text-stone-500 italic">Belum ada wewenang menu aktif.</p>
                        @endforelse
                    </div>
                    <a href="{{ route('profile.edit') }}" class="btn btn-outline btn-sm mt-4 w-full"><x-icon name="circle-user-round" class="size-4" /> Profil akun</a>
                </div>
            </section>

            <section class="card p-5">
                <h3 class="flex items-center gap-2 text-[15px] font-bold text-ink-900"><x-icon name="zap" class="size-[18px] text-gold-500" /> Menu operasional saya</h3>
                <div class="mt-4 space-y-2">
                    @forelse ($menuSaya as $key => $perm)
                        <a href="{{ route($perm['route']) }}" class="group flex items-center gap-3 rounded-xl border border-stone-200 p-3 transition hover:border-brand-300 hover:bg-brand-50/40">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl ring-1 transition {{ $nada[$nadaIzin[$key] ?? 'brand'] }} group-hover:bg-brand-700 group-hover:text-white group-hover:ring-brand-700"><x-icon :name="$ikonIzin[$key] ?? 'circle-check'" class="size-5" /></span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold text-ink-900">{{ $perm['label'] }}</span>
                                <span class="line-clamp-2 block text-xs leading-snug text-stone-500">{{ $perm['description'] }}</span>
                            </span>
                            <x-icon name="arrow-right" class="size-4 text-stone-300 transition group-hover:translate-x-0.5 group-hover:text-brand-600" />
                        </a>
                    @empty
                        <div class="rounded-xl bg-stone-50 px-4 py-6 text-center">
                            <x-icon name="lock" class="mx-auto size-6 text-stone-400" />
                            <p class="mt-2 text-sm text-stone-500">Tidak ada menu yang dapat dibuka.</p>
                        </div>
                    @endforelse
                </div>
            </section>

            @if ($bisaKonsultasi && $myAnswered->isNotEmpty())
                <section class="card p-5">
                    <h3 class="flex items-center gap-2 text-[15px] font-bold text-ink-900"><x-icon name="check-check" class="size-[18px] text-violet-600" /> Jawaban terakhir Anda</h3>
                    <ul class="mt-3 divide-y divide-stone-100">
                        @foreach ($myAnswered as $kon)
                            <li>
                                <a href="{{ route('operator.konsultasi.show', $kon->id) }}" class="block py-2.5 hover:text-brand-700">
                                    <span class="line-clamp-1 text-sm font-semibold text-ink-900">{{ Str::limit(strip_tags($kon->pertanyaan), 80) }}</span>
                                    <span class="mt-0.5 block text-xs text-stone-500">{{ $kon->nama }} · {{ $tgl($kon->answered_at ?? $kon->updated_at) }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <section class="card relative overflow-hidden p-5">
                <div class="pattern-islamic-dark absolute inset-0"></div>
                <div class="relative">
                    <h3 class="flex items-center gap-2 text-[15px] font-bold text-ink-900"><x-icon name="lightbulb" class="size-[18px] text-gold-500" /> Petunjuk pelayanan</h3>
                    <ul class="mt-3 space-y-2.5 text-sm leading-relaxed text-stone-600">
                        <li class="flex gap-2.5"><x-icon name="circle-check" class="mt-0.5 size-4 text-brand-600" /> <span>Prioritaskan menjawab <b class="font-semibold text-ink-900">Live Chat</b> dan <b class="font-semibold text-ink-900">Konsultasi Syariah</b> dengan bahasa santun dan islami.</span></li>
                        <li class="flex gap-2.5"><x-icon name="circle-check" class="mt-0.5 size-4 text-brand-600" /> <span>Pastikan setiap berita yang dipublikasikan telah terverifikasi sumber dan kategorinya.</span></li>
                        <li class="flex gap-2.5"><x-icon name="circle-check" class="mt-0.5 size-4 text-brand-600" /> <span>Jaga kerahasiaan data pribadi masyarakat yang melakukan konsultasi ataupun live chat.</span></li>
                    </ul>
                </div>
            </section>
        </aside>
    </div>

    @if (($bisaFatwa || $bisaSurat) && ! $fatwaSuratDiUtama)
        <div class="mt-6">
            @yield('dasbor-fatwa-surat')
        </div>
    @endif

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
