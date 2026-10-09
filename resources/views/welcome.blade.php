@php
    $tahunBerdiri = preg_match('/\b(19|20)\d{2}\b/', (string) $profil['berdiri'], $m) ? $m[0] : '1975';
@endphp

<x-layouts.site :og-image="$heroBeritas->first()?->gambar_url">
    {{-- ================= HERO ================= --}}
    <section class="bg-gradient-brand relative isolate overflow-hidden" x-data="slider({{ max(1, $heroBeritas->count()) }})" @mouseenter="paused = true" @mouseleave="paused = false"
             @touchstart.passive="onTouchStart($event)" @touchend.passive="onTouchEnd($event)" aria-roledescription="carousel" aria-label="Berita utama">
        {{-- Latar slide --}}
        @foreach ($heroBeritas as $i => $berita)
            <div class="absolute inset-0 -z-10" x-show="current === {{ $i }}" x-transition.opacity.duration.1000ms @if ($i > 0) x-cloak @endif>
                @if ($berita->gambar_url)
                    <img src="{{ $berita->gambar_url }}" alt="" class="size-full scale-105 object-cover" @if ($i === 0) fetchpriority="high" @else loading="lazy" @endif>
                @endif
                <div class="absolute inset-0 bg-gradient-to-r from-brand-950 via-brand-950/85 to-brand-950/40"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-brand-950 via-transparent to-brand-950/60"></div>
            </div>
        @endforeach
        <div class="pattern-islamic absolute inset-0 -z-10"></div>
        <div class="absolute top-1/4 -right-40 -z-10 size-[520px] rounded-full bg-gold-400/10 blur-3xl"></div>
        <svg class="animate-spin-slow absolute -top-40 -left-40 -z-10 size-[520px] text-gold-300/10" viewBox="0 0 200 200" fill="none" stroke="currentColor" stroke-width=".6" aria-hidden="true">
            @for ($r = 0; $r < 8; $r++)<rect x="50" y="50" width="100" height="100" transform="rotate({{ $r * 11.25 }} 100 100)"/>@endfor
        </svg>

        <div class="container-x grid gap-10 pt-32 pb-36 lg:grid-cols-[1fr_380px] lg:items-center lg:gap-14 lg:pt-44 lg:pb-44">
            {{-- Teks slide --}}
            <div class="relative min-w-0">
                <div class="grid">
                    @forelse ($heroBeritas as $i => $berita)
                        <article x-show="current === {{ $i }}" @if ($i > 0) x-cloak @endif
                                 x-transition:enter="transition duration-700 ease-out delay-200" x-transition:enter-start="translate-y-6 opacity-0"
                                 class="col-start-1 row-start-1" aria-roledescription="slide" aria-label="{{ $i + 1 }} dari {{ $heroBeritas->count() }}">
                            <div class="flex flex-wrap items-center gap-3">
                                @if ($berita->kategori)
                                    <span class="badge bg-gold-400 text-brand-950">{{ $berita->kategori }}</span>
                                @endif
                                <span class="flex items-center gap-1.5 text-xs text-white/65"><x-icon name="calendar-days" class="size-3.5" /> {{ $berita->tanggal_terbit?->translatedFormat('d F Y') }}</span>
                                <span class="flex items-center gap-1.5 text-xs text-white/65"><x-icon name="eye" class="size-3.5" /> {{ number_format($berita->views ?? 0, 0, ',', '.') }} kali dibaca</span>
                            </div>
                            <{{ $i === 0 ? 'h1' : 'h2' }} class="mt-5 max-w-3xl font-display text-3xl leading-[1.15] font-semibold text-balance text-white sm:text-5xl lg:text-[3.4rem]">
                                <a href="{{ route('berita.detail', $berita->slug) }}" class="hover:text-gold-200">{{ $berita->judul }}</a>
                            </{{ $i === 0 ? 'h1' : 'h2' }}>
                            <p class="mt-5 line-clamp-3 max-w-2xl text-base leading-relaxed text-white/75 sm:text-lg">{{ $berita->ringkasan(220) }}</p>
                            <div class="mt-8 flex flex-wrap gap-3">
                                <a href="{{ route('berita.detail', $berita->slug) }}" class="btn btn-gold btn-lg">Baca Selengkapnya <x-icon name="arrow-right" class="size-4" /></a>
                                <a href="{{ route('berita.list') }}" class="btn btn-glass btn-lg">Semua Kabar</a>
                            </div>
                        </article>
                    @empty
                        <div>
                            <p class="eyebrow text-gold-300!">Assalamu’alaikum</p>
                            <h1 class="mt-5 font-display text-4xl leading-tight font-semibold text-white sm:text-6xl">{{ $site['site_name'] }}<br><span class="text-gradient-gold">{{ $site['site_region'] }}</span></h1>
                            <p class="mt-5 max-w-xl text-lg text-white/75">{{ $site['site_description'] }}</p>
                        </div>
                    @endforelse
                </div>
                @if ($heroBeritas->count() > 1)
                    <div class="mt-10 flex items-center gap-3">
                        @foreach ($heroBeritas as $i => $berita)
                            <button type="button" @click="go({{ $i }})" class="relative h-1 w-10 overflow-hidden rounded-full bg-white/20 sm:w-16" aria-label="Tampilkan slide {{ $i + 1 }}">
                                <span class="absolute inset-y-0 left-0 rounded-full bg-gold-400" :class="current === {{ $i }} ? 'w-full transition-[width] duration-[7000ms] ease-linear' : (current > {{ $i }} ? 'w-full' : 'w-0')"></span>
                            </button>
                        @endforeach
                        <div class="ml-auto flex gap-2">
                            <button type="button" @click="go(current - 1)" class="grid size-10 place-items-center rounded-full border border-white/20 text-white hover:bg-white/10" aria-label="Sebelumnya"><x-icon name="chevron-left" class="size-5" /></button>
                            <button type="button" @click="go(current + 1)" class="grid size-10 place-items-center rounded-full border border-white/20 text-white hover:bg-white/10" aria-label="Berikutnya"><x-icon name="chevron-right" class="size-5" /></button>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Jadwal sholat --}}
            <aside x-data="prayerTimes" class="relative rounded-3xl border border-white/15 bg-white/[.07] p-5 text-white shadow-2xl backdrop-blur-xl sm:p-6" aria-label="Jadwal sholat hari ini" :aria-label="tomorrow ? 'Jadwal sholat besok' : 'Jadwal sholat hari ini'">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-bold tracking-[.2em] text-gold-300 uppercase">Jadwal Sholat<span x-show="tomorrow" x-cloak> · Besok</span></p>
                        <p class="mt-1 flex items-center gap-1.5 text-sm text-white/70"><x-icon name="map-pin" class="size-3.5" /> <span><span x-text="city">Muara Bulian</span>, Batanghari</span></p>
                    </div>
                    <div class="text-right" x-data="clock">
                        <p class="font-display text-2xl font-semibold" x-text="time"></p>
                        <p class="text-[11px] text-white/60" x-text="hijri"></p>
                    </div>
                </div>
                <div class="mt-5 rounded-2xl bg-gradient-to-br from-gold-300 to-gold-500 p-4 text-brand-950">
                    <p class="text-xs font-semibold opacity-75">Menuju waktu <span x-text="next?.name"></span> · <span x-text="next?.time"></span></p>
                    <p class="mt-1 font-mono text-3xl font-bold tracking-wider tabular-nums" x-text="countdown">--:--:--</p>
                </div>
                <ul class="mt-4 grid grid-cols-2 gap-1.5 text-sm">
                    <template x-for="p in items" :key="p.key">
                        <li class="flex items-center justify-between rounded-xl px-3 py-2 transition" :class="p.active ? 'bg-white/15 ring-1 ring-gold-300/50' : 'bg-white/[.04]'">
                            <span class="text-white/75" :class="p.main && 'font-semibold text-white'" x-text="p.name"></span>
                            <span class="font-semibold tabular-nums" :class="p.active ? 'text-gold-300' : 'text-white'" x-text="p.time"></span>
                        </li>
                    </template>
                </ul>
                <p class="mt-3 text-[10.5px] text-white/45">Perhitungan astronomis lokal (Subuh 20°, Isya 18°, setara Kemenag RI) + ihtiyath 2 menit.</p>
            </aside>
        </div>
    </section>

    {{-- ================= LAYANAN CEPAT ================= --}}
    <section class="container-x relative z-10 -mt-20 lg:-mt-24" x-data aria-label="Layanan cepat">
        <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-5">
            @foreach ([
                ['Tanya Ulama', 'Ajukan pertanyaan keagamaan', 'file-pen-line', route('tanya-ulama'), true],
                ['Konsultasi Online', 'Live chat dengan petugas MUI', 'messages-square', null, false],
                ['Arsip Fatwa', 'Keputusan Komisi Fatwa', 'scale', route('fatwa'), false],
                ['Arsip Surat', 'Surat resmi & keputusan', 'file-badge', route('surat'), false],
                ['Tanya Jawab Umat', 'Jawaban ulama untuk umat', 'circle-help', route('konsultasi.list'), false],
            ] as [$label, $desc, $icon, $url, $highlight])
                <a href="{{ $url ?? '#' }}" @if (!$url) @click.prevent="$dispatch('open-chat')" @endif @class([
                    'group relative overflow-hidden rounded-2xl p-4 transition duration-300 hover:-translate-y-1 sm:p-5',
                    'bg-gradient-to-br from-gold-300 to-gold-500 text-brand-950 shadow-[var(--shadow-glow)]' => $highlight,
                    'card hover:shadow-[var(--shadow-lift)]' => !$highlight,
                    'col-span-2 lg:col-span-1' => $loop->last,
                ])>
                    <span @class([
                        'grid size-11 place-items-center rounded-xl transition',
                        'bg-brand-950/10' => $highlight,
                        'bg-brand-50 text-brand-700 group-hover:bg-brand-700 group-hover:text-gold-300' => !$highlight,
                    ])><x-icon :name="$icon" class="size-5" /></span>
                    <h3 @class(['mt-4 text-[15px] leading-tight font-bold', 'text-brand-950' => $highlight])>{{ $label }}</h3>
                    <p @class(['mt-1 text-xs', 'text-brand-950/70' => $highlight, 'text-stone-500' => !$highlight])>{{ $desc }}</p>
                    <x-icon name="arrow-up-right" class="absolute top-4 right-4 size-4 opacity-40 transition group-hover:opacity-100" />
                </a>
            @endforeach
        </div>
    </section>

    {{-- ================= TENTANG & STATISTIK ================= --}}
    <section class="container-x mt-24 grid gap-12 lg:grid-cols-12 lg:items-center">
        <div class="reveal relative lg:col-span-5">
            <div class="relative mx-auto aspect-[4/5] max-w-[19rem] sm:max-w-sm">
                <div class="absolute inset-0 translate-x-4 translate-y-4 rounded-[2rem] border-2 border-gold-400/60"></div>
                <div class="bg-gradient-brand relative size-full overflow-hidden rounded-[2rem] shadow-[var(--shadow-lift)]">
                    <div class="pattern-islamic absolute inset-0"></div>
                    <div class="absolute -top-16 -right-16 size-56 rounded-full bg-gold-400/20 blur-3xl"></div>
                    <div class="absolute inset-0 grid place-items-center pb-16">
                        <div class="relative">
                            <div class="absolute -inset-6 rounded-full border border-gold-300/30"></div>
                            <div class="absolute -inset-12 rounded-full border border-gold-300/15"></div>
                            <img src="{{ $site['logo_url'] }}" alt="Lambang Majelis Ulama Indonesia" loading="lazy" class="relative size-40 rounded-full bg-white object-contain p-1.5 shadow-2xl ring-4 ring-gold-300/70">
                        </div>
                    </div>
                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-brand-950 to-transparent p-6 pt-20 pb-16 sm:pb-6">
                        <p class="font-display text-xl font-semibold text-white">{{ $site['site_name'] }}</p>
                        <p class="text-xs font-semibold tracking-wider text-gold-300 uppercase">{{ $site['site_region'] }}</p>
                    </div>
                </div>
                <div class="animate-float absolute -right-3 -bottom-8 rounded-2xl bg-white p-4 shadow-[var(--shadow-lift)] sm:-right-10 sm:-bottom-6">
                    <p class="text-[11px] font-bold tracking-wider text-stone-400 uppercase">MUI berdiri</p>
                    <p class="font-display text-3xl font-bold text-brand-700">{{ $tahunBerdiri }}</p>
                </div>
            </div>
        </div>
        <div class="reveal lg:col-span-7">
            <p class="eyebrow">Tentang Kami</p>
            <h2 class="section-title mt-3">Pelayan umat, penjaga akidah, dan mitra pemerintah di Bumi Serentak Bak Regam</h2>
            <x-icon name="quote" class="mt-6 size-10 text-gold-400" />
            <p class="mt-3 text-[16.5px] leading-[1.9] text-stone-600">{{ $profil['sekilas'] ?? 'Majelis Ulama Indonesia (MUI) adalah wadah musyawarah para ulama, zu’ama, dan cendekiawan muslim dalam membimbing, membina, dan mengayomi umat Islam.' }}</p>

            @if ($profil['peran']->isNotEmpty())
                <div class="mt-7 grid gap-3 sm:grid-cols-3">
                    @foreach ($profil['peran'] as $peran)
                        <div class="rounded-2xl border border-stone-200 bg-white p-4">
                            <span class="font-display text-sm font-bold text-gold-500">0{{ $loop->iteration }}</span>
                            <p class="mt-1 font-semibold text-ink-900">{{ $peran['title'] }}</p>
                            <p class="mt-1 line-clamp-3 text-xs leading-relaxed text-stone-500">{{ $peran['desc'] }}</p>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('profilemui') }}" class="btn btn-primary">Mengenal MUI Batanghari <x-icon name="arrow-right" class="size-4" /></a>
                <a href="{{ route('struktur-organisasi') }}" class="btn btn-outline">Struktur Pengurus</a>
            </div>

            <dl class="mt-10 grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ([
                    ['Fatwa', $stats['fatwa'], 'scale'],
                    ['Berita', $stats['berita'], 'newspaper'],
                    ['Surat Resmi', $stats['surat'], 'file-badge'],
                    ['Tanya Jawab', $stats['konsultasi'], 'messages-square'],
                ] as [$label, $value, $icon])
                    <div class="rounded-2xl border border-stone-200 bg-white p-4" x-data="counter({{ $value }})" x-intersect.once="run()">
                        <x-icon :name="$icon" class="size-5 text-gold-500" />
                        <dd class="mt-2 font-display text-3xl font-bold text-brand-800 tabular-nums" x-text="display">{{ $value }}</dd>
                        <dt class="text-xs font-medium text-stone-500">{{ $label }}</dt>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    {{-- ================= BERITA TERBARU ================= --}}
    <section class="container-x mt-28">
        <div class="reveal flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="eyebrow">Kabar Terkini</p>
                <h2 class="section-title mt-3">Berita & Kegiatan</h2>
            </div>
            <a href="{{ route('berita.list') }}" class="btn btn-outline btn-sm">Lihat semua berita <x-icon name="arrow-right" class="size-4" /></a>
        </div>

        @if ($beritaUtama)
            <div class="mt-10 grid gap-6 lg:grid-cols-12">
                <article class="group reveal relative overflow-hidden rounded-3xl lg:col-span-7">
                    <x-cover :src="$beritaUtama->gambar_url" :alt="$beritaUtama->judul" icon="" class="aspect-[4/3] size-full lg:aspect-auto lg:min-h-[520px]" />
                    <div class="absolute inset-0 bg-gradient-to-t from-brand-950 via-brand-950/40 to-transparent"></div>
                    <div class="absolute inset-x-0 bottom-0 p-6 sm:p-8">
                        @if ($beritaUtama->kategori)
                            <span class="badge bg-gold-400 text-brand-950">{{ $beritaUtama->kategori }}</span>
                        @endif
                        <h3 class="mt-3 font-display text-2xl leading-snug font-semibold text-white sm:text-3xl">
                            <a href="{{ route('berita.detail', $beritaUtama->slug) }}" class="after:absolute after:inset-0">{{ $beritaUtama->judul }}</a>
                        </h3>
                        <p class="mt-3 line-clamp-2 max-w-xl text-sm text-white/75">{{ $beritaUtama->ringkasan(180) }}</p>
                        <p class="mt-4 flex items-center gap-4 text-xs text-white/60">
                            <span class="flex items-center gap-1.5"><x-icon name="calendar-days" class="size-3.5" /> {{ $beritaUtama->tanggal_terbit?->translatedFormat('d F Y') }}</span>
                            <span class="flex items-center gap-1.5"><x-icon name="eye" class="size-3.5" /> {{ number_format($beritaUtama->views ?? 0, 0, ',', '.') }} kali dibaca</span>
                        </p>
                    </div>
                </article>
                <div class="reveal grid content-start gap-4 sm:grid-cols-2 lg:col-span-5 lg:grid-cols-1">
                    @foreach ($beritaLain as $berita)
                        <div class="card p-3 transition hover:border-brand-200 hover:shadow-[var(--shadow-lift)]"><x-post-card :berita="$berita" horizontal /></div>
                    @endforeach
                </div>
            </div>
        @else
            <x-empty-state class="mt-10" icon="newspaper" title="Belum ada berita" message="Berita kegiatan MUI Batanghari akan tampil di sini." />
        @endif
    </section>

    {{-- ================= FATWA TERBARU ================= --}}
    <section class="bg-gradient-brand relative mt-28 overflow-hidden py-24">
        <div class="pattern-islamic absolute inset-0"></div>
        <div class="absolute -right-40 -bottom-40 size-[500px] rounded-full bg-gold-400/10 blur-3xl"></div>
        <div class="container-x relative grid gap-12 lg:grid-cols-12">
            <div class="reveal lg:col-span-4">
                <p class="eyebrow text-gold-300!">Arsip Resmi</p>
                <h2 class="mt-3 font-display text-3xl leading-tight font-semibold text-white sm:text-4xl">Fatwa & Keputusan MUI</h2>
                <p class="mt-4 leading-relaxed text-white/70">Keputusan Komisi Fatwa Majelis Ulama Indonesia yang dapat dibaca langsung melalui penampil PDF dan diunduh untuk rujukan umat.</p>
                <form action="{{ route('fatwa') }}" class="relative mt-8" role="search">
                    <x-icon name="search" class="pointer-events-none absolute top-1/2 left-4 z-10 size-5 -translate-y-1/2 text-white/60" />
                    <input name="q" type="search" placeholder="Cari judul atau kata kunci…" aria-label="Cari fatwa" class="w-full rounded-2xl border border-white/15 bg-white/10 py-4 pr-24 pl-12 text-white backdrop-blur placeholder:text-white/50 focus:border-gold-400 focus:outline-none">
                    <button class="btn btn-gold btn-sm absolute top-1/2 right-2 -translate-y-1/2">Cari</button>
                </form>
                @if ($kategoriFatwa->isNotEmpty())
                    <div class="mt-6 flex flex-wrap gap-2">
                        @foreach ($kategoriFatwa as $kategori)
                            <a href="{{ route('fatwa', ['kategori' => $kategori->slug]) }}" class="rounded-full border border-white/15 px-4 py-1.5 text-sm text-white/80 transition hover:border-gold-400 hover:bg-gold-400 hover:text-brand-950">{{ $kategori->nama }}</a>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="reveal space-y-3 lg:col-span-8">
                @forelse ($fatwaTerbaru as $fatwa)
                    <a href="{{ route('fatwa.detail', $fatwa) }}" class="group flex items-start gap-4 rounded-2xl border border-white/10 bg-white/[.06] p-4 backdrop-blur transition hover:border-gold-400/50 hover:bg-white/10 sm:gap-5 sm:p-5">
                        <span class="hidden size-14 shrink-0 flex-col items-center justify-center rounded-xl bg-gold-400/15 text-gold-300 ring-1 ring-gold-400/30 sm:flex">
                            <span class="text-[10px] font-bold tracking-wider uppercase">{{ $fatwa->created_at?->translatedFormat('M') }}</span>
                            <span class="font-display text-xl leading-none font-bold">{{ $fatwa->created_at?->format('Y') }}</span>
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="badge bg-white/10 text-gold-300">{{ $fatwa->kategori?->nama ?? 'Fatwa' }}</span>
                                @if ($fatwa->status_fatwa && $fatwa->status_fatwa !== 'aktif')
                                    <span class="text-xs text-white/50">· {{ \App\Models\Fatwa::STATUSES[$fatwa->status_fatwa] ?? ucfirst($fatwa->status_fatwa) }}</span>
                                @endif
                                <span class="flex items-center gap-1 text-xs text-white/50">· <x-icon name="eye" class="size-3" /> {{ number_format($fatwa->views ?? 0, 0, ',', '.') }}</span>
                            </div>
                            <h3 class="mt-2 leading-snug font-semibold text-white group-hover:text-gold-200">{{ $fatwa->judul }}</h3>
                        </div>
                        <x-icon name="arrow-up-right" class="size-5 shrink-0 text-white/40 transition group-hover:text-gold-300" />
                    </a>
                @empty
                    <p class="rounded-2xl border border-white/10 p-8 text-center text-white/60">Belum ada fatwa yang dipublikasikan.</p>
                @endforelse
                <a href="{{ route('fatwa') }}" class="inline-flex items-center gap-2 pt-2 text-sm font-semibold text-gold-300 hover:text-gold-200">Jelajahi seluruh arsip fatwa <x-icon name="arrow-right" class="size-4" /></a>
            </div>
        </div>
    </section>

    {{-- ================= KHUTBAH & TERPOPULER ================= --}}
    <section class="container-x mt-28 grid gap-12 lg:grid-cols-12">
        <div class="lg:col-span-8">
            <div class="reveal flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="eyebrow">Khazanah Ilmu</p>
                    <h2 class="section-title mt-3">Kajian & Bimbingan Umat</h2>
                </div>
                <a href="{{ route('berita.list', ['kategori' => 'Khutbah']) }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900">Naskah khutbah →</a>
            </div>
            <div class="reveal mt-8 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                @forelse ($beritaKhutbah as $berita)
                    <x-post-card :berita="$berita" />
                @empty
                    <x-empty-state class="sm:col-span-3" icon="book-open" title="Belum ada khutbah" />
                @endforelse
            </div>
        </div>
        <aside class="reveal lg:col-span-4" x-data>
            <div class="card overflow-hidden">
                <div class="flex items-center gap-3 border-b border-stone-100 bg-sand-100/60 px-6 py-5">
                    <span class="grid size-10 place-items-center rounded-xl bg-gold-400 text-brand-950"><x-icon name="flame" class="size-5" /></span>
                    <div>
                        <h2 class="font-bold text-ink-900">Terpopuler</h2>
                        <p class="text-xs text-stone-500">Paling banyak dibaca umat</p>
                    </div>
                </div>
                <ol class="divide-y divide-stone-100">
                    @forelse ($beritaPopuler as $berita)
                        <li>
                            <a href="{{ route('berita.detail', $berita->slug) }}" class="group flex gap-4 px-6 py-4 hover:bg-brand-50/40">
                                <span class="font-display text-2xl leading-none font-bold text-stone-300 group-hover:text-gold-500">{{ $loop->iteration }}</span>
                                <span class="min-w-0">
                                    <span class="text-[11px] font-bold tracking-wider text-gold-600 uppercase">{{ $berita->kategori }}</span>
                                    <span class="mt-0.5 line-clamp-2 block text-sm font-semibold text-ink-900 group-hover:text-brand-700">{{ $berita->judul }}</span>
                                </span>
                            </a>
                        </li>
                    @empty
                        <li class="px-6 py-8 text-center text-sm text-stone-500">Belum ada berita.</li>
                    @endforelse
                </ol>
            </div>

            <div class="bg-gradient-brand relative mt-6 overflow-hidden rounded-2xl p-6 text-white">
                <div class="pattern-islamic absolute inset-0"></div>
                <div class="relative">
                    <x-icon name="headset" class="size-8 text-gold-300" />
                    <h3 class="mt-3 font-display text-xl font-semibold text-white">Punya pertanyaan keagamaan?</h3>
                    <p class="mt-2 text-sm text-white/70">Konsultasikan langsung dengan petugas MUI melalui live chat, atau kirim pertanyaan tertulis kepada Komisi Fatwa.</p>
                    <div class="mt-5 flex flex-wrap gap-2">
                        <button type="button" @click="$dispatch('open-chat')" class="btn btn-gold btn-sm">Mulai Konsultasi</button>
                        <a href="{{ route('tanya-ulama') }}" class="btn btn-glass btn-sm">Tanya Ulama</a>
                    </div>
                </div>
            </div>
        </aside>
    </section>

    {{-- ================= TANYA JAWAB ================= --}}
    @if ($tanyaJawab->isNotEmpty())
        <section class="container-x mt-28">
            <div class="reveal flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="eyebrow">Tanya Ulama</p>
                    <h2 class="section-title mt-3">Tanya Jawab Umat</h2>
                    <p class="mt-3 max-w-2xl text-stone-600">Pertanyaan masyarakat yang telah dijawab oleh ulama MUI Kabupaten Batanghari.</p>
                </div>
                <a href="{{ route('konsultasi.list') }}" class="btn btn-outline btn-sm">Semua tanya jawab <x-icon name="arrow-right" class="size-4" /></a>
            </div>
            <div class="reveal mt-10 grid gap-5 md:grid-cols-2">
                @foreach ($tanyaJawab as $tanya)
                    <article class="group card card-hover relative flex flex-col p-6">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="badge badge-green">{{ $tanya->kategori }}</span>
                            <span class="text-xs text-stone-500">{{ $tanya->answered_at?->translatedFormat('d M Y') }}</span>
                        </div>
                        <h3 class="mt-4 flex gap-3 font-semibold leading-snug text-ink-900">
                            <span class="grid size-7 shrink-0 place-items-center rounded-lg bg-gold-100 font-display text-sm text-gold-700">T</span>
                            <a href="{{ route('konsultasi.detail', $tanya) }}" class="line-clamp-3 after:absolute after:inset-0 group-hover:text-brand-700">{{ $tanya->pertanyaan }}</a>
                        </h3>
                        <p class="mt-3 flex gap-3 text-sm leading-relaxed text-stone-600">
                            <span class="grid size-7 shrink-0 place-items-center rounded-lg bg-brand-50 font-display text-sm text-brand-700">J</span>
                            <span class="line-clamp-3">{{ Str::limit(strip_tags((string) $tanya->jawaban), 220) }}</span>
                        </p>
                        <p class="mt-auto flex items-center gap-2 pt-5 text-xs text-stone-500">
                            <x-icon name="user-round" class="size-3.5" /> {{ $tanya->nama_samaran }}{{ $tanya->kab_kota ? ', '.$tanya->kab_kota : '' }}
                        </p>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
</x-layouts.site>
