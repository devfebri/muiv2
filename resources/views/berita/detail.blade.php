@php
    $penulis = $berita->penulis?->name ?? 'Redaksi MUI Batanghari';
    $inisial = collect(preg_split('/\s+/', trim($penulis)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    $isiPolos = strip_tags((string) $berita->isi) === (string) $berita->isi;
@endphp

<x-layouts.site :title="$berita->judul" :description="$berita->ringkasan(200)" :og-image="$berita->gambar_url" og-type="article">
    @push('head')
        <script type="application/ld+json">
            {!! json_encode(array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'NewsArticle',
                'headline' => $berita->judul,
                'datePublished' => $berita->tanggal_terbit?->toIso8601String(),
                'dateModified' => $berita->updated_at?->toIso8601String(),
                'image' => $berita->gambar_url,
                'articleSection' => $berita->kategori,
                'author' => ['@type' => 'Person', 'name' => $penulis],
                'publisher' => ['@type' => 'Organization', 'name' => $site['site_short'], 'logo' => ['@type' => 'ImageObject', 'url' => $site['logo_url']]],
            ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    @endpush

    {{-- Progres membaca --}}
    <div x-data="readingProgress" class="fixed inset-x-0 top-0 z-[55] h-1" aria-hidden="true">
        <div class="h-full bg-gradient-to-r from-gold-300 to-gold-500 transition-[width] duration-150" :style="`width:${progress}%`"></div>
    </div>

    <x-page-hero :title="$berita->judul" :eyebrow="$berita->kategori" :crumbs="array_filter(['Kabar' => route('berita.list'), $berita->kategori => $berita->kategori ? route('berita.list', ['kategori' => $berita->kategori]) : null]) + [Str::limit($berita->judul, 40) => null]">
        <div class="mt-6 flex flex-wrap items-center gap-x-6 gap-y-3 text-sm text-white/70">
            <span class="flex items-center gap-2">
                <span class="grid size-9 place-items-center rounded-full bg-gold-400 text-xs font-bold text-brand-950">{{ $inisial ?: 'MUI' }}</span>
                {{ $penulis }}
            </span>
            <span class="flex items-center gap-1.5"><x-icon name="calendar-days" class="size-4 text-gold-400" /> {{ $berita->tanggal_terbit?->translatedFormat('l, d F Y · H:i') }} WIB</span>
            <span class="flex items-center gap-1.5"><x-icon name="clock" class="size-4 text-gold-400" /> {{ $berita->waktuBaca() }} menit baca</span>
            <span class="flex items-center gap-1.5"><x-icon name="eye" class="size-4 text-gold-400" /> {{ number_format($berita->views ?? 0, 0, ',', '.') }} kali dilihat</span>
        </div>
    </x-page-hero>

    <div class="container-x mt-10 grid gap-12 lg:grid-cols-12">
        <article class="min-w-0 lg:col-span-8">
            @if ($berita->gambar_url)
                <figure class="-mt-2 overflow-hidden rounded-3xl shadow-[var(--shadow-lift)]">
                    <img src="{{ $berita->gambar_url }}" alt="{{ $berita->judul }}" fetchpriority="high" class="aspect-[3/2] w-full object-cover">
                </figure>
            @endif

            <div class="prose-mui mt-8">
                @if ($isiPolos)
                    <p>{!! nl2br(e($berita->isi)) !!}</p>
                @else
                    {!! $berita->isi !!}
                @endif
            </div>

            @if ($berita->kategori)
                <div class="mt-10 flex flex-wrap items-center gap-2">
                    <x-icon name="tag" class="size-4 text-stone-400" />
                    <a href="{{ route('berita.list', ['kategori' => $berita->kategori]) }}" class="rounded-full bg-sand-100 px-3 py-1 text-xs font-semibold text-stone-600 hover:bg-brand-700 hover:text-white">#{{ Str::slug($berita->kategori, '') }}</a>
                </div>
            @endif

            {{-- Bagikan --}}
            <div x-data="share(@js($berita->judul), @js(route('berita.detail', $berita->slug)))" class="mt-10 flex flex-wrap items-center gap-3 rounded-2xl border border-stone-200 bg-white p-5">
                <p class="mr-auto text-sm font-semibold text-ink-900">Bagikan kebaikan ini</p>
                <a :href="link('whatsapp')" target="_blank" rel="noopener" class="grid size-10 place-items-center rounded-xl bg-[#25D366]/10 text-[#128C4B] hover:bg-[#25D366] hover:text-white" aria-label="Bagikan ke WhatsApp"><x-icon name="whatsapp" class="size-4" /></a>
                <a :href="link('facebook')" target="_blank" rel="noopener" class="grid size-10 place-items-center rounded-xl bg-[#1877F2]/10 text-[#1877F2] hover:bg-[#1877F2] hover:text-white" aria-label="Bagikan ke Facebook"><x-icon name="facebook" class="size-4" /></a>
                <a :href="link('x')" target="_blank" rel="noopener" class="grid size-10 place-items-center rounded-xl bg-stone-100 text-stone-800 hover:bg-stone-900 hover:text-white" aria-label="Bagikan ke X"><x-icon name="x-twitter" class="size-4" /></a>
                <a :href="link('telegram')" target="_blank" rel="noopener" class="grid size-10 place-items-center rounded-xl bg-sky-100 text-sky-600 hover:bg-sky-500 hover:text-white" aria-label="Bagikan ke Telegram"><x-icon name="send" class="size-4" /></a>
                <button type="button" @click="copy()" class="flex h-10 items-center gap-2 rounded-xl bg-stone-100 px-4 text-sm font-semibold text-stone-700 hover:bg-stone-200">
                    <x-icon name="link" class="size-4" /> <span x-text="copied ? 'Tersalin!' : 'Salin tautan'">Salin tautan</span>
                </button>
            </div>

            <a href="{{ route('berita.list') }}" class="mt-8 inline-flex items-center gap-2 text-sm font-semibold text-brand-700 hover:text-brand-900"><x-icon name="arrow-left" class="size-4" /> Kembali ke daftar berita</a>
        </article>

        <aside class="lg:col-span-4">
            <div class="sticky top-24 space-y-6">
                <div class="card p-6">
                    <h2 class="flex items-center gap-2 font-bold text-ink-900"><x-icon name="trending-up" class="size-4 text-gold-500" /> Terpopuler</h2>
                    <div class="mt-5 space-y-5">
                        @foreach ($terpopuler as $item)
                            <x-post-card :berita="$item" horizontal />
                        @endforeach
                    </div>
                </div>
                <div class="bg-gradient-brand relative overflow-hidden rounded-2xl p-6 text-white">
                    <div class="pattern-islamic absolute inset-0"></div>
                    <div class="relative">
                        <x-icon name="file-pen-line" class="size-8 text-gold-300" />
                        <h3 class="mt-3 font-display text-xl font-semibold text-white">Butuh jawaban ulama?</h3>
                        <p class="mt-2 text-sm text-white/70">Ajukan pertanyaan keagamaan kepada Komisi Fatwa MUI Kabupaten Batanghari.</p>
                        <a href="{{ route('tanya-ulama') }}" class="btn btn-gold btn-sm mt-5">Ajukan Pertanyaan</a>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    @if ($beritaTerkait->isNotEmpty())
        <section class="container-x mt-20">
            <p class="eyebrow">{{ $berita->kategori }}</p>
            <h2 class="section-title mt-3">Baca juga</h2>
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($beritaTerkait as $item)
                    <x-post-card :berita="$item" />
                @endforeach
            </div>
        </section>
    @endif
</x-layouts.site>
