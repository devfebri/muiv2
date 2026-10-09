@props(['src' => null, 'alt' => '', 'label' => null, 'icon' => 'newspaper', 'eager' => false])

<div {{ $attributes->merge(['class' => 'relative overflow-hidden bg-brand-900']) }}>
    @if ($src)
        <img src="{{ $src }}" alt="{{ $alt }}" @if ($eager) fetchpriority="high" @else loading="lazy" @endif decoding="async"
             class="absolute inset-0 size-full object-cover transition duration-700 group-hover:scale-105">
    @else
        <div class="bg-gradient-brand absolute inset-0"></div>
        <div class="pattern-islamic absolute inset-0 transition duration-700 group-hover:scale-110"></div>
        <div class="absolute -right-10 -bottom-10 size-40 rounded-full bg-gold-400/15 blur-2xl"></div>
        @if ($icon)
            <div class="absolute inset-0 flex flex-col items-center justify-center gap-2 p-4 text-center">
                <span class="grid size-12 place-items-center rounded-2xl bg-white/10 text-gold-300 ring-1 ring-white/15 backdrop-blur">
                    <x-icon :name="$icon" class="size-6" />
                </span>
                @if ($label)
                    <span class="line-clamp-2 max-w-[80%] font-display text-sm text-white/80">{{ $label }}</span>
                @endif
            </div>
        @endif
    @endif
    {{ $slot }}
</div>
