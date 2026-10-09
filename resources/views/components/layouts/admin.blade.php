@props(['title' => 'Dashboard', 'header' => null])
@php
    $user = auth()->user();
    $isAdmin = $user->isAdmin();
    $p = $isAdmin ? 'admin' : 'operator';
    // [label, ikon, route, pola aktif, izin (null = semua, 'admin' = khusus admin), kunci hitungan notifikasi]
    $menu = [
        ['Utama', [
            ['Dashboard', 'layout-dashboard', "{$p}.dashboard", "{$p}.dashboard", null, null],
        ]],
        ['Layanan Umat', [
            ['Live Chat', 'messages-square', 'admin.livechat.index', 'admin.livechat.*', 'livechat', 'chat_count'],
            ['Tanya Ulama', 'message-circle-question', "{$p}.konsultasi.index", "{$p}.konsultasi.*", 'konsultasi', 'konsultasi_count'],
        ]],
        ['Arsip Digital', [
            ['Arsip Surat', 'folder-archive', "{$p}.surat.index", "{$p}.surat.*", 'surat', null],
            ['Fatwa MUI', 'scale', "{$p}.fatwa.index", "{$p}.fatwa.*", 'fatwa', null],
            ['Kategori Fatwa', 'tags', "{$p}.kategori-fatwa.index", "{$p}.kategori-fatwa.*", 'kategori-fatwa', null],
        ]],
        ['Konten Website', [
            ['Berita & Artikel', 'newspaper', "{$p}.berita.index", "{$p}.berita.*", 'berita', null],
            ['Kategori Berita', 'tag', "{$p}.kategori.index", "{$p}.kategori.*", 'kategori', null],
        ]],
        ['Sistem', [
            ['Pengguna', 'users', 'admin.users.index', 'admin.users.*', 'admin', null],
            ['Hak Akses Operator', 'shield-check', 'admin.operator-permissions.index', 'admin.operator-permissions.*', 'admin', null],
            ['Pengaturan Situs', 'settings', 'admin.pengaturan.index', 'admin.pengaturan.*', 'admin', null],
            ['Profil Akun', 'circle-user-round', 'profile.edit', 'profile.*', null, null],
        ]],
    ];
    $canSee = fn (?string $perm) => $perm === null || ($perm === 'admin' ? $isAdmin : $user->hasMenuPermission($perm));
    $toasts = collect(['success' => 'success', 'status' => 'success', 'info' => 'info', 'warning' => 'warning', 'error' => 'error'])
        ->filter(fn ($type, $key) => session()->has($key) && is_string(session($key)))
        ->map(fn ($type, $key) => ['type' => $type, 'message' => session($key)])
        ->values();
    $initials = collect(preg_split('/\s+/', trim($user->name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#0c402f">
    <title>{{ $title }} — Panel {{ $site['site_short'] }}</title>
    <link rel="icon" type="image/png" href="{{ $site['logo_url'] }}">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/admin.js'])
    @stack('head')
</head>
<body class="bg-stone-100/70" x-data="adminShell(@js(route('notifications.poll')))">
    <a href="#konten" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[100] focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:shadow">Lewati ke konten</a>

    {{-- Sidebar --}}
    <div x-show="sidebar" x-cloak x-transition.opacity class="fixed inset-0 z-40 bg-brand-950/60 backdrop-blur-sm lg:hidden" @click="sidebar = false"></div>
    <aside class="bg-gradient-brand fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col transition-all duration-300 lg:translate-x-0"
           :class="{ 'translate-x-0': sidebar, 'lg:w-20': collapsed }" aria-label="Menu panel">
        <div class="pattern-islamic pointer-events-none absolute inset-0"></div>
        <div class="relative flex h-[72px] shrink-0 items-center justify-between border-b border-white/10 px-5">
            <a href="{{ route($p.'.dashboard') }}" class="flex items-center gap-3 overflow-hidden">
                <img src="{{ $site['logo_url'] }}" alt="Logo MUI" class="size-10 shrink-0 rounded-full bg-white object-contain p-0.5 ring-2 ring-gold-300/60">
                <span class="leading-tight whitespace-nowrap" x-show="!collapsed">
                    <span class="block font-display text-base font-semibold text-white">Panel {{ $isAdmin ? 'Admin' : 'Operator' }}</span>
                    <span class="block text-[10px] font-bold tracking-[.2em] text-gold-300 uppercase">{{ $site['site_short'] }}</span>
                </span>
            </a>
            <button type="button" @click="sidebar = false" class="text-white/70 lg:hidden" aria-label="Tutup menu"><x-icon name="x" class="size-5" /></button>
        </div>

        <nav class="scrollbar-none relative flex-1 space-y-6 overflow-y-auto px-3 py-5">
            @foreach ($menu as [$section, $items])
                @php $visible = collect($items)->filter(fn ($i) => $canSee($i[4])); @endphp
                @if ($visible->isNotEmpty())
                    <div>
                        <p class="mb-2 px-3 text-[10px] font-bold tracking-[.2em] whitespace-nowrap text-white/40 uppercase" x-show="!collapsed">{{ $section }}</p>
                        <div class="h-px bg-white/10" x-show="collapsed" x-cloak></div>
                        <ul class="space-y-0.5">
                            @foreach ($visible as $item)
                                @php $active = request()->routeIs($item[3]); @endphp
                                <li>
                                    <a href="{{ route($item[2]) }}" title="{{ $item[0] }}" @if ($active) aria-current="page" @endif @class([
                                        'group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13.5px] font-medium whitespace-nowrap transition',
                                        'bg-white/12 text-white shadow-inner ring-1 ring-white/10' => $active,
                                        'text-white/65 hover:bg-white/5 hover:text-white' => !$active,
                                    ])>
                                        @if ($active)<span class="absolute top-2 bottom-2 -left-3 w-1 rounded-r-full bg-gold-400"></span>@endif
                                        <x-icon :name="$item[1]" :class="$active ? 'size-[18px] text-gold-300' : 'size-[18px]'" />
                                        <span x-show="!collapsed" class="flex-1">{{ $item[0] }}</span>
                                        @if ($item[5])
                                            <span x-show="notif.{{ $item[5] }} > 0" x-text="notif.{{ $item[5] }}" x-cloak
                                                  class="grid min-w-5 place-items-center rounded-full bg-gold-400 px-1.5 text-[10px] font-bold text-brand-950"
                                                  :class="collapsed && 'absolute top-1 right-1'"></span>
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @endforeach
        </nav>

        <div class="relative shrink-0 border-t border-white/10 p-3">
            <a href="{{ route('home.public') }}" target="_blank" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13px] whitespace-nowrap text-white/65 hover:bg-white/5 hover:text-white">
                <x-icon name="external-link" class="size-[18px]" /> <span x-show="!collapsed">Lihat Website</span>
            </a>
            <button type="button" @click="collapsed = !collapsed" class="hidden w-full items-center gap-3 rounded-xl px-3 py-2.5 text-[13px] whitespace-nowrap text-white/65 hover:bg-white/5 hover:text-white lg:flex">
                <x-icon name="panel-left-close" class="size-[18px] transition" ::class="collapsed && 'rotate-180'" /> <span x-show="!collapsed">Ciutkan menu</span>
            </button>
        </div>
    </aside>

    {{-- Konten --}}
    <div class="transition-all duration-300 lg:pl-72" :class="collapsed && 'lg:pl-20!'">
        <header class="sticky top-0 z-30 flex h-[72px] items-center gap-3 border-b border-stone-200 bg-white/85 px-4 backdrop-blur-xl sm:px-6 lg:px-8">
            <button type="button" @click="sidebar = true" class="grid size-10 place-items-center rounded-xl text-stone-600 hover:bg-stone-100 lg:hidden" aria-label="Buka menu"><x-icon name="menu" class="size-5" /></button>
            <div class="min-w-0 flex-1">
                <h1 class="truncate text-lg font-bold text-ink-900">{{ $title }}</h1>
                @if ($header)<p class="hidden truncate text-xs text-stone-500 sm:block">{{ $header }}</p>@endif
            </div>

            <span x-data="clock" x-text="text" class="hidden rounded-full bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-800 ring-1 ring-brand-100 xl:block"></span>

            @if ($user->hasMenuPermission('livechat'))
                <a href="{{ route('admin.livechat.index') }}" class="relative grid size-10 place-items-center rounded-xl text-stone-600 hover:bg-stone-100" title="Live chat" aria-label="Live chat">
                    <x-icon name="messages-square" class="size-5" />
                    <span x-show="notif.chat_count > 0" x-cloak class="absolute top-1.5 right-1.5 size-2.5 rounded-full bg-red-500 ring-2 ring-white"></span>
                </a>
            @endif

            {{-- Notifikasi --}}
            <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
                <button type="button" @click="open = !open" :aria-expanded="open" class="relative grid size-10 place-items-center rounded-xl text-stone-600 hover:bg-stone-100" aria-label="Notifikasi">
                    <x-icon name="bell" class="size-5" />
                    <span x-show="notif.total > 0" x-cloak x-text="notif.total" class="absolute top-1 right-0.5 grid min-w-4 place-items-center rounded-full bg-red-500 px-1 text-[9px] font-bold text-white ring-2 ring-white"></span>
                </button>
                <div x-show="open" x-cloak x-transition class="absolute right-0 mt-2 w-80 overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-[var(--shadow-lift)] sm:w-96">
                    <div class="flex items-center justify-between border-b border-stone-100 px-4 py-3">
                        <p class="text-sm font-bold text-ink-900">Notifikasi</p>
                        <span class="badge badge-gold" x-text="notif.total + ' baru'"></span>
                    </div>
                    <ul class="scrollbar-thin max-h-96 divide-y divide-stone-100 overflow-y-auto">
                        <template x-for="item in notif.items" :key="item.id">
                            <li>
                                <a :href="item.url" class="flex gap-3 px-4 py-3 hover:bg-brand-50/50">
                                    <span class="grid size-9 shrink-0 place-items-center rounded-xl" :class="item.type === 'livechat' ? 'bg-red-50 text-red-600' : 'bg-gold-50 text-gold-700'">
                                        <x-icon name="messages-square" class="size-4" x-show="item.type === 'livechat'" />
                                        <x-icon name="message-circle-question" class="size-4" x-show="item.type !== 'livechat'" />
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="flex items-center justify-between gap-2">
                                            <span class="truncate text-sm font-semibold text-ink-900" x-text="item.title"></span>
                                            <span class="shrink-0 text-[10px] text-stone-400" x-text="item.time"></span>
                                        </span>
                                        <span class="block truncate text-xs font-medium text-stone-600" x-text="item.sender"></span>
                                        <span class="line-clamp-2 block text-xs text-stone-500" x-text="item.message"></span>
                                    </span>
                                </a>
                            </li>
                        </template>
                        <li x-show="!notif.items.length" class="px-4 py-10 text-center text-sm text-stone-500">
                            <x-icon name="bell-off" class="mx-auto size-6 text-stone-300" />
                            <p class="mt-2">Tidak ada notifikasi baru.</p>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Menu pengguna --}}
            <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
                <button type="button" @click="open = !open" :aria-expanded="open" class="flex items-center gap-3 rounded-xl py-1.5 pr-2 pl-1.5 hover:bg-stone-100" aria-label="Menu akun">
                    @if ($user->foto_url)
                        <img src="{{ $user->foto_url }}" alt="" class="size-9 rounded-lg object-cover">
                    @else
                        <span class="grid size-9 place-items-center rounded-lg bg-brand-700 text-xs font-bold text-gold-300">{{ $initials }}</span>
                    @endif
                    <span class="hidden text-left leading-tight md:block">
                        <span class="block max-w-40 truncate text-sm font-semibold text-ink-900">{{ $user->name }}</span>
                        <span class="block text-[11px] text-stone-500">{{ $isAdmin ? 'Administrator' : 'Operator' }}</span>
                    </span>
                    <x-icon name="chevron-down" class="hidden size-4 text-stone-400 md:block" />
                </button>
                <div x-show="open" x-cloak x-transition class="absolute right-0 mt-2 w-60 overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-[var(--shadow-lift)]">
                    <div class="border-b border-stone-100 px-4 py-3">
                        <p class="truncate text-sm font-semibold text-ink-900">{{ $user->email }}</p>
                        <p class="mt-0.5 flex items-center gap-1 text-xs text-brand-700"><x-icon name="shield-check" class="size-3.5" /> {{ $isAdmin ? 'Akses penuh administrator' : 'Akses operator' }}</p>
                    </div>
                    <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-stone-700 hover:bg-stone-50"><x-icon name="circle-user-round" class="size-4" /> Profil Akun</a>
                    <a href="{{ route('home.public') }}" target="_blank" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-stone-700 hover:bg-stone-50"><x-icon name="external-link" class="size-4" /> Lihat Website</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="flex w-full items-center gap-2.5 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50"><x-icon name="log-out" class="size-4" /> Keluar</button>
                    </form>
                </div>
            </div>
        </header>

        <main id="konten" class="p-4 sm:p-6 lg:p-8">
            {{ $slot }}
        </main>

        <footer class="px-4 pb-6 text-center text-xs text-stone-400 sm:px-8">
            © {{ date('Y') }} {{ $site['site_short'] }} · Sistem Informasi Majelis Ulama Indonesia Kabupaten Batanghari
        </footer>
    </div>

    {{-- Toast --}}
    <div x-data="toasts({{ Js::from($toasts) }})" class="pointer-events-none fixed right-4 bottom-4 z-[95] flex w-[calc(100%-2rem)] max-w-sm flex-col gap-2" aria-live="polite">
        <template x-for="t in items" :key="t.id">
            <div x-transition class="pointer-events-auto flex items-start gap-3 rounded-2xl bg-white p-4 shadow-2xl ring-1 ring-stone-200">
                <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full"
                      :class="{ 'bg-brand-100 text-brand-700': t.type === 'success', 'bg-gold-100 text-gold-700': t.type === 'warning' || t.type === 'info', 'bg-red-100 text-red-700': t.type === 'error' }">
                    <x-icon name="check" class="size-3.5" x-show="t.type === 'success'" />
                    <x-icon name="info" class="size-3.5" x-show="t.type !== 'success'" />
                </span>
                <p class="flex-1 text-sm text-stone-700" x-text="t.message"></p>
                <button type="button" @click="remove(t.id)" class="text-stone-400 hover:text-stone-600" aria-label="Tutup"><x-icon name="x" class="size-4" /></button>
            </div>
        </template>
    </div>

    {{-- Dialog konfirmasi --}}
    <div x-data="confirmDialog" x-show="open" x-cloak class="fixed inset-0 z-[100] grid place-items-center p-4" @keydown.escape.window="open && cancel()" role="alertdialog" aria-modal="true">
        <div x-show="open" x-transition.opacity class="absolute inset-0 bg-brand-950/50 backdrop-blur-sm" @click="cancel()"></div>
        <div x-show="open" x-transition class="relative w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl">
            <span class="grid size-12 place-items-center rounded-2xl" :class="tone === 'danger' ? 'bg-red-50 text-red-600' : 'bg-brand-50 text-brand-700'"><x-icon name="triangle-alert" class="size-6" /></span>
            <h2 class="mt-4 text-lg font-bold text-ink-900" x-text="title"></h2>
            <p class="mt-2 text-sm leading-relaxed text-stone-600" x-text="message"></p>
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" @click="cancel()" class="btn btn-outline btn-sm">Batal</button>
                <button type="button" x-ref="confirm" @click="proceed()" class="btn btn-sm" :class="tone === 'danger' ? 'btn-danger' : 'btn-primary'" x-text="confirmText"></button>
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
