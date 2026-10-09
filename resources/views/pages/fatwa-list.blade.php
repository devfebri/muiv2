@php
    $kategoriTerpilih = $kategoriList->first(fn ($k) => (string) $kategoriAktif === (string) $k->slug || (string) $kategoriAktif === (string) $k->id);
    $adaFilter = filled($kategoriAktif) || filled($statusFatwaAktif) || filled($search);
@endphp

<x-layouts.site :title="$kategoriTerpilih ? 'Fatwa: '.$kategoriTerpilih->nama : 'Arsip Fatwa MUI'" description="Arsip resmi fatwa Majelis Ulama Indonesia yang dapat dibaca dan diunduh oleh umat.">
    <x-page-hero title="Arsip Fatwa MUI" eyebrow="Dokumen Resmi" :crumbs="$kategoriTerpilih ? ['Fatwa' => route('fatwa'), $kategoriTerpilih->nama => null] : ['Fatwa' => null]"
                 subtitle="Telusuri keputusan Komisi Fatwa Majelis Ulama Indonesia. Baca langsung melalui penampil dokumen atau unduh salinan PDF-nya.">
        <form class="mt-8 max-w-3xl" role="search">
            <div class="relative">
                <x-icon name="search" class="pointer-events-none absolute top-1/2 left-4 z-10 size-5 -translate-y-1/2 text-white/60" />
                <input name="q" value="{{ $search }}" type="search" placeholder="Cari nomor, judul, atau kata kunci fatwa…" aria-label="Cari fatwa" class="w-full rounded-2xl border border-white/15 bg-white/10 py-4 pr-28 pl-12 text-white backdrop-blur placeholder:text-white/50 focus:border-gold-400 focus:outline-none">
                <button class="btn btn-gold btn-sm absolute top-1/2 right-2 -translate-y-1/2">Cari</button>
            </div>
            @foreach (array_filter(['kategori' => $kategoriAktif, 'status' => $statusFatwaAktif]) as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
        </form>
        <div class="mt-6 flex flex-wrap gap-2 text-sm">
            <span class="rounded-full bg-white/10 px-3.5 py-1.5 text-white/80 ring-1 ring-white/15"><b class="text-white">{{ $totalSemua }}</b> fatwa terbit</span>
            @foreach (\App\Models\Fatwa::STATUSES as $key => $label)
                <span class="rounded-full bg-white/10 px-3.5 py-1.5 text-white/80 ring-1 ring-white/15"><b class="text-gold-300">{{ $statStatus[$key] ?? 0 }}</b> {{ strtolower($label) }}</span>
            @endforeach
        </div>
    </x-page-hero>

    <div class="container-x mt-10 grid gap-10 lg:grid-cols-12">
        {{-- Filter --}}
        <aside class="lg:col-span-4 xl:col-span-3" x-data="{ open: false }">
            <button type="button" @click="open = !open" :aria-expanded="open" class="btn btn-outline w-full lg:hidden"><x-icon name="list-filter" class="size-4" /> Filter fatwa</button>
            <form x-ref="f" class="card mt-3 space-y-6 p-6 lg:sticky lg:top-24 lg:mt-0 lg:block" :class="open ? 'block' : 'hidden'">
                @if ($search)<input type="hidden" name="q" value="{{ $search }}">@endif
                <div>
                    <p class="text-[11px] font-bold tracking-[.2em] text-stone-400 uppercase">Kategori</p>
                    <div class="mt-3 space-y-1">
                        <label class="flex cursor-pointer items-center justify-between rounded-xl px-3 py-2 text-sm hover:bg-stone-50 has-checked:bg-brand-50 has-checked:font-semibold has-checked:text-brand-800">
                            <span class="flex items-center gap-2.5"><input type="radio" name="kategori" value="" class="accent-brand-600" @checked(blank($kategoriAktif)) @change="$refs.f.submit()"> Semua kategori</span>
                            <span class="text-xs text-stone-400">{{ $totalSemua }}</span>
                        </label>
                        @foreach ($kategoriList as $kategori)
                            <label class="flex cursor-pointer items-center justify-between gap-2 rounded-xl px-3 py-2 text-sm hover:bg-stone-50 has-checked:bg-brand-50 has-checked:font-semibold has-checked:text-brand-800">
                                <span class="flex items-center gap-2.5"><input type="radio" name="kategori" value="{{ $kategori->slug }}" class="accent-brand-600" @checked($kategoriTerpilih?->is($kategori)) @change="$refs.f.submit()"> {{ $kategori->nama }}</span>
                                <span class="text-xs text-stone-400">{{ $kategori->fatwas_count }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label class="text-[11px] font-bold tracking-[.2em] text-stone-400 uppercase" for="f-status">Status fatwa</label>
                    <select id="f-status" name="status" class="input mt-3" @change="$refs.f.submit()">
                        <option value="">Semua status</option>
                        @foreach (\App\Models\Fatwa::STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected($statusFatwaAktif === $key)>{{ $label }} ({{ $statStatus[$key] ?? 0 }})</option>
                        @endforeach
                    </select>
                </div>
                <noscript><button class="btn btn-primary w-full">Terapkan filter</button></noscript>
                @if ($adaFilter)
                    <a href="{{ route('fatwa') }}" class="flex items-center justify-center gap-1.5 text-sm font-semibold text-red-600 hover:text-red-700"><x-icon name="x" class="size-4" /> Reset semua filter</a>
                @endif
            </form>
        </aside>

        {{-- Daftar --}}
        <div class="lg:col-span-8 xl:col-span-9">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-stone-600"><strong class="text-ink-900">{{ $fatwas->total() }}</strong> fatwa ditemukan{{ $search ? ' untuk “'.$search.'”' : '' }}</p>
                <p class="flex items-center gap-1.5 text-xs text-stone-500"><x-icon name="shield-check" class="size-4 text-brand-600" /> Dokumen resmi Majelis Ulama Indonesia</p>
            </div>

            <div class="space-y-4">
                @forelse ($fatwas as $fatwa)
                    <article class="group card card-hover relative flex flex-col gap-5 p-5 sm:flex-row sm:p-6">
                        <div class="flex shrink-0 items-center gap-4 sm:w-24 sm:flex-col sm:items-center sm:gap-2 sm:border-r sm:border-stone-100 sm:pr-6">
                            <span @class([
                                'grid size-14 place-items-center rounded-2xl',
                                'bg-brand-700 text-gold-300' => $fatwa->status_fatwa === 'aktif',
                                'bg-gold-100 text-gold-700' => $fatwa->status_fatwa === 'direvisi',
                                'bg-stone-100 text-stone-500' => $fatwa->status_fatwa === 'digantikan',
                            ])>
                                <x-icon name="scale" class="size-6" />
                            </span>
                            <div class="text-left sm:text-center">
                                <p class="text-[11px] font-bold tracking-wider text-stone-400 uppercase">{{ $fatwa->created_at?->translatedFormat('M') }}</p>
                                <p class="font-display text-xl leading-none font-bold text-ink-900">{{ $fatwa->created_at?->format('Y') }}</p>
                            </div>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($fatwa->kategori)
                                    <span class="badge badge-green">{{ $fatwa->kategori->nama }}</span>
                                @endif
                                <x-fatwa-status :status="$fatwa->status_fatwa ?? 'aktif'" />
                            </div>
                            <h2 class="mt-3 font-display text-lg leading-snug font-semibold text-ink-900 group-hover:text-brand-700 sm:text-xl">
                                <a href="{{ route('fatwa.detail', $fatwa) }}" class="after:absolute after:inset-0">{{ $fatwa->judul }}</a>
                            </h2>
                            @if ($fatwa->keterangan)
                                <p class="mt-2 line-clamp-2 text-sm leading-relaxed text-stone-600">{{ $fatwa->keterangan }}</p>
                            @endif
                            <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-stone-500">
                                <span class="flex items-center gap-1.5"><x-icon name="calendar-days" class="size-3.5" /> {{ $fatwa->created_at?->translatedFormat('d F Y') }}</span>
                                <span class="flex items-center gap-1.5"><x-icon name="eye" class="size-3.5" /> {{ number_format($fatwa->views ?? 0, 0, ',', '.') }} dibaca</span>
                                <span class="flex items-center gap-1.5 font-semibold text-brand-700"><x-icon name="book-open" class="size-3.5" /> Baca Fatwa</span>
                                @if ($fatwa->file_url)
                                    <a href="{{ $fatwa->file_url }}" download class="relative z-10 flex items-center gap-1.5 font-semibold text-brand-700 hover:text-brand-900"><x-icon name="file-down" class="size-3.5" /> Unduh PDF</a>
                                @endif
                            </div>
                        </div>
                        <x-icon name="arrow-up-right" class="absolute top-6 right-6 hidden size-5 text-stone-300 transition group-hover:text-brand-600 sm:block" />
                    </article>
                @empty
                    <x-empty-state icon="file-search" title="Fatwa tidak ditemukan" message="Coba gunakan kata kunci lain atau reset filter pencarian.">
                        <a href="{{ route('tanya-ulama') }}" class="btn btn-primary btn-sm mt-5">Ajukan Pertanyaan ke Ulama</a>
                    </x-empty-state>
                @endforelse
            </div>
            <div class="mt-10">{{ $fatwas->links() }}</div>
        </div>
    </div>
</x-layouts.site>
