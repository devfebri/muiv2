@props(['icon' => 'inbox', 'title' => 'Belum ada data', 'message' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-2xl border border-dashed border-stone-300 bg-white/60 px-6 py-14 text-center']) }}>
    <span class="grid size-14 place-items-center rounded-2xl bg-brand-50 text-brand-600 ring-8 ring-brand-50/50">
        <x-icon :name="$icon" class="size-7" />
    </span>
    <h3 class="mt-5 font-semibold text-ink-900">{{ $title }}</h3>
    @if ($message)
        <p class="mt-1.5 max-w-sm text-sm text-stone-500">{{ $message }}</p>
    @endif
    {{ $slot }}
</div>
