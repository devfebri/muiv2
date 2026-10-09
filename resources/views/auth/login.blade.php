<x-layouts.auth title="Masuk">
    <p class="eyebrow">Portal Pengurus</p>
    <h1 class="mt-3 font-display text-3xl font-semibold">Assalamu’alaikum,<br>silakan masuk</h1>
    <p class="mt-3 text-sm text-stone-500">Akses panel pengelolaan berita, fatwa, arsip surat, dan layanan umat {{ $site['site_short'] }}.</p>

    @if (session('status') || session('success'))
        <div class="mt-6 flex items-start gap-3 rounded-xl bg-brand-50 p-4 text-sm text-brand-800 ring-1 ring-brand-200" role="status">
            <x-icon name="circle-check-big" class="mt-0.5 size-4" /> {{ session('status') ?? session('success') }}
        </div>
    @endif
    @if (session('error') || session('warning'))
        <div class="mt-6 flex items-start gap-3 rounded-xl bg-gold-50 p-4 text-sm text-gold-900 ring-1 ring-gold-200" role="alert">
            <x-icon name="circle-alert" class="mt-0.5 size-4" /> {{ session('error') ?? session('warning') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5" x-data="{ show: false, loading: false }" @submit="loading = true">
        @csrf
        <div>
            <label for="username" class="label">Username atau email</label>
            <div class="relative">
                <x-icon name="user-round" class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-stone-400" />
                <input id="username" name="username" type="text" value="{{ old('username') }}" required autofocus autocomplete="username" placeholder="mis. admin"
                       class="input pl-10 @error('username') input-error @enderror">
            </div>
            @error('username')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
        </div>
        <div>
            <div class="flex items-center justify-between">
                <label for="password" class="label">Kata sandi</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="mb-1.5 text-xs font-semibold text-brand-700 hover:text-brand-900">Lupa kata sandi?</a>
                @endif
            </div>
            <div class="relative">
                <x-icon name="key-round" class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-stone-400" />
                <input id="password" name="password" :type="show ? 'text' : 'password'" type="password" required autocomplete="current-password" placeholder="••••••••"
                       class="input px-10 @error('password') input-error @enderror">
                <button type="button" @click="show = !show" class="absolute top-1/2 right-3 -translate-y-1/2 text-stone-400 hover:text-stone-600" :aria-label="show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'">
                    <x-icon name="eye" class="size-4" x-show="!show" /><x-icon name="eye-off" class="size-4" x-show="show" x-cloak />
                </button>
            </div>
            @error('password')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
        </div>
        <label class="flex items-center gap-2 text-sm text-stone-600">
            <input type="checkbox" name="remember" class="checkbox" @checked(old('remember'))> Ingat saya di perangkat ini
        </label>
        <button class="btn btn-primary btn-lg w-full" :disabled="loading">
            <x-icon name="loader-circle" class="size-4 animate-spin" x-show="loading" x-cloak />
            <x-icon name="log-in" class="size-4" x-show="!loading" /> Masuk ke Panel
        </button>
    </form>

    <p class="mt-8 flex items-start gap-2 text-xs leading-relaxed text-stone-400">
        <x-icon name="shield-check" class="size-4 shrink-0 text-brand-600" />
        Halaman ini khusus pengurus MUI. Sesi berakhir otomatis setelah {{ config('session.lifetime') }} menit tidak aktif.
    </p>
    <a href="{{ route('home.public') }}" class="mt-6 inline-flex items-center gap-1 text-sm font-semibold text-brand-700 hover:text-brand-900"><x-icon name="arrow-left" class="size-4" /> Kembali ke situs</a>
</x-layouts.auth>
