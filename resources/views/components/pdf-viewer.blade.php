@props(['url', 'title' => 'Dokumen PDF', 'download' => null, 'downloadName' => null])

<section x-data="{ full: false, loaded: false }" @keydown.escape.window="full = false"
         {{ $attributes->merge(['class' => 'card overflow-hidden']) }} :class="full && 'fixed! inset-0 z-[90] rounded-none!'">
    <header class="flex items-center gap-2 border-b border-stone-100 bg-sand-100/60 px-4 py-3 sm:gap-3 sm:px-5">
        <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-red-50 text-red-600 ring-1 ring-red-100"><x-icon name="file-text" class="size-5" /></span>
        <p class="min-w-0 flex-1 truncate text-sm font-semibold text-ink-900" title="{{ $title }}">{{ $title }}</p>
        <a href="{{ $url }}" target="_blank" rel="noopener" class="btn btn-ghost btn-sm" title="Buka di tab baru"><x-icon name="external-link" class="size-4" /><span class="hidden sm:inline">Buka</span></a>
        <a href="{{ $download ?? $url }}" download="{{ $downloadName }}" class="btn btn-primary btn-sm"><x-icon name="download" class="size-4" /><span class="hidden sm:inline">Unduh</span></a>
        <button type="button" @click="full = !full" class="btn btn-ghost btn-sm hidden sm:inline-flex" :aria-label="full ? 'Keluar dari layar penuh' : 'Tampilkan layar penuh'">
            <x-icon name="maximize-2" class="size-4" x-show="!full" /><x-icon name="minimize-2" class="size-4" x-show="full" x-cloak />
        </button>
    </header>
    <div class="relative bg-stone-100" :class="full ? 'h-[calc(100vh-61px)]' : 'h-[75vh] min-h-[480px]'">
        <div x-show="!loaded" class="absolute inset-0 grid place-items-center text-sm text-stone-500">
            <span class="flex items-center gap-2"><x-icon name="loader-circle" class="size-5 animate-spin text-brand-600" /> Memuat dokumen…</span>
        </div>
        <iframe src="{{ $url }}#view=FitH" title="{{ $title }}" class="relative size-full" @load="loaded = true"></iframe>
    </div>
    <p class="border-t border-stone-100 px-5 py-3 text-xs leading-relaxed text-stone-500 sm:hidden">Pratinjau PDF mungkin tidak tampil di sebagian peramban ponsel. Gunakan tombol <b>Buka</b> atau <b>Unduh</b>.</p>
</section>
