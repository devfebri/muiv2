<x-layouts.auth title="Atur Ulang Kata Sandi">
    <p class="eyebrow">Pemulihan Akun</p>
    <h1 class="mt-3 font-display text-3xl font-semibold">Buat kata sandi baru</h1>
    <p class="mt-3 text-sm text-stone-500">Gunakan minimal 8 karakter dengan kombinasi huruf dan angka agar akun tetap aman.</p>

    <form method="POST" action="{{ route('password.update') }}" class="mt-8 space-y-5" x-data="{ show: false, loading: false }" @submit="loading = true">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div>
            <label for="email" class="label">Alamat email</label>
            <div class="relative">
                <x-icon name="mail" class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-stone-400" />
                <input id="email" name="email" type="email" value="{{ $email ?? old('email') }}" required autocomplete="email" class="input pl-10 @error('email') input-error @enderror">
            </div>
            @error('email')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password" class="label">Kata sandi baru</label>
            <div class="relative">
                <x-icon name="key-round" class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-stone-400" />
                <input id="password" name="password" :type="show ? 'text' : 'password'" type="password" required autofocus autocomplete="new-password" class="input px-10 @error('password') input-error @enderror">
                <button type="button" @click="show = !show" class="absolute top-1/2 right-3 -translate-y-1/2 text-stone-400 hover:text-stone-600" :aria-label="show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'">
                    <x-icon name="eye" class="size-4" x-show="!show" /><x-icon name="eye-off" class="size-4" x-show="show" x-cloak />
                </button>
            </div>
            @error('password')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password-confirm" class="label">Ulangi kata sandi baru</label>
            <div class="relative">
                <x-icon name="key-round" class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-stone-400" />
                <input id="password-confirm" name="password_confirmation" :type="show ? 'text' : 'password'" type="password" required autocomplete="new-password" class="input pl-10">
            </div>
        </div>
        <button class="btn btn-primary btn-lg w-full" :disabled="loading">
            <x-icon name="loader-circle" class="size-4 animate-spin" x-show="loading" x-cloak />
            <x-icon name="save" class="size-4" x-show="!loading" /> Simpan Kata Sandi
        </button>
    </form>
</x-layouts.auth>
