@php
    $user = auth()->user();
    $base = $user->isAdmin() ? route('admin.surat.index') : route('operator.surat.index');
    $accept = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'];
@endphp

<x-layouts.admin title="Arsip Surat" header="Kelola arsip surat masuk & keluar MUI Batanghari">
    <div x-data="suratPage(@js($base))">
        <div x-data="serverTable({ url: @js($base), columns: ['id', 'nomor_surat', 'perihal', 'tanggal_surat', 'created_at'], order: [3, 'desc'] })">
            <x-admin.page-header eyebrow="Arsip Digital" title="Arsip Surat" description="Simpan surat masuk & keluar beserta berkasnya (PDF, Word, Excel, atau gambar). Arsip juga tampil di halaman publik Arsip Surat.">
                <x-slot:actions>
                    <a href="{{ route('surat') }}" target="_blank" rel="noopener" class="btn btn-outline"><x-icon name="external-link" class="size-4" /> Halaman publik</a>
                    <button type="button" @click="$dispatch('surat:open')" class="btn btn-primary"><x-icon name="upload" class="size-4" /> Unggah Surat</button>
                </x-slot:actions>
            </x-admin.page-header>

            <div class="mt-6 grid gap-4 sm:grid-cols-3">
                <x-stat-card label="Total arsip surat" icon="folder-archive" bind="meta.stats?.total ?? '–'" note="Seluruh surat terarsip" />
                <x-stat-card label="Diunggah bulan ini" icon="calendar-check" tone="gold" bind="meta.stats?.bulan_ini ?? '–'" note="{{ now()->translatedFormat('F Y') }}" />
                <x-stat-card label="Diunggah oleh Anda" icon="user-check" tone="blue" bind="meta.stats?.saya ?? '–'" note="Akun {{ $user->name }}" />
            </div>

            <x-admin.table class="mt-6" colspan="7" empty="Belum ada arsip surat" empty-icon="folder-archive" search-placeholder="Cari nomor surat atau perihal…">
                <x-slot:head>
                    <th class="w-12">#</th>
                    <x-admin.th col="1" class="hidden sm:table-cell">No. Surat</x-admin.th>
                    <x-admin.th col="2">Perihal</x-admin.th>
                    <x-admin.th col="3">Tgl. Surat</x-admin.th>
                    <th>Berkas</th>
                    <x-admin.th col="4">Diunggah</x-admin.th>
                    <th class="text-right">Aksi</th>
                </x-slot:head>
                <x-slot:row>
                    <tr>
                        <td class="text-stone-400 tabular-nums" x-text="rowNumber(index)"></td>
                        <td class="hidden whitespace-nowrap sm:table-cell">
                            <span x-show="row.nomor_surat" class="inline-flex rounded-md bg-brand-50 px-2 py-1 font-mono text-xs font-semibold text-brand-800 ring-1 ring-brand-600/10" x-text="row.nomor_surat"></span>
                            <span x-show="!row.nomor_surat" class="text-xs text-stone-400 italic">Tanpa nomor</span>
                        </td>
                        <td class="min-w-56">
                            <p x-show="row.nomor_surat" class="mb-1 font-mono text-[11px] font-semibold text-brand-700 sm:hidden" x-text="row.nomor_surat"></p>
                            <button type="button" @click="$dispatch('surat:view', row)" class="line-clamp-2 text-left font-semibold text-ink-900 transition hover:text-brand-700" :title="row.perihal" x-text="row.perihal"></button>
                        </td>
                        <td class="whitespace-nowrap text-stone-600" x-text="MUIAdmin.formatDate(row.tanggal_surat)"></td>
                        <td class="whitespace-nowrap">
                            <div class="flex items-center gap-1" x-show="row.file_url">
                                <button type="button" @click="$dispatch('surat:view', row)" class="inline-flex items-center gap-2 rounded-lg border border-stone-200 bg-white py-1 pr-2.5 pl-1 text-xs font-semibold text-stone-700 transition hover:border-brand-400 hover:text-brand-700" :title="'Pratinjau berkas ' + row.file_surat" aria-label="Pratinjau berkas surat">
                                    <span class="grid size-6 place-items-center rounded-md ring-1" :class="fileTone(row.file_ext)">
                                        <x-icon name="file-text" class="size-3.5" x-show="fileKind(row.file_ext) === 'pdf'" />
                                        <x-icon name="file-type" class="size-3.5" x-show="fileKind(row.file_ext) === 'word'" x-cloak />
                                        <x-icon name="file-spreadsheet" class="size-3.5" x-show="fileKind(row.file_ext) === 'excel'" x-cloak />
                                        <x-icon name="file-image" class="size-3.5" x-show="fileKind(row.file_ext) === 'image'" x-cloak />
                                        <x-icon name="file" class="size-3.5" x-show="fileKind(row.file_ext) === 'other'" x-cloak />
                                    </span>
                                    <span x-text="fileLabel(row.file_ext)"></span>
                                </button>
                                <a :href="row.file_url" :download="downloadName(row)" class="grid size-8 place-items-center rounded-lg text-stone-500 transition hover:bg-brand-50 hover:text-brand-700" title="Unduh berkas" aria-label="Unduh berkas surat"><x-icon name="download" class="size-4" /></a>
                            </div>
                            <span x-show="!row.file_url" class="text-xs text-stone-400 italic">Tidak ada berkas</span>
                        </td>
                        <td class="whitespace-nowrap">
                            <div class="flex items-center gap-2.5">
                                <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-brand-700 text-[11px] font-bold text-gold-300" x-text="initials(row.pengunggah?.name)" aria-hidden="true"></span>
                                <div class="leading-tight">
                                    <p class="text-[13px] font-semibold text-ink-900" x-text="row.pengunggah?.name ?? 'Tidak diketahui'"></p>
                                    <p class="mt-0.5 text-xs text-stone-500" :title="'Pukul ' + clock(row.created_at)" x-text="MUIAdmin.formatDate(row.created_at)"></p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="flex justify-end gap-1.5">
                                <button type="button" @click="$dispatch('surat:view', row)" class="grid size-8 place-items-center rounded-lg text-stone-500 hover:bg-sky-50 hover:text-sky-700" title="Detail & pratinjau" aria-label="Lihat detail surat"><x-icon name="eye" class="size-4" /></button>
                                <button type="button" @click="$dispatch('surat:open', row)" class="grid size-8 place-items-center rounded-lg text-stone-500 hover:bg-brand-50 hover:text-brand-700" title="Ubah" aria-label="Ubah surat"><x-icon name="pencil" class="size-4" /></button>
                                <button type="button" @click="remove(row)" class="grid size-8 place-items-center rounded-lg text-stone-500 hover:bg-red-50 hover:text-red-600" title="Hapus" aria-label="Hapus surat"><x-icon name="trash-2" class="size-4" /></button>
                            </div>
                        </td>
                    </tr>
                </x-slot:row>
            </x-admin.table>
        </div>

        {{-- Formulir unggah / ubah --}}
        <div x-data="crudForm({ name: 'surat', storeUrl: @js($base), updateUrl: @js($base.'/:id'), defaults: { nomor_surat: '', tanggal_surat: '', perihal: '' } })">
            <x-admin.modal title="mode === 'edit' ? 'Ubah Arsip Surat' : 'Unggah Surat'" icon="folder-archive">
                <form x-ref="form" @submit.prevent="submit()" class="flex min-h-0 flex-1 flex-col">
                    <div class="scrollbar-thin flex-1 space-y-5 overflow-y-auto p-6">
                        <div class="grid gap-5 sm:grid-cols-5">
                            <div class="sm:col-span-3">
                                <label for="s-nomor" class="label">Nomor surat <span class="font-normal text-stone-400">(opsional)</span></label>
                                <input id="s-nomor" x-ref="first" name="nomor_surat" x-model="data.nomor_surat" type="text" maxlength="100" placeholder="cth: 001/MUI-BTH/IX/2026" class="input font-mono" :class="error('nomor_surat') && 'input-error'">
                                <p class="field-error" x-show="error('nomor_surat')" x-text="error('nomor_surat')"></p>
                            </div>
                            <div class="sm:col-span-2">
                                <label for="s-tanggal" class="label">Tanggal surat <span class="text-red-500">*</span></label>
                                <input id="s-tanggal" name="tanggal_surat" x-model="data.tanggal_surat" type="date" required class="input" :class="error('tanggal_surat') && 'input-error'">
                                <p class="field-error" x-show="error('tanggal_surat')" x-text="error('tanggal_surat')"></p>
                            </div>
                        </div>

                        <div>
                            <label for="s-perihal" class="label">Perihal <span class="text-red-500">*</span></label>
                            <textarea id="s-perihal" name="perihal" x-model="data.perihal" rows="3" maxlength="500" required placeholder="Tuliskan perihal atau ringkasan isi surat…" class="input resize-none" :class="error('perihal') && 'input-error'"></textarea>
                            <div class="mt-1 flex items-start gap-3">
                                <p class="field-error mt-0" x-show="error('perihal')" x-text="error('perihal')"></p>
                                <p class="ml-auto shrink-0 text-xs text-stone-400 tabular-nums"><span x-text="(data.perihal || '').length"></span>/500</p>
                            </div>
                        </div>

                        <div x-data="dropFile({ field: 'file_surat', maxMb: 5, exts: @js($accept) })" @surat:open.window="reset()">
                            <p class="label">
                                Berkas surat
                                <span x-show="mode === 'create'" class="text-red-500">*</span>
                                <span x-show="mode === 'edit'" x-cloak class="font-normal text-stone-400">(biarkan kosong jika tidak diganti)</span>
                            </p>

                            <div x-show="mode === 'edit' && data.file_url && !name" x-cloak class="mb-3 flex items-center gap-3 rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-3">
                                <span class="grid size-10 shrink-0 place-items-center rounded-lg ring-1" :class="fileTone(data.file_ext)">
                                    <x-icon name="file-text" class="size-5" x-show="fileKind(data.file_ext) === 'pdf'" />
                                    <x-icon name="file-type" class="size-5" x-show="fileKind(data.file_ext) === 'word'" x-cloak />
                                    <x-icon name="file-spreadsheet" class="size-5" x-show="fileKind(data.file_ext) === 'excel'" x-cloak />
                                    <x-icon name="file-image" class="size-5" x-show="fileKind(data.file_ext) === 'image'" x-cloak />
                                    <x-icon name="file" class="size-5" x-show="fileKind(data.file_ext) === 'other'" x-cloak />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-ink-900" x-text="data.file_surat"></p>
                                    <p class="text-xs text-stone-500">Berkas saat ini · <span x-text="fileLabel(data.file_ext)"></span></p>
                                </div>
                                <a :href="data.file_url" target="_blank" rel="noopener" class="btn btn-ghost btn-sm"><x-icon name="external-link" class="size-4" /><span class="hidden sm:inline">Lihat</span></a>
                            </div>

                            <label for="s-file" x-show="!name" class="relative flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed px-6 py-8 text-center transition focus-within:ring-4 focus-within:ring-brand-500/15"
                                   :class="dragging ? 'border-brand-500 bg-brand-50' : ((problem || error('file_surat')) ? 'border-red-300 bg-red-50/40' : 'border-stone-300 bg-sand-50 hover:border-brand-400 hover:bg-brand-50/50')"
                                   @dragenter="dragging = true" @dragover="dragging = true" @dragleave="dragging = false" @drop="dragging = false">
                                <input id="s-file" x-ref="file" type="file" name="file_surat" accept="{{ collect($accept)->map(fn ($e) => '.'.$e)->implode(',') }}" :required="mode === 'create'" @change="pick($event)" class="absolute inset-0 size-full cursor-pointer opacity-0" aria-describedby="s-file-hint">
                                <span class="grid size-12 place-items-center rounded-2xl bg-white text-brand-600 shadow-sm ring-1 ring-stone-200 transition" :class="dragging && 'scale-110'"><x-icon name="cloud-upload" class="size-6" /></span>
                                <p class="mt-3 text-sm font-semibold text-ink-900"><span class="text-brand-700 underline decoration-gold-400 decoration-2 underline-offset-4">Pilih berkas</span> atau seret ke sini</p>
                                <p id="s-file-hint" class="mt-1 text-xs text-stone-500">PDF, DOC, DOCX, XLS, XLSX, JPG, PNG · maks. 5 MB</p>
                            </label>

                            <div x-show="name" x-cloak class="flex items-center gap-3 rounded-xl border border-brand-200 bg-brand-50/60 px-3.5 py-3">
                                <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-white text-brand-700 ring-1 ring-brand-100"><x-icon name="file-check" class="size-5" /></span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-ink-900" x-text="name"></p>
                                    <p class="text-xs text-stone-500"><span x-text="size"></span> · <span x-text="mode === 'edit' ? 'akan menggantikan berkas lama' : 'siap diunggah'"></span></p>
                                </div>
                                <a x-show="previewable" :href="url" target="_blank" rel="noopener" class="btn btn-ghost btn-sm"><x-icon name="eye" class="size-4" /><span class="hidden sm:inline">Pratinjau</span></a>
                                <button type="button" @click="reset()" class="grid size-8 shrink-0 place-items-center rounded-lg text-stone-500 hover:bg-red-50 hover:text-red-600" title="Batalkan pilihan" aria-label="Batalkan pilihan berkas"><x-icon name="x" class="size-4" /></button>
                            </div>
                            <p class="field-error" x-show="problem || error('file_surat')" x-cloak x-text="problem || error('file_surat')"></p>
                        </div>
                    </div>
                    <footer class="flex shrink-0 justify-end gap-2 border-t border-stone-100 bg-stone-50/60 px-6 py-4">
                        <button type="button" @click="close()" class="btn btn-outline">Batal</button>
                        <button type="submit" class="btn btn-primary" :disabled="saving">
                            <x-icon name="loader-circle" class="size-4 animate-spin" x-show="saving" x-cloak />
                            <x-icon name="save" class="size-4" x-show="!saving" />
                            <span x-text="saving ? 'Menyimpan…' : (mode === 'edit' ? 'Simpan Perubahan' : 'Unggah Surat')"></span>
                        </button>
                    </footer>
                </form>
            </x-admin.modal>
        </div>

        {{-- Detail & pratinjau berkas --}}
        <div x-data="suratViewer">
            <x-admin.modal title="'Detail Arsip Surat'" icon="file-text" size="max-w-6xl">
                <div class="scrollbar-thin flex min-h-0 flex-1 flex-col overflow-y-auto lg:flex-row lg:overflow-hidden">
                    <aside class="scrollbar-thin shrink-0 space-y-5 border-b border-stone-100 p-6 lg:w-80 lg:overflow-y-auto lg:border-r lg:border-b-0">
                        <div>
                            <span x-show="doc?.nomor_surat" class="inline-flex rounded-md bg-brand-50 px-2 py-1 font-mono text-xs font-semibold text-brand-800 ring-1 ring-brand-600/10" x-text="doc?.nomor_surat"></span>
                            <span x-show="!doc?.nomor_surat" class="badge badge-gray">Tanpa nomor surat</span>
                            <h3 class="mt-3 font-display text-xl leading-snug font-semibold text-ink-900" x-text="doc?.perihal"></h3>
                        </div>
                        <dl class="divide-y divide-stone-100 rounded-2xl border border-stone-200 text-sm">
                            <div class="flex items-start gap-3 px-4 py-3">
                                <x-icon name="calendar-days" class="mt-0.5 size-4 text-brand-600" />
                                <div><dt class="text-xs text-stone-500">Tanggal surat</dt><dd class="font-semibold text-ink-900" x-text="MUIAdmin.formatDate(doc?.tanggal_surat, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })"></dd></div>
                            </div>
                            <div class="flex items-start gap-3 px-4 py-3">
                                <x-icon name="user-round" class="mt-0.5 size-4 text-brand-600" />
                                <div><dt class="text-xs text-stone-500">Diunggah oleh</dt><dd class="font-semibold text-ink-900" x-text="doc?.pengunggah?.name ?? 'Tidak diketahui'"></dd></div>
                            </div>
                            <div class="flex items-start gap-3 px-4 py-3">
                                <x-icon name="clock" class="mt-0.5 size-4 text-brand-600" />
                                <div><dt class="text-xs text-stone-500">Tanggal unggah</dt><dd class="font-semibold text-ink-900"><span x-text="MUIAdmin.formatDate(doc?.created_at, { day: 'numeric', month: 'long', year: 'numeric' })"></span> · <span x-text="clock(doc?.created_at)"></span></dd></div>
                            </div>
                            <div class="flex items-start gap-3 px-4 py-3">
                                <x-icon name="paperclip" class="mt-0.5 size-4 text-brand-600" />
                                <div class="min-w-0"><dt class="text-xs text-stone-500">Berkas</dt><dd class="truncate font-semibold text-ink-900" :title="doc?.file_surat" x-text="doc?.file_surat ? fileLabel(doc.file_ext) + ' · ' + doc.file_surat : 'Tidak ada berkas'"></dd></div>
                            </div>
                        </dl>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" @click="edit()" class="btn btn-outline btn-sm"><x-icon name="pencil" class="size-4" /> Ubah data</button>
                            <a x-show="doc?.file_url" :href="doc?.file_url" :download="doc ? downloadName(doc) : null" class="btn btn-primary btn-sm"><x-icon name="download" class="size-4" /> Unduh berkas</a>
                        </div>
                    </aside>

                    <section class="flex min-h-[65vh] flex-1 flex-col bg-stone-100 lg:min-h-[72vh]">
                        <header class="flex items-center gap-3 border-b border-stone-200 bg-sand-100/70 px-4 py-3">
                            <span class="grid size-9 shrink-0 place-items-center rounded-lg ring-1" :class="fileTone(doc?.file_ext)">
                                <x-icon name="file-text" class="size-5" x-show="kind === 'pdf'" />
                                <x-icon name="file-type" class="size-5" x-show="kind === 'word'" x-cloak />
                                <x-icon name="file-spreadsheet" class="size-5" x-show="kind === 'excel'" x-cloak />
                                <x-icon name="file-image" class="size-5" x-show="kind === 'image'" x-cloak />
                                <x-icon name="file" class="size-5" x-show="kind === 'other'" x-cloak />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-ink-900">Pratinjau berkas</p>
                                <p class="truncate font-mono text-[11px] text-stone-500" x-text="doc?.file_surat ?? '—'"></p>
                            </div>
                            <a x-show="doc?.file_url" :href="doc?.file_url" target="_blank" rel="noopener" class="btn btn-ghost btn-sm" title="Buka di tab baru"><x-icon name="external-link" class="size-4" /><span class="hidden sm:inline">Buka</span></a>
                        </header>
                        <div class="relative flex-1">
                            <template x-if="open && doc?.file_url && kind === 'pdf'">
                                <div class="absolute inset-0">
                                    <div x-show="!loaded" class="absolute inset-0 grid place-items-center text-sm text-stone-500">
                                        <span class="flex items-center gap-2"><x-icon name="loader-circle" class="size-5 animate-spin text-brand-600" /> Memuat dokumen…</span>
                                    </div>
                                    <iframe :src="doc.file_url + '#view=FitH'" :title="'Berkas surat ' + (doc.nomor_surat || doc.perihal)" class="relative size-full" @load="loaded = true"></iframe>
                                </div>
                            </template>
                            <template x-if="open && doc?.file_url && kind === 'image'">
                                <div class="absolute inset-0 grid place-items-center overflow-auto p-6">
                                    <img :src="doc.file_url" :alt="'Berkas surat ' + (doc.nomor_surat || doc.perihal)" class="max-h-full max-w-full rounded-xl bg-white object-contain shadow-[var(--shadow-soft)]">
                                </div>
                            </template>
                            <template x-if="open && doc && (!doc.file_url || ['word', 'excel', 'other'].includes(kind))">
                                <div class="absolute inset-0 grid place-items-center p-6">
                                    <div class="max-w-sm text-center">
                                        <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-white text-brand-600 shadow-sm ring-1 ring-stone-200"><x-icon name="file-search" class="size-7" /></span>
                                        <p class="mt-4 font-semibold text-ink-900" x-text="doc.file_url ? 'Pratinjau tidak tersedia untuk berkas ' + fileLabel(doc.file_ext) : 'Surat ini belum memiliki berkas'"></p>
                                        <p class="mt-1.5 text-sm text-stone-500" x-text="doc.file_url ? 'Unduh berkas untuk membukanya dengan aplikasi Office di perangkat Anda.' : 'Unggah berkas melalui tombol Ubah data.'"></p>
                                        <a x-show="doc.file_url" :href="doc.file_url" :download="downloadName(doc)" class="btn btn-primary btn-sm mt-5"><x-icon name="download" class="size-4" /> Unduh berkas</a>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <p x-show="kind === 'pdf'" class="border-t border-stone-200 bg-white px-5 py-3 text-xs leading-relaxed text-stone-500 sm:hidden">Pratinjau PDF mungkin tidak tampil di sebagian peramban ponsel. Gunakan tombol <b>Buka</b> atau <b>Unduh berkas</b>.</p>
                    </section>
                </div>
            </x-admin.modal>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                const KIND = { pdf: 'pdf', doc: 'word', docx: 'word', xls: 'excel', xlsx: 'excel', jpg: 'image', jpeg: 'image', png: 'image' };
                const LABEL = { pdf: 'PDF', word: 'Word', excel: 'Excel', image: 'Gambar', other: 'Berkas' };
                const TONE = {
                    pdf: 'bg-red-50 text-red-600 ring-red-100',
                    word: 'bg-sky-50 text-sky-700 ring-sky-100',
                    excel: 'bg-emerald-50 text-emerald-700 ring-emerald-100',
                    image: 'bg-violet-50 text-violet-700 ring-violet-100',
                    other: 'bg-stone-100 text-stone-600 ring-stone-200',
                };
                const slug = (text) => String(text || '').normalize('NFKD').replace(/[\u0300-\u036f]/g, '').replace(/[^A-Za-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 80);

                /** Utilitas halaman arsip surat (dipakai tabel, formulir & penampil). */
                Alpine.data('suratPage', (base) => ({
                    base,
                    fileKind: (ext) => KIND[String(ext || '').toLowerCase()] ?? 'other',
                    fileLabel(ext) { return LABEL[this.fileKind(ext)]; },
                    fileTone(ext) { return TONE[this.fileKind(ext)]; },
                    downloadName: (row) => `Surat-${slug(row.nomor_surat || row.perihal) || row.id}.${String(row.file_ext || 'pdf').toLowerCase()}`,
                    initials: (name) => String(name || '?').trim().split(/\s+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase(),
                    clock(value) {
                        const d = new Date(String(value || '').replace(' ', 'T'));
                        return Number.isNaN(d.getTime()) ? '' : d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }).replace('.', ':');
                    },
                    remove(row) {
                        const label = row.nomor_surat ? `nomor ${row.nomor_surat}` : `“${String(row.perihal).slice(0, 80)}”`;
                        return MUIAdmin.destroy(`${base}/${row.id}`, {
                            title: 'Hapus arsip surat?',
                            message: `Surat ${label} beserta berkas terlampirnya akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.`,
                        });
                    },
                }));

                /** Penampil detail & pratinjau berkas surat. */
                Alpine.data('suratViewer', () => ({
                    open: false,
                    doc: null,
                    loaded: false,
                    init() {
                        window.addEventListener('surat:view', (e) => {
                            this.doc = { ...e.detail };
                            this.loaded = false;
                            this.open = true;
                        });
                    },
                    get kind() {
                        return this.doc ? this.fileKind(this.doc.file_ext) : 'other';
                    },
                    close() {
                        this.open = false;
                    },
                    edit() {
                        const row = this.doc;
                        this.open = false;
                        this.$dispatch('surat:open', row);
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
                    get previewable() {
                        return ['pdf', 'jpg', 'jpeg', 'png'].includes(this.ext);
                    },
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
                            this.problem = `Format ${ext ? '.' + ext : 'berkas ini'} tidak didukung. Gunakan ${exts.map((x) => x.toUpperCase()).join(', ')}.`;
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
