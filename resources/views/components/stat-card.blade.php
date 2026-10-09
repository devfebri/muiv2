@props(['label', 'value' => '–', 'icon', 'tone' => 'brand', 'note' => null, 'href' => null, 'bind' => null, 'bindNote' => null])

@php
    $tones = [
        'brand' => 'bg-brand-50 text-brand-700 ring-brand-100',
        'gold' => 'bg-gold-50 text-gold-700 ring-gold-100',
        'blue' => 'bg-sky-50 text-sky-700 ring-sky-100',
        'red' => 'bg-red-50 text-red-600 ring-red-100',
        'purple' => 'bg-violet-50 text-violet-700 ring-violet-100',
        'stone' => 'bg-stone-100 text-stone-600 ring-stone-200',
    ];
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'card group flex items-start justify-between gap-4 p-5 transition hover:shadow-[var(--shadow-lift)]']) }}>
    <div class="min-w-0">
        <p class="text-[13px] font-medium text-stone-500">{{ $label }}</p>
        <p class="mt-2 text-3xl font-bold tracking-tight text-ink-900 tabular-nums" @if ($bind) x-text="{{ $bind }}" @endif>{{ $value }}</p>
        @if ($note || $bindNote)<p class="mt-1.5 text-xs text-stone-500" @if ($bindNote) x-text="{{ $bindNote }}" @endif>{{ $note }}</p>@endif
    </div>
    <span class="grid size-12 shrink-0 place-items-center rounded-2xl ring-1 {{ $tones[$tone] ?? $tones['brand'] }}"><x-icon :name="$icon" class="size-5" /></span>
</{{ $tag }}>
