{{-- Kepala kolom tabel; isi atribut "col" (indeks kolom urut di controller) agar dapat diurutkan. --}}
@props(['col' => null])

<th {{ $attributes }}>
    @if ($col !== null)
        <button type="button" @click="sortBy({{ (int) $col }})" class="group inline-flex items-center gap-1 uppercase hover:text-brand-700"
                :aria-sort="sortIcon({{ (int) $col }}) === 'none' ? 'none' : (sortIcon({{ (int) $col }}) === 'asc' ? 'ascending' : 'descending')">
            {{ $slot }}
            <x-icon name="chevrons-up-down" class="size-3.5 opacity-40 group-hover:opacity-80" x-show="sortIcon({{ (int) $col }}) === 'none'" />
            <x-icon name="chevron-up" class="size-3.5 text-brand-600" x-show="sortIcon({{ (int) $col }}) === 'asc'" x-cloak />
            <x-icon name="chevron-down" class="size-3.5 text-brand-600" x-show="sortIcon({{ (int) $col }}) === 'desc'" x-cloak />
        </button>
    @else
        {{ $slot }}
    @endif
</th>
