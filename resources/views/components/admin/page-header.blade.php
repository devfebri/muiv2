@props(['title', 'eyebrow' => null, 'description' => null])

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-end justify-between gap-4']) }}>
    <div class="min-w-0">
        @if ($eyebrow)<p class="eyebrow">{{ $eyebrow }}</p>@endif
        <h2 class="mt-2 font-display text-2xl leading-tight font-semibold text-ink-900 sm:text-3xl">{{ $title }}</h2>
        @if ($description)<p class="mt-1.5 max-w-2xl text-sm text-stone-500">{{ $description }}</p>@endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
