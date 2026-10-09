@props(['title' => 'Masuk'])
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#0c402f">
    <title>{{ $title }} — Portal Pengurus {{ $site['site_short'] }}</title>
    <link rel="icon" type="image/png" href="{{ $site['logo_url'] }}">
    <script>document.documentElement.classList.add('js')</script>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-sand-50">
    <div class="grid min-h-screen lg:grid-cols-2">
        <div class="bg-gradient-brand relative hidden overflow-hidden lg:flex lg:flex-col lg:justify-between lg:p-12">
            <div class="pattern-islamic absolute inset-0"></div>
            <svg class="animate-spin-slow absolute -right-40 -bottom-40 size-[560px] text-gold-300/10" viewBox="0 0 200 200" fill="none" stroke="currentColor" stroke-width=".6" aria-hidden="true">
                @for ($r = 0; $r < 8; $r++)<rect x="50" y="50" width="100" height="100" transform="rotate({{ $r * 11.25 }} 100 100)"/>@endfor
            </svg>
            <a href="{{ route('home.public') }}" class="relative self-start"><x-logo light /></a>
            <div class="relative">
                <p class="arabic text-4xl leading-relaxed text-gold-300">إِنَّ اللّٰهَ يَأْمُرُكُمْ أَنْ تُؤَدُّوا الْأَمٰنٰتِ إِلٰٓى أَهْلِهَا</p>
                <p class="mt-4 max-w-md font-display text-2xl text-white italic">“Sesungguhnya Allah menyuruh kamu menyampaikan amanat kepada yang berhak menerimanya.”</p>
                <p class="mt-2 text-sm tracking-wider text-white/50 uppercase">QS. An-Nisā’ [4]: 58</p>
            </div>
            <div class="relative flex flex-wrap items-center gap-6 text-xs text-white/60">
                <span class="flex items-center gap-1.5"><x-icon name="shield-check" class="size-4 text-gold-400" /> Akses khusus pengurus</span>
                <span class="flex items-center gap-1.5"><x-icon name="user-cog" class="size-4 text-gold-400" /> Hak akses berbasis peran</span>
                <span class="flex items-center gap-1.5"><x-icon name="timer" class="size-4 text-gold-400" /> Sesi berakhir otomatis</span>
            </div>
        </div>
        <main class="flex items-center justify-center p-6 sm:p-12">
            <div class="w-full max-w-md">
                <a href="{{ route('home.public') }}" class="mb-10 inline-block lg:hidden"><x-logo /></a>
                {{ $slot }}
            </div>
        </main>
    </div>
</body>
</html>
