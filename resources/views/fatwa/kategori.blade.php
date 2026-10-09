@php
    $user = auth()->user();
    $p = $user->isAdmin() ? 'admin' : 'operator';
    $base = route($p.'.kategori-fatwa.index');
    $fatwaUrl = $user->hasMenuPermission('fatwa') ? route($p.'.fatwa.index') : null;
@endphp

<x-layouts.admin title="Kategori Fatwa" header="Kelola klasifikasi dan kategori fatwa MUI Batanghari">
    <div x-data="kategoriFatwaPage({ base: @js($base), fatwaUrl: @js($fatwaUrl), stats: @js(['total' => $total, 'aktif' => $aktif, 'nonaktif' => $nonaktif, 'fatwa' => $totalFatwa]) })">
        <div x-data="serverTable({ url: @js($base), columns: ['id', 'nama', 'slug', 'deskripsi', 'fatwas_count', 'aktif', 'created_at'], order: [1, 'asc'], filters: { filter_aktif: '' } })">
            <x-admin.page-header eyebrow="Arsip Digital" title="Kategori Fatwa" description="Klasifikasi untuk mengelompokkan fatwa. Kategori nonaktif tidak muncul sebagai pilihan saat menambah fatwa maupun di halaman publik.">
                <x-slot:actions>
                    @if ($fatwaUrl)
                        <a href="{{ $fatwaUrl }}" class="btn btn-outline"><x-icon name="arrow-left" class="size-4" /> Daftar Fatwa</a>
                    @endif
                    <button type="button" @click="$dispatch('kategori-fatwa:open')" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Tambah Kategori</button>
                </x-slot:actions>
            </x-admin.page-header>

            <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-stat-card label="Total kategori" icon="tags" bind="stats.total" :value="$total" />
                <x-stat-card label="Kategori aktif" icon="circle-check-big" tone="gold" bind="stats.aktif" :value="$aktif" />
                <x-stat-card label="Nonaktif" icon="eye-off" tone="stone" bind="stats.nonaktif" :value="$nonaktif" />
                <x-stat-card label="Fatwa terkategori" icon="scale" tone="blue" bind="stats.fatwa" :value="$totalFatwa" />
            </div>

            <x-admin.table class="mt-6" colspan="7" empty="Tidak ada kategori fatwa untuk ditampilkan" empty-icon="tags" search-placeholder="Cari nama, slug, atau deskripsi kategori…">
                <x-slot:filters>
                    <select x-model="filters.filter_aktif" class="input w-auto" aria-label="Filter status">
                        <option value="">Semua status</option>
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                </x-slot:filters>
                <x-slot:actions>
                    <button type="button" @click="load(); refreshStats()" class="grid size-[42px] shrink-0 place-items-center rounded-xl border border-stone-300 bg-white text-stone-600 transition hover:border-brand-500 hover:text-brand-700" title="Muat ulang" aria-label="Muat ulang tabel"><x-icon name="refresh-cw" class="size-4" ::class="loading && 'animate-spin'" /></button>
                </x-slot:actions>
                <x-slot:head>
                    <th class="w-12">#</th>
                    <x-admin.th col="1">Kategori</x-admin.th>
                    <x-admin.th col="3" class="hidden md:table-cell">Deskripsi</x-admin.th>
                    <x-admin.th col="4">Jumlah Fatwa</x-admin.th>
                    <x-admin.th col="5">Status</x-admin.th>
                    <x-admin.th col="6">Dibuat</x-admin.th>
                    <th class="text-right">Aksi</th>
                </x-slot:head>
                <x-slot:row>
                    <tr>
                        <td class="text-stone-400 tabular-nums" x-text="rowNumber(index)"></td>
                        <td>
                            <div class="flex min-w-52 items-center gap-3">
                                <span class="grid size-9 shrink-0 place-items-center rounded-xl ring-1 transition" :class="row.aktif ? 'bg-brand-50 text-brand-700 ring-brand-100' : 'bg-stone-100 text-stone-400 ring-stone-200'"><x-icon name="tag" class="size-4" /></span>
                                <div class="min-w-0">
                                    <p class="font-semibold text-ink-900" x-text="row.nama"></p>
                                    <p class="font-mono text-[11px] text-stone-400" x-text="row.slug"></p>
                                </div>
                            </div>
                        </td>
                        <td class="hidden md:table-cell"><p class="line-clamp-2 max-w-xs text-stone-600" :title="row.deskripsi" x-text="row.deskripsi || '—'"></p></td>
                        <td class="whitespace-nowrap">
                            <template x-if="fatwaUrl && row.aktif && row.fatwas_count > 0">
                                <a :href="fatwaUrl + '?kategori=' + row.id" class="group inline-flex items-center gap-1.5 rounded-lg bg-gold-50 px-2.5 py-1 text-xs font-bold text-gold-800 ring-1 ring-gold-600/20 transition hover:bg-gold-100" title="Lihat fatwa dalam kategori ini">
                                    <x-icon name="scale" class="size-3.5" /><span x-text="row.fatwas_count + ' fatwa'"></span><x-icon name="arrow-up-right" class="size-3 opacity-60 transition group-hover:opacity-100" />
                                </a>
                            </template>
                            <template x-if="!(fatwaUrl && row.aktif && row.fatwas_count > 0)">
                                <span class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-bold ring-1" :class="row.fatwas_count > 0 ? 'bg-gold-50 text-gold-800 ring-gold-600/20' : 'bg-stone-50 text-stone-500 ring-stone-200'">
                                    <x-icon name="scale" class="size-3.5" /><span x-text="(row.fatwas_count || 0) + ' fatwa'"></span>
                                </span>
                            </template>
                        </td>
                        <td class="whitespace-nowrap">
                            <button type="button" role="switch" :aria-checked="row.aktif ? 'true' : 'false'" @click="toggleStatus(row)" :disabled="!!busy[row.id]"
                                    class="inline-flex items-center gap-2.5 rounded-full disabled:cursor-wait disabled:opacity-60"
                                    :aria-label="(row.aktif ? 'Nonaktifkan kategori ' : 'Aktifkan kategori ') + row.nama" :title="row.aktif ? 'Klik untuk menonaktifkan' : 'Klik untuk mengaktifkan'">
                                <span class="relative h-5 w-9 shrink-0 rounded-full transition" :class="row.aktif ? 'bg-brand-600' : 'bg-stone-300'">
                                    <span class="absolute top-0.5 left-0.5 size-4 rounded-full bg-white shadow transition" :class="row.aktif && 'translate-x-4'"></span>
                                </span>
                                <span class="text-xs font-semibold" :class="row.aktif ? 'text-brand-700' : 'text-stone-500'" x-text="row.aktif ? 'Aktif' : 'Nonaktif'"></span>
                            </button>
                        </td>
                        <td class="whitespace-nowrap">
                            <p class="text-stone-600" x-text="when(row.created_at).date"></p>
                            <p class="text-xs text-stone-400" x-text="when(row.created_at).time"></p>
                        </td>
                        <td>
                            <div class="flex justify-end gap-1.5">
                                <button type="button" @click="$dispatch('kategori-fatwa:open', row)" class="grid size-8 place-items-center rounded-lg text-stone-500 hover:bg-brand-50 hover:text-brand-700" title="Ubah" aria-label="Ubah kategori fatwa"><x-icon name="pencil" class="size-4" /></button>
                                <button type="button" @click="remove(row)" class="grid size-8 place-items-center rounded-lg text-stone-500 hover:bg-red-50 hover:text-red-600" title="Hapus" aria-label="Hapus kategori fatwa"><x-icon name="trash-2" class="size-4" /></button>
                            </div>
                        </td>
                    </tr>
                </x-slot:row>
            </x-admin.table>
        </div>

        {{-- Formulir tambah / ubah --}}
        <div x-data="crudForm({ name: 'kategori-fatwa', storeUrl: @js($base), updateUrl: @js($base.'/:id'), defaults: { nama: '', deskripsi: '', aktif: true } })">
            <x-admin.modal title="mode === 'edit' ? 'Ubah Kategori Fatwa' : 'Tambah Kategori Fatwa'" icon="tags" size="max-w-xl">
                <form x-ref="form" @submit.prevent="submit()" class="flex min-h-0 flex-1 flex-col">
                    <div class="scrollbar-thin flex-1 space-y-5 overflow-y-auto p-6">
                        <div>
                            <label for="kf-nama" class="label">Nama kategori <span class="text-red-500">*</span></label>
                            <input id="kf-nama" x-ref="first" name="nama" x-model="data.nama" type="text" maxlength="150" required placeholder="cth: Ibadah, Ekonomi Syariah, Produk Halal" class="input" :class="error('nama') && 'input-error'">
                            <p class="field-error" x-show="error('nama')" x-text="error('nama')"></p>
                            <p class="mt-1.5 flex items-center gap-1.5 text-xs text-stone-500" x-show="!error('nama')"><x-icon name="link" class="size-3.5" /> Slug otomatis: <span class="font-mono text-brand-700" x-text="slugify(data.nama) || '—'"></span></p>
                        </div>
                        <div>
                            <label for="kf-deskripsi" class="label">Deskripsi / ruang lingkup <span class="font-normal text-stone-400">(opsional)</span></label>
                            <textarea id="kf-deskripsi" name="deskripsi" x-model="data.deskripsi" rows="3" maxlength="500" placeholder="Keterangan singkat tentang lingkup fatwa yang masuk dalam kategori ini…" class="input resize-none" :class="error('deskripsi') && 'input-error'"></textarea>
                            <div class="mt-1 flex items-start gap-3">
                                <p class="field-error mt-0" x-show="error('deskripsi')" x-text="error('deskripsi')"></p>
                                <p class="ml-auto shrink-0 text-xs text-stone-400 tabular-nums"><span x-text="(data.deskripsi || '').length"></span>/500</p>
                            </div>
                        </div>
                        <label class="flex cursor-pointer items-center justify-between gap-4 rounded-xl border border-stone-200 p-4 hover:border-brand-300">
                            <span>
                                <span class="block text-sm font-semibold text-ink-900">Kategori aktif</span>
                                <span class="block text-xs text-stone-500" x-text="data.aktif ? 'Dapat dipilih saat menambah fatwa & tampil di halaman publik.' : 'Disembunyikan dari formulir fatwa & halaman publik.'"></span>
                            </span>
                            <span class="relative inline-flex shrink-0">
                                <input type="hidden" name="aktif" value="0">
                                <input id="kf-aktif" type="checkbox" name="aktif" value="1" x-model="data.aktif" class="peer sr-only">
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
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                const BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

                /** Data & aksi halaman Kategori Fatwa (statistik, toggle status, hapus). */
                Alpine.data('kategoriFatwaPage', ({ base, fatwaUrl, stats }) => ({
                    base,
                    fatwaUrl,
                    stats,
                    busy: {},
                    init() {
                        window.addEventListener('table:reload', () => this.refreshStats());
                    },
                    async refreshStats() {
                        try {
                            const res = await MUIAdmin.http(`${base}?draw=1&start=0&length=1&filter_aktif=1`);
                            const total = Number(res.recordsTotal ?? 0);
                            const aktif = Number(res.recordsFiltered ?? 0);
                            Object.assign(this.stats, { total, aktif, nonaktif: Math.max(0, total - aktif) });
                        } catch {
                            /* statistik bersifat pelengkap */
                        }
                    },
                    /** Tanggal dari controller berformat "d/m/Y H:i". */
                    when(value) {
                        const m = /^(\d{2})\/(\d{2})\/(\d{4})\s+(\d{2}):(\d{2})/.exec(String(value || ''));
                        return m ? { date: `${m[1]} ${BULAN[Number(m[2]) - 1]} ${m[3]}`, time: `${m[4]}:${m[5]}` } : { date: value || '—', time: '' };
                    },
                    slugify: (text) => String(text || '').normalize('NFKD').replace(/[\u0300-\u036f]/g, '').toLowerCase()
                        .replace(/_+/g, '-').replace(/@/g, '-at-').replace(/[^a-z0-9\s-]/g, '').trim().replace(/[\s-]+/g, '-').replace(/^-+|-+$/g, ''),
                    async toggleStatus(row) {
                        if (this.busy[row.id]) return;
                        this.busy[row.id] = true;
                        try {
                            const res = await MUIAdmin.http(`${base}/${row.id}/toggle-status`, { method: 'PATCH' });
                            row.aktif = res.aktif ? 1 : 0;
                            MUIAdmin.toast(row.aktif ? `Kategori “${row.nama}” diaktifkan.` : `Kategori “${row.nama}” dinonaktifkan.`);
                            MUIAdmin.reloadTables();
                        } catch (e) {
                            MUIAdmin.toast(e.message || 'Gagal memperbarui status kategori.', 'error');
                        } finally {
                            delete this.busy[row.id];
                        }
                    },
                    async remove(row) {
                        const count = Number(row.fatwas_count || 0);
                        const res = await MUIAdmin.destroy(`${base}/${row.id}`, {
                            title: 'Hapus kategori fatwa?',
                            message: count > 0
                                ? `Kategori “${row.nama}” memiliki ${count} fatwa terkait. Jika dihapus, fatwa tersebut tidak lagi memiliki kategori. Lanjutkan?`
                                : `Kategori fatwa “${row.nama}” akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.`,
                        });
                        if (res) this.stats.fatwa = Math.max(0, Number(this.stats.fatwa) - count);
                    },
                }));
            });
        </script>
    @endpush
</x-layouts.admin>
