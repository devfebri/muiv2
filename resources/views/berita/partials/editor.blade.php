{{--
    Editor berita (dipakai berita.create & berita.edit).
    Variabel: $kategoriList, $berita (null saat membuat baru).
    Kontrak backend: POST {admin|operator}.berita.store / PUT ….update (multipart),
    field: judul, kategori, isi (HTML dari Quill), status, published_at, gambar, hapus_gambar (ubah).
--}}
@php
    $user = auth()->user();
    $p = $user->isAdmin() ? 'admin' : 'operator';
    $berita = $berita ?? null;
    $editing = (bool) $berita?->exists;
    $namaKategori = $kategoriList->map(fn ($k) => is_object($k) ? $k->nama : $k)->values();
    $kategoriAwal = (string) old('kategori', $berita?->kategori ?? '');
    $statusAwal = old('status', $berita?->status ?? 'draft');
    $statusAwal = in_array($statusAwal, ['draft', 'published', 'archived'], true) ? $statusAwal : 'draft';
    $kelolaKategori = $user->hasMenuPermission('kategori') ? route($p.'.kategori.index') : null;
    $statusPilihan = [
        'draft' => ['file-pen-line', 'Draft', 'border-gold-500 bg-gold-50 text-gold-800 shadow-sm'],
        'published' => ['circle-check-big', 'Terbit', 'border-brand-600 bg-brand-50 text-brand-800 shadow-sm'],
        'archived' => ['archive', 'Arsip', 'border-stone-500 bg-stone-100 text-stone-800 shadow-sm'],
    ];
    $config = [
        'mode' => $editing ? 'edit' : 'create',
        'judul' => (string) old('judul', $berita?->judul ?? ''),
        'kategori' => $kategoriAwal,
        'status' => $statusAwal,
        'publishedAt' => (string) old('published_at', $editing ? $berita->published_at?->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')),
        'gambarSaatIni' => $editing ? $berita->gambar_url : null,
        'hapusGambar' => (string) old('hapus_gambar', '0') === '1',
        'slugAwal' => $editing ? $berita->slug : null,
        'judulTersimpan' => $editing ? $berita->judul : null,
        'errors' => collect($errors->getMessages())->map(fn ($pesan) => $pesan[0]),
    ];
    $hostBerita = preg_replace('#^https?://#', '', url('berita'));
@endphp

@push('head')
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
    <style>
        /* ── Quill: disesuaikan dengan sistem desain (brand hijau, aksen emas) ── */
        .berita-editor .ql-toolbar.ql-snow {
            display: flex; flex-wrap: wrap; align-items: center; gap: 4px;
            padding: 9px 12px; border: 0; border-block: 1px solid #f0eeec;
            background: rgb(250 250 249 / 0.94); backdrop-filter: blur(10px);
            font-family: inherit;
        }
        @media (min-width: 768px) {
            .berita-editor .ql-toolbar.ql-snow { position: sticky; top: 72px; z-index: 20; padding: 9px 18px; }
        }
        .berita-editor .ql-toolbar.ql-snow .ql-formats { display: inline-flex; align-items: center; gap: 1px; margin: 0; padding-right: 4px; border-right: 1px solid #e7e5e4; }
        .berita-editor .ql-toolbar.ql-snow .ql-formats:last-child { border-right: 0; padding-right: 0; }
        .berita-editor .ql-snow.ql-toolbar button { float: none; width: 30px; height: 30px; padding: 6px; border-radius: 8px; transition: background-color .15s; }
        .berita-editor .ql-snow.ql-toolbar button:hover,
        .berita-editor .ql-snow.ql-toolbar button:focus-visible { background: #effaf4; }
        .berita-editor .ql-snow.ql-toolbar button.ql-active { background: #d8f2e3; }
        .berita-editor .ql-snow .ql-stroke { stroke: #57534e; }
        .berita-editor .ql-snow .ql-fill,
        .berita-editor .ql-snow .ql-stroke.ql-fill { fill: #57534e; }
        .berita-editor .ql-snow.ql-toolbar button:hover .ql-stroke,
        .berita-editor .ql-snow.ql-toolbar button:focus-visible .ql-stroke,
        .berita-editor .ql-snow.ql-toolbar button.ql-active .ql-stroke,
        .berita-editor .ql-snow.ql-toolbar .ql-picker-label:hover .ql-stroke,
        .berita-editor .ql-snow.ql-toolbar .ql-picker-label.ql-active .ql-stroke,
        .berita-editor .ql-snow.ql-toolbar .ql-picker-item:hover .ql-stroke,
        .berita-editor .ql-snow.ql-toolbar .ql-picker-item.ql-selected .ql-stroke { stroke: #126245; }
        .berita-editor .ql-snow.ql-toolbar button:hover .ql-fill,
        .berita-editor .ql-snow.ql-toolbar button:focus-visible .ql-fill,
        .berita-editor .ql-snow.ql-toolbar button.ql-active .ql-fill,
        .berita-editor .ql-snow.ql-toolbar .ql-picker-label:hover .ql-fill,
        .berita-editor .ql-snow.ql-toolbar .ql-picker-label.ql-active .ql-fill,
        .berita-editor .ql-snow.ql-toolbar .ql-picker-item:hover .ql-fill,
        .berita-editor .ql-snow.ql-toolbar .ql-picker-item.ql-selected .ql-fill { fill: #126245; }
        .berita-editor .ql-snow.ql-toolbar button:hover,
        .berita-editor .ql-snow.ql-toolbar button.ql-active,
        .berita-editor .ql-snow.ql-toolbar .ql-picker-label:hover,
        .berita-editor .ql-snow.ql-toolbar .ql-picker-label.ql-active,
        .berita-editor .ql-snow.ql-toolbar .ql-picker-item:hover,
        .berita-editor .ql-snow.ql-toolbar .ql-picker-item.ql-selected { color: #126245; }
        .berita-editor .ql-snow .ql-picker { float: none; height: 30px; color: #57534e; font-size: 13px; font-weight: 600; }
        .berita-editor .ql-snow .ql-picker-label { display: flex; align-items: center; border-radius: 8px; border: 1px solid transparent; }
        .berita-editor .ql-snow .ql-picker-label:hover { background: #effaf4; }
        .berita-editor .ql-snow .ql-picker.ql-header { width: 126px; }
        .berita-editor .ql-toolbar.ql-snow .ql-picker.ql-expanded .ql-picker-label { border-color: #d6d3d1; background: #fff; color: #126245; }
        .berita-editor .ql-toolbar.ql-snow .ql-picker-options {
            margin-top: 6px; padding: 6px; border: 1px solid #e7e5e4; border-radius: 14px; background: #fff;
            box-shadow: 0 2px 4px rgb(16 35 27 / 0.04), 0 24px 48px -20px rgb(16 35 27 / 0.28);
        }
        .berita-editor .ql-toolbar.ql-snow .ql-picker.ql-expanded .ql-picker-options { border-color: #e7e5e4; }
        .berita-editor .ql-snow .ql-picker.ql-header .ql-picker-item { padding: 6px 10px; border-radius: 9px; }
        .berita-editor .ql-snow .ql-picker.ql-header .ql-picker-item:hover { background: #effaf4; }
        .berita-editor .ql-snow .ql-color-picker .ql-picker-options { width: 172px; padding: 8px; }
        .berita-editor .ql-snow .ql-color-picker .ql-picker-item { border-radius: 5px; }
        .berita-editor .ql-snow .ql-icon-picker .ql-picker-options { padding: 4px; }
        .berita-editor .ql-snow .ql-picker.ql-header .ql-picker-label::before,
        .berita-editor .ql-snow .ql-picker.ql-header .ql-picker-item::before { content: 'Paragraf'; }
        .berita-editor .ql-snow .ql-picker.ql-header .ql-picker-label[data-value="1"]::before,
        .berita-editor .ql-snow .ql-picker.ql-header .ql-picker-item[data-value="1"]::before { content: 'Judul utama'; }
        .berita-editor .ql-snow .ql-picker.ql-header .ql-picker-label[data-value="2"]::before,
        .berita-editor .ql-snow .ql-picker.ql-header .ql-picker-item[data-value="2"]::before { content: 'Judul bagian'; }
        .berita-editor .ql-snow .ql-picker.ql-header .ql-picker-label[data-value="3"]::before,
        .berita-editor .ql-snow .ql-picker.ql-header .ql-picker-item[data-value="3"]::before { content: 'Subjudul'; }
        .berita-editor .ql-snow .ql-picker.ql-header .ql-picker-label[data-value="4"]::before,
        .berita-editor .ql-snow .ql-picker.ql-header .ql-picker-item[data-value="4"]::before { content: 'Subjudul kecil'; }
        .berita-editor .ql-snow .ql-picker.ql-header .ql-picker-item[data-value="2"]::before { font-family: var(--font-display), Georgia, serif; font-size: 1.2em; font-weight: 600; }
        .berita-editor .ql-snow .ql-picker.ql-header .ql-picker-item[data-value="3"]::before { font-size: 1.1em; font-weight: 700; }
        .berita-editor .ql-snow .ql-picker.ql-header .ql-picker-item[data-value="4"]::before { font-size: 1em; font-weight: 700; }

        .berita-editor .ql-container.ql-snow { border: 0; font-family: inherit; }
        .berita-editor .ql-snow .ql-editor { min-height: 460px; padding: 28px 32px 48px; font-size: 16.5px; line-height: 1.85; color: #44403c; }
        .berita-editor .ql-snow .ql-editor.ql-blank::before { left: 32px; right: 32px; font-style: normal; color: #a8a29e; }
        @media (max-width: 639px) {
            .berita-editor .ql-toolbar.ql-snow .ql-formats { padding-right: 0; border-right: 0; }
            .berita-editor .ql-snow .ql-editor { min-height: 340px; padding: 20px 20px 32px; font-size: 16px; }
            .berita-editor .ql-snow .ql-editor.ql-blank::before { left: 20px; right: 20px; }
        }
        .berita-editor .ql-snow .ql-editor > * + * { margin-top: 1.1em; }
        .berita-editor .ql-snow .ql-editor h1,
        .berita-editor .ql-snow .ql-editor h2 { font-family: var(--font-display), Georgia, serif; font-weight: 600; line-height: 1.3; color: #10231b; }
        .berita-editor .ql-snow .ql-editor h1 { font-size: 1.875rem; }
        .berita-editor .ql-snow .ql-editor h2 { font-size: 1.5rem; margin-top: 1.5em; }
        .berita-editor .ql-snow .ql-editor h3 { font-size: 1.25rem; font-weight: 700; color: #10231b; margin-top: 1.4em; }
        .berita-editor .ql-snow .ql-editor h4 { font-size: 1.125rem; font-weight: 700; color: #10231b; margin-top: 1.3em; }
        .berita-editor .ql-snow .ql-editor > :first-child { margin-top: 0; }
        .berita-editor .ql-snow .ql-editor strong { font-weight: 700; color: #10231b; }
        .berita-editor .ql-snow .ql-editor a { color: #126245; font-weight: 500; text-decoration: underline; text-decoration-color: #dfb035; text-decoration-thickness: 2px; text-underline-offset: 4px; }
        .berita-editor .ql-snow .ql-editor blockquote {
            margin: 0; padding: 14px 18px 14px 22px; border-left: 4px solid #dfb035; border-radius: 0 16px 16px 0;
            background: linear-gradient(90deg, #fdf9ec, transparent); font-family: var(--font-display), Georgia, serif;
            font-size: 1.125rem; font-style: italic; color: #292524;
        }
        .berita-editor .ql-snow .ql-editor ul > li::before { color: #c9951f; }
        .berita-editor .ql-snow .ql-editor ol > li::before { color: #177a53; font-weight: 700; }
        .berita-editor .ql-snow .ql-editor pre.ql-syntax { padding: 14px 16px; border-radius: 12px; background: #10231b; color: #f5f5f4; }
        .berita-editor .ql-snow .ql-editor img { border-radius: 16px; }

        .berita-editor .ql-snow .ql-tooltip {
            z-index: 25; padding: 8px 12px; border: 1px solid #e7e5e4; border-radius: 14px; color: #44403c; font-size: 13px;
            box-shadow: 0 2px 4px rgb(16 35 27 / 0.04), 0 24px 48px -20px rgb(16 35 27 / 0.28);
        }
        .berita-editor .ql-snow .ql-tooltip input[type=text] { width: 220px; height: 32px; padding: 4px 10px; border: 1px solid #d6d3d1; border-radius: 9px; outline: none; }
        .berita-editor .ql-snow .ql-tooltip input[type=text]:focus { border-color: #259868; box-shadow: 0 0 0 3px rgb(37 152 104 / 0.15); }
        .berita-editor .ql-snow .ql-tooltip a { color: #126245; font-weight: 600; }
        .berita-editor .ql-snow .ql-tooltip::before { content: 'Kunjungi:'; }
        .berita-editor .ql-snow .ql-tooltip[data-mode=link]::before { content: 'Tautan:'; }
        .berita-editor .ql-snow .ql-tooltip a.ql-action::after { content: 'Ubah'; border-right-color: #e7e5e4; }
        .berita-editor .ql-snow .ql-tooltip a.ql-remove::before { content: 'Hapus'; }
        .berita-editor .ql-snow .ql-tooltip.ql-editing a.ql-action::after { content: 'Simpan'; }
    </style>
@endpush

<div x-data="beritaEditor(@js($config))" class="berita-editor" @keydown.ctrl.s.window.prevent="submit()" @keydown.meta.s.window.prevent="submit()">
    <x-admin.page-header eyebrow="Berita & Artikel" :title="$editing ? 'Ubah Berita' : 'Tulis Berita Baru'"
        :description="$editing
            ? 'Terakhir diperbarui '.$berita->updated_at?->translatedFormat('d F Y').' · '.number_format($berita->views ?? 0, 0, ',', '.').' kali dibaca'
            : 'Lengkapi judul, isi, kategori, dan gambar utama, lalu simpan sebagai draft atau langsung publikasikan.'">
        <x-slot:actions>
            <a href="{{ route($p.'.berita.index') }}" class="btn btn-outline"><x-icon name="arrow-left" class="size-4" /> Kembali</a>
            @if ($editing && $berita->status === 'published')
                <a href="{{ route('berita.detail', $berita->slug) }}" target="_blank" rel="noopener" class="btn btn-outline"><x-icon name="external-link" class="size-4" /> Lihat di Website</a>
            @endif
            <button type="button" @click="$dispatch('berita:pratinjau')" class="btn btn-outline"><x-icon name="eye" class="size-4" /> Pratinjau</button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($errors->any())
        <div class="mt-6 flex gap-3 rounded-2xl border border-red-200 bg-red-50/80 p-4 sm:p-5" role="alert">
            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-red-100 text-red-600"><x-icon name="circle-alert" class="size-5" /></span>
            <div class="min-w-0">
                <p class="font-semibold text-red-800">Berita belum tersimpan. Periksa kembali isian berikut:</p>
                <ul class="mt-1.5 list-disc space-y-0.5 pl-5 text-sm text-red-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                @unless ($editing)
                    <p class="mt-2 text-xs text-red-600/80">Catatan: gambar utama perlu dipilih ulang karena berkas tidak ikut terkirim kembali.</p>
                @endunless
            </div>
        </div>
    @endif

    <form x-ref="form" method="POST" enctype="multipart/form-data" novalidate @submit.prevent="submit()"
          action="{{ $editing ? route($p.'.berita.update', $berita) : route($p.'.berita.store') }}" class="mt-6">
        @csrf
        @if ($editing)
            @method('PUT')
            <input type="hidden" name="hapus_gambar" :value="hapusGambar ? 1 : 0">
        @endif
        <input type="hidden" name="isi" x-ref="isi" :value="isi" :disabled="fallback">
        <textarea x-ref="seed" class="hidden" aria-hidden="true" tabindex="-1" disabled>{{ old('isi', $berita?->isi ?? '') }}</textarea>

        <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
            {{-- ── Kolom konten ───────────────────────────── --}}
            <div class="min-w-0 space-y-6">
                <section class="card p-5 transition focus-within:border-brand-300 focus-within:shadow-[var(--shadow-lift)] sm:p-7" :class="errors.judul && 'border-red-300!'">
                    <label for="judul" class="eyebrow">Judul berita <span class="text-red-500">*</span></label>
                    <textarea id="judul" name="judul" x-ref="judul" x-model="judul" rows="1" maxlength="255" required
                              placeholder="Tulis judul berita yang jelas & menarik…" aria-describedby="judul-bantuan"
                              @input="autosize(); errors.judul = null" @keydown.enter.prevent="focusEditor()"
                              class="mt-3 block w-full resize-none overflow-hidden border-0 bg-transparent p-0 font-display text-[26px] leading-[1.25] font-semibold text-ink-900 placeholder:text-stone-300 focus:ring-0 focus:outline-none focus-visible:outline-none sm:text-[34px]"></textarea>
                    <div class="mt-4 h-0.5 rounded-full bg-gradient-to-r from-brand-600 via-gold-400 to-transparent"></div>
                    <div id="judul-bantuan" class="mt-3 flex flex-wrap items-center justify-between gap-x-4 gap-y-1.5 text-xs">
                        <p class="flex min-w-0 items-center gap-1.5 text-stone-500" title="Pratinjau tautan berita">
                            <x-icon name="link" class="size-3.5 text-gold-500" />
                            <span class="min-w-0 truncate">{{ $hostBerita }}/<span class="font-semibold text-brand-700" x-text="slug || 'judul-berita'"></span></span>
                        </p>
                        <span class="tabular-nums" :class="judul.length > 230 ? 'font-semibold text-gold-700' : 'text-stone-500'"><span x-text="judul.length"></span>/255 karakter</span>
                    </div>
                    <p class="field-error" x-show="errors.judul" x-cloak><x-icon name="circle-alert" class="size-3.5" /> <span x-text="errors.judul"></span></p>
                    @if ($editing && $berita->status === 'published')
                        <p x-show="slugAwal && slug && judul.trim() !== (judulTersimpan || '').trim() && slug !== slugAwal" x-cloak x-transition class="mt-3 flex items-start gap-2 rounded-xl bg-gold-50 px-3.5 py-2.5 text-xs leading-relaxed text-gold-800 ring-1 ring-gold-200">
                            <x-icon name="triangle-alert" class="mt-px size-3.5 text-gold-600" />
                            <span>Judul berubah sehingga tautan berita ikut berubah. Tautan lama yang sudah dibagikan tidak akan berfungsi lagi.</span>
                        </p>
                    @endif
                </section>

                <section class="card transition" :class="errors.isi && 'border-red-300! ring-4 ring-red-500/10'">
                    <header class="flex items-center justify-between gap-3 px-5 py-3.5 sm:px-7">
                        <h2 id="isi-label" class="flex items-center gap-2 text-sm font-bold text-ink-900"><x-icon name="file-pen-line" class="size-4 text-brand-600" /> Isi berita <span class="text-red-500">*</span></h2>
                        <span class="hidden items-center gap-1 text-[11px] text-stone-500 sm:flex"><kbd class="rounded-md border border-stone-200 bg-white px-1.5 py-px font-sans text-[10px] font-semibold text-stone-600 shadow-xs">Ctrl</kbd><kbd class="rounded-md border border-stone-200 bg-white px-1.5 py-px font-sans text-[10px] font-semibold text-stone-600 shadow-xs">S</kbd> untuk menyimpan</span>
                    </header>
                    <div x-ignore><div data-quill></div></div>
                    <div x-show="fallback" x-cloak class="border-t border-stone-100 p-5 sm:p-7">
                        <p class="mb-3 flex items-center gap-2 rounded-xl bg-gold-50 px-3.5 py-2.5 text-xs text-gold-800 ring-1 ring-gold-200"><x-icon name="triangle-alert" class="size-4" /> Editor teks gagal dimuat (periksa koneksi internet). Isi tetap dapat diubah sebagai HTML di bawah ini.</p>
                        <textarea name="isi" x-model="isi" :disabled="!fallback" rows="14" class="input font-mono text-[13px]" aria-labelledby="isi-label"></textarea>
                    </div>
                    <footer class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 rounded-b-2xl border-t border-stone-100 bg-stone-50/60 px-5 py-3 text-xs text-stone-500 sm:px-7">
                        <span class="flex flex-wrap items-center gap-x-4 gap-y-1">
                            <span class="flex items-center gap-1.5"><x-icon name="type" class="size-3.5 text-stone-400" /> <b class="font-semibold text-stone-700 tabular-nums" x-text="kata.toLocaleString('id-ID')"></b> kata</span>
                            <span x-show="kata > 0" class="flex items-center gap-1.5"><x-icon name="clock" class="size-3.5 text-stone-400" /> ± <b class="font-semibold text-stone-700 tabular-nums" x-text="menitBaca"></b> menit baca</span>
                        </span>
                        <span class="field-error mt-0!" x-show="errors.isi" x-cloak><x-icon name="circle-alert" class="size-3.5" /> <span x-text="errors.isi"></span></span>
                    </footer>
                </section>
            </div>

            {{-- ── Kolom pengaturan (lekat di desktop) ───────── --}}
            <aside data-panel class="grid items-start gap-6 md:grid-cols-2 xl:sticky xl:top-[min(92px,calc(100dvh_-_var(--tinggi-panel,0px)_-_16px))] xl:grid-cols-1 xl:gap-5" aria-label="Pengaturan publikasi">
                {{-- Publikasi --}}
                <section class="card overflow-hidden">
                    <header class="bg-gradient-brand relative flex items-center justify-between gap-3 px-5 py-4">
                        <div class="pattern-islamic absolute inset-0"></div>
                        <h2 class="relative flex items-center gap-2.5 font-display text-lg font-semibold text-white">
                            <span class="grid size-8 place-items-center rounded-lg bg-white/10 text-gold-300 ring-1 ring-white/15"><x-icon name="send" class="size-4" /></span>
                            Publikasi
                        </h2>
                        <span class="relative badge bg-white/95 text-brand-900 shadow-sm">
                            <span class="size-1.5 rounded-full" :class="{ 'bg-gold-500': status === 'draft', 'bg-brand-500': status === 'published', 'bg-stone-400': status === 'archived' }"></span>
                            <span x-text="statusInfo.badge"></span>
                        </span>
                    </header>
                    <div class="space-y-4 p-5">
                        <fieldset>
                            <legend class="label">Status <span class="text-red-500">*</span></legend>
                            <div class="grid grid-cols-3 gap-2">
                                @foreach ($statusPilihan as $nilai => [$ikon, $teks, $aktif])
                                    <label class="flex cursor-pointer flex-col items-center gap-1 rounded-xl border px-2 py-2.5 text-xs font-bold transition select-none has-[:focus-visible]:ring-4 has-[:focus-visible]:ring-brand-500/20"
                                           :class="status === @js($nilai) ? @js($aktif) : 'border-stone-200 bg-white text-stone-500 hover:border-brand-300 hover:text-brand-700'">
                                        <input type="radio" name="status" value="{{ $nilai }}" x-model="status" class="sr-only">
                                        <x-icon :name="$ikon" class="size-[18px]" />
                                        {{ $teks }}
                                    </label>
                                @endforeach
                            </div>
                            <p class="mt-2 text-xs leading-relaxed text-stone-500" x-text="statusInfo.help"></p>
                            @error('status')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                        </fieldset>

                        <div x-show="status === 'published'" x-collapse>
                            <label for="published_at" class="label">Tanggal publikasi</label>
                            <input id="published_at" type="datetime-local" name="published_at" x-model="publishedAt" class="input" :class="errors.published_at && 'input-error'">
                            <p class="mt-1.5 text-xs text-stone-500">Kosongkan untuk memakai waktu saat disimpan.</p>
                            <p class="field-error" x-show="errors.published_at" x-cloak><x-icon name="circle-alert" class="size-3.5" /> <span x-text="errors.published_at"></span></p>
                        </div>

                        <div>
                            <div class="mb-1.5 flex items-center justify-between gap-2">
                                <label for="kategori" class="label mb-0">Kategori <span class="text-red-500">*</span></label>
                                @if ($kelolaKategori)
                                    <a href="{{ $kelolaKategori }}" target="_blank" rel="noopener" class="flex items-center gap-1 text-xs font-semibold text-brand-700 hover:text-brand-900"><x-icon name="settings-2" class="size-3.5" /> Kelola</a>
                                @endif
                            </div>
                            <select id="kategori" name="kategori" x-model="kategori" required class="input" :class="errors.kategori && 'input-error'" @change="errors.kategori = null">
                                <option value="">— Pilih kategori —</option>
                                @foreach ($namaKategori as $nama)
                                    <option value="{{ $nama }}">{{ $nama }}</option>
                                @endforeach
                                @if ($kategoriAwal !== '' && ! $namaKategori->contains($kategoriAwal))
                                    <option value="{{ $kategoriAwal }}">{{ $kategoriAwal }} (nonaktif)</option>
                                @endif
                            </select>
                            <p class="field-error" x-show="errors.kategori" x-cloak><x-icon name="circle-alert" class="size-3.5" /> <span x-text="errors.kategori"></span></p>
                            @if ($namaKategori->isEmpty())
                                <p class="mt-2 flex items-start gap-1.5 text-xs text-stone-500">
                                    <x-icon name="info" class="mt-px size-3.5 text-gold-500" />
                                    <span>Belum ada kategori aktif. @if ($kelolaKategori)<a href="{{ $kelolaKategori }}" target="_blank" rel="noopener" class="font-semibold text-brand-700 underline">Tambah kategori</a> terlebih dahulu.@else Hubungi admin untuk menambahkan kategori.@endif</span>
                                </p>
                            @endif
                        </div>
                    </div>
                    <div class="hidden space-y-2 border-t border-stone-100 bg-stone-50/70 px-5 pt-4 pb-3 xl:block">
                        <button type="submit" class="btn btn-primary w-full" :disabled="submitting">
                            <x-icon name="loader-circle" class="size-4 animate-spin" x-show="submitting" x-cloak />
                            <span x-show="!submitting" class="contents">
                                <x-icon name="save" class="size-4" x-show="status === 'draft'" />
                                <x-icon name="send" class="size-4" x-show="status === 'published'" x-cloak />
                                <x-icon name="archive" class="size-4" x-show="status === 'archived'" x-cloak />
                            </span>
                            <span x-text="submitting ? 'Menyimpan…' : labelSimpan"></span>
                        </button>
                        <button type="button" x-show="status !== 'draft'" x-cloak @click="submit('draft')" :disabled="submitting" class="btn btn-outline w-full"><x-icon name="file-pen-line" class="size-4" /> Simpan sebagai Draft</button>
                        <p class="flex items-center justify-center gap-1.5 text-[11px]" :class="dirty ? 'text-gold-700' : 'text-stone-500'">
                            <span class="size-1.5 rounded-full" :class="dirty ? 'bg-gold-500' : 'bg-stone-300'"></span>
                            <span x-text="dirty ? 'Ada perubahan yang belum disimpan' : (mode === 'edit' ? 'Belum ada perubahan' : 'Belum disimpan')"></span>
                        </p>
                    </div>
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-2.5 border-t border-stone-100 px-5 py-3.5 text-xs" aria-label="Informasi berita">
                        <div class="min-w-0">
                            <dt class="flex items-center gap-1.5 text-stone-500"><x-icon name="user-round" class="size-3.5 text-gold-500" /> Penulis</dt>
                            <dd class="mt-0.5 truncate font-semibold text-stone-700">{{ $editing ? ($berita->penulis?->name ?? $user->name) : $user->name }}</dd>
                        </div>
                        <div>
                            <dt class="flex items-center gap-1.5 text-stone-500"><x-icon name="calendar-days" class="size-3.5 text-gold-500" /> Dibuat</dt>
                            <dd class="mt-0.5 font-semibold text-stone-700">{{ ($editing ? $berita->created_at : now())?->translatedFormat('d M Y') }}</dd>
                        </div>
                        @if ($editing)
                            <div>
                                <dt class="flex items-center gap-1.5 text-stone-500"><x-icon name="history" class="size-3.5 text-gold-500" /> Diperbarui</dt>
                                <dd class="mt-0.5 font-semibold text-stone-700">{{ $berita->updated_at?->translatedFormat('d M Y, H:i') }}</dd>
                            </div>
                            <div>
                                <dt class="flex items-center gap-1.5 text-stone-500"><x-icon name="eye" class="size-3.5 text-gold-500" /> Dibaca</dt>
                                <dd class="mt-0.5 font-semibold text-stone-700">{{ number_format($berita->views ?? 0, 0, ',', '.') }} kali</dd>
                            </div>
                        @endif
                    </dl>
                </section>

                {{-- Gambar utama --}}
                <section class="card p-5" @dragover.prevent="dragging = true" @dragleave="if (!$el.contains($event.relatedTarget)) dragging = false" @drop.prevent="drop($event)"
                         :class="dragging && 'border-brand-400 ring-4 ring-brand-500/15'">
                    <h2 class="flex items-center gap-2 text-sm font-bold text-ink-900"><x-icon name="image" class="size-4 text-brand-600" /> Gambar utama @unless ($editing)<span class="text-red-500">*</span>@endunless</h2>
                    <input x-ref="file" id="gambar" type="file" name="gambar" accept="image/jpeg,image/png,image/jpg,image/webp,.jpg,.jpeg,.png,.webp" class="sr-only" tabindex="-1" @change="pick($event)" aria-label="Pilih gambar utama">

                    <template x-if="pratinjauGambar">
                        <div class="relative mt-4 overflow-hidden rounded-xl bg-stone-100 ring-1 ring-stone-200">
                            <img :src="pratinjauGambar" alt="Pratinjau gambar utama" class="aspect-[16/10] w-full object-cover" @load="ukuran = $event.target.naturalWidth + ' × ' + $event.target.naturalHeight + ' px'; resolusiRendah = $event.target.naturalWidth < 800">
                            <span class="badge absolute top-2.5 left-2.5 bg-white/95 text-stone-700 shadow-sm" x-text="berkas.nama ? 'Gambar baru' : 'Gambar saat ini'"></span>
                            <div class="absolute inset-x-0 bottom-0 flex gap-2 bg-gradient-to-t from-black/65 via-black/30 to-transparent p-3 pt-12">
                                <button type="button" @click="$refs.file.click()" class="btn btn-glass btn-sm flex-1"><x-icon name="image-up" class="size-4" /> Ganti</button>
                                <button type="button" @click="hapusPilihan()" class="btn btn-glass btn-sm flex-1 hover:border-red-400 hover:bg-red-600"><x-icon name="trash-2" class="size-4" /> <span x-text="berkas.nama ? 'Batal' : 'Hapus'"></span></button>
                            </div>
                        </div>
                    </template>
                    <div x-show="pratinjauGambar" class="mt-2 flex items-center justify-between gap-2 text-[11px] text-stone-500">
                        <span class="min-w-0 truncate" x-text="berkas.nama || 'Tersimpan di server'"></span>
                        <span class="shrink-0 tabular-nums" x-text="[ukuran, berkas.ukuran].filter(Boolean).join(' · ')"></span>
                    </div>
                    <p x-show="pratinjauGambar && resolusiRendah" x-cloak class="mt-2 flex items-center gap-1.5 text-[11px] text-gold-700"><x-icon name="info" class="size-3.5" /> Resolusi rendah, gambar bisa tampak buram di halaman berita.</p>

                    <button type="button" x-show="!pratinjauGambar" @click="$refs.file.click()"
                            class="mt-4 flex w-full flex-col items-center justify-center rounded-xl border-2 border-dashed px-4 py-6 text-center transition"
                            :class="dragging ? 'border-brand-500 bg-brand-50' : (errors.gambar ? 'border-red-300 bg-red-50/40' : 'border-stone-300 bg-stone-50/70 hover:border-brand-400 hover:bg-brand-50/50')">
                        <span class="grid size-12 place-items-center rounded-2xl bg-white text-brand-600 shadow-sm ring-1 ring-stone-200"><x-icon name="cloud-upload" class="size-6" /></span>
                        <span class="mt-3 text-sm font-semibold text-ink-900" x-text="dragging ? 'Lepaskan untuk mengunggah' : 'Klik atau seret gambar ke sini'"></span>
                        <span class="mt-1 text-xs text-stone-500">JPG, PNG, atau WEBP · maks. 2 MB</span>
                    </button>
                    <p x-show="!pratinjauGambar" class="mt-2.5 flex items-start gap-1.5 text-[11px] leading-relaxed text-stone-500"><x-icon name="info" class="mt-px size-3.5" /> Gunakan foto lanskap (±16:10), lebar minimal 1200 px agar tajam di semua layar.</p>
                    @if ($editing)
                        <p x-show="hapusGambar && !berkas.nama" x-cloak class="mt-3 flex items-center justify-between gap-2 rounded-xl bg-red-50 px-3.5 py-2.5 text-xs text-red-700 ring-1 ring-red-200">
                            <span class="flex items-center gap-1.5"><x-icon name="trash-2" class="size-3.5" /> Gambar lama akan dihapus saat disimpan.</span>
                            <button type="button" @click="hapusGambar = false" class="shrink-0 font-semibold underline hover:text-red-900">Batalkan</button>
                        </p>
                    @endif
                    <p class="field-error" x-show="errors.gambar" x-cloak><x-icon name="circle-alert" class="size-3.5" /> <span x-text="errors.gambar"></span></p>
                </section>

            </aside>
        </div>

        {{-- Bilah aksi lekat untuk layar kecil & sedang --}}
        <div class="sticky bottom-0 z-20 -mx-4 mt-6 border-t border-stone-200 bg-white/90 px-4 py-3 backdrop-blur-xl sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8 xl:hidden">
            <div class="flex items-center gap-2">
                <p class="hidden min-w-0 flex-1 items-center gap-1.5 text-xs sm:flex" :class="dirty ? 'text-gold-700' : 'text-stone-500'">
                    <span class="size-1.5 shrink-0 rounded-full" :class="dirty ? 'bg-gold-500' : 'bg-stone-300'"></span>
                    <span class="truncate" x-text="dirty ? 'Ada perubahan yang belum disimpan' : 'Status: ' + statusInfo.badge"></span>
                </p>
                <button type="button" x-show="status !== 'draft'" x-cloak @click="submit('draft')" :disabled="submitting" class="btn btn-outline flex-1 px-3 sm:flex-none">Simpan Draft</button>
                <button type="button" @click="submit()" :disabled="submitting" class="btn btn-primary flex-1 px-3 sm:flex-none">
                    <x-icon name="loader-circle" class="size-4 animate-spin" x-show="submitting" x-cloak />
                    <span x-text="submitting ? 'Menyimpan…' : labelSimpan"></span>
                </button>
            </div>
        </div>
    </form>

    {{-- Pratinjau tampilan berita --}}
    <div x-data="{ open: false, close() { this.open = false } }" @berita:pratinjau.window="sinkron(); open = true">
        <x-admin.modal title="'Pratinjau Berita'" icon="eye" size="max-w-3xl">
            <div class="scrollbar-thin min-h-0 flex-1 overflow-y-auto">
                <div class="relative aspect-[21/9] overflow-hidden bg-brand-900">
                    <template x-if="pratinjauGambar">
                        <img :src="pratinjauGambar" alt="" class="absolute inset-0 size-full object-cover">
                    </template>
                    <template x-if="!pratinjauGambar">
                        <div class="bg-gradient-brand absolute inset-0 grid place-items-center">
                            <div class="pattern-islamic absolute inset-0"></div>
                            <span class="relative grid size-14 place-items-center rounded-2xl bg-white/10 text-gold-300 ring-1 ring-white/15"><x-icon name="image" class="size-6" /></span>
                        </div>
                    </template>
                    <div class="absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-black/50 to-transparent"></div>
                    <span class="badge absolute bottom-4 left-6 bg-white/95 text-brand-800 shadow-sm" x-text="kategori || 'Tanpa kategori'"></span>
                </div>
                <article class="p-6 sm:p-8">
                    <h3 class="font-display text-2xl leading-snug font-semibold text-ink-900 sm:text-[28px]" x-text="judul || 'Judul berita belum diisi'" :class="!judul && 'text-stone-400!'"></h3>
                    <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-xs text-stone-500">
                        <span class="flex items-center gap-1.5"><x-icon name="user-round" class="size-3.5 text-gold-500" /> {{ $editing ? ($berita->penulis?->name ?? $user->name) : $user->name }}</span>
                        <span class="flex items-center gap-1.5"><x-icon name="calendar-days" class="size-3.5 text-gold-500" /> <span x-text="tanggalTampil"></span></span>
                        <span class="flex items-center gap-1.5"><x-icon name="clock" class="size-3.5 text-gold-500" /> <span x-text="menitBaca + ' menit baca'"></span></span>
                    </div>
                    <div class="prose-mui mt-6 border-t border-stone-100 pt-6 text-[15px]" x-html="isi || '<p class=&quot;text-stone-400 italic&quot;>Isi berita masih kosong.</p>'"></div>
                </article>
            </div>
            <footer class="flex shrink-0 flex-wrap items-center justify-between gap-2 border-t border-stone-100 bg-stone-50/60 px-6 py-4">
                <p class="text-xs text-stone-500">Tampilan perkiraan; tata letak final mengikuti halaman berita.</p>
                <button type="button" @click="close()" class="btn btn-primary">Lanjut Menulis</button>
            </footer>
        </x-admin.modal>
    </div>
</div>

@push('scripts')
    <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
    <script>
        document.addEventListener('alpine:init', () => {
            const MAKS_GAMBAR = 2048 * 1024; // sama dengan aturan validasi max:2048 (KB)
            const TIPE_GAMBAR = ['image/jpeg', 'image/png', 'image/webp'];
            const STATUS = {
                draft: { badge: 'Draft', help: 'Konsep, belum tampil di website.' },
                published: { badge: 'Dipublikasi', help: 'Tampil di halaman berita website.' },
                archived: { badge: 'Diarsipkan', help: 'Disembunyikan dari website, tetap tersimpan.' },
            };
            const TIPS = {
                '.ql-header .ql-picker-label': 'Gaya teks',
                'button.ql-bold': 'Tebal (Ctrl+B)',
                'button.ql-italic': 'Miring (Ctrl+I)',
                'button.ql-underline': 'Garis bawah (Ctrl+U)',
                'button.ql-strike': 'Coret',
                '.ql-color .ql-picker-label': 'Warna teks',
                '.ql-background .ql-picker-label': 'Warna sorotan',
                'button.ql-list[value="ordered"]': 'Daftar bernomor',
                'button.ql-list[value="bullet"]': 'Daftar berpoin',
                'button.ql-indent[value="-1"]': 'Kurangi indentasi',
                'button.ql-indent[value="+1"]': 'Tambah indentasi',
                '.ql-align .ql-picker-label': 'Perataan teks',
                'button.ql-blockquote': 'Kutipan',
                'button.ql-code-block': 'Blok kode',
                'button.ql-link': 'Sisipkan tautan',
                'button.ql-image': 'Sisipkan gambar ke isi',
                'button.ql-clean': 'Hapus format',
            };

            const ukuranBerkas = (b) => (b > 1048576 ? `${(b / 1048576).toFixed(1)} MB` : `${Math.max(1, Math.round(b / 1024))} KB`);
            const escapeHtml = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
            // Isi lama berupa teks polos → paragraf HTML agar baris baru tidak hilang di editor.
            const keHtml = (teks) => (/<[a-z][\s\S]*>/i.test(teks)
                ? teks
                : teks.split(/\n{2,}/).map((p) => `<p>${escapeHtml(p).replace(/\n/g, '<br>')}</p>`).join(''));

            Alpine.data('beritaEditor', (cfg) => {
                let quill = null;
                let berkasValid = null; // File terakhir yang lolos validasi (agar pilihan gagal tidak menghapusnya)
                let urlObjek = null;

                return {
                    mode: cfg.mode,
                    judul: cfg.judul,
                    judulTersimpan: cfg.judulTersimpan,
                    kategori: cfg.kategori,
                    status: cfg.status,
                    publishedAt: cfg.publishedAt,
                    isi: '',
                    kata: 0,
                    gambarSaatIni: cfg.gambarSaatIni,
                    hapusGambar: cfg.hapusGambar,
                    slugAwal: cfg.slugAwal,
                    berkas: { url: null, nama: null, ukuran: null },
                    ukuran: null,
                    resolusiRendah: false,
                    dragging: false,
                    errors: { ...(cfg.errors || {}) },
                    dirty: false,
                    submitting: false,
                    fallback: false,

                    init() {
                        const awal = keHtml((this.$refs.seed.value || '').trim());
                        this.$nextTick(() => this.autosize());

                        // Panel kanan lekat: bila lebih tinggi dari layar, bagian bawahnya tetap terjangkau tanpa gulir bersarang.
                        const panel = this.$el.querySelector('[data-panel]');
                        if (panel && 'ResizeObserver' in window) {
                            new ResizeObserver(() => panel.style.setProperty('--tinggi-panel', `${panel.offsetHeight}px`)).observe(panel);
                        }
                        window.addEventListener('resize', () => this.autosize());

                        if (typeof window.Quill === 'undefined') {
                            this.fallback = true;
                            this.isi = awal;
                            this.hitung(awal.replace(/<[^>]+>/g, ' '));
                        } else {
                            this.pasangEditor(awal);
                        }

                        ['judul', 'kategori', 'status', 'publishedAt', 'hapusGambar'].forEach((k) => this.$watch(k, () => { this.dirty = true; }));
                        this.$watch('isi', () => { if (this.fallback) this.dirty = true; });
                        window.addEventListener('beforeunload', (e) => {
                            if (this.dirty && !this.submitting) {
                                e.preventDefault();
                                e.returnValue = '';
                            }
                        });
                        // Kembali lewat tombol "Back" (bfcache): aktifkan lagi tombol simpan.
                        window.addEventListener('pageshow', (e) => { if (e.persisted) this.submitting = false; });
                    },

                    pasangEditor(awal) {
                        const host = this.$el.querySelector('[data-quill]');
                        quill = new Quill(host, {
                            theme: 'snow',
                            placeholder: 'Tulis isi berita di sini… Gunakan “Judul bagian” untuk membagi tulisan panjang.',
                            modules: {
                                toolbar: {
                                    container: [
                                        [{ header: [2, 3, 4, false] }],
                                        ['bold', 'italic', 'underline', 'strike'],
                                        [{ color: [] }, { background: [] }],
                                        [{ list: 'ordered' }, { list: 'bullet' }],
                                        [{ indent: '-1' }, { indent: '+1' }],
                                        [{ align: [] }],
                                        ['blockquote', 'code-block'],
                                        ['link', 'image'],
                                        ['clean'],
                                    ],
                                    handlers: { image: () => this.sisipGambar() },
                                },
                            },
                        });

                        if (awal) quill.setContents(quill.clipboard.convert(awal), 'silent');
                        quill.history.clear();

                        const toolbar = quill.getModule('toolbar').container;
                        Object.entries(TIPS).forEach(([sel, tip]) => toolbar.querySelectorAll(sel).forEach((el) => {
                            el.setAttribute('title', tip);
                            el.setAttribute('aria-label', tip);
                        }));
                        toolbar.setAttribute('aria-label', 'Format isi berita');
                        quill.root.setAttribute('role', 'textbox');
                        quill.root.setAttribute('aria-multiline', 'true');
                        quill.root.setAttribute('aria-labelledby', 'isi-label');

                        quill.on('text-change', (_delta, _old, source) => {
                            this.sinkron();
                            if (source === 'user') {
                                this.dirty = true;
                                this.errors.isi = null;
                            }
                        });
                        this.sinkron();
                    },

                    /** Salin HTML Quill ke input tersembunyi "isi" + hitung kata. */
                    sinkron() {
                        if (!quill) return;
                        const teks = quill.getText();
                        const kosong = !teks.trim() && !quill.root.querySelector('img, iframe, video');
                        this.isi = kosong ? '' : quill.root.innerHTML;
                        this.hitung(teks);
                    },
                    hitung(teks) {
                        const t = (teks || '').trim();
                        this.kata = t ? t.split(/\s+/).length : 0;
                    },

                    get menitBaca() {
                        return Math.max(1, Math.ceil(this.kata / 200));
                    },
                    get slug() {
                        return this.judul.normalize('NFKD').replace(/[\u0300-\u036f]/g, '').toLowerCase()
                            .replace(/_/g, '-').replace(/@/g, '-at-').replace(/[^a-z0-9\s-]/g, '')
                            .replace(/[\s-]+/g, '-').replace(/^-+|-+$/g, '');
                    },
                    get statusInfo() {
                        return STATUS[this.status] ?? STATUS.draft;
                    },
                    get labelSimpan() {
                        const edit = this.mode === 'edit';
                        if (this.status === 'published') return edit ? 'Perbarui & Publikasikan' : 'Publikasikan';
                        if (this.status === 'archived') return 'Simpan ke Arsip';
                        return edit ? 'Simpan Perubahan (Draft)' : 'Simpan Draft';
                    },
                    get pratinjauGambar() {
                        return this.berkas.url || (this.gambarSaatIni && !this.hapusGambar ? this.gambarSaatIni : null);
                    },
                    get tanggalTampil() {
                        const d = this.status === 'published' && this.publishedAt ? new Date(this.publishedAt) : new Date();
                        return d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
                    },

                    autosize() {
                        const el = this.$refs.judul;
                        if (!el) return;
                        el.style.height = 'auto';
                        el.style.height = `${el.scrollHeight}px`;
                    },
                    focusEditor() {
                        if (quill) {
                            quill.focus();
                            quill.setSelection(0, 0);
                        }
                    },

                    /* ── Gambar utama ───────────────────────── */
                    pick(e) {
                        const file = e.target.files?.[0];
                        if (file) this.pasangBerkas(file);
                        else this.pulihkanInput(); // dialog dibatalkan
                    },
                    drop(e) {
                        this.dragging = false;
                        const file = e.dataTransfer?.files?.[0];
                        if (!file) return;
                        const dt = new DataTransfer();
                        dt.items.add(file);
                        this.$refs.file.files = dt.files;
                        this.pasangBerkas(file);
                    },
                    pasangBerkas(file) {
                        let pesan = null;
                        if (!TIPE_GAMBAR.includes(file.type)) pesan = 'Format gambar harus JPEG, PNG, JPG, atau WEBP.';
                        else if (file.size > MAKS_GAMBAR) pesan = `Ukuran gambar maksimal 2 MB (berkas ini ${ukuranBerkas(file.size)}).`;
                        if (pesan) {
                            this.errors.gambar = pesan;
                            MUIAdmin.toast(pesan, 'error');
                            this.pulihkanInput();
                            return;
                        }
                        if (urlObjek) URL.revokeObjectURL(urlObjek);
                        urlObjek = URL.createObjectURL(file);
                        berkasValid = file;
                        this.berkas = { url: urlObjek, nama: file.name, ukuran: ukuranBerkas(file.size) };
                        this.ukuran = null;
                        this.errors.gambar = null;
                        this.dirty = true;
                    },
                    pulihkanInput() {
                        const dt = new DataTransfer();
                        if (berkasValid) dt.items.add(berkasValid);
                        this.$refs.file.files = dt.files;
                    },
                    hapusPilihan() {
                        if (this.berkas.url) {
                            URL.revokeObjectURL(this.berkas.url);
                            urlObjek = null;
                            berkasValid = null;
                            this.berkas = { url: null, nama: null, ukuran: null };
                            this.$refs.file.value = '';
                        } else if (this.gambarSaatIni) {
                            this.hapusGambar = true;
                        }
                        this.ukuran = null;
                        this.resolusiRendah = false;
                        this.dirty = true;
                    },

                    /** Gambar di dalam isi (disimpan sebagai data URI seperti bawaan Quill), dibatasi 2 MB. */
                    sisipGambar() {
                        const input = document.createElement('input');
                        input.type = 'file';
                        input.accept = 'image/png,image/jpeg,image/gif,image/webp';
                        input.addEventListener('change', () => {
                            const file = input.files?.[0];
                            if (!file) return;
                            if (!file.type.startsWith('image/')) return MUIAdmin.toast('Berkas yang dipilih bukan gambar.', 'error');
                            if (file.size > MAKS_GAMBAR) return MUIAdmin.toast('Gambar di dalam isi berita maksimal 2 MB.', 'error');
                            const reader = new FileReader();
                            reader.onload = () => {
                                const range = quill.getSelection(true);
                                quill.insertEmbed(range.index, 'image', reader.result, 'user');
                                quill.setSelection(range.index + 1, 0, 'silent');
                            };
                            reader.readAsDataURL(file);
                        });
                        input.click();
                    },

                    /* ── Simpan ─────────────────────────────── */
                    validasi() {
                        const e = {};
                        if (!this.judul.trim()) e.judul = 'Judul berita wajib diisi.';
                        if (!this.kategori) e.kategori = 'Kategori berita wajib dipilih.';
                        if (!this.isi.trim() || this.isi === '<p><br></p>') e.isi = 'Isi berita wajib diisi.';
                        if (this.mode === 'create' && !this.berkas.nama) e.gambar = 'Gambar utama (thumbnail) wajib diunggah.';
                        this.errors = e;
                        return e;
                    },
                    async submit(paksaStatus = null) {
                        if (this.submitting) return;
                        if (paksaStatus) this.status = paksaStatus;
                        this.sinkron();
                        const e = this.validasi();
                        const pertama = Object.keys(e)[0];
                        if (pertama) {
                            MUIAdmin.toast(e[pertama], 'error');
                            const target = { judul: this.$refs.judul, kategori: document.getElementById('kategori'), isi: quill?.root, gambar: this.$refs.file.closest('section') }[pertama];
                            target?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            if (pertama === 'isi') quill?.focus();
                            else if (pertama !== 'gambar') target?.focus({ preventScroll: true });
                            return;
                        }
                        this.submitting = true;
                        await this.$nextTick();
                        if (!this.fallback) this.$refs.isi.value = this.isi;
                        this.$refs.form.submit();
                    },
                };
            });
        });
    </script>
@endpush
