@php
    $highlight = fn (?string $text) => $q !== ''
        ? preg_replace('/('.preg_quote(e($q), '/').')/iu', '<mark class="rounded bg-gold-100 px-0.5 text-ink-900">$1</mark>', e((string) $text))
        : e((string) $text);
    $groups = [
        'berita' => ['Berita & Kegiatan', 'newspaper', route('berita.list', ['q' => $q])],
        'fatwa' => ['Fatwa MUI', 'scale', route('fatwa', ['q' => $q])],
        'surat' => ['Arsip Surat', 'file-badge', route('surat', ['q' => $q])],
        'konsultasi' => ['Tanya Jawab Umat', 'messages-square', route('konsultasi.list', ['q' => $q, 'status' => 'dijawab'])],
    ];
@endphp

<x-layouts.site :title="$q ? 'Pencarian: '.$q : 'Pencarian'">
    <x-page-hero :title="$q ? 'Hasil pencarian “'.Str::limit($q, 60).'”' : 'Cari di MUI Batanghari'" eyebrow="Pencarian" :crumbs="['Pencarian' => null]"
                 :subtitle="$results ? number_format($total, 0, ',', '.').' hasil ditemukan di berita, fatwa, arsip surat, dan tanya jawab.' : 'Telusuri berita, fatwa, arsip surat, dan tanya jawab umat dalam satu tempat.'">
        <form class="relative mt-8 max-w-2xl" role="search">
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-4 z-10 size-5 -translate-y-1/2 text-white/60" />
            <input name="q" value="{{ $q }}" type="search" minlength="2" required autofocus placeholder="Ketik kata kunci, mis. halal, zakat, vaksin…" aria-label="Kata kunci pencarian" class="w-full rounded-2xl border border-white/15 bg-white/10 py-4 pr-28 pl-12 text-white backdrop-blur placeholder:text-white/50 focus:border-gold-400 focus:outline-none">
            <button class="btn btn-gold btn-sm absolute top-1/2 right-2 -translate-y-1/2">Cari</button>
        </form>
    </x-page-hero>

    <div class="container-x mt-10">
        @if (!$results)
            <div class="card p-8 text-center">
                @if ($q !== '')
                    <p class="text-sm text-red-600">Kata kunci minimal 2 karakter.</p>
                @endif
                <p class="text-xs font-bold tracking-wider text-stone-400 uppercase">Pencarian populer</p>
                <div class="mt-4 flex flex-wrap justify-center gap-2">
                    @foreach (['Halal', 'Zakat', 'Khutbah', 'Vaksin', 'Muamalah', 'Wakaf', 'Pernikahan', 'Riba'] as $term)
                        <a href="{{ route('search', ['q' => $term]) }}" class="rounded-full bg-sand-100 px-4 py-2 text-sm text-stone-700 transition hover:bg-brand-700 hover:text-white">{{ $term }}</a>
                    @endforeach
                </div>
            </div>
        @else
            {{-- Ringkasan per jenis --}}
            <nav class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="Ringkasan hasil">
                @foreach ($groups as $key => [$label, $icon, $url])
                    <a href="{{ $results[$key]['total'] ? '#hasil-'.$key : $url }}" class="card flex items-center gap-3 p-4 transition hover:border-brand-200">
                        <span class="grid size-10 place-items-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100"><x-icon :name="$icon" class="size-5" /></span>
                        <span>
                            <span class="block font-display text-2xl leading-none font-bold text-ink-900">{{ $results[$key]['total'] }}</span>
                            <span class="text-xs text-stone-500">{{ $label }}</span>
                        </span>
                    </a>
                @endforeach
            </nav>

            @if ($total === 0)
                <x-empty-state class="mt-10" icon="search-x" title="Tidak ada hasil" :message="'Tidak ditemukan konten untuk “'.$q.'”. Coba kata kunci lain atau ajukan pertanyaan kepada ulama.'">
                    <a href="{{ route('tanya-ulama') }}" class="btn btn-primary btn-sm mt-5">Tanya Ulama</a>
                </x-empty-state>
            @endif

            @if ($results['berita']['total'])
                <section id="hasil-berita" class="mt-14 scroll-mt-28">
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <h2 class="flex items-center gap-2 font-display text-2xl font-semibold text-ink-900"><x-icon name="newspaper" class="size-6 text-gold-500" /> Berita & Kegiatan <span class="text-base font-normal text-stone-400">({{ $results['berita']['total'] }})</span></h2>
                        <a href="{{ $groups['berita'][2] }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900">Lihat semua →</a>
                    </div>
                    <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($results['berita']['items'] as $berita)
                            <x-post-card :berita="$berita" />
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($results['fatwa']['total'])
                <section id="hasil-fatwa" class="mt-14 scroll-mt-28">
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <h2 class="flex items-center gap-2 font-display text-2xl font-semibold text-ink-900"><x-icon name="scale" class="size-6 text-gold-500" /> Fatwa MUI <span class="text-base font-normal text-stone-400">({{ $results['fatwa']['total'] }})</span></h2>
                        <a href="{{ $groups['fatwa'][2] }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900">Lihat semua →</a>
                    </div>
                    <div class="mt-6 space-y-3">
                        @foreach ($results['fatwa']['items'] as $fatwa)
                            <a href="{{ route('fatwa.detail', $fatwa) }}" class="group card flex items-start gap-4 p-5 transition hover:border-brand-200 hover:shadow-[var(--shadow-lift)]">
                                <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-brand-700 text-gold-300"><x-icon name="scale" class="size-5" /></span>
                                <span class="min-w-0 flex-1">
                                    <span class="flex flex-wrap items-center gap-2">
                                        @if ($fatwa->kategori)<span class="badge badge-green">{{ $fatwa->kategori->nama }}</span>@endif
                                        <x-fatwa-status :status="$fatwa->status_fatwa ?? 'aktif'" />
                                    </span>
                                    <span class="mt-2 block font-semibold leading-snug text-ink-900 group-hover:text-brand-700">{!! $highlight($fatwa->judul) !!}</span>
                                    @if ($fatwa->keterangan)
                                        <span class="mt-1 line-clamp-2 block text-sm text-stone-500">{!! $highlight(Str::limit($fatwa->keterangan, 180)) !!}</span>
                                    @endif
                                </span>
                                <x-icon name="arrow-up-right" class="size-5 shrink-0 text-stone-300 group-hover:text-brand-600" />
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($results['surat']['total'])
                <section id="hasil-surat" class="mt-14 scroll-mt-28">
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <h2 class="flex items-center gap-2 font-display text-2xl font-semibold text-ink-900"><x-icon name="file-badge" class="size-6 text-gold-500" /> Arsip Surat <span class="text-base font-normal text-stone-400">({{ $results['surat']['total'] }})</span></h2>
                        <a href="{{ $groups['surat'][2] }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900">Lihat semua →</a>
                    </div>
                    <div class="mt-6 grid gap-3 md:grid-cols-2">
                        @foreach ($results['surat']['items'] as $surat)
                            <a href="{{ $surat->file_url ?? route('surat', ['q' => $surat->nomor_surat]) }}" @if ($surat->file_url) target="_blank" rel="noopener" @endif class="group card flex items-start gap-4 p-5 transition hover:border-brand-200">
                                <span class="flex size-12 shrink-0 flex-col items-center justify-center rounded-xl bg-gold-50 text-gold-700 ring-1 ring-gold-200">
                                    <span class="font-display text-lg leading-none font-bold">{{ $surat->tanggal_surat?->format('d') }}</span>
                                    <span class="text-[9px] font-bold uppercase">{{ $surat->tanggal_surat?->translatedFormat('M y') }}</span>
                                </span>
                                <span class="min-w-0">
                                    <span class="block font-mono text-[11px] text-stone-500">{!! $highlight($surat->nomor_surat) !!}</span>
                                    <span class="mt-1 block font-semibold leading-snug text-ink-900 group-hover:text-brand-700">{!! $highlight($surat->perihal) !!}</span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($results['konsultasi']['total'])
                <section id="hasil-konsultasi" class="mt-14 scroll-mt-28">
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <h2 class="flex items-center gap-2 font-display text-2xl font-semibold text-ink-900"><x-icon name="messages-square" class="size-6 text-gold-500" /> Tanya Jawab Umat <span class="text-base font-normal text-stone-400">({{ $results['konsultasi']['total'] }})</span></h2>
                        <a href="{{ $groups['konsultasi'][2] }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900">Lihat semua →</a>
                    </div>
                    <div class="mt-6 space-y-3">
                        @foreach ($results['konsultasi']['items'] as $tanya)
                            <a href="{{ route('konsultasi.detail', $tanya) }}" class="group card block p-5 transition hover:border-brand-200">
                                <span class="badge badge-green">{{ $tanya->kategori }}</span>
                                <span class="mt-2 line-clamp-2 block font-semibold leading-snug text-ink-900 group-hover:text-brand-700">{!! $highlight($tanya->pertanyaan) !!}</span>
                                <span class="mt-1 line-clamp-2 block text-sm text-stone-500">{!! $highlight(Str::limit((string) $tanya->jawaban, 200)) !!}</span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        @endif
    </div>
</x-layouts.site>
