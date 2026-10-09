<x-layouts.auth title="Konfirmasi Kata Sandi">
    <p class="eyebrow">Keamanan</p>
    <h1 class="mt-3 font-display text-3xl font-semibold">Konfirmasi kata sandi</h1>
    <p class="mt-3 text-sm text-stone-500">Demi keamanan, masukkan kembali kata sandi Anda sebelum melanjutkan.</p>

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-8 space-y-5" x-data="{ show: false, loading: false }" @submit="loading = true">
        @csrf
        <div>
            <label for="password" class="label">Kata sandi</label>
            <div class="relative">
                <x-icon name="key-round" class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-stone-400" />
                <input id="password" name="password" :type="show ? 'text' : 'password'" type="password" required autofocus autocomplete="current-password" class="input px-10 @error('password') input-error @enderror">
                <button type="button" @click="show = !show" class="absolute top-1/2 right-3 -translate-y-1/2 text-stone-400 hover:text-stone-600" :aria-label="show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'">
                    <x-icon name="eye" class="size-4" x-show="!show" /><x-icon name="eye-off" class="size-4" x-show="show" x-cloak />
                </button>
            </div>
            @error('password')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
        </div>
        <button class="btn btn-primary btn-lg w-full" :disabled="loading">
            <x-icon name="loader-circle" class="size-4 animate-spin" x-show="loading" x-cloak /> Konfirmasi
        </button>
    </form>

    @if (Route::has('password.request'))
        <a href="{{ route('password.request') }}" class="mt-8 inline-flex items-center gap-1 text-sm font-semibold text-brand-700 hover:text-brand-900">Lupa kata sandi?</a>
    @endif
</x-layouts.auth>
