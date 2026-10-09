@php
    $user = auth()->user();
    $p = $user->isAdmin() ? 'admin' : 'operator';
    $base = route($p.'.fatwa.index');
    $kategoriUrl = $user->hasMenuPermission('kategori-fatwa') ? route($p.'.kategori-fatwa.index') : null;
    // Pratinjau filter kategori dari tautan halaman Kategori Fatwa (?kategori=ID), hanya untuk kategori aktif.
    $presetKategori = (int) request()->query('kategori');
    $presetKategori = $kategoriFatwas->contains('id', $presetKategori) ? (string) $presetKategori : '';
    // [ikon, keterangan, gaya terpilih]
    $statusMeta = [
        'aktif' => ['circle-check-big', 'Fatwa berlaku dan menjadi rujukan saat ini.', 'has-checked:border-brand-500 has-checked:bg-brand-50 has-checked:text-brand-800'],
        'direvisi' => ['pencil', 'Sebagian ketentuan fatwa telah diperbarui oleh fatwa lain.', 'has-checked:border-gold-400 has-checked:bg-gold-50 has-checked:text-gold-800'],
        'digantikan' => ['history', 'Fatwa tidak berlaku lagi karena digantikan oleh fatwa yang lebih baru.', 'has-checked:border-stone-400 has-checked:bg-stone-100 has-checked:text-stone-800'],
    ];
    $statusHints = collect($statuses)->mapWithKeys(fn ($label, $key) => [$key => $statusMeta[$key][1] ?? $label]);
@endphp

<x-layouts.admin title="Fatwa MUI" header="Kelola koleksi fatwa MUI Batanghari — unggah & publikasikan PDF fatwa">
    <div x-data="fatwaPage({ base: @js($base), kategoriIds: @js($kategoriFatwas->pluck('id')), statusHints: @js($statusHints) })">
        <div x-data="serverTable({ url: @js($base), columns: ['id', 'judul', 'kategori_fatwa_id', 'status_fatwa', 'keterangan', 'filepdf', 'publikasi', 'created_at'], order: [7, 'desc'], filters: { filter_publikasi: '', filter_status_fatwa: '', filter_kategori_fatwa: @js($presetKategori) } })">
            <x-admin.page-header eyebrow="Arsip Digital" title="Fatwa MUI" description="Unggah PDF fatwa, atur status keberlakuan, lalu publikasikan ke website.">
                <x-slot:actions>
                    <a href="{{ route('fatwa') }}" target="_blank" rel="noopener" class="btn btn-outline" title="Lihat halaman publik" aria-label="Lihat halaman publik fatwa"><x-icon name="external-link" class="size-4" /><span class="hidden sm:inline">Halaman publik</span></a>
                    @if ($kategoriUrl)
                        <a href="{{ $kategoriUrl }}" class="btn btn-outline" title="Kelola kategori fatwa" aria-label="Kelola kategori fatwa"><x-icon name="tags" class="size-4" /><span class="hidden sm:inline">Kelola Kategori</span></a>
                    @endif
                    <button type="button" @click="$dispatch('fatwa:open')" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Tambah Fatwa</button>
                </x-slot:actions>
            </x-admin.page-header>

            <div class="mt-6 grid gap-4 sm:grid-cols-3">
                <x-stat-card label="Total fatwa" icon="scale" bind="stats.total ?? '–'" note="Seluruh koleksi fatwa" />
                <x-stat-card label="Dipublikasikan" icon="globe" tone="gold" bind="stats.publik ?? '–'" bind-note="stats.total ? percent(stats.publik) + '% tampil di website' : 'Tampil di website'" />
                <x-stat-card label="Draf" icon="eye-off" tone="stone" bind="stats.draft ?? '–'" note="Disembunyikan dari halaman publik" />
            </div>

            <x-admin.table class="mt-6" colspan="7" empty="Tidak ada fatwa untuk ditampilkan" empty-icon="scale" search-placeholder="Cari judul atau keterangan fatwa…">
                <x-slot:filters>
                    <select x-model="filters.filter_publikasi" class="input w-auto" aria-label="Filter publikasi">
                        <option value="">Semua publikasi</option>
                        <option value="1">Dipublikasikan</option>
                        <option value="0">Draf</option>
                    </select>
                    <select x-model="filters.filter_status_fatwa" class="input w-auto" aria-label="Filter status keberlakuan">
                        <option value="">Semua status</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <select x-model="filters.filter_kategori_fatwa" class="input w-auto max-w-60" aria-label="Filter kategori fatwa">
                        <option value="">Semua kategori</option>
                        @foreach ($kategoriFatwas as $kf)
                            <option value="{{ $kf->id }}">{{ $kf->nama }}</option>
                        @endforeach
                    </select>
                </x-slot:filters>
                <x-slot:actions>
                    <button type="button" x-show="search || Object.values(filters).some((v) => v !== '')" x-cloak @click="search = ''; filters = { filter_publikasi: '', filter_status_fatwa: '', filter_kategori_fatwa: '' }" class="btn btn-ghost btn-sm"><x-icon name="x" class="size-4" /> Reset</button>
                </x-slot:actions>
                <x-slot:head>
                    <th class="w-12">#</th>
                    <x-admin.th col="1">Fatwa</x-admin.th>
                    <x-admin.th col="2">Kategori</x-admin.th>
                    <x-admin.th col="3">Status</x-admin.th>
                    <x-admin.th col="6">Publikasi</x-admin.th>
                    <x-admin.th col="7">Dibuat</x-admin.th>
                    <th class="text-right">Aksi</th>
                </x-slot:head>
                <x-slot:row>
                    <tr>
                        <td class="text-stone-400 tabular-nums" x-text="rowNumber(index)"></td>
                        <td>
                            <div class="min-w-64">
                                <button type="button" @click="$dispatch('fatwa:view', row)" class="line-clamp-2 text-left font-semibold text-ink-900 transition hover:text-brand-700" :title="row.judul" x-text="row.judul"></button>
                                <p x-show="row.keterangan" class="mt-0.5 line-clamp-1 text-xs text-stone-500" :title="row.keterangan" x-text="row.keterangan"></p>
                                <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] font-medium text-stone-500">
                                    <button x-show="row.file_url" type="button" @click="$dispatch('fatwa:view', row)" class="inline-flex items-center gap-1 font-semibold text-red-600 hover:underline" :title="'Pratinjau ' + row.file_name"><x-icon name="file-text" class="size-3.5" /> PDF</button>
                                    <span x-show="!row.file_url" class="inline-flex items-center gap-1 text-stone-400"><x-icon name="file-x" class="size-3.5" /> Belum ada PDF</span>
                                    <span class="inline-flex items-center gap-1"><x-icon name="eye" class="size-3.5" /> <span x-text="views(row.views) + ' kali dilihat'"></span></span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="max-w-44">
                                <span x-show="row.kategori_nama" class="inline-flex items-start gap-1.5 text-[13px] leading-snug font-medium text-brand-800"><x-icon name="tag" class="mt-0.5 size-3.5 text-gold-600" /><span x-text="row.kategori_nama"></span></span>
                                <span x-show="!row.kategori_nama" class="text-xs text-stone-400 italic">Tanpa kategori</span>
                            </div>
                        </td>
                        <td class="whitespace-nowrap">
                            <span class="badge" :class="statusBadge(row.status_fatwa)">
                                <x-icon name="circle-check-big" class="size-3" x-show="row.status_fatwa === 'aktif'" />
                                <x-icon name="pencil" class="size-3" x-show="row.status_fatwa === 'direvisi'" x-cloak />
                                <x-icon name="history" class="size-3" x-show="row.status_fatwa === 'digantikan'" x-cloak />
                                <span x-text="row.status_fatwa_label"></span>
                            </span>
                        </td>
                        <td class="whitespace-nowrap">
                            <button type="button" role="switch" :aria-checked="row.publikasi ? 'true' : 'false'" @click="togglePublikasi(row)" :disabled="!!busy[row.id]"
                                    class="inline-flex items-center gap-2.5 rounded-full disabled:cursor-wait disabled:opacity-60"
                                    :aria-label="(row.publikasi ? 'Sembunyikan fatwa dari publik: ' : 'Publikasikan fatwa: ') + row.judul" :title="row.publikasi ? 'Klik untuk menyembunyikan dari halaman publik' : 'Klik untuk memublikasikan'">
                                <span class="relative h-5 w-9 shrink-0 rounded-full transition" :class="row.publikasi ? 'bg-brand-600' : 'bg-stone-300'">
                                    <span class="absolute top-0.5 left-0.5 size-4 rounded-full bg-white shadow transition" :class="row.publikasi && 'translate-x-4'"></span>
                                </span>
                                <span class="text-xs font-semibold" :class="row.publikasi ? 'text-brand-700' : 'text-stone-500'" x-text="row.publikasi ? 'Publik' : 'Draf'"></span>
                            </button>
                        </td>
                        <td class="whitespace-nowrap text-stone-500" x-text="MUIAdmin.formatDate(row.created_at)"></td>
                        <td>
                            <div class="flex justify-end gap-1.5">
                                <button type="button" @click="$dispatch('fatwa:view', row)" class="grid size-8 place-items-center rounded-lg text-stone-500 hover:bg-sky-50 hover:text-sky-700" title="Detail & PDF" aria-label="Lihat detail fatwa"><x-icon name="eye" class="size-4" /></button>
                                <button type="button" @click="$dispatch('fatwa:open', row)" class="grid size-8 place-items-center rounded-lg text-stone-500 hover:bg-brand-50 hover:text-brand-700" title="Ubah" aria-label="Ubah fatwa"><x-icon name="pencil" class="size-4" /></button>
                                <button type="button" @click="remove(row)" class="grid size-8 place-items-center rounded-lg text-stone-500 hover:bg-red-50 hover:text-red-600" title="Hapus" aria-label="Hapus fatwa"><x-icon name="trash-2" class="size-4" /></button>
                            </div>
                        </td>
                    </tr>
                </x-slot:row>
            </x-admin.table>
        </div>

        {{-- Formulir tambah / ubah --}}
        <div x-data="crudForm({ name: 'fatwa', storeUrl: @js($base), updateUrl: @js($base.'/:id'), defaults: { judul: '', kategori_fatwa_id: '', status_fatwa: 'aktif', keterangan: '', publikasi: true } })">
            <x-admin.modal title="mode === 'edit' ? 'Ubah Fatwa' : 'Tambah Fatwa'" icon="scale" size="max-w-3xl">
                <form x-ref="form" @submit.prevent="submit()" class="flex min-h-0 flex-1 flex-col">
                    <div class="scrollbar-thin flex-1 space-y-5 overflow-y-auto p-6">
                        <div>
                            <label for="f-judul" class="label">Judul fatwa <span class="text-red-500">*</span></label>
                            <input id="f-judul" x-ref="first" name="judul" x-model="data.judul" type="text" maxlength="255" required placeholder="cth: Fatwa MUI No. 1 Tahun 2024 tentang …" class="input" :class="error('judul') && 'input-error'">
                            <p class="field-error" x-show="error('judul')" x-text="error('judul')"></p>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-5">
                            <div class="sm:col-span-2">
                                <label for="f-kategori" class="label">Kategori <span class="font-normal text-stone-400">(opsional)</span></label>
                                <select id="f-kategori" name="kategori_fatwa_id" x-model="data.kategori_fatwa_id" class="input" :class="error('kategori_fatwa_id') && 'input-error'">
                                    <option value="">— Tanpa kategori —</option>
                                    @foreach ($kategoriFatwas as $kf)
                                        <option value="{{ $kf->id }}">{{ $kf->nama }}</option>
                                    @endforeach
                                    <template x-if="data.kategori_fatwa_id && !kategoriIds.includes(Number(data.kategori_fatwa_id))">
                                        <option :value="data.kategori_fatwa_id" x-text="(data.kategori_nama || 'Kategori #' + data.kategori_fatwa_id) + ' (nonaktif)'"></option>
                                    </template>
                                </select>
                                @if ($kategoriFatwas->isEmpty())
                                    <p class="mt-1.5 text-xs text-stone-500">Belum ada kategori fatwa yang aktif.@if ($kategoriUrl) <a href="{{ $kategoriUrl }}" class="font-semibold text-brand-700 hover:underline">Kelola kategori</a>@endif</p>
                                @endif
                                <p class="field-error" x-show="error('kategori_fatwa_id')" x-text="error('kategori_fatwa_id')"></p>
                            </div>

                            <fieldset class="sm:col-span-3">
                                <legend class="label">Status keberlakuan <span class="text-red-500">*</span></legend>
                                <div class="grid grid-cols-3 gap-2">
                                    @foreach ($statuses as $value => $label)
                                        @php [$icon, , $tone] = $statusMeta[$value] ?? ['info', '', 'has-checked:border-brand-500 has-checked:bg-brand-50 has-checked:text-brand-800']; @endphp
                                        <label class="flex h-[42px] cursor-pointer items-center justify-center gap-1.5 rounded-xl border border-stone-300 bg-white px-2 text-center text-stone-500 transition hover:border-brand-300 has-focus-visible:ring-4 has-focus-visible:ring-brand-500/20 {{ $tone }}">
                                            <input type="radio" name="status_fatwa" value="{{ $value }}" x-model="data.status_fatwa" required class="sr-only">
                                            <x-icon :name="$icon" class="hidden size-4 min-[420px]:block" />
                                            <span class="text-[13px] font-semibold">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <p class="mt-1.5 text-xs text-stone-500" x-text="statusHints[data.status_fatwa] ?? ''"></p>
                                <p class="field-error" x-show="error('status_fatwa')" x-text="error('status_fatwa')"></p>
                            </fieldset>
                        </div>

                        <div>
                            <label for="f-keterangan" class="label">Keterangan <span class="font-normal text-stone-400">(opsional)</span></label>
                            <textarea id="f-keterangan" name="keterangan" x-model="data.keterangan" rows="3" maxlength="1000" placeholder="Ringkasan atau catatan mengenai fatwa ini…" class="input resize-none" :class="error('keterangan') && 'input-error'"></textarea>
                            <div class="mt-1 flex items-start gap-3">
                                <p class="field-error mt-0" x-show="error('keterangan')" x-text="error('keterangan')"></p>
                                <p class="ml-auto shrink-0 text-xs text-stone-400 tabular-nums"><span x-text="(data.keterangan || '').length"></span>/1000</p>
                            </div>
                        </div>

                        <div x-data="dropFile({ field: 'filepdf', maxMb: 10, exts: ['pdf'] })" @fatwa:open.window="reset()">
                            <p class="label">
                                Berkas PDF fatwa
                                <span x-show="mode === 'create'" class="text-red-500">*</span>
                                <span x-show="mode === 'edit'" x-cloak class="font-normal text-stone-400">(biarkan kosong jika tidak diganti)</span>
                            </p>

                            <div x-show="mode === 'edit' && data.file_url && !name" x-cloak class="mb-3 flex items-center gap-3 rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-3">
                                <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-red-50 text-red-600 ring-1 ring-red-100"><x-icon name="file-text" class="size-5" /></span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-ink-900" x-text="data.file_name"></p>
                                    <p class="text-xs text-stone-500">Berkas PDF saat ini</p>
                                </div>
                                <a :href="data.file_url" target="_blank" rel="noopener" class="btn btn-ghost btn-sm"><x-icon name="external-link" class="size-4" /><span class="hidden sm:inline">Lihat</span></a>
                            </div>

                            <label for="f-file" x-show="!name" class="relative flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed px-6 py-6 text-center transition focus-within:ring-4 focus-within:ring-brand-500/15"
                                   :class="dragging ? 'border-brand-500 bg-brand-50' : ((problem || error('filepdf')) ? 'border-red-300 bg-red-50/40' : 'border-stone-300 bg-sand-50 hover:border-brand-400 hover:bg-brand-50/50')"
                                   @dragenter="dragging = true" @dragover="dragging = true" @dragleave="dragging = false" @drop="dragging = false">
                                <input id="f-file" x-ref="file" type="file" name="filepdf" accept=".pdf,application/pdf" :required="mode === 'create'" @change="pick($event)" class="absolute inset-0 size-full cursor-pointer opacity-0" aria-describedby="f-file-hint">
                                <span class="grid size-12 place-items-center rounded-2xl bg-white text-red-600 shadow-sm ring-1 ring-stone-200 transition" :class="dragging && 'scale-110'"><x-icon name="file-up" class="size-6" /></span>
                                <p class="mt-3 text-sm font-semibold text-ink-900"><span class="text-brand-700 underline decoration-gold-400 decoration-2 underline-offset-4">Pilih berkas PDF</span> atau seret ke sini</p>
                                <p id="f-file-hint" class="mt-1 text-xs text-stone-500">Hanya format PDF · maks. 10 MB</p>
                            </label>

                            <div x-show="name" x-cloak class="flex items-center gap-3 rounded-xl border border-brand-200 bg-brand-50/60 px-3.5 py-3">
                                <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-white text-brand-700 ring-1 ring-brand-100"><x-icon name="file-check" class="size-5" /></span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-ink-900" x-text="name"></p>
                                    <p class="text-xs text-stone-500"><span x-text="size"></span> · <span x-text="mode === 'edit' ? 'akan menggantikan PDF lama' : 'siap diunggah'"></span></p>
                                </div>
                                <a :href="url" target="_blank" rel="noopener" class="btn btn-ghost btn-sm"><x-icon name="eye" class="size-4" /><span class="hidden sm:inline">Pratinjau</span></a>
                                <button type="button" @click="reset()" class="grid size-8 shrink-0 place-items-center rounded-lg text-stone-500 hover:bg-red-50 hover:text-red-600" title="Batalkan pilihan" aria-label="Batalkan pilihan berkas"><x-icon name="x" class="size-4" /></button>
                            </div>
                            <p class="field-error" x-show="problem || error('filepdf')" x-cloak x-text="problem || error('filepdf')"></p>
                        </div>

                        <label class="flex cursor-pointer items-center justify-between gap-4 rounded-xl border border-stone-200 p-4 hover:border-brand-300">
                            <span>
                                <span class="block text-sm font-semibold text-ink-900">Publikasikan di website</span>
                                <span class="block text-xs text-stone-500" x-text="data.publikasi ? 'Fatwa tampil di halaman publik Fatwa MUI.' : 'Fatwa disimpan sebagai draf dan hanya terlihat di panel ini.'"></span>
                            </span>
                            <span class="relative inline-flex shrink-0">
                                <input type="hidden" name="publikasi" value="0">
                                <input id="f-publikasi" type="checkbox" name="publikasi" value="1" x-model="data.publikasi" class="peer sr-only">
                                <span class="h-6 w-11 rounded-full bg-stone-300 transition peer-checked:bg-brand-600 peer-focus-visible:ring-4 peer-focus-visible:ring-brand-500/20"></span>
                                <span class="absolute top-0.5 left-0.5 size-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                            </span>
                        </label>
                    </div>
                    <footer class="flex shrink-0 justify-end gap-2 border-t border-stone-100 bg-stone-50/60 px-6 py-4">
                        <button type="button" @click="close()" class="btn btn-outline">Batal</button>
                        <button type="submit" class="btn btn-primary" :disabled="saving">
                            <x-icon name="loader-circle" class="size-4 animate-spin" x-show="saving" x-cloak />
                            <x-icon name="save" class="size-4" x-show="!saving" />
                            <span x-text="saving ? 'Menyimpan…' : (mode === 'edit' ? 'Simpan Perubahan' : 'Simpan Fatwa')"></span>
                        </button>
                    </footer>
                </form>
            </x-admin.modal>
        </div>

        {{-- Detail & pratinjau PDF --}}
        <div x-data="fatwaViewer">
            <x-admin.modal title="'Detail Fatwa'" icon="scale" size="max-w-6xl">
                <div class="scrollbar-thin flex min-h-0 flex-1 flex-col overflow-y-auto lg:flex-row lg:overflow-hidden">
                    <aside class="scrollbar-thin shrink-0 space-y-5 border-b border-stone-100 p-6 lg:w-[22rem] lg:overflow-y-auto lg:border-r lg:border-b-0">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="badge" :class="statusBadge(doc?.status_fatwa)">
                                    <x-icon name="circle-check-big" class="size-3" x-show="doc?.status_fatwa === 'aktif'" />
                                    <x-icon name="pencil" class="size-3" x-show="doc?.status_fatwa === 'direvisi'" x-cloak />
                                    <x-icon name="history" class="size-3" x-show="doc?.status_fatwa === 'digantikan'" x-cloak />
                                    <span x-text="doc?.status_fatwa_label"></span>
                                </span>
                                <span class="badge" :class="doc?.publikasi ? 'badge-blue' : 'badge-gray'">
                                    <x-icon name="globe" class="size-3" x-show="doc?.publikasi" />
                                    <x-icon name="eye-off" class="size-3" x-show="!doc?.publikasi" x-cloak />
                                    <span x-text="doc?.publikasi ? 'Dipublikasikan' : 'Draf'"></span>
                                </span>
                            </div>
                            <h3 class="mt-3 font-display text-xl leading-snug font-semibold text-ink-900" x-text="doc?.judul"></h3>
                            <p class="mt-2 text-sm leading-relaxed whitespace-pre-line text-stone-600" x-show="doc?.keterangan" x-text="doc?.keterangan"></p>
                        </div>
                        <dl class="divide-y divide-stone-100 rounded-2xl border border-stone-200 text-sm">
                            <div class="flex items-start gap-3 px-4 py-3">
                                <x-icon name="tag" class="mt-0.5 size-4 text-brand-600" />
                                <div><dt class="text-xs text-stone-500">Kategori</dt><dd class="font-semibold text-ink-900" x-text="doc?.kategori_nama || 'Tanpa kategori'"></dd></div>
                            </div>
                            <div class="flex items-start gap-3 px-4 py-3">
                                <x-icon name="eye" class="mt-0.5 size-4 text-brand-600" />
                                <div><dt class="text-xs text-stone-500">Dilihat</dt><dd class="font-semibold text-ink-900" x-text="views(doc?.views) + ' kali'"></dd></div>
                            </div>
                            <div class="flex items-start gap-3 px-4 py-3">
                                <x-icon name="calendar-days" class="mt-0.5 size-4 text-brand-600" />
                                <div><dt class="text-xs text-stone-500">Dibuat</dt><dd class="font-semibold text-ink-900" x-text="MUIAdmin.formatDate(doc?.created_at, { day: 'numeric', month: 'long', year: 'numeric' })"></dd></div>
                            </div>
                            <div class="flex items-start gap-3 px-4 py-3">
                                <x-icon name="calendar-clock" class="mt-0.5 size-4 text-brand-600" />
                                <div><dt class="text-xs text-stone-500">Terakhir diperbarui</dt><dd class="font-semibold text-ink-900" x-text="MUIAdmin.formatDate(doc?.updated_at, { day: 'numeric', month: 'long', year: 'numeric' })"></dd></div>
                            </div>
                        </dl>
                        <button type="button" role="switch" :aria-checked="doc?.publikasi ? 'true' : 'false'" @click="togglePublikasi(doc)" :disabled="!doc || !!busy[doc.id]"
                                class="flex w-full items-center justify-between gap-4 rounded-xl border border-stone-200 p-4 text-left transition hover:border-brand-300 disabled:cursor-wait disabled:opacity-60">
                            <span>
                                <span class="block text-sm font-semibold text-ink-900">Publikasi</span>
                                <span class="block text-xs text-stone-500" x-text="doc?.publikasi ? 'Tampil di halaman publik' : 'Disembunyikan dari halaman publik'"></span>
                            </span>
                            <span class="relative h-6 w-11 shrink-0 rounded-full transition" :class="doc?.publikasi ? 'bg-brand-600' : 'bg-stone-300'">
                                <span class="absolute top-0.5 left-0.5 size-5 rounded-full bg-white shadow transition" :class="doc?.publikasi && 'translate-x-5'"></span>
                            </span>
                        </button>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" @click="edit()" class="btn btn-outline btn-sm"><x-icon name="pencil" class="size-4" /> Ubah fatwa</button>
                            <a x-show="doc?.file_url" :href="doc?.file_url" :download="doc ? downloadName(doc) : null" class="btn btn-primary btn-sm"><x-icon name="download" class="size-4" /> Unduh PDF</a>
                        </div>
                    </aside>

                    <section class="flex min-h-[65vh] flex-1 flex-col bg-stone-100 lg:min-h-[74vh]">
                        <header class="flex items-center gap-3 border-b border-stone-200 bg-sand-100/70 px-4 py-3">
                            <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-red-50 text-red-600 ring-1 ring-red-100"><x-icon name="file-text" class="size-5" /></span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-ink-900">Dokumen PDF fatwa</p>
                                <p class="truncate font-mono text-[11px] text-stone-500" x-text="doc?.file_name ?? 'Belum ada berkas'"></p>
                            </div>
                            <a x-show="doc?.file_url" :href="doc?.file_url" target="_blank" rel="noopener" class="btn btn-ghost btn-sm" title="Buka di tab baru"><x-icon name="external-link" class="size-4" /><span class="hidden sm:inline">Buka</span></a>
                        </header>
                        <div class="relative flex-1">
                            <template x-if="open && doc?.file_url">
                                <div class="absolute inset-0">
                                    <div x-show="!loaded" class="absolute inset-0 grid place-items-center text-sm text-stone-500">
                                        <span class="flex items-center gap-2"><x-icon name="loader-circle" class="size-5 animate-spin text-brand-600" /> Memuat dokumen…</span>
                                    </div>
                                    <iframe :src="doc.file_url + '#view=FitH'" :title="'PDF ' + doc.judul" class="relative size-full" @load="loaded = true"></iframe>
                                </div>
                            </template>
                            <template x-if="open && doc && !doc.file_url">
                                <div class="absolute inset-0 grid place-items-center p-6">
                                    <div class="max-w-sm text-center">
                                        <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-white text-red-500 shadow-sm ring-1 ring-stone-200"><x-icon name="file-x" class="size-7" /></span>
                                        <p class="mt-4 font-semibold text-ink-900">Fatwa ini belum memiliki berkas PDF</p>
                                        <p class="mt-1.5 text-sm text-stone-500">Unggah PDF melalui tombol <b>Ubah fatwa</b>.</p>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <p x-show="doc?.file_url" class="border-t border-stone-200 bg-white px-5 py-3 text-xs leading-relaxed text-stone-500 sm:hidden">Pratinjau PDF mungkin tidak tampil di sebagian peramban ponsel. Gunakan tombol <b>Buka</b> atau <b>Unduh PDF</b>.</p>
                    </section>
                </div>
            </x-admin.modal>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                const slug = (text) => String(text || '').normalize('NFKD').replace(/[\u0300-\u036f]/g, '').replace(/[^A-Za-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 80);
                const short = (text, n = 80) => (String(text || '').length > n ? String(text).slice(0, n).trimEnd() + '…' : String(text || ''));

                /** Data & aksi halaman Fatwa (statistik, toggle publikasi, hapus). */
                Alpine.data('fatwaPage', ({ base, kategoriIds, statusHints }) => ({
                    base,
                    kategoriIds,
                    statusHints,
                    stats: { total: null, publik: null, draft: null },
                    busy: {},
                    init() {
                        this.loadStats();
                        window.addEventListener('table:reload', () => this.loadStats());
                    },
                    async loadStats() {
                        try {
                            // Hitungan publikasi memakai filter bawaan endpoint DataTables (cukup 1 baris).
                            const res = await MUIAdmin.http(`${base}?draw=1&start=0&length=1&filter_publikasi=1`);
                            const total = Number(res.recordsTotal ?? 0);
                            const publik = Number(res.recordsFiltered ?? 0);
                            this.stats = { total, publik, draft: Math.max(0, total - publik) };
                        } catch {
                            /* statistik bersifat pelengkap */
                        }
                    },
                    percent(value) {
                        return this.stats.total ? Math.round((Number(value) / this.stats.total) * 100) : 0;
                    },
                    views: (value) => Number(value || 0).toLocaleString('id-ID'),
                    statusBadge: (status) => ({ aktif: 'badge-green', direvisi: 'badge-gold' })[status] ?? 'badge-gray',
                    downloadName: (row) => `Fatwa-${slug(row.judul) || row.id}.pdf`,
                    async togglePublikasi(row) {
                        if (!row || this.busy[row.id]) return;
                        this.busy[row.id] = true;
                        try {
                            const res = await MUIAdmin.http(`${base}/${row.id}/toggle-publikasi`, { method: 'PATCH' });
                            row.publikasi = res.publikasi ? 1 : 0;
                            MUIAdmin.toast(row.publikasi ? 'Fatwa dipublikasikan ke halaman publik.' : 'Fatwa disembunyikan dari halaman publik.');
                            MUIAdmin.reloadTables();
                        } catch (e) {
                            MUIAdmin.toast(e.message || 'Gagal mengubah status publikasi.', 'error');
                        } finally {
                            delete this.busy[row.id];
                        }
                    },
                    remove(row) {
                        return MUIAdmin.destroy(`${base}/${row.id}`, {
                            title: 'Hapus fatwa?',
                            message: `Fatwa “${short(row.judul)}” beserta berkas PDF-nya akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.`,
                        });
                    },
                }));

                /** Penampil detail & PDF fatwa. */
                Alpine.data('fatwaViewer', () => ({
                    open: false,
                    doc: null,
                    loaded: false,
                    init() {
                        window.addEventListener('fatwa:view', (e) => {
                            this.doc = { ...e.detail };
                            this.loaded = false;
                            this.open = true;
                        });
                    },
                    close() {
                        this.open = false;
                    },
                    edit() {
                        const row = this.doc;
                        this.open = false;
                        this.$dispatch('fatwa:open', row);
                    },
                }));

                /** Area unggah berkas (klik / seret & lepas) dengan validasi jenis & ukuran di sisi klien. */
                Alpine.data('dropFile', ({ field, maxMb, exts }) => ({
                    name: null,
                    size: null,
                    ext: null,
                    url: null,
                    dragging: false,
                    problem: null,
                    human: (bytes) => (bytes >= 1048576 ? `${(bytes / 1048576).toFixed(1).replace('.', ',')} MB` : `${Math.max(1, Math.round(bytes / 1024))} KB`),
                    forget() {
                        if (this.url) URL.revokeObjectURL(this.url);
                        Object.assign(this, { name: null, size: null, ext: null, url: null, dragging: false });
                    },
                    reset() {
                        this.forget();
                        this.problem = null;
                        if (this.$refs.file) this.$refs.file.value = '';
                    },
                    pick(e) {
                        const file = e.target.files?.[0];
                        this.forget();
                        // Tanpa berkas (mis. dialog dibatalkan): pertahankan pesan validasi sebelumnya.
                        if (!file) return;
                        const ext = (file.name.includes('.') ? file.name.split('.').pop() : '').toLowerCase();
                        if (!exts.includes(ext)) {
                            this.reset();
                            this.problem = `Format ${ext ? '.' + ext : 'berkas ini'} tidak didukung. Hanya berkas ${exts.map((x) => x.toUpperCase()).join(', ')} yang diterima.`;
                            return;
                        }
                        if (file.size > maxMb * 1048576) {
                            this.reset();
                            this.problem = `Ukuran berkas ${this.human(file.size)} melebihi batas ${maxMb} MB.`;
                            return;
                        }
                        Object.assign(this, { name: file.name, size: this.human(file.size), ext, url: URL.createObjectURL(file), problem: null });
                        if (this.errors?.[field]) delete this.errors[field];
                    },
                }));
            });
        </script>
    @endpush
</x-layouts.admin>
