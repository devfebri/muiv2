@php
    $user = auth()->user();
    $p = $user->isAdmin() ? 'admin' : 'operator';
    $base = route($p.'.berita.index');
    $warna = $kategoriList->mapWithKeys(fn ($k) => is_object($k) ? [$k->nama => $k->warna] : [$k => null]);
@endphp

<x-layouts.admin title="Berita & Artikel" header="Kelola berita, artikel, dan kabar kegiatan untuk website">
    <div x-data="beritaPage({ base: @js($base), uploads: @js(asset('uploads/berita')), publik: @js(url('berita')), warna: @js($warna) })">
        <div x-data="serverTable({ url: @js($base), columns: ['id', 'judul', 'kategori', 'status', 'published_at', 'penulis', 'created_at'], order: [6, 'desc'], filters: { filter_status: '', filter_kategori: '' } })">
            <x-admin.page-header eyebrow="Konten Website" title="Berita & Artikel" description="Tulis, terbitkan, dan arsipkan berita. Hanya berita berstatus dipublikasi yang tampil di website.">
                <x-slot:actions>
                    <a href="{{ route('berita.list') }}" target="_blank" rel="noopener" class="btn btn-outline"><x-icon name="external-link" class="size-4" /> Halaman Berita</a>
                    <a href="{{ route($p.'.berita.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Tulis Berita</a>
                </x-slot:actions>
            </x-admin.page-header>

            <div class="mt-6 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
                <x-stat-card label="Total" icon="newspaper" bind="meta.stats?.total ?? '–'" />
                <x-stat-card label="Dipublikasi" icon="circle-check-big" tone="blue" bind="meta.stats?.published ?? '–'" />
                <x-stat-card label="Draft" icon="file-pen-line" tone="gold" bind="meta.stats?.draft ?? '–'" />
                <x-stat-card label="Diarsipkan" icon="archive" tone="stone" bind="meta.stats?.archived ?? '–'" />
            </div>

            <x-admin.table class="mt-6 @container" colspan="7" empty="Belum ada berita" empty-icon="newspaper" search-placeholder="Cari judul, kategori, atau status…">
                <x-slot:filters>
                    <select x-model="filters.filter_status" class="input w-auto" aria-label="Filter status">
                        <option value="">Semua status</option>
                        <option value="published">Dipublikasi</option>
                        <option value="draft">Draft</option>
                        <option value="archived">Diarsipkan</option>
                    </select>
                    <select x-model="filters.filter_kategori" class="input w-auto max-w-52" aria-label="Filter kategori">
                        <option value="">Semua kategori</option>
                        @foreach ($warna->keys() as $nama)
                            <option value="{{ $nama }}">{{ $nama }}</option>
                        @endforeach
                    </select>
                </x-slot:filters>
                <x-slot:actions>
                    <button type="button" x-show="search || filters.filter_status || filters.filter_kategori" x-cloak @click="reset()" class="btn btn-ghost btn-sm" title="Hapus pencarian & filter">
                        <x-icon name="rotate-ccw" class="size-4" /> Reset
                    </button>
                </x-slot:actions>
                <x-slot:head>
                    <th class="hidden w-10 px-3! @min-[62rem]:table-cell">#</th>
                    <x-admin.th col="1">Berita</x-admin.th>
                    <x-admin.th col="2" class="hidden @min-[62rem]:table-cell">Kategori</x-admin.th>
                    <x-admin.th col="3" class="hidden @min-[62rem]:table-cell">Status</x-admin.th>
                    <th class="hidden @min-[62rem]:table-cell">Penulis</th>
                    <x-admin.th col="6" class="hidden @min-[62rem]:table-cell">Dibuat</x-admin.th>
                    <th class="hidden pr-3! text-right @min-[62rem]:table-cell">Aksi</th>
                </x-slot:head>
                <x-slot:row>
                    <tr>
                        <td class="hidden px-3! text-stone-400 tabular-nums @min-[62rem]:table-cell" x-text="rowNumber(index)"></td>
                        <td class="@min-[62rem]:min-w-64">
                            <div class="flex items-start gap-3">
                                <button type="button" @click="$dispatch('berita:detail', row)" class="relative h-12 w-16 shrink-0 overflow-hidden rounded-lg bg-brand-900 ring-1 ring-stone-200/80 @xl:h-16 @xl:w-24 @min-[62rem]:h-12 @min-[62rem]:w-16" :aria-label="'Pratinjau ' + row.judul">
                                    <template x-if="row.gambar">
                                        <img :src="imageUrl(row.gambar)" alt="" loading="lazy" class="size-full object-cover transition duration-300 hover:scale-105">
                                    </template>
                                    <template x-if="!row.gambar">
                                        <span class="bg-gradient-brand grid size-full place-items-center text-gold-300"><x-icon name="newspaper" class="size-5" /></span>
                                    </template>
                                </button>
                                <div class="min-w-0 flex-1 @min-[62rem]:max-w-md">
                                    <button type="button" @click="$dispatch('berita:detail', row)" class="line-clamp-2 text-left leading-snug font-semibold text-ink-900 transition hover:text-brand-700" x-text="row.judul"></button>
                                    <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs text-stone-500">
                                        <span class="inline-flex items-center gap-1" title="Tanggal terbit">
                                            <x-icon name="calendar-days" class="size-3.5 text-gold-500" />
                                            <span x-text="row.published_at ? MUIAdmin.formatDate(row.published_at) : 'Belum terbit'"></span>
                                        </span>
                                        <span class="inline-flex items-center gap-1" title="Jumlah dibaca"><x-icon name="eye" class="size-3.5" /> <span x-text="(row.views ?? 0).toLocaleString('id-ID')"></span></span>
                                    </p>
                                    {{-- Ringkas untuk layar kecil: kolom lain disembunyikan --}}
                                    <div class="mt-2 flex flex-wrap items-center gap-1.5 @min-[62rem]:hidden">
                                        <span class="badge" :class="statusOf(row.status).badge">
                                            <span class="size-1.5 rounded-full bg-current"></span>
                                            <span x-text="statusOf(row.status).label"></span>
                                        </span>
                                        <span class="badge badge-gray">
                                            <span class="size-1.5 rounded-full" :style="`background:${warna[row.kategori] || '#c9951f'}`"></span>
                                            <span x-text="row.kategori || '—'"></span>
                                        </span>
                                    </div>
                                    <div class="mt-1.5 flex items-center justify-between gap-2 @min-[62rem]:hidden">
                                        <div class="min-w-0 text-[11px] leading-tight text-stone-500">
                                            <p class="truncate font-semibold text-stone-600" x-text="row.penulis?.name || '—'"></p>
                                            <p x-text="'Dibuat ' + MUIAdmin.formatDate(row.created_at)"></p>
                                        </div>
                                        <div class="-mr-1.5 flex shrink-0 items-center">
                                            <a x-show="row.status === 'published'" :href="publicUrl(row)" target="_blank" rel="noopener" class="grid size-8 place-items-center rounded-lg text-stone-500 hover:bg-gold-50 hover:text-gold-700" aria-label="Lihat berita di website"><x-icon name="external-link" class="size-4" /></a>
                                            <a :href="editUrl(row)" class="grid size-8 place-items-center rounded-lg text-stone-500 hover:bg-brand-50 hover:text-brand-700" aria-label="Ubah berita"><x-icon name="pencil" class="size-4" /></a>
                                            <button type="button" @click="hapus(row)" class="grid size-8 place-items-center rounded-lg text-stone-500 hover:bg-red-50 hover:text-red-600" aria-label="Hapus berita"><x-icon name="trash-2" class="size-4" /></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="hidden @min-[62rem]:table-cell">
                            <span class="inline-flex items-center gap-2 font-medium whitespace-nowrap text-stone-700">
                                <span class="size-2.5 shrink-0 rounded-full ring-4 ring-stone-100" :style="`background:${warna[row.kategori] || '#c9951f'}`"></span>
                                <span x-text="row.kategori || '—'"></span>
                            </span>
                        </td>
                        <td class="hidden @min-[62rem]:table-cell">
                            <span class="badge" :class="statusOf(row.status).badge">
                                <span class="size-1.5 rounded-full bg-current"></span>
                                <span x-text="statusOf(row.status).label"></span>
                            </span>
                        </td>
                        <td class="hidden @min-[62rem]:table-cell">
                            <div class="flex items-center gap-2 whitespace-nowrap">
                                <span class="grid size-6 shrink-0 place-items-center rounded-md bg-brand-50 text-[10px] font-bold text-brand-700 ring-1 ring-brand-100" x-text="initials(row.penulis?.name)"></span>
                                <span class="text-stone-700" x-text="row.penulis?.name || '—'"></span>
                            </div>
                        </td>
                        <td class="hidden whitespace-nowrap text-stone-500 @min-[62rem]:table-cell" x-text="MUIAdmin.formatDate(row.created_at)"></td>
                        <td class="hidden pr-3! @min-[62rem]:table-cell">
                            <div class="flex justify-end gap-0.5">
                                <button type="button" @click="$dispatch('berita:detail', row)" class="grid size-8 place-items-center rounded-lg text-stone-500 hover:bg-sky-50 hover:text-sky-700" title="Pratinjau" aria-label="Pratinjau berita"><x-icon name="eye" class="size-4" /></button>
                                <a x-show="row.status === 'published'" :href="publicUrl(row)" target="_blank" rel="noopener" class="grid size-8 place-items-center rounded-lg text-stone-500 hover:bg-gold-50 hover:text-gold-700" title="Lihat di website" aria-label="Lihat berita di website"><x-icon name="external-link" class="size-4" /></a>
                                <a :href="editUrl(row)" class="grid size-8 place-items-center rounded-lg text-stone-500 hover:bg-brand-50 hover:text-brand-700" title="Ubah" aria-label="Ubah berita"><x-icon name="pencil" class="size-4" /></a>
                                <button type="button" @click="hapus(row)" class="grid size-8 place-items-center rounded-lg text-stone-500 hover:bg-red-50 hover:text-red-600" title="Hapus" aria-label="Hapus berita"><x-icon name="trash-2" class="size-4" /></button>
                            </div>
                        </td>
                    </tr>
                </x-slot:row>
            </x-admin.table>
        </div>

        {{-- Pratinjau detail berita --}}
        <div x-data="beritaDetail">
            <x-admin.modal title="'Pratinjau Berita'" icon="newspaper" size="max-w-3xl">
                <div class="scrollbar-thin min-h-0 flex-1 overflow-y-auto">
                    <template x-if="item">
                        <article>
                            <div class="relative aspect-[21/9] overflow-hidden bg-brand-900">
                                <template x-if="item.gambar">
                                    <img :src="imageUrl(item.gambar)" :alt="item.judul" class="absolute inset-0 size-full object-cover">
                                </template>
                                <template x-if="!item.gambar">
                                    <div class="bg-gradient-brand absolute inset-0 grid place-items-center">
                                        <div class="pattern-islamic absolute inset-0"></div>
                                        <span class="relative grid size-14 place-items-center rounded-2xl bg-white/10 text-gold-300 ring-1 ring-white/15"><x-icon name="image" class="size-6" /></span>
                                    </div>
                                </template>
                                <div class="absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-black/50 to-transparent"></div>
                                <div class="absolute bottom-4 left-6 flex flex-wrap gap-2">
                                    <span class="badge bg-white/95 text-brand-800 shadow-sm" x-text="item.kategori"></span>
                                    <span class="badge shadow-sm" :class="statusOf(item.status).badge">
                                        <span class="size-1.5 rounded-full bg-current"></span>
                                        <span x-text="statusOf(item.status).label"></span>
                                    </span>
                                </div>
                            </div>
                            <div class="p-6 sm:p-8">
                                <h3 class="font-display text-2xl leading-snug font-semibold text-ink-900 sm:text-[28px]" x-text="item.judul"></h3>
                                <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-xs text-stone-500">
                                    <span class="flex items-center gap-1.5"><x-icon name="user-round" class="size-3.5 text-gold-500" /> <span x-text="item.penulis?.name || '—'"></span></span>
                                    <span class="flex items-center gap-1.5"><x-icon name="calendar-days" class="size-3.5 text-gold-500" /> Dibuat <span x-text="MUIAdmin.formatDate(item.created_at, { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' })"></span></span>
                                    <span class="flex items-center gap-1.5"><x-icon name="send" class="size-3.5 text-gold-500" /> Terbit <span x-text="item.published_at ? MUIAdmin.formatDate(item.published_at, { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—'"></span></span>
                                    <span class="flex items-center gap-1.5"><x-icon name="clock" class="size-3.5 text-gold-500" /> <span x-text="readTime(item.isi) + ' menit baca'"></span></span>
                                    <span class="flex items-center gap-1.5"><x-icon name="eye" class="size-3.5 text-gold-500" /> <span x-text="(item.views ?? 0).toLocaleString('id-ID') + ' kali dibaca'"></span></span>
                                </div>
                                <div class="prose-mui mt-6 border-t border-stone-100 pt-6 text-[15px]" x-html="safeHtml(item.isi)"></div>
                            </div>
                        </article>
                    </template>
                </div>
                <footer class="flex shrink-0 flex-wrap items-center justify-end gap-2 border-t border-stone-100 bg-stone-50/60 px-6 py-4">
                    <button type="button" @click="close()" class="btn btn-ghost mr-auto">Tutup</button>
                    <a x-show="item?.status === 'published'" :href="item ? publicUrl(item) : '#'" target="_blank" rel="noopener" class="btn btn-outline"><x-icon name="external-link" class="size-4" /> Lihat di website</a>
                    <a :href="item ? editUrl(item) : '#'" class="btn btn-primary"><x-icon name="pencil" class="size-4" /> Ubah Berita</a>
                </footer>
            </x-admin.modal>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                const STATUS = {
                    published: { label: 'Dipublikasi', badge: 'badge-green' },
                    draft: { label: 'Draft', badge: 'badge-gold' },
                    archived: { label: 'Diarsipkan', badge: 'badge-gray' },
                };

                // Pembantu tampilan untuk tabel & pratinjau (cakupan induk; dipakai komponen di dalamnya).
                Alpine.data('beritaPage', (cfg) => ({
                    warna: cfg.warna || {},
                    imageUrl(gambar) {
                        return gambar ? `${cfg.uploads}/${String(gambar).split('/').pop()}` : null;
                    },
                    publicUrl(row) {
                        return `${cfg.publik}/${encodeURIComponent(row.slug || row.id)}`;
                    },
                    editUrl(row) {
                        return `${cfg.base}/${row.id}/edit`;
                    },
                    statusOf(status) {
                        return STATUS[status] ?? { label: status || '—', badge: 'badge-gray' };
                    },
                    initials(name) {
                        return (name || '?').trim().split(/\s+/).slice(0, 2).map((w) => w.charAt(0).toUpperCase()).join('');
                    },
                    readTime(html) {
                        const text = new DOMParser().parseFromString(html || '', 'text/html').body.textContent || '';
                        const words = text.trim().split(/\s+/).filter(Boolean).length;
                        return Math.max(1, Math.ceil(words / 200));
                    },
                    hapus(row) {
                        MUIAdmin.destroy(`${cfg.base}/${row.id}`, {
                            title: 'Hapus berita?',
                            message: `Berita “${row.judul}” beserta gambar utamanya akan dihapus permanen dan tidak dapat dikembalikan.`,
                        });
                    },
                }));

                Alpine.data('beritaDetail', () => ({
                    open: false,
                    item: null,
                    init() {
                        window.addEventListener('berita:detail', (e) => {
                            this.item = JSON.parse(JSON.stringify(e.detail ?? null));
                            this.open = !!this.item;
                        });
                    },
                    close() {
                        this.open = false;
                    },
                    // Isi berita ditulis staf; tetap bersihkan skrip & atribut aktif sebelum disisipkan.
                    safeHtml(html) {
                        if (!html) return '<p class="text-stone-400 italic">Tidak ada konten.</p>';
                        const doc = new DOMParser().parseFromString(String(html), 'text/html');
                        doc.body.querySelectorAll('script, style, link, meta, object, embed, form').forEach((el) => el.remove());
                        doc.body.querySelectorAll('*').forEach((el) => {
                            [...el.attributes].forEach((attr) => {
                                const name = attr.name.toLowerCase();
                                const unsafeUrl = ['href', 'src', 'action', 'formaction'].includes(name) && /^\s*javascript:/i.test(attr.value);
                                if (/^(on|x-|@|:)/.test(name) || unsafeUrl) el.removeAttribute(attr.name);
                            });
                        });
                        if (!/<[a-z][\s\S]*>/i.test(String(html))) {
                            return String(html).split(/\n{2,}/).map((p) => `<p>${MUIAdmin.esc(p).replace(/\n/g, '<br>')}</p>`).join('');
                        }
                        return doc.body.innerHTML;
                    },
                }));
            });
        </script>
    @endpush
</x-layouts.admin>
