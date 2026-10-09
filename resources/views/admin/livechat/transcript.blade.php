@php
    $petugas = $session->operator ? ($session->operator->name_gelar ?: $session->operator->name) : null;
    $statusLabels = ['menunggu' => 'Menunggu', 'aktif' => 'Sedang berlangsung', 'selesai' => 'Selesai', 'bot' => 'Dilayani chatbot (FAQ)'];
    $roles = [
        'pengunjung' => ['Pengunjung', 'border-stone-300', 'text-stone-700'],
        'operator' => ['Petugas', 'border-brand-600', 'text-brand-800'],
        'bot' => ['Chatbot', 'border-gold-400', 'text-gold-800'],
        'system' => ['Sistem', 'border-stone-200', 'text-stone-500'],
    ];
    $mulai = $session->started_at ?? $session->created_at;
    $durasi = $session->closed_at && $mulai ? $mulai->diffForHumans($session->closed_at, \Carbon\CarbonInterface::DIFF_ABSOLUTE) : null;
    $meta = [
        ['Nomor antrian', '#'.$session->antrian_nomor],
        ['Waktu masuk', $session->created_at->translatedFormat('d F Y, H:i:s').' WIB'],
        ['Nama pengunjung', $session->nama_pengunjung],
        ['Waktu selesai', $session->closed_at ? $session->closed_at->translatedFormat('d F Y, H:i:s').' WIB' : 'Sedang berlangsung'],
        ['No. WhatsApp / Telp', $session->nohp_pengunjung ?: '-'],
        ['Petugas pelayan', $petugas ?? 'MUI Bot / Asisten Virtual'],
        ['Email', $session->email_pengunjung ?: '-'],
        ['Status sesi', $statusLabels[$session->status] ?? ucfirst($session->status)],
        ['Topik layanan', $session->topik ?: 'Layanan Umum'],
        ['Durasi · jumlah pesan', ($durasi ?? 'Belum selesai').' · '.$session->messages->count().' pesan'],
    ];
    // Teks pesan → HTML aman: escape dulu, lalu **tebal**, tautan, dan baris baru (sama seperti tampilan chat).
    $format = fn (string $text) => preg_replace(
        ['/\*\*(.+?)\*\*/s', '~(https?://[^\s<]+)~'],
        ['<strong>$1</strong>', '<a href="$1" class="text-brand-700 underline underline-offset-2">$1</a>'],
        nl2br(e($text))
    );
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Transkrip Live Chat #{{ $session->antrian_nomor }} — {{ $session->nama_pengunjung }} · {{ $site['site_short'] }}</title>
    <link rel="icon" type="image/png" href="{{ $site['logo_url'] }}">
    @fonts
    @vite(['resources/css/app.css'])
    <style>
        @page { size: A4; margin: 14mm 14mm 16mm; }
        @media print {
            html, body { background: #fff !important; }
            .transcript-line { break-inside: avoid; }
            a { color: inherit; text-decoration: none; }
        }
    </style>
</head>
<body class="min-h-screen bg-stone-200/70 text-stone-800 antialiased print:min-h-0 print:bg-white">
    {{-- Bilah alat (tidak ikut tercetak) --}}
    <div class="sticky top-0 z-10 border-b border-stone-200 bg-white/90 backdrop-blur print:hidden">
        <div class="mx-auto flex max-w-[210mm] flex-wrap items-center justify-between gap-3 px-4 py-3">
            <div class="flex min-w-0 items-center gap-2 text-sm">
                <a href="{{ route('admin.livechat.index', ['tab' => 'riwayat']) }}" class="inline-flex items-center gap-1.5 font-semibold text-brand-700 hover:text-brand-900"><x-icon name="arrow-left" class="size-4" /> Riwayat live chat</a>
                <span class="text-stone-300">/</span>
                <span class="truncate text-stone-500">Transkrip #{{ $session->antrian_nomor }}</span>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="window.print()" class="btn btn-primary btn-sm"><x-icon name="printer" class="size-4" /> Cetak / Simpan PDF</button>
                <button type="button" onclick="window.close(); setTimeout(() => { location.href = @js(route('admin.livechat.index', ['tab' => 'riwayat'])); }, 300);" class="btn btn-outline btn-sm">Tutup</button>
            </div>
        </div>
    </div>

    <main class="mx-auto my-6 max-w-[210mm] bg-white px-6 py-8 shadow-[var(--shadow-lift)] sm:my-10 sm:px-[16mm] sm:py-[14mm] print:m-0 print:max-w-none print:p-0 print:shadow-none">
        {{-- Kop surat --}}
        <header class="flex items-center gap-4 border-b-4 border-double border-ink-900 pb-4 sm:gap-6">
            <img src="{{ $site['logo_url'] }}" alt="Logo Majelis Ulama Indonesia" class="size-16 shrink-0 object-contain sm:size-20">
            <div class="min-w-0 flex-1 text-center">
                <p class="text-xs font-bold tracking-[.18em] text-stone-700 uppercase sm:text-sm">{{ $site['site_name'] }} (MUI)</p>
                <p class="font-display text-lg leading-tight font-semibold text-brand-800 uppercase sm:text-xl">Dewan Pimpinan {{ $site['site_region'] }}</p>
                <p class="mt-1 text-[11px] text-stone-600 sm:text-xs">Sekretariat: {{ $site['address'] }}</p>
                <p class="text-[11px] text-stone-600 sm:text-xs">
                    {{ collect([$site['phone'] ? 'Telepon: '.$site['phone'] : null, $site['whatsapp'] ? 'WhatsApp: '.$site['whatsapp'] : null, $site['email'] ? 'Email: '.$site['email'] : null])->filter()->implode(' · ') }}
                </p>
            </div>
            <span class="hidden size-20 shrink-0 sm:block" aria-hidden="true"></span>
        </header>

        {{-- Judul --}}
        <div class="mt-7 text-center">
            <h1 class="text-base font-bold tracking-wide text-ink-900 uppercase underline decoration-1 underline-offset-4 sm:text-lg">Transkrip Percakapan Layanan Live Chat</h1>
            <p class="mt-1.5 font-mono text-[11px] break-all text-stone-500">ID Sesi: {{ $session->session_token }}</p>
        </div>

        {{-- Data sesi --}}
        <dl class="mt-6 grid gap-x-6 gap-y-2.5 rounded-xl border border-stone-200 bg-stone-50/70 p-4 text-[13px] sm:grid-cols-2 print:bg-transparent">
            @foreach ($meta as [$label, $value])
                <div class="flex gap-2">
                    <dt class="w-32 shrink-0 text-stone-500">{{ $label }}</dt>
                    <dd class="min-w-0 font-semibold break-words text-ink-900">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>

        {{-- Isi percakapan --}}
        <section class="mt-8">
            <h2 class="flex items-center gap-2 border-b border-stone-200 pb-2 text-sm font-bold tracking-wider text-ink-900 uppercase">
                <x-icon name="messages-square" class="size-4 text-brand-700 print:hidden" /> Isi percakapan
            </h2>
            @forelse ($session->messages as $msg)
                @php [$role, $border, $color] = $roles[$msg->sender_type] ?? [ucfirst($msg->sender_type), 'border-stone-300', 'text-stone-700']; @endphp
                <article class="transcript-line border-b border-dotted border-stone-200 py-3">
                    <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-0.5">
                        <p class="text-[13px] font-bold {{ $color }}">
                            {{ $msg->sender_name }}
                            <span class="ml-1 rounded border border-current/25 px-1.5 py-px text-[10px] font-semibold tracking-wider uppercase opacity-80">{{ $role }}</span>
                        </p>
                        <time datetime="{{ $msg->created_at->toIso8601String() }}" class="font-mono text-[11px] text-stone-500">{{ $msg->created_at->format('d/m/Y H:i:s') }}</time>
                    </div>
                    <div @class(['mt-1.5 border-l-2 pl-3 text-[13.5px] leading-relaxed break-words', $border, 'text-stone-500 italic' => $msg->sender_type === 'system', 'text-stone-800' => $msg->sender_type !== 'system'])>{!! $format((string) $msg->pesan) !!}</div>
                </article>
            @empty
                <p class="py-6 text-center text-sm text-stone-500 italic">Tidak ada riwayat pesan.</p>
            @endforelse
        </section>

        {{-- Pengesahan --}}
        <div class="mt-12 flex justify-end break-inside-avoid">
            <div class="w-64 text-center text-[13px]">
                <p>Muara Bulian, {{ now()->translatedFormat('d F Y') }}</p>
                <p class="font-semibold">Petugas / Operator Layanan,</p>
                <div class="h-20"></div>
                <p class="font-bold underline underline-offset-4">{{ $petugas ?? 'Sekretariat '.$site['site_short'] }}</p>
            </div>
        </div>

        <p class="mt-10 border-t border-stone-200 pt-3 text-center text-[10.5px] text-stone-400">
            Dicetak dari Sistem Informasi {{ $site['site_short'] }} pada {{ now()->translatedFormat('d F Y, H:i') }} WIB oleh {{ auth()->user()->name_gelar ?: auth()->user()->name }}.
        </p>
    </main>
</body>
</html>
