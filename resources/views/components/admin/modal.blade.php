{{--
    Modal admin. Wajib berada di dalam komponen Alpine yang memiliki properti "open" dan metode "close()"
    (mis. crudForm). Atribut "title" adalah EKSPRESI Alpine, contoh: title="mode === 'edit' ? 'Ubah' : 'Tambah'".
--}}
@props(['title' => "''", 'size' => 'max-w-2xl', 'icon' => null])

<template x-teleport="body">
    <div x-show="open" x-cloak class="fixed inset-0 z-[90] flex items-end justify-center sm:items-center sm:p-4" role="dialog" aria-modal="true" @keydown.escape.window="open && close()">
        <div x-show="open" x-transition.opacity class="absolute inset-0 bg-brand-950/60 backdrop-blur-sm" @click="close()"></div>
        <div x-show="open" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-6 opacity-0 sm:translate-y-0 sm:scale-95" x-transition:leave="transition duration-150" x-transition:leave-end="opacity-0"
             {{ $attributes->merge(['class' => "relative flex max-h-[94vh] w-full {$size} flex-col overflow-hidden rounded-t-3xl bg-white shadow-2xl sm:rounded-3xl"]) }}>
            <header class="bg-gradient-brand relative flex shrink-0 items-center justify-between gap-3 px-6 py-4 text-white">
                <div class="pattern-islamic absolute inset-0"></div>
                <h2 class="relative flex items-center gap-2.5 font-display text-xl font-semibold text-white">
                    @if ($icon)<span class="grid size-8 place-items-center rounded-lg bg-white/10 text-gold-300 ring-1 ring-white/15"><x-icon :name="$icon" class="size-4" /></span>@endif
                    <span x-text="{{ $title }}"></span>
                </h2>
                <button type="button" @click="close()" class="relative grid size-9 place-items-center rounded-xl text-white/80 hover:bg-white/10" aria-label="Tutup"><x-icon name="x" class="size-5" /></button>
            </header>
            {{ $slot }}
        </div>
    </div>
</template>
