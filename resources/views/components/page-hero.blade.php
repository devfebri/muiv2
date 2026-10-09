@props(['title', 'subtitle' => null, 'eyebrow' => null, 'crumbs' => []])

<section class="bg-gradient-brand relative overflow-hidden">
    <div class="pattern-islamic absolute inset-0"></div>
    <div class="absolute -top-24 -right-24 size-80 rounded-full bg-gold-400/20 blur-3xl"></div>
    <div class="absolute -bottom-32 left-1/3 size-80 rounded-full bg-brand-400/20 blur-3xl"></div>
    <div class="container-x relative pt-32 pb-14 sm:pt-40 sm:pb-20">
        <nav aria-label="Breadcrumb" class="mb-5 flex flex-wrap items-center gap-1.5 text-[13px] text-white/60">
            <a href="{{ route('home.public') }}" class="flex items-center gap-1 hover:text-gold-300"><x-icon name="house" class="size-3.5" /> Beranda</a>
            @foreach ($crumbs as $label => $url)
                <x-icon name="chevron-right" class="size-3.5 text-white/30" />
                @if ($url)
                    <a href="{{ $url }}" class="hover:text-gold-300">{{ $label }}</a>
                @else
                    <span class="text-white/90">{{ $label }}</span>
                @endif
            @endforeach
        </nav>
        @if ($eyebrow)
            <p class="eyebrow text-gold-300!">{{ $eyebrow }}</p>
        @endif
        <h1 class="mt-3 max-w-4xl font-display text-3xl leading-tight font-semibold text-balance text-white sm:text-5xl">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-4 max-w-2xl text-base leading-relaxed text-white/75 sm:text-lg">{{ $subtitle }}</p>
        @endif
        {{ $slot }}
    </div>
    <svg class="absolute -bottom-px left-0 w-full text-sand-50" viewBox="0 0 1440 40" preserveAspectRatio="none" fill="currentColor" aria-hidden="true"><path d="M0 40h1440V18C1200 38 960 0 720 10S240 38 0 14Z"/></svg>
</section>
