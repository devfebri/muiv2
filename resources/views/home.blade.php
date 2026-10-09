<x-layouts.admin title="Dashboard">
    <div class="card mx-auto max-w-xl p-8 text-center">
        <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-brand-50 text-brand-700 ring-1 ring-brand-100"><x-icon name="circle-check-big" class="size-7" /></span>
        <h2 class="mt-5 font-display text-2xl font-semibold text-ink-900">Anda sudah masuk</h2>
        @if (session('status'))
            <p class="mt-2 text-sm text-brand-700">{{ session('status') }}</p>
        @endif
        <a href="{{ route('dashboard') }}" class="btn btn-primary mt-6"><x-icon name="layout-dashboard" class="size-4" /> Buka Dashboard</a>
    </div>
</x-layouts.admin>
