<x-layouts.site :title="$tahunAktif ? 'Arsip Surat Tahun '.$tahunAktif : 'Arsip Surat Resmi'" description="Arsip surat resmi, edaran, dan keputusan Majelis Ulama Indonesia Kabupaten Batanghari.">
    <x-page-hero title="Arsip Surat Resmi MUI" eyebrow="Dokumen Resmi" :crumbs="$tahunAktif ? ['Arsip Surat' => route('surat'), 'Tahun '.$tahunAktif => null] : ['Arsip Surat' => null]"
                 subtitle="Surat edaran, undangan, rekomendasi, dan keputusan Dewan Pimpinan MUI Kabupaten Batanghari yang terbuka untuk umat.">
        <form class="relative mt-8 max-w-3xl" role="search">
            @if ($tahunAktif)<input type="hidden" name="tahun" value="{{ $tahunAktif }}">@endif
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-4 z-10 size-5 -translate-y-1/2 text-white/60" />
            <input name="q" value="{{ $search }}" type="search" placeholder="Cari nomor surat atau perihal…" aria-label="Cari surat" class="w-full rounded-2xl border border-white/15 bg-white/10 py-4 pr-28 pl-12 text-white backdrop-blur placeholder:text-white/50 focus:border-gold-400 focus:outline-none">
            <button class="btn btn-gold btn-sm absolute top-1/2 right-2 -translate-y-1/2">Cari</button>
        </form>
        <div class="mt-6 flex flex-wrap gap-2 text-sm">
            <span class="rounded-full bg-white/10 px-3.5 py-1.5 text-white/80 ring-1 ring-white/15"><b class="text-white">{{ $totalSemua }}</b> surat terarsip</span>
            <span class="rounded-full bg-white/10 px-3.5 py-1.5 text-white/80 ring-1 ring-white/15"><b class="text-gold-300">{{ $totalTahunIni }}</b> surat tahun {{ date('Y') }}</span>
        </div>
    </x-page-hero>

    <div class="container-x mt-10" x-data="{ doc: null, loaded: false, open(d) { this.loaded = false; this.doc = d; }, close() { this.doc = null; } }" @keydown.escape.window="close()">
        {{-- Filter tahun --}}
        <nav class="scrollbar-none -mx-4 flex gap-2 overflow-x-auto px-4 pb-2" aria-label="Filter tahun">
            <a href="{{ route('surat', array_filter(['q' => $search])) }}" @class(['chip shrink-0', 'active' => blank($tahunAktif)])>Semua tahun</a>
            @foreach ($tahunList as $tahun)
                <a href="{{ route('surat', array_filter(['tahun' => $tahun, 'q' => $search])) }}" @class(['chip shrink-0', 'active' => (string) $tahunAktif === (string) $tahun])>{{ $tahun }}</a>
            @endforeach
        </nav>

        <div class="mt-6 mb-5 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-stone-600"><strong class="text-ink-900">{{ $surats->total() }}</strong> surat ditemukan{{ $search ? ' untuk “'.$search.'”' : '' }}</p>
            <p class="flex items-center gap-1.5 text-xs text-stone-500"><x-icon name="shield-check" class="size-4 text-brand-600" /> Salinan resmi Sekretariat MUI</p>
        </div>

        <div class="space-y-4">
            @forelse ($surats as $surat)
                <article class="group card card-hover flex flex-col gap-5 p-5 sm:flex-row sm:items-center sm:p-6">
                    <div class="flex shrink-0 items-center gap-4 sm:w-24 sm:flex-col sm:gap-1 sm:border-r sm:border-stone-100 sm:pr-6">
                        <span class="font-display text-3xl leading-none font-bold text-brand-700">{{ $surat->tanggal_surat?->format('d') }}</span>
                        <span class="text-[11px] font-bold tracking-wider text-stone-400 uppercase">{{ $surat->tanggal_surat?->translatedFormat('M Y') }}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="flex flex-wrap items-center gap-2">
                            <span class="badge badge-gold"><x-icon name="file-badge" class="size-3" /> Surat Resmi</span>
                            @if ($surat->nomor_surat)
                                <span class="font-mono text-xs text-stone-500">No. {{ $surat->nomor_surat }}</span>
                            @endif
                        </p>
                        <h2 class="mt-2 leading-snug font-semibold text-ink-900 sm:text-lg">{{ $surat->perihal }}</h2>
                        <p class="mt-2 flex items-center gap-1.5 text-xs text-stone-500"><x-icon name="calendar-days" class="size-3.5" /> {{ $surat->tanggal_surat?->translatedFormat('l, d F Y') }}</p>
                    </div>
                    @if ($surat->file_url)
                        <div class="flex shrink-0 gap-2">
                            <button type="button" @click="open(@js(['url' => $surat->file_url, 'title' => $surat->perihal, 'nomor' => $surat->nomor_surat]))" class="btn btn-primary btn-sm"><x-icon name="eye" class="size-4" /> Lihat Surat</button>
                            <a href="{{ $surat->file_url }}" download class="btn btn-outline btn-sm" aria-label="Unduh {{ $surat->perihal }}"><x-icon name="download" class="size-4" /></a>
                        </div>
                    @endif
                </article>
            @empty
                <x-empty-state icon="file-search" title="Surat tidak ditemukan" message="Coba ubah kata kunci atau pilih tahun lainnya." />
            @endforelse
        </div>
        <div class="mt-10">{{ $surats->links() }}</div>

        {{-- Pratinjau dokumen --}}
        <template x-teleport="body">
            <div x-show="doc" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center p-0 sm:p-6" role="dialog" aria-modal="true" :aria-label="doc?.title">
                <div x-show="doc" x-transition.opacity class="absolute inset-0 bg-brand-950/70 backdrop-blur-sm" @click="close()"></div>
                <div x-show="doc" x-transition:enter="transition duration-200" x-transition:enter-start="scale-95 opacity-0" class="relative flex h-full w-full max-w-5xl flex-col overflow-hidden bg-white shadow-2xl sm:h-[90vh] sm:rounded-3xl">
                    <header class="flex items-center gap-3 border-b border-stone-100 bg-sand-100/60 px-4 py-3 sm:px-5">
                        <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-red-50 text-red-600 ring-1 ring-red-100"><x-icon name="file-text" class="size-5" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-ink-900" x-text="doc?.title"></p>
                            <p class="truncate font-mono text-[11px] text-stone-500" x-show="doc?.nomor" x-text="'No. ' + doc?.nomor"></p>
                        </div>
                        <a :href="doc?.url" target="_blank" rel="noopener" class="btn btn-ghost btn-sm"><x-icon name="external-link" class="size-4" /><span class="hidden sm:inline">Buka</span></a>
                        <a :href="doc?.url" download class="btn btn-primary btn-sm"><x-icon name="download" class="size-4" /><span class="hidden sm:inline">Unduh</span></a>
                        <button type="button" @click="close()" class="grid size-9 place-items-center rounded-xl text-stone-500 hover:bg-stone-100" aria-label="Tutup"><x-icon name="x" class="size-5" /></button>
                    </header>
                    <div class="relative flex-1 bg-stone-100">
                        <div x-show="!loaded" class="absolute inset-0 grid place-items-center text-sm text-stone-500">
                            <span class="flex items-center gap-2"><x-icon name="loader-circle" class="size-5 animate-spin text-brand-600" /> Memuat dokumen…</span>
                        </div>
                        <template x-if="doc">
                            <iframe :src="doc.url + '#view=FitH'" :title="doc.title" class="relative size-full" @load="loaded = true"></iframe>
                        </template>
                    </div>
                    <p class="border-t border-stone-100 px-5 py-3 text-xs text-stone-500 sm:hidden">Pratinjau PDF mungkin tidak tampil di sebagian peramban ponsel. Gunakan tombol <b>Buka</b> atau <b>Unduh</b>.</p>
                </div>
            </div>
        </template>
    </div>
</x-layouts.site>
