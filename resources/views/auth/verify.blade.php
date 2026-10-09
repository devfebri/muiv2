<x-layouts.auth title="Verifikasi Email">
    <p class="eyebrow">Verifikasi</p>
    <h1 class="mt-3 font-display text-3xl font-semibold">Periksa email Anda</h1>
    <p class="mt-3 text-sm leading-relaxed text-stone-500">Sebelum melanjutkan, silakan buka tautan verifikasi yang telah kami kirimkan ke alamat email Anda.</p>

    @if (session('resent'))
        <div class="mt-6 flex items-start gap-3 rounded-xl bg-brand-50 p-4 text-sm text-brand-800 ring-1 ring-brand-200" role="status">
            <x-icon name="mail-check" class="mt-0.5 size-4" /> Tautan verifikasi baru telah dikirim ke email Anda.
        </div>
    @endif

    <form method="POST" action="{{ route('verification.resend') }}" class="mt-8">
        @csrf
        <p class="text-sm text-stone-600">Belum menerima email?</p>
        <button type="submit" class="btn btn-outline mt-3"><x-icon name="refresh-cw" class="size-4" /> Kirim ulang tautan verifikasi</button>
    </form>
</x-layouts.auth>
