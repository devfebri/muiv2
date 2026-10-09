@php
    $judulHalaman = $kategoriAktif ? 'Kabar: '.$kategoriAktif : 'Berita & Kegiatan';
    $tampilkanUtama = !$search && $beritas->onFirstPage() && $beritas->count() > 2;
    $daftar = $tampilkanUtama ? $beritas->getCollection()->skip(1) : $beritas->getCollection();
@endphp

<x-layouts.site :title="$search ? 'Pencarian: '.$search : $judulHalaman">
    <x-page-hero :title="$judulHalaman" eyebrow="Kabar MUI" :crumbs="$kategoriAktif ? ['Kabar' => route('berita.list'), $kategoriAktif => null] : ['Kabar' => null]"
                 subtitle="Informasi kegiatan, kajian keislaman, khutbah, dan kabar terbaru Majelis Ulama Indonesia Kabupaten Batanghari.">
        <form class="relative mt-8 max-w-xl" role="search">
            @if ($kategoriAktif)<input type="hidden" name="kategori" value="{{ $kategoriAktif }}">@endif
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-4 z-10 size-5 -translate-y-1/2 text-white/60" />
            <input name="q" value="{{ $search }}" type="search" placeholder="Cari judul atau isi berita…" aria-label="Cari berita" class="w-full rounded-2xl border border-white/15 bg-white/10 py-4 pr-28 pl-12 text-white backdrop-blur placeholder:text-white/50 focus:border-gold-400 focus:outline-none">
            <button class="btn btn-gold btn-sm absolute top-1/2 right-2 -translate-y-1/2">Cari</button>
        </form>
    </x-page-hero>

    <div class="container-x mt-10">
        {{-- Tab kategori --}}
        <nav class="scrollbar-none -mx-4 flex gap-2 overflow-x-auto px-4 pb-2 lg:mx-0 lg:flex-wrap lg:overflow-visible lg:px-0" aria-label="Kategori berita">
            <a href="{{ route('berita.list', array_filter(['q' => $search])) }}" @class(['chip shrink-0', 'active' => !$kategoriAktif])>Semua</a>
            @foreach ($kategoriList as $kategori)
                <a href="{{ route('berita.list', array_filter(['kategori' => $kategori, 'q' => $search])) }}" @class(['chip shrink-0', 'active' => $kategoriAktif === $kategori])>
                    {{ $kategori }}
                    @if ($statKategori[$kategori] ?? 0)
                        <span @class(['rounded-full px-1.5 text-[11px]', 'bg-white/20' => $kategoriAktif === $kategori, 'bg-stone-100 text-stone-500' => $kategoriAktif !== $kategori])>{{ $statKategori[$kategori] }}</span>
                    @endif
                </a>
            @endforeach
        </nav>

        <div class="mt-8 grid gap-10 lg:grid-cols-12">
            <div class="lg:col-span-8">
                @if ($search)
                    <p class="mb-6 text-sm text-stone-600">Hasil pencarian untuk <strong class="text-ink-900">“{{ $search }}”</strong> — {{ $beritas->total() }} berita ditemukan.
                        <a href="{{ route('search', ['q' => $search]) }}" class="font-semibold text-brand-700 hover:underline">Cari juga di fatwa & arsip →</a></p>
                @endif

                @if ($beritas->isNotEmpty())
                    @if ($tampilkanUtama)
                        @php $utama = $beritas->first(); @endphp
                        <article class="group card card-hover relative mb-6 grid overflow-hidden md:grid-cols-2">
                            <x-cover :src="$utama->gambar_url" :alt="$utama->judul" :label="$utama->kategori" eager class="aspect-[16/10] md:aspect-auto md:min-h-[300px]">
                                <span class="badge absolute top-4 left-4 bg-gold-400 text-brand-950 shadow-sm">Terbaru</span>
                            </x-cover>
                            <div class="flex flex-col p-6 sm:p-8">
                                <span class="text-[11px] font-bold tracking-wider text-gold-600 uppercase">{{ $utama->kategori }}</span>
                                <h2 class="mt-2 font-display text-2xl leading-snug font-semibold text-ink-900 transition group-hover:text-brand-700">
                                    <a href="{{ route('berita.detail', $utama->slug) }}" class="after:absolute after:inset-0">{{ $utama->judul }}</a>
                                </h2>
                                <p class="mt-3 line-clamp-4 text-sm leading-relaxed text-stone-600">{{ $utama->ringkasan(260) }}</p>
                                <div class="mt-auto flex flex-wrap items-center gap-4 pt-6 text-xs text-stone-500">
                                    <span class="flex items-center gap-1.5"><x-icon name="calendar-days" class="size-3.5 text-gold-500" /> {{ $utama->tanggal_terbit?->translatedFormat('d F Y') }}</span>
                                    <span class="flex items-center gap-1.5"><x-icon name="clock" class="size-3.5" /> {{ $utama->waktuBaca() }} menit baca</span>
                                    <span class="flex items-center gap-1.5"><x-icon name="eye" class="size-3.5" /> {{ number_format($utama->views ?? 0, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </article>
                    @endif

                    <div class="grid gap-6 sm:grid-cols-2">
                        @foreach ($daftar as $berita)
                            <x-post-card :berita="$berita" />
                        @endforeach
                    </div>
                    <div class="mt-10">{{ $beritas->links() }}</div>
                @else
                    <x-empty-state icon="newspaper" title="Berita tidak ditemukan" message="Coba ubah kata kunci atau pilih kategori lainnya.">
                        <a href="{{ route('berita.list') }}" class="btn btn-outline btn-sm mt-5">Lihat semua berita</a>
                    </x-empty-state>
                @endif
            </div>

            <aside class="space-y-6 lg:col-span-4">
                <div class="card p-6">
                    <h2 class="flex items-center gap-2 font-bold text-ink-900"><x-icon name="list-filter" class="size-4 text-gold-500" /> Kategori</h2>
                    <ul class="mt-4 space-y-1">
                        <li>
                            <a href="{{ route('berita.list') }}" @class(['flex items-center justify-between rounded-xl px-3 py-2.5 text-sm transition', 'bg-brand-50 font-semibold text-brand-800' => !$kategoriAktif, 'text-stone-600 hover:bg-stone-50' => $kategoriAktif])>
                                Semua kategori <span class="rounded-full bg-stone-100 px-2 text-xs text-stone-500">{{ collect($statKategori)->sum() }}</span>
                            </a>
                        </li>
                        @foreach ($kategoriList as $kategori)
                            <li>
                                <a href="{{ route('berita.list', ['kategori' => $kategori]) }}" @class(['flex items-center justify-between rounded-xl px-3 py-2.5 text-sm transition', 'bg-brand-50 font-semibold text-brand-800' => $kategoriAktif === $kategori, 'text-stone-600 hover:bg-stone-50' => $kategoriAktif !== $kategori])>
                                    <span class="flex items-center gap-2"><span class="size-2 rounded-full bg-gold-400"></span> {{ $kategori }}</span>
                                    <span class="rounded-full bg-stone-100 px-2 text-xs text-stone-500">{{ $statKategori[$kategori] ?? 0 }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="card p-6">
                    <h2 class="flex items-center gap-2 font-bold text-ink-900"><x-icon name="trending-up" class="size-4 text-gold-500" /> Terpopuler</h2>
                    <ol class="mt-5 space-y-5">
                        @foreach ($terpopuler as $populer)
                            <li class="flex gap-4">
                                <span class="font-display text-3xl leading-none font-bold text-gold-400/70">{{ $loop->iteration }}</span>
                                <a href="{{ route('berita.detail', $populer->slug) }}" class="group">
                                    <p class="line-clamp-2 text-sm font-semibold text-ink-900 group-hover:text-brand-700">{{ $populer->judul }}</p>
                                    <p class="mt-1 text-xs text-stone-500">{{ number_format($populer->views ?? 0, 0, ',', '.') }} kali dibaca</p>
                                </a>
                            </li>
                        @endforeach
                    </ol>
                </div>

                <div class="bg-gradient-brand relative overflow-hidden rounded-2xl p-6 text-white">
                    <div class="pattern-islamic absolute inset-0"></div>
                    <div class="relative">
                        <x-icon name="file-pen-line" class="size-8 text-gold-300" />
                        <h3 class="mt-3 font-display text-xl font-semibold text-white">Punya pertanyaan keagamaan?</h3>
                        <p class="mt-2 text-sm text-white/70">Ajukan pertanyaan kepada ulama MUI Kabupaten Batanghari dan dapatkan jawaban yang bertanggung jawab.</p>
                        <a href="{{ route('tanya-ulama') }}" class="btn btn-gold btn-sm mt-5">Tanya Ulama</a>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</x-layouts.site>
