<x-layouts.auth title="Lupa Kata Sandi">
    <p class="eyebrow">Pemulihan Akun</p>
    <h1 class="mt-3 font-display text-3xl font-semibold">Lupa kata sandi?</h1>
    <p class="mt-3 text-sm text-stone-500">Masukkan alamat email akun Anda. Kami akan mengirimkan tautan untuk membuat kata sandi baru.</p>

    @if (session('status'))
        <div class="mt-6 flex items-start gap-3 rounded-xl bg-brand-50 p-4 text-sm text-brand-800 ring-1 ring-brand-200" role="status">
            <x-icon name="mail-check" class="mt-0.5 size-4" /> {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-5" x-data="{ loading: false }" @submit="loading = true">
        @csrf
        <div>
            <label for="email" class="label">Alamat email</label>
            <div class="relative">
                <x-icon name="mail" class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-stone-400" />
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="input pl-10 @error('email') input-error @enderror">
            </div>
            @error('email')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
        </div>
        <button class="btn btn-primary btn-lg w-full" :disabled="loading">
            <x-icon name="loader-circle" class="size-4 animate-spin" x-show="loading" x-cloak />
            <x-icon name="send" class="size-4" x-show="!loading" /> Kirim Tautan Reset
        </button>
    </form>

    <a href="{{ route('login') }}" class="mt-8 inline-flex items-center gap-1 text-sm font-semibold text-brand-700 hover:text-brand-900"><x-icon name="arrow-left" class="size-4" /> Kembali ke halaman masuk</a>
</x-layouts.auth>
