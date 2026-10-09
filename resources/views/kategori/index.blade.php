@php
    $base = auth()->user()->isOperator() ? route('operator.kategori.index') : route('admin.kategori.index');
    $presets = ['#177a53', '#126245', '#0f766e', '#0284c7', '#1e40af', '#7c3aed', '#be185d', '#dc2626', '#ea580c', '#c9951f', '#a97418', '#6b7280'];
@endphp

<x-layouts.admin title="Kategori Berita" header="Kelola kategori untuk pengelompokan berita & artikel">
    <div x-data="serverTable({ url: @js($base), columns: ['id', 'nama', 'slug', 'aktif', 'urutan', 'created_at'], order: [4, 'asc'], filters: { filter_aktif: '' } })">
        <x-admin.page-header eyebrow="Konten Website" title="Kategori Berita" description="Kategori menentukan pengelompokan berita di halaman publik. Urutan kecil tampil lebih dulu.">
            <x-slot:actions>
                <button type="button" @click="$dispatch('kategori:open')" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Tambah Kategori</button>
            </x-slot:actions>
        </x-admin.page-header>

        <div class="mt-6 grid gap-4 sm:grid-cols-3">
            <x-stat-card label="Total kategori" icon="tags" bind="meta.stats?.total ?? '–'" />
            <x-stat-card label="Aktif" icon="circle-check-big" tone="gold" bind="meta.stats?.aktif ?? '–'" />
            <x-stat-card label="Nonaktif" icon="eye-off" tone="stone" bind="meta.stats?.nonaktif ?? '–'" />
        </div>

        <x-admin.table class="mt-6" colspan="7" empty="Belum ada kategori" empty-icon="tags" search-placeholder="Cari nama atau deskripsi kategori…">
            <x-slot:filters>
                <select x-model="filters.filter_aktif" class="input w-auto" aria-label="Filter status">
                    <option value="">Semua status</option>
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>
            </x-slot:filters>
            <x-slot:head>
                <th class="w-12">#</th>
                <x-admin.th col="1">Kategori</x-admin.th>
                <th>Deskripsi</th>
                <x-admin.th col="4">Urutan</x-admin.th>
                <x-admin.th col="3">Status</x-admin.th>
                <x-admin.th col="5">Dibuat</x-admin.th>
                <th class="text-right">Aksi</th>
            </x-slot:head>
            <x-slot:row>
                <tr>
                    <td class="text-stone-400 tabular-nums" x-text="rowNumber(index)"></td>
                    <td>
                        <div class="flex items-center gap-3">
                            <span class="size-3.5 shrink-0 rounded-full ring-4 ring-stone-100" :style="`background:${row.warna || '#177a53'}`"></span>
                            <div class="min-w-0">
                                <p class="font-semibold text-ink-900" x-text="row.nama"></p>
                                <p class="font-mono text-[11px] text-stone-400" x-text="row.slug"></p>
                            </div>
                        </div>
                    </td>
                    <td class="max-w-xs"><p class="line-clamp-2 text-stone-600" x-text="row.deskripsi || '—'"></p></td>
                    <td><span class="font-bold text-brand-700 tabular-nums" x-text="row.urutan"></span></td>
                    <td>
                        <span class="badge" :class="row.aktif ? 'badge-green' : 'badge-gray'" x-text="row.aktif ? 'Aktif' : 'Nonaktif'"></span>
                    </td>
                    <td class="whitespace-nowrap text-stone-500" x-text="MUIAdmin.formatDate(row.created_at)"></td>
                    <td>
                        <div class="flex justify-end gap-1.5">
                            <button type="button" @click="$dispatch('kategori:open', row)" class="grid size-8 place-items-center rounded-lg text-stone-500 hover:bg-brand-50 hover:text-brand-700" title="Ubah" aria-label="Ubah kategori"><x-icon name="pencil" class="size-4" /></button>
                            <button type="button" @click="MUIAdmin.destroy(@js($base) + '/' + row.id, { title: 'Hapus kategori?', message: `Kategori “${row.nama}” akan dihapus permanen.` })" class="grid size-8 place-items-center rounded-lg text-stone-500 hover:bg-red-50 hover:text-red-600" title="Hapus" aria-label="Hapus kategori"><x-icon name="trash-2" class="size-4" /></button>
                        </div>
                    </td>
                </tr>
            </x-slot:row>
        </x-admin.table>
    </div>

    {{-- Formulir tambah / ubah --}}
    <div x-data="crudForm({ name: 'kategori', storeUrl: @js($base), updateUrl: @js($base.'/:id'), defaults: { nama: '', warna: '#177a53', deskripsi: '', aktif: true, urutan: 0 } })">
        <x-admin.modal title="mode === 'edit' ? 'Ubah Kategori' : 'Tambah Kategori'" icon="tags">
            <form x-ref="form" @submit.prevent="submit()" class="flex min-h-0 flex-1 flex-col">
                <div class="scrollbar-thin flex-1 space-y-5 overflow-y-auto p-6">
                    <div class="grid gap-5 sm:grid-cols-3">
                        <div class="sm:col-span-2">
                            <label for="k-nama" class="label">Nama kategori <span class="text-red-500">*</span></label>
                            <input id="k-nama" x-ref="first" name="nama" x-model="data.nama" type="text" maxlength="100" required placeholder="cth: Berita Utama" class="input" :class="error('nama') && 'input-error'">
                            <p class="field-error" x-show="error('nama')" x-text="error('nama')"></p>
                        </div>
                        <div>
                            <label for="k-urutan" class="label">Urutan tampil</label>
                            <input id="k-urutan" name="urutan" x-model="data.urutan" type="number" min="0" class="input" :class="error('urutan') && 'input-error'">
                            <p class="mt-1.5 text-xs text-stone-500">Angka kecil tampil lebih dulu</p>
                        </div>
                    </div>
                    <div>
                        <label for="k-deskripsi" class="label">Deskripsi <span class="font-normal text-stone-400">(opsional)</span></label>
                        <textarea id="k-deskripsi" name="deskripsi" x-model="data.deskripsi" rows="3" maxlength="500" placeholder="Deskripsi singkat tentang kategori ini…" class="input resize-none"></textarea>
                        <p class="mt-1 text-right text-xs text-stone-400"><span x-text="(data.deskripsi || '').length"></span>/500</p>
                    </div>
                    <div>
                        <p class="label">Warna badge <span class="text-red-500">*</span></p>
                        <div class="flex flex-wrap items-center gap-4">
                            <input type="color" name="warna" x-model="data.warna" class="h-11 w-14 cursor-pointer rounded-xl border border-stone-300 bg-white p-1" aria-label="Pilih warna">
                            <div class="flex flex-wrap gap-2">
                                @foreach ($presets as $warna)
                                    <button type="button" @click="data.warna = '{{ $warna }}'" class="size-7 rounded-full ring-offset-2 transition hover:scale-110" style="background: {{ $warna }}" :class="data.warna === '{{ $warna }}' && 'ring-2 ring-ink-900'" aria-label="Warna {{ $warna }}"></button>
                                @endforeach
                            </div>
                        </div>
                        <p class="mt-3 flex items-center gap-2 text-xs text-stone-500">Pratinjau:
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold text-white" :style="`background:${data.warna}`" x-text="data.nama || 'Kategori'"></span>
                        </p>
                    </div>
                    <label class="flex cursor-pointer items-center justify-between gap-4 rounded-xl border border-stone-200 p-4 hover:border-brand-300">
                        <span>
                            <span class="block text-sm font-semibold text-ink-900">Tampilkan kategori</span>
                            <span class="block text-xs text-stone-500">Kategori nonaktif tidak muncul di halaman publik.</span>
                        </span>
                        <span class="relative inline-flex shrink-0">
                            <input type="hidden" name="aktif" value="0">
                            <input type="checkbox" name="aktif" value="1" x-model="data.aktif" class="peer sr-only">
                            <span class="h-6 w-11 rounded-full bg-stone-300 transition peer-checked:bg-brand-600 peer-focus-visible:ring-4 peer-focus-visible:ring-brand-500/20"></span>
                            <span class="absolute top-0.5 left-0.5 size-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                        </span>
                    </label>
                </div>
                <footer class="flex shrink-0 justify-end gap-2 border-t border-stone-100 bg-stone-50/60 px-6 py-4">
                    <button type="button" @click="close()" class="btn btn-outline">Batal</button>
                    <button type="submit" class="btn btn-primary" :disabled="saving">
                        <x-icon name="loader-circle" class="size-4 animate-spin" x-show="saving" x-cloak />
                        <x-icon name="save" class="size-4" x-show="!saving" /> Simpan
                    </button>
                </footer>
            </form>
        </x-admin.modal>
    </div>
</x-layouts.admin>
