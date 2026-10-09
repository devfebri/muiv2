@props(['berita', 'horizontal' => false])

@if ($horizontal)
    <a href="{{ route('berita.detail', $berita->slug) }}" class="group flex gap-4">
        <x-cover :src="$berita->gambar_url" :alt="$berita->judul" class="aspect-square w-24 shrink-0 rounded-xl sm:w-28" />
        <div class="min-w-0">
            <span class="text-[11px] font-bold tracking-wider text-gold-600 uppercase">{{ $berita->kategori }}</span>
            <h3 class="mt-1 line-clamp-2 leading-snug font-semibold text-ink-900 transition group-hover:text-brand-700">{{ $berita->judul }}</h3>
            <p class="mt-1.5 flex items-center gap-1.5 text-xs text-stone-500">
                <x-icon name="calendar-days" class="size-3.5" /> {{ $berita->tanggal_terbit?->translatedFormat('d M Y') }}
            </p>
        </div>
    </a>
@else
    <article class="group card card-hover relative flex h-full flex-col overflow-hidden">
        <x-cover :src="$berita->gambar_url" :alt="$berita->judul" :label="$berita->kategori" class="aspect-[16/10]">
            @if ($berita->kategori)
                <span class="badge absolute top-3 left-3 bg-white/95 text-brand-800 shadow-sm backdrop-blur">{{ $berita->kategori }}</span>
            @endif
        </x-cover>
        <div class="flex flex-1 flex-col p-5 sm:p-6">
            <div class="flex items-center gap-3 text-xs text-stone-500">
                <time datetime="{{ $berita->tanggal_terbit?->toDateString() }}" class="flex items-center gap-1.5">
                    <x-icon name="calendar-days" class="size-3.5 text-gold-500" /> {{ $berita->tanggal_terbit?->translatedFormat('d M Y') }}
                </time>
                <span class="size-1 rounded-full bg-stone-300"></span>
                <span class="flex items-center gap-1.5"><x-icon name="eye" class="size-3.5" /> {{ number_format($berita->views ?? 0, 0, ',', '.') }}</span>
            </div>
            <h3 class="mt-2.5 line-clamp-2 font-display text-[19px] leading-snug font-semibold text-ink-900 transition group-hover:text-brand-700">
                <a href="{{ route('berita.detail', $berita->slug) }}" class="after:absolute after:inset-0">{{ $berita->judul }}</a>
            </h3>
            <p class="mt-2.5 line-clamp-3 text-sm leading-relaxed text-stone-600">{{ $berita->ringkasan(150) }}</p>
            <div class="mt-auto flex items-center justify-between pt-5 text-xs text-stone-500">
                <span class="flex items-center gap-1.5"><x-icon name="clock" class="size-3.5" /> {{ $berita->waktuBaca() }} menit baca</span>
                <span class="flex items-center gap-1 font-semibold text-brand-700 transition-all group-hover:gap-2">Baca <x-icon name="arrow-right" class="size-3.5" /></span>
            </div>
        </div>
    </article>
@endif
