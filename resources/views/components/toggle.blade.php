@props(['name', 'checked' => false, 'label', 'description' => null])

<label {{ $attributes->merge(['class' => 'flex cursor-pointer items-start justify-between gap-4 rounded-xl border border-stone-200 bg-white p-4 transition hover:border-brand-300']) }}>
    <span>
        <span class="block text-sm font-semibold text-ink-900">{{ $label }}</span>
        @if ($description)<span class="mt-0.5 block text-xs text-stone-500">{{ $description }}</span>@endif
    </span>
    <span class="relative inline-flex shrink-0">
        <input type="hidden" name="{{ $name }}" value="0">
        <input type="checkbox" name="{{ $name }}" value="1" class="peer sr-only" @checked(old($name, $checked))>
        <span class="h-6 w-11 rounded-full bg-stone-300 transition peer-checked:bg-brand-600 peer-focus-visible:ring-4 peer-focus-visible:ring-brand-500/20"></span>
        <span class="absolute top-0.5 left-0.5 size-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
    </span>
</label>
