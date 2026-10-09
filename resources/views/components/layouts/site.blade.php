@props(['title' => null, 'description' => null, 'ogImage' => null, 'ogType' => 'website', 'solid' => false])
@php
    $nav = require resource_path('views/components/layouts/nav.php');
    $brand = $site['site_short'];
    $pageTitle = $title ? $title.' — '.$brand : $brand.' — '.$site['site_name'].' '.$site['site_region'];
    $pageDescription = $description ?? $site['site_description'];
    $isActive = fn ($pattern) => collect((array) $pattern)->contains(fn ($p) => request()->routeIs($p));
    $socials = collect(['facebook', 'instagram', 'youtube', 'tiktok', 'x-twitter'])->filter(fn ($s) => filled($site[$s]));
    $socialLabels = ['facebook' => 'Facebook', 'instagram' => 'Instagram', 'youtube' => 'YouTube', 'tiktok' => 'TikTok', 'x-twitter' => 'X (Twitter)'];
    $waNumber = preg_replace('/\D/', '', (string) $site['whatsapp']);
    $waNumber = str_starts_with($waNumber, '0') ? '62'.substr($waNumber, 1) : $waNumber;
@endphp
<!DOCTYPE html>
<html lang="id" class="scroll-pt-24">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ Str::limit(strip_tags($pageDescription), 160) }}">
    <meta name="theme-color" content="#0c402f">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:site_name" content="{{ $brand }}">
    <meta property="og:locale" content="id_ID">
    <meta property="og:title" content="{{ $title ?? $brand }}">
    <meta property="og:description" content="{{ Str::limit(strip_tags($pageDescription), 200) }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $ogImage ?? $site['logo_url'] }}">
    <meta name="twitter:card" content="{{ $ogImage ? 'summary_large_image' : 'summary' }}">
    <link rel="icon" type="image/png" href="{{ $site['logo_url'] }}">
    <link rel="apple-touch-icon" href="{{ $site['logo_url'] }}">
    <script>document.documentElement.classList.add('js')</script>
    <script>
        window.MUI = {
            chat: {{ Js::from([
                'init' => route('livechat.init'),
                'start' => route('livechat.start'),
                'send' => route('livechat.send'),
                'askFaq' => route('livechat.ask-faq'),
                'poll' => route('livechat.poll'),
                'close' => route('livechat.close'),
            ]) }},
        };
    </script>
    <script type="application/ld+json">
        {!! json_encode(array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'ReligiousOrganization',
            'name' => $site['site_name'].' '.$site['site_region'],
            'alternateName' => $brand,
            'url' => url('/'),
            'logo' => $site['logo_url'],
            'address' => $site['address'],
            'telephone' => $site['phone'],
            'email' => $site['email'],
            'sameAs' => $socials->map(fn ($s) => $site[$s])->values()->all(),
        ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen pb-[72px] lg:pb-0">
    <a href="#konten" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[100] focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:shadow">Lewati ke konten</a>

    {{-- ============ HEADER ============ --}}
    <header x-data="siteHeader({{ $solid ? 'true' : 'false' }})" class="fixed inset-x-0 top-0 z-50 transition-transform duration-300" :class="hidden && '-translate-y-full'">
        {{-- Bilah atas --}}
        <div class="hidden border-b border-white/10 bg-brand-950/95 text-[12.5px] text-white/75 backdrop-blur transition-all duration-300 lg:block"
             :class="scrolled && 'lg:-mt-10'">
            <div class="container-x flex h-10 items-center justify-between gap-6">
                <div class="flex items-center gap-5" x-data="clock">
                    <span class="flex items-center gap-1.5"><x-icon name="calendar-days" class="size-3.5 text-gold-400" /> <span x-text="masehi">{{ now()->translatedFormat('l, j F Y') }}</span></span>
                    <span class="flex items-center gap-1.5"><x-icon name="moon-star" class="size-3.5 text-gold-400" /> <span x-text="hijri"></span></span>
                </div>
                <div class="flex items-center gap-5">
                    <span x-data="prayerTimes" class="flex items-center gap-1.5" title="Waktu sholat berikutnya — Muara Bulian">
                        <x-icon name="alarm-clock" class="size-3.5 text-gold-400" />
                        <span x-show="next" x-cloak><span x-text="next?.name"></span> <b class="font-semibold text-white" x-text="next?.time"></b> <span class="text-white/50">· <span x-text="countdown"></span></span></span>
                    </span>
                    @if ($site['phone'])
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $site['phone']) }}" class="hidden items-center gap-1.5 hover:text-gold-300 xl:flex"><x-icon name="phone" class="size-3.5" /> {{ $site['phone'] }}</a>
                    @endif
                    @if ($site['email'])
                        <a href="mailto:{{ $site['email'] }}" class="hidden items-center gap-1.5 hover:text-gold-300 xl:flex"><x-icon name="mail" class="size-3.5" /> {{ $site['email'] }}</a>
                    @endif
                    @if ($socials->isNotEmpty())
                        <span class="h-4 w-px bg-white/15"></span>
                        <div class="flex items-center gap-3">
                            @foreach ($socials as $social)
                                <a href="{{ $site[$social] }}" target="_blank" rel="noopener" class="hover:text-gold-300" aria-label="{{ $socialLabels[$social] }}"><x-icon :name="$social" class="size-3.5" /></a>
                            @endforeach
                        </div>
                    @endif
                    <span class="h-4 w-px bg-white/15"></span>
                    @auth
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-1.5 font-semibold text-gold-300 hover:text-gold-200"><x-icon name="layout-dashboard" class="size-3.5" /> Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="flex items-center gap-1.5 hover:text-gold-300"><x-icon name="log-in" class="size-3.5" /> Masuk</a>
                    @endauth
                </div>
            </div>
        </div>

        {{-- Navigasi utama --}}
        <div class="transition-all duration-300" :class="scrolled ? 'bg-white/90 shadow-[0_8px_30px_-12px_rgba(6,36,26,.25)] backdrop-blur-xl' : 'bg-transparent'">
            <div class="container-x flex h-[72px] items-center justify-between gap-4">
                <a href="{{ route('home.public') }}" class="relative shrink-0" aria-label="Beranda {{ $brand }}">
                    <span x-show="!scrolled" @class(['block', 'hidden' => $solid])><x-logo light /></span>
                    <span x-show="scrolled" @if (!$solid) x-cloak @endif class="block"><x-logo /></span>
                </a>

                <nav class="hidden items-center gap-0.5 lg:flex" aria-label="Menu utama">
                    @foreach ($nav as $item)
                        @if (isset($item['children']))
                            <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false" @keydown.escape="open = false" @focusout="$el.contains($event.relatedTarget) || (open = false)">
                                <button type="button" @click="open = !open" :aria-expanded="open"
                                        class="nav-link {{ $isActive($item['active']) ? 'active' : '' }}"
                                        :class="!scrolled && 'text-white/90! hover:text-gold-300!'">
                                    {{ $item['label'] }} <x-icon name="chevron-down" class="size-3.5 opacity-60 transition" ::class="open && 'rotate-180'" />
                                </button>
                                <div x-show="open" x-cloak x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-2 opacity-0" x-transition:leave="transition duration-150" x-transition:leave-end="opacity-0"
                                     class="absolute top-full left-1/2 w-[360px] -translate-x-1/2 pt-3">
                                    <div class="overflow-hidden rounded-2xl border border-stone-200/80 bg-white p-2 shadow-[var(--shadow-lift)]">
                                        @foreach ($item['children'] as $child)
                                            <a href="{{ $child['url'] }}" @if (!empty($child['chat'])) @click.prevent="open = false; $dispatch('open-chat')" @endif
                                               class="group flex items-start gap-3 rounded-xl p-3 transition hover:bg-brand-50/70">
                                                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100 transition group-hover:bg-brand-700 group-hover:text-gold-300">
                                                    <x-icon :name="$child['icon']" class="size-5" />
                                                </span>
                                                <span>
                                                    <span class="block text-sm font-semibold text-ink-900">{{ $child['label'] }}</span>
                                                    <span class="block text-xs text-stone-500">{{ $child['desc'] }}</span>
                                                </span>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @else
                            <a href="{{ $item['url'] }}" class="nav-link {{ $isActive($item['active']) ? 'active' : '' }}" :class="!scrolled && 'text-white/90! hover:text-gold-300!'">{{ $item['label'] }}</a>
                        @endif
                    @endforeach
                </nav>

                <div class="flex items-center gap-2">
                    <button type="button" @click="$dispatch('open-search')" class="group hidden items-center gap-2 rounded-xl px-3 py-2 text-sm transition sm:flex"
                            :class="scrolled ? 'border border-stone-200 bg-stone-50 text-stone-500 hover:border-brand-400' : 'border border-white/20 bg-white/10 text-white/80 hover:bg-white/20'">
                        <x-icon name="search" class="size-4" />
                        <span class="hidden xl:inline">Cari…</span>
                        <kbd class="hidden rounded-md px-1.5 text-[10px] font-semibold xl:inline" :class="scrolled ? 'bg-white text-stone-400 ring-1 ring-stone-200' : 'bg-white/10 text-white/60'">Ctrl K</kbd>
                    </button>
                    <button type="button" @click="$dispatch('open-search')" class="grid size-10 place-items-center rounded-xl sm:hidden" :class="scrolled ? 'text-stone-700' : 'text-white'" aria-label="Cari">
                        <x-icon name="search" class="size-5" />
                    </button>
                    <a href="{{ route('tanya-ulama') }}" class="btn btn-gold btn-sm hidden xl:inline-flex"><x-icon name="messages-square" class="size-4" /> Tanya Ulama</a>
                    <button type="button" @click="mobile = true" class="grid size-10 place-items-center rounded-xl lg:hidden" :class="scrolled ? 'text-stone-800 hover:bg-stone-100' : 'text-white hover:bg-white/10'" aria-label="Buka menu">
                        <x-icon name="menu" class="size-6" />
                    </button>
                </div>
            </div>
        </div>

        {{-- Menu mobile --}}
        <template x-teleport="body">
            <div x-show="mobile" x-cloak class="fixed inset-0 z-[60] lg:hidden" role="dialog" aria-modal="true" aria-label="Menu navigasi" @keydown.escape.window="mobile = false">
                <div x-show="mobile" x-transition.opacity class="absolute inset-0 bg-brand-950/60 backdrop-blur-sm" @click="mobile = false"></div>
                <aside x-show="mobile" x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-x-full" x-transition:leave="transition duration-200 ease-in" x-transition:leave-end="translate-x-full"
                       class="absolute inset-y-0 right-0 flex w-[88%] max-w-sm flex-col bg-white shadow-2xl">
                    <div class="bg-gradient-brand relative overflow-hidden px-5 pt-5 pb-6">
                        <div class="pattern-islamic absolute inset-0"></div>
                        <div class="relative flex items-center justify-between">
                            <x-logo light />
                            <button type="button" @click="mobile = false" class="grid size-10 place-items-center rounded-xl text-white hover:bg-white/10" aria-label="Tutup menu"><x-icon name="x" class="size-6" /></button>
                        </div>
                        <form action="{{ route('search') }}" class="relative mt-5" role="search">
                            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-white/50" />
                            <input type="search" name="q" placeholder="Cari berita, fatwa, surat…" aria-label="Kata kunci pencarian" class="w-full rounded-xl border border-white/15 bg-white/10 py-3 pr-4 pl-10 text-sm text-white placeholder:text-white/50 focus:border-gold-400 focus:outline-none">
                        </form>
                    </div>
                    <nav class="scrollbar-thin flex-1 overflow-y-auto p-3">
                        @foreach ($nav as $item)
                            @if (isset($item['children']))
                                <div x-data="{ open: {{ $isActive($item['active']) ? 'true' : 'false' }} }" class="border-b border-stone-100">
                                    <button type="button" @click="open = !open" :aria-expanded="open" class="flex w-full items-center justify-between px-3 py-3.5 text-[15px] font-semibold text-ink-900">
                                        {{ $item['label'] }} <x-icon name="chevron-down" class="size-4 text-stone-400 transition" ::class="open && 'rotate-180'" />
                                    </button>
                                    <div x-show="open" x-collapse class="pb-2">
                                        @foreach ($item['children'] as $child)
                                            <a href="{{ $child['url'] }}" @if (!empty($child['chat'])) @click.prevent="mobile = false; $dispatch('open-chat')" @endif
                                               class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-stone-600 hover:bg-brand-50 hover:text-brand-800">
                                                <x-icon :name="$child['icon']" class="size-4 text-brand-600" /> {{ $child['label'] }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <a href="{{ $item['url'] }}" class="block border-b border-stone-100 px-3 py-3.5 text-[15px] font-semibold {{ $isActive($item['active']) ? 'text-brand-700' : 'text-ink-900' }}">{{ $item['label'] }}</a>
                            @endif
                        @endforeach
                        <div class="mt-4 rounded-2xl bg-sand-100 p-4" x-data="prayerTimes">
                            <p class="text-[11px] font-bold tracking-wider text-gold-700 uppercase">Waktu sholat berikutnya</p>
                            <p class="mt-1 text-sm text-stone-700" x-show="next" x-cloak><b class="text-brand-800" x-text="next?.name"></b> pukul <b x-text="next?.time"></b> · <span x-text="countdown"></span> lagi</p>
                        </div>
                    </nav>
                    <div class="grid grid-cols-2 gap-2 border-t border-stone-100 p-4">
                        <a href="{{ route('tanya-ulama') }}" class="btn btn-gold btn-sm"><x-icon name="messages-square" class="size-4" /> Tanya Ulama</a>
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn btn-outline btn-sm"><x-icon name="layout-dashboard" class="size-4 text-brand-600" /> Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-outline btn-sm"><x-icon name="log-in" class="size-4 text-brand-600" /> Masuk</a>
                        @endauth
                    </div>
                </aside>
            </div>
        </template>
    </header>

    <main id="konten">
        {{ $slot }}
    </main>

    {{-- ============ FOOTER ============ --}}
    <footer class="bg-gradient-brand relative mt-24 overflow-hidden text-white/70">
        <div class="pattern-islamic absolute inset-0"></div>
        <div class="absolute -top-40 -left-40 size-96 rounded-full bg-gold-400/10 blur-3xl"></div>

        <div class="container-x relative">
            {{-- CTA --}}
            <div class="relative -mb-2 grid gap-6 border-b border-white/10 py-12 md:grid-cols-[1fr_auto] md:items-center">
                <div>
                    <p class="arabic text-2xl text-gold-300 sm:text-3xl">وَتَعَاوَنُوا عَلَى الْبِرِّ وَالتَّقْوَىٰ</p>
                    <p class="mt-2 font-display text-xl text-white italic sm:text-2xl">“Dan tolong-menolonglah kamu dalam kebajikan dan takwa.”</p>
                    <p class="mt-1 text-xs tracking-wider text-white/50 uppercase">QS. Al-Mā’idah [5]: 2</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('tanya-ulama') }}" class="btn btn-gold"><x-icon name="file-pen-line" class="size-4" /> Ajukan Pertanyaan</a>
                    <button type="button" @click="$dispatch('open-chat')" x-data class="btn btn-glass"><x-icon name="messages-square" class="size-4" /> Konsultasi Online</button>
                </div>
            </div>

            <div class="grid gap-10 py-14 sm:grid-cols-2 lg:grid-cols-12">
                <div class="lg:col-span-4">
                    <x-logo light />
                    <p class="mt-5 max-w-sm text-sm leading-relaxed">{{ $site['site_description'] }}</p>
                    @if ($socials->isNotEmpty())
                        <div class="mt-6 flex gap-2">
                            @foreach ($socials as $social)
                                <a href="{{ $site[$social] }}" target="_blank" rel="noopener" aria-label="{{ $socialLabels[$social] }}"
                                   class="grid size-10 place-items-center rounded-xl bg-white/5 text-white/70 ring-1 ring-white/10 transition hover:bg-gold-400 hover:text-brand-950"><x-icon :name="$social" class="size-4" /></a>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="lg:col-span-2">
                    <h3 class="text-sm font-bold tracking-wider text-white uppercase">Jelajahi</h3>
                    <ul class="mt-5 space-y-3 text-sm">
                        <li><a href="{{ route('profilemui') }}" class="hover:text-gold-300">Profil MUI</a></li>
                        <li><a href="{{ route('visi-misi') }}" class="hover:text-gold-300">Visi & Misi</a></li>
                        <li><a href="{{ route('struktur-organisasi') }}" class="hover:text-gold-300">Struktur Organisasi</a></li>
                        <li><a href="{{ route('berita.list') }}" class="hover:text-gold-300">Berita & Kegiatan</a></li>
                        <li><a href="{{ route('berita.list', ['kategori' => 'Khutbah']) }}" class="hover:text-gold-300">Khutbah Jumat</a></li>
                        <li><a href="{{ route('kontak') }}" class="hover:text-gold-300">Kontak</a></li>
                    </ul>
                </div>
                <div class="lg:col-span-2">
                    <h3 class="text-sm font-bold tracking-wider text-white uppercase">Layanan Umat</h3>
                    <ul class="mt-5 space-y-3 text-sm">
                        <li><a href="{{ route('fatwa') }}" class="hover:text-gold-300">Arsip Fatwa</a></li>
                        <li><a href="{{ route('surat') }}" class="hover:text-gold-300">Arsip Surat</a></li>
                        <li><a href="{{ route('tanya-ulama') }}" class="hover:text-gold-300">Tanya Ulama</a></li>
                        <li><a href="{{ route('konsultasi.list') }}" class="hover:text-gold-300">Tanya Jawab Umat</a></li>
                        <li><button type="button" x-data @click="$dispatch('open-chat')" class="hover:text-gold-300">Konsultasi Online</button></li>
                        <li><a href="{{ route('search') }}" class="hover:text-gold-300">Pencarian</a></li>
                    </ul>
                </div>
                <div class="sm:col-span-2 lg:col-span-4">
                    <h3 class="text-sm font-bold tracking-wider text-white uppercase">Sekretariat</h3>
                    <ul class="mt-5 space-y-4 text-sm">
                        <li class="flex gap-3"><x-icon name="map-pin" class="mt-0.5 size-4 text-gold-400" /> <span>{{ $site['address'] }}</span></li>
                        @if ($site['phone'])
                            <li class="flex gap-3"><x-icon name="phone" class="mt-0.5 size-4 text-gold-400" /> <a href="tel:{{ preg_replace('/[^0-9+]/', '', $site['phone']) }}" class="hover:text-gold-300">{{ $site['phone'] }}</a></li>
                        @endif
                        @if ($waNumber)
                            <li class="flex gap-3"><x-icon name="whatsapp" class="mt-0.5 size-4 text-gold-400" /> <a href="https://wa.me/{{ $waNumber }}" target="_blank" rel="noopener" class="hover:text-gold-300">{{ $site['whatsapp'] }}</a></li>
                        @endif
                        @if ($site['email'])
                            <li class="flex gap-3"><x-icon name="mail" class="mt-0.5 size-4 text-gold-400" /> <a href="mailto:{{ $site['email'] }}" class="hover:text-gold-300">{{ $site['email'] }}</a></li>
                        @endif
                        <li class="flex gap-3"><x-icon name="clock" class="mt-0.5 size-4 text-gold-400" /> <span>{{ $site['office_hours'] }}</span></li>
                    </ul>
                </div>
            </div>

            <div class="flex flex-col items-center justify-between gap-3 border-t border-white/10 py-6 text-xs text-white/50 sm:flex-row">
                <p>© {{ date('Y') }} {{ $site['site_name'] }} {{ $site['site_region'] }}. Hak cipta dilindungi.</p>
                <p class="flex items-center gap-4">
                    <a href="{{ route('kontak') }}" class="hover:text-gold-300">Kontak</a>
                    <a href="{{ route('login') }}" class="flex items-center gap-1 hover:text-gold-300"><x-icon name="lock" class="size-3" /> Portal Pengurus</a>
                </p>
            </div>
        </div>
    </footer>

    {{-- ============ NAVIGASI BAWAH (MOBILE) ============ --}}
    <nav x-data class="fixed inset-x-0 bottom-0 z-40 border-t border-stone-200 bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur-xl lg:hidden" aria-label="Navigasi cepat">
        <div class="grid h-[64px] grid-cols-5">
            @foreach ([
                ['Beranda', 'house', route('home.public'), 'home.public'],
                ['Fatwa', 'scale', route('fatwa'), ['fatwa', 'surat']],
                null,
                ['Tanya', 'file-pen-line', route('tanya-ulama'), ['tanya-ulama', 'konsultasi.*']],
                ['Kabar', 'newspaper', route('berita.list'), 'berita.*'],
            ] as $tab)
                @if ($tab === null)
                    <div class="relative flex justify-center">
                        <button type="button" @click="$dispatch('open-chat')" class="absolute -top-5 grid size-14 place-items-center rounded-2xl bg-gradient-to-br from-gold-300 to-gold-500 text-brand-950 shadow-[var(--shadow-glow)] ring-4 ring-white" aria-label="Konsultasi online">
                            <x-icon name="messages-square" class="size-6" />
                        </button>
                        <span class="absolute bottom-2 text-[10.5px] font-semibold text-stone-500">Chat</span>
                    </div>
                @else
                    <a href="{{ $tab[2] }}" @class(['flex flex-col items-center justify-center gap-1 text-[10.5px] font-semibold', 'text-brand-700' => $isActive($tab[3]), 'text-stone-500' => !$isActive($tab[3])])>
                        <x-icon :name="$tab[1]" class="size-5" /> {{ $tab[0] }}
                    </a>
                @endif
            @endforeach
        </div>
    </nav>

    {{-- ============ PENCARIAN CEPAT ============ --}}
    <div x-data="searchModal" x-show="open" x-cloak class="fixed inset-0 z-[70] flex items-start justify-center p-4 pt-[12vh]" role="dialog" aria-modal="true" aria-label="Pencarian">
        <div x-show="open" x-transition.opacity class="absolute inset-0 bg-brand-950/60 backdrop-blur-sm" @click="open = false"></div>
        <div x-show="open" x-transition:enter="transition duration-200" x-transition:enter-start="scale-95 opacity-0" class="relative w-full max-w-2xl overflow-hidden rounded-3xl bg-white shadow-2xl">
            <form action="{{ route('search') }}" class="flex items-center gap-3 border-b border-stone-100 px-5" role="search">
                <x-icon name="search" class="size-5 text-brand-600" />
                <input x-ref="q" type="search" name="q" placeholder="Cari berita, fatwa, surat, tanya jawab…" aria-label="Kata kunci pencarian" class="h-16 flex-1 bg-transparent text-base text-ink-900 placeholder:text-stone-400 focus:outline-none">
                <kbd class="rounded-md bg-stone-100 px-2 py-1 text-[10px] font-semibold text-stone-500">ESC</kbd>
            </form>
            <div class="p-5">
                <p class="text-xs font-bold tracking-wider text-stone-400 uppercase">Pencarian populer</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach (['Halal', 'Zakat', 'Khutbah', 'Vaksin', 'Muamalah', 'Wakaf', 'Pernikahan'] as $term)
                        <a href="{{ route('search', ['q' => $term]) }}" class="rounded-full bg-sand-100 px-3.5 py-1.5 text-sm text-stone-700 transition hover:bg-brand-700 hover:text-white">{{ $term }}</a>
                    @endforeach
                </div>
                <div class="mt-6 grid gap-2 sm:grid-cols-3">
                    <a href="{{ route('fatwa') }}" class="flex items-center gap-2 rounded-xl border border-stone-200 p-3 text-sm font-semibold hover:border-brand-400 hover:bg-brand-50"><x-icon name="scale" class="size-4 text-brand-600" /> Arsip Fatwa</a>
                    <a href="{{ route('surat') }}" class="flex items-center gap-2 rounded-xl border border-stone-200 p-3 text-sm font-semibold hover:border-brand-400 hover:bg-brand-50"><x-icon name="file-badge" class="size-4 text-brand-600" /> Arsip Surat</a>
                    <a href="{{ route('konsultasi.list') }}" class="flex items-center gap-2 rounded-xl border border-stone-200 p-3 text-sm font-semibold hover:border-brand-400 hover:bg-brand-50"><x-icon name="circle-help" class="size-4 text-brand-600" /> Tanya Jawab</a>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ LIVE CHAT ============ --}}
    <x-chat-widget />

    {{-- Kembali ke atas --}}
    <button x-data="{ show: false }" x-init="window.addEventListener('scroll', () => show = scrollY > 800, { passive: true })" x-show="show" x-cloak x-transition
            @click="scrollTo({ top: 0 })" class="fixed right-5 bottom-28 z-30 hidden size-11 place-items-center rounded-xl bg-white text-brand-700 shadow-[var(--shadow-lift)] ring-1 ring-stone-200 transition hover:bg-brand-700 hover:text-white lg:grid" aria-label="Kembali ke atas">
        <x-icon name="arrow-up" class="size-5" />
    </button>

    @if (session('success') || session('warning') || session('error'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 7000)" x-show="show" x-transition class="fixed top-24 left-1/2 z-[80] w-[92%] max-w-md -translate-x-1/2" role="status">
            <div class="flex items-start gap-3 rounded-2xl bg-white p-4 shadow-2xl ring-1 ring-stone-200">
                <x-icon name="{{ session('success') ? 'circle-check-big' : 'circle-alert' }}" class="size-5 {{ session('success') ? 'text-brand-600' : (session('error') ? 'text-red-600' : 'text-gold-600') }}" />
                <p class="flex-1 text-sm text-stone-700">{{ session('success') ?? session('warning') ?? session('error') }}</p>
                <button type="button" @click="show = false" class="text-stone-400 hover:text-stone-600" aria-label="Tutup"><x-icon name="x" class="size-4" /></button>
            </div>
        </div>
    @endif

    @stack('scripts')
</body>
</html>
