@php $logo = $site['logo_url'] ?? asset('gambar/mui.png'); @endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#0c402f">
    <title>@yield('code') — @yield('title') · {{ $site['site_short'] ?? 'MUI Batanghari' }}</title>
    <link rel="icon" type="image/png" href="{{ $logo }}">
    @fonts
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gradient-brand relative grid min-h-screen place-items-center overflow-hidden p-6 text-center text-white">
    <div class="pattern-islamic absolute inset-0"></div>
    <div class="absolute -top-32 -right-32 size-96 rounded-full bg-gold-400/15 blur-3xl"></div>
    <div class="absolute -bottom-32 -left-32 size-96 rounded-full bg-brand-400/20 blur-3xl"></div>
    <main class="relative max-w-lg">
        <a href="{{ url('/') }}" class="inline-block" aria-label="Beranda">
            <img src="{{ $logo }}" alt="Logo Majelis Ulama Indonesia" class="mx-auto size-20 rounded-full bg-white object-contain p-1 shadow-2xl ring-4 ring-gold-300/60">
        </a>
        <p class="mt-8 font-display text-8xl font-bold text-gradient-gold">@yield('code')</p>
        <h1 class="mt-4 font-display text-3xl font-semibold text-white">@yield('title')</h1>
        <p class="mt-3 leading-relaxed text-white/70">@yield('message')</p>
        @yield('extra')
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ url('/') }}" class="btn btn-gold">Kembali ke Beranda</a>
            <a href="javascript:history.back()" class="btn btn-glass">Halaman sebelumnya</a>
        </div>
        <p class="arabic mt-12 text-xl text-gold-300/80">إِنَّ مَعَ الْعُسْرِ يُسْرًا</p>
        <p class="mt-1 text-xs text-white/40">“Sesungguhnya bersama kesulitan ada kemudahan.” — QS. Al-Insyirah [94]: 6</p>
    </main>
</body>
</html>
