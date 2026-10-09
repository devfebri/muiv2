{{--
    Tabel admin server-side. Wajib berada di dalam x-data="serverTable({...})".
    Slot: $head (isi <tr> header), $row (SATU elemen <tr> untuk setiap baris; variabel Alpine: row, index),
          $filters (opsional, kontrol filter tambahan), $actions (opsional, tombol di kanan toolbar).
--}}
@props(['colspan' => 6, 'empty' => 'Belum ada data', 'emptyIcon' => 'inbox', 'searchPlaceholder' => 'Cari…'])

<div {{ $attributes->merge(['class' => 'card overflow-hidden']) }}>
    <div class="flex flex-wrap items-center gap-3 border-b border-stone-100 p-4">
        <div class="relative min-w-52 flex-1">
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-stone-400" />
            <input type="search" x-model="search" placeholder="{{ $searchPlaceholder }}" aria-label="Cari data" class="input pl-10">
        </div>
        {{ $filters ?? '' }}
        <select x-model.number="perPage" class="input w-auto" aria-label="Jumlah per halaman">
            <option value="10">10 / hal</option>
            <option value="25">25 / hal</option>
            <option value="50">50 / hal</option>
        </select>
        {{ $actions ?? '' }}
    </div>

    <div class="relative overflow-x-auto">
        <div x-show="loading && rows.length" x-cloak class="absolute inset-x-0 top-0 h-0.5 overflow-hidden bg-brand-100">
            <div class="h-full w-1/3 animate-[shimmer_1.2s_linear_infinite] bg-brand-500"></div>
        </div>
        <table class="table-clean">
            <thead><tr>{{ $head }}</tr></thead>
            <tbody :class="loading && rows.length && 'opacity-60'">
                <template x-for="(row, index) in rows" :key="row.id ?? index">
                    {{ $row }}
                </template>
                <template x-if="loading && !rows.length">
                    <tr><td colspan="{{ $colspan }}" class="px-4 py-6">
                        <div class="space-y-3">
                            <div class="skeleton h-5 w-2/3"></div>
                            <div class="skeleton h-5 w-1/2"></div>
                            <div class="skeleton h-5 w-3/5"></div>
                        </div>
                    </td></tr>
                </template>
                <template x-if="!loading && !rows.length">
                    <tr><td colspan="{{ $colspan }}" class="px-4 py-14 text-center">
                        <span class="mx-auto grid size-12 place-items-center rounded-2xl bg-brand-50 text-brand-600"><x-icon :name="$emptyIcon" class="size-6" /></span>
                        <p class="mt-3 font-semibold text-ink-900" x-text="error ? 'Gagal memuat data' : (search ? 'Tidak ada hasil untuk “' + search + '”' : (Object.values(filters).some(v => v !== '' && v !== null && v !== undefined) ? 'Tidak ada data yang cocok dengan filter' : @js($empty)))"></p>
                        <p class="mt-1 text-xs text-stone-500" x-show="error" x-text="error"></p>
                        <button type="button" x-show="!error && (search || Object.values(filters).some(v => v !== '' && v !== null && v !== undefined))" @click="reset()" class="btn btn-outline btn-sm mt-4">
                            <x-icon name="rotate-ccw" class="size-4" /> Reset pencarian & filter
                        </button>
                    </td></tr>
                </template>
            </tbody>
        </table>
    </div>

    <div class="flex flex-col items-center justify-between gap-3 border-t border-stone-100 px-4 py-3 sm:flex-row">
        <p class="text-xs text-stone-500">
            Menampilkan <b class="text-stone-700" x-text="from"></b>–<b class="text-stone-700" x-text="to"></b> dari <b class="text-stone-700" x-text="filtered"></b> data
            <span x-show="filtered !== total" x-cloak>(disaring dari <span x-text="total"></span>)</span>
        </p>
        <nav class="flex items-center gap-1" aria-label="Navigasi halaman tabel">
            <button type="button" @click="go(page - 1)" :disabled="page <= 1" class="grid size-9 place-items-center rounded-lg border border-stone-200 bg-white text-stone-600 transition hover:border-brand-500 hover:text-brand-700 disabled:opacity-40" aria-label="Sebelumnya"><x-icon name="chevron-left" class="size-4" /></button>
            <template x-for="(p, i) in pageList" :key="i + '-' + p">
                <button type="button" @click="go(p)" :disabled="p === '…'" x-text="p"
                        class="hidden min-w-9 rounded-lg px-2 py-1.5 text-sm font-semibold transition sm:block"
                        :class="p === page ? 'bg-brand-700 text-white shadow-sm' : (p === '…' ? 'text-stone-400' : 'border border-stone-200 bg-white text-stone-600 hover:border-brand-500 hover:text-brand-700')"></button>
            </template>
            <span class="px-2 text-sm font-semibold text-stone-600 sm:hidden"><span x-text="page"></span> / <span x-text="pages"></span></span>
            <button type="button" @click="go(page + 1)" :disabled="page >= pages" class="grid size-9 place-items-center rounded-lg border border-stone-200 bg-white text-stone-600 transition hover:border-brand-500 hover:text-brand-700 disabled:opacity-40" aria-label="Berikutnya"><x-icon name="chevron-right" class="size-4" /></button>
        </nav>
    </div>
</div>
