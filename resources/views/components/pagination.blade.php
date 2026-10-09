@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman" class="flex flex-col items-center justify-between gap-4 sm:flex-row">
        <p class="text-sm text-stone-500">
            Menampilkan <span class="font-semibold text-stone-800">{{ $paginator->firstItem() }}</span>–<span class="font-semibold text-stone-800">{{ $paginator->lastItem() }}</span>
            dari <span class="font-semibold text-stone-800">{{ $paginator->total() }}</span> data
        </p>
        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="grid size-10 place-items-center rounded-xl text-stone-300"><x-icon name="chevron-left" class="size-4" /></span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="grid size-10 place-items-center rounded-xl border border-stone-200 bg-white text-stone-600 transition hover:border-brand-500 hover:text-brand-700" aria-label="Sebelumnya"><x-icon name="chevron-left" class="size-4" /></a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="hidden px-2 text-stone-400 sm:inline">…</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="grid size-10 place-items-center rounded-xl bg-brand-700 text-sm font-bold text-white shadow-sm">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="hidden size-10 place-items-center rounded-xl border border-stone-200 bg-white text-sm font-semibold text-stone-600 transition hover:border-brand-500 hover:text-brand-700 sm:grid">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="grid size-10 place-items-center rounded-xl border border-stone-200 bg-white text-stone-600 transition hover:border-brand-500 hover:text-brand-700" aria-label="Berikutnya"><x-icon name="chevron-right" class="size-4" /></a>
            @else
                <span class="grid size-10 place-items-center rounded-xl text-stone-300"><x-icon name="chevron-right" class="size-4" /></span>
            @endif
        </div>
    </nav>
@endif
