{{-- Tata letak sederhana untuk halaman bawaan autentikasi lama; mengikuti desain portal pengurus. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $site['site_short'] }}</title>
    <link rel="icon" type="image/png" href="{{ $site['logo_url'] }}">
    <script>document.documentElement.classList.add('js')</script>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-sand-50">
    <header class="bg-gradient-brand relative overflow-hidden">
        <div class="pattern-islamic absolute inset-0"></div>
        <div class="container-x relative flex h-[72px] items-center justify-between">
            <a href="{{ url('/') }}"><x-logo light /></a>
            @auth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-glass btn-sm"><x-icon name="log-out" class="size-4" /> Keluar</button>
                </form>
            @endauth
        </div>
    </header>
    <main class="container-x py-12">
        <div class="mx-auto max-w-2xl">
            @yield('content')
        </div>
    </main>
</body>
</html>
