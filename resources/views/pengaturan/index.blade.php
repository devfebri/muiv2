@php
    $s = $settings;
    $val = fn (string $key, ?string $default = null) => old($key, $s[$key] ?? $default);
    $sections = [
        'profil' => ['label' => 'Profil MUI', 'icon' => 'landmark', 'desc' => 'Sejarah, peran & tugas pokok', 'url' => route('profilemui')],
        'visi_misi' => ['label' => 'Visi & Misi', 'icon' => 'compass', 'desc' => 'Visi, butir misi & wasathiyah', 'url' => route('visi-misi')],
        'struktur' => ['label' => 'Struktur Organisasi', 'icon' => 'network', 'desc' => 'Bagan, pimpinan & komisi', 'url' => route('struktur-organisasi')],
        'kontak' => ['label' => 'Kontak & Medsos', 'icon' => 'phone', 'desc' => 'Alamat, peta & media sosial', 'url' => route('kontak')],
        'livechat' => ['label' => 'Live Chat & Jam Kerja', 'icon' => 'messages-square', 'desc' => 'Status, jadwal & pesan bot', 'url' => route('home.public')],
    ];
    $active = old('_section', $tab);
    $active = array_key_exists($active, $sections) ? $active : 'profil';

    $baganFile = $s['struktur_bagan_gambar'] ?? null;
    $bagan = filled($baganFile) ? asset('uploads/pengaturan/'.basename($baganFile)) : null;

    // Jam disajikan sebagai HH:MM agar cocok dengan <input type="time">.
    $timeVal = function (string $key, string $default) use ($val) {
        $raw = (string) $val($key, $default);

        return preg_match('/^(\d{1,2})[.:](\d{2})/', $raw, $m) ? sprintf('%02d:%02d', (int) $m[1], (int) $m[2]) : $raw;
    };
    $chatEnabled = old('_section') === 'livechat' ? old('chat_is_enabled') === '1' : (($s['chat_is_enabled'] ?? '1') === '1');
    $days = collect(explode(',', (string) $val('chat_operational_days', '1,2,3,4,5')))->map(fn ($d) => trim($d))
        ->filter(fn ($d) => in_array($d, ['1', '2', '3', '4', '5', '6', '7'], true))->unique()->sort()->values()->all();
    $dayNames = ['1' => 'Senin', '2' => 'Selasa', '3' => 'Rabu', '4' => 'Kamis', '5' => 'Jumat', '6' => 'Sabtu', '7' => 'Minggu'];

    $peranDefaults = [1 => 'Khadimul Ummah', 2 => 'Himayatul Ummah', 3 => 'Shodiqul Hukumah'];
    $sekilasLabels = [1 => 'Paragraf 1 — Pengertian umum MUI', 2 => 'Paragraf 2 — Sejarah pendirian MUI', 3 => 'Paragraf 3 — Perjalanan & komitmen MUI'];
    $pimpinan = [
        'struktur_ketua_umum' => ['Ketua Umum', 'crown'],
        'struktur_wakil_ketua_umum' => ['Wakil Ketua Umum', 'user-round-check'],
        'struktur_sekjen' => ['Sekretaris Jenderal (Sekjen)', 'notebook-pen'],
        'struktur_bendahara_umum' => ['Bendahara Umum', 'wallet'],
        'struktur_ketua_bidang' => ['Ketua-Ketua Bidang', 'users'],
    ];
    // [label, ikon, placeholder, dasar tautan profil, dasar tautan pencarian] — sama dengan Setting::siteProfile().
    $socials = [
        'kontak_instagram' => ['Instagram', 'instagram', '@username atau tautan profil', 'https://www.instagram.com/', null],
        'kontak_youtube' => ['YouTube', 'youtube', 'Nama kanal, @handle, atau tautan', 'https://www.youtube.com/@', 'https://www.youtube.com/results?search_query='],
        'kontak_facebook' => ['Facebook', 'facebook', 'Nama halaman atau tautan', 'https://www.facebook.com/', 'https://www.facebook.com/search/top?q='],
        'kontak_twitter' => ['X (Twitter)', 'x-twitter', '@username atau tautan', 'https://x.com/', null],
        'kontak_tiktok' => ['TikTok', 'tiktok', '@username atau tautan', 'https://www.tiktok.com/@', null],
    ];
    $config = [
        'active' => $active,
        'labels' => collect($sections)->map(fn ($x) => $x['label']),
        'links' => collect($sections)->map(fn ($x) => $x['url']),
        'days' => $days,
    ];
@endphp

<x-layouts.admin title="Pengaturan Situs" header="Pengaturan Website — Tentang Kami, Kontak & Live Chat">
    <div x-data="settingsPage(@js($config))">
        <x-admin.page-header eyebrow="Sistem" title="Pengaturan Situs" description="Kelola konten halaman “Tentang Kami” dan layanan live chat yang tampil di website. Setiap bagian disimpan terpisah.">
            <x-slot:actions>
                <a :href="links[tab]" href="{{ $sections[$active]['url'] }}" target="_blank" rel="noopener" class="btn btn-outline"><x-icon name="external-link" class="size-4" /> Lihat halaman publik</a>
            </x-slot:actions>
        </x-admin.page-header>

        <div class="mt-6 grid grid-cols-1 items-start gap-6 lg:grid-cols-[16.5rem_minmax(0,1fr)]">
            {{-- Navigasi bagian --}}
            <nav class="min-w-0 lg:sticky lg:top-24" aria-label="Kelompok pengaturan">
                <div role="tablist" aria-orientation="vertical" class="scrollbar-none -mx-4 flex gap-2 overflow-x-auto px-4 pb-1 sm:-mx-6 sm:px-6 lg:mx-0 lg:flex-col lg:gap-1.5 lg:overflow-visible lg:px-0 lg:pb-0">
                    @foreach ($sections as $key => $sec)
                        <button type="button" role="tab" id="tab-{{ $key }}" aria-controls="form-{{ $key }}" data-tab="{{ $key }}"
                                :aria-selected="tab === @js($key)" :tabindex="tab === @js($key) ? 0 : -1"
                                @click="go(@js($key))" @keydown.arrow-down.prevent="move(1)" @keydown.arrow-right.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.arrow-left.prevent="move(-1)"
                                class="group flex shrink-0 items-center gap-3 rounded-xl border px-3 py-2.5 text-left transition lg:py-3"
                                :class="tab === @js($key) ? 'border-brand-200 bg-white shadow-[var(--shadow-soft)]' : 'border-stone-200 bg-white/60 hover:border-stone-300 hover:bg-white lg:border-transparent lg:bg-transparent'">
                            <span class="grid size-9 shrink-0 place-items-center rounded-lg transition" :class="tab === @js($key) ? 'bg-brand-700 text-gold-300' : 'bg-stone-100 text-stone-500 group-hover:text-brand-700'"><x-icon :name="$sec['icon']" class="size-4" /></span>
                            <span class="min-w-0">
                                <span class="flex items-center gap-2 text-sm font-semibold whitespace-nowrap" :class="tab === @js($key) ? 'text-ink-900' : 'text-stone-600'">
                                    {{ $sec['label'] }}
                                    @if ($errors->any() && $active === $key)
                                        <span class="size-2 rounded-full bg-red-500" title="Ada isian yang perlu diperbaiki"></span>
                                    @endif
                                    <span x-show="dirty[@js($key)]" x-cloak class="size-2 rounded-full bg-gold-500" title="Belum disimpan"></span>
                                </span>
                                <span class="hidden text-xs text-stone-500 lg:block">{{ $sec['desc'] }}</span>
                            </span>
                        </button>
                    @endforeach
                </div>
                <div class="mt-5 hidden rounded-2xl border border-gold-200/70 bg-gold-50/60 p-4 text-xs leading-relaxed text-gold-900 lg:block">
                    <p class="flex items-center gap-2 font-semibold"><x-icon name="info" class="size-4 text-gold-600" /> Disimpan per bagian</p>
                    <p class="mt-1.5 text-gold-800/90">Tombol simpan hanya menyimpan bagian yang sedang dibuka. Titik emas menandai bagian dengan perubahan yang belum disimpan.</p>
                </div>
            </nav>

            <div class="min-w-0" x-ref="content">
                @if ($errors->any())
                    <div role="alert" class="mb-6 flex gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                        <x-icon name="circle-alert" class="mt-0.5 size-5 shrink-0 text-red-600" />
                        <div>
                            <p class="font-semibold">Pengaturan {{ $sections[$active]['label'] }} belum tersimpan. Periksa isian berikut:</p>
                            <ul class="mt-1.5 list-disc space-y-0.5 pl-5 text-red-700">
                                @foreach ($errors->all() as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                {{-- ============ 1. PROFIL MUI ============ --}}
                <form id="form-profil" method="POST" action="{{ route('admin.pengaturan.update') }}" role="tabpanel" aria-labelledby="tab-profil"
                      x-show="tab === 'profil'" @if ($active !== 'profil') x-cloak @endif @submit="submit($event)" class="space-y-6">
                    @csrf
                    <input type="hidden" name="_section" value="profil">

                    <section class="card">
                        <header class="flex items-start gap-3 border-b border-stone-100 px-5 py-4 sm:px-6">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100"><x-icon name="heading" class="size-5" /></span>
                            <div><h3 class="font-semibold text-ink-900">Judul &amp; pengantar halaman</h3><p class="mt-0.5 text-xs text-stone-500">Teks utama pada banner halaman Profil MUI.</p></div>
                        </header>
                        <div class="grid grid-cols-1 gap-6 p-5 sm:p-6 xl:grid-cols-5">
                            <div class="space-y-5 xl:col-span-3">
                                <div x-data="charCount(255)">
                                    <div class="flex items-baseline justify-between gap-3"><label for="f-profil_title" class="label">Judul halaman <span class="text-red-500">*</span></label><span class="text-[11px] tabular-nums" :class="tone" x-text="text"></span></div>
                                    <input id="f-profil_title" name="profil_title" type="text" maxlength="255" required value="{{ $val('profil_title') }}" x-model.fill="v.profil_title" class="input @error('profil_title') input-error @enderror">
                                    @error('profil_title')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                                </div>
                                <div x-data="charCount()">
                                    <div class="flex items-baseline justify-between gap-3"><label for="f-profil_subtitle" class="label">Subjudul / ringkasan banner</label><span class="text-[11px] tabular-nums" :class="tone" x-text="text"></span></div>
                                    <textarea id="f-profil_subtitle" name="profil_subtitle" rows="3" x-model.fill="v.profil_subtitle" class="input resize-y leading-relaxed @error('profil_subtitle') input-error @enderror">{{ $val('profil_subtitle') }}</textarea>
                                    @error('profil_subtitle')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                                </div>
                            </div>
                            <div class="xl:col-span-2">
                                <p class="label flex items-center gap-1.5"><x-icon name="eye" class="size-3.5 text-stone-400" /> Pratinjau banner</p>
                                <div class="bg-gradient-brand relative overflow-hidden rounded-2xl px-5 py-6 text-white">
                                    <div class="pattern-islamic absolute inset-0"></div>
                                    <div class="absolute -top-10 -right-10 size-32 rounded-full bg-gold-400/20 blur-2xl"></div>
                                    <div class="relative">
                                        <p class="text-[10px] font-bold tracking-[.25em] text-gold-300 uppercase">Profil</p>
                                        <p class="mt-2 font-display text-xl leading-tight font-semibold text-balance text-white" x-text="v.profil_title || 'Profil Majelis Ulama Indonesia'"></p>
                                        <p class="mt-2 line-clamp-4 text-xs leading-relaxed text-white/70" x-show="v.profil_subtitle" x-text="v.profil_subtitle"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="card">
                        <header class="flex items-start gap-3 border-b border-stone-100 px-5 py-4 sm:px-6">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100"><x-icon name="book-open" class="size-5" /></span>
                            <div><h3 class="font-semibold text-ink-900">Sekilas tentang MUI</h3><p class="mt-0.5 text-xs text-stone-500">Tiga paragraf penjelasan profil &amp; sejarah. Paragraf kosong tidak ditampilkan.</p></div>
                        </header>
                        <div class="space-y-5 p-5 sm:p-6">
                            @foreach ($sekilasLabels as $i => $label)
                                <div x-data="charCount()">
                                    <div class="flex items-baseline justify-between gap-3"><label for="f-profil_sekilas_{{ $i }}" class="label">{{ $label }}</label><span class="text-[11px] tabular-nums" :class="tone" x-text="text"></span></div>
                                    <textarea id="f-profil_sekilas_{{ $i }}" name="profil_sekilas_{{ $i }}" rows="4" class="input resize-y leading-relaxed @error('profil_sekilas_'.$i) input-error @enderror">{{ $val('profil_sekilas_'.$i) }}</textarea>
                                    @error('profil_sekilas_'.$i)<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <section class="card">
                        <header class="flex items-start gap-3 border-b border-stone-100 px-5 py-4 sm:px-6">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100"><x-icon name="shield-check" class="size-5" /></span>
                            <div><h3 class="font-semibold text-ink-900">Tiga peran strategis MUI</h3><p class="mt-0.5 text-xs text-stone-500">Pilar peran: Khadimul Ummah, Himayatul Ummah, dan Shodiqul Hukumah. Peran tanpa judul tidak ditampilkan.</p></div>
                        </header>
                        <div class="grid grid-cols-1 gap-4 p-5 sm:p-6">
                            @foreach ($peranDefaults as $i => $default)
                                <div class="grid grid-cols-1 gap-4 rounded-2xl bg-sand-100/60 p-4 ring-1 ring-sand-200 md:grid-cols-5">
                                    <p class="flex items-center gap-2 text-xs font-bold tracking-wider text-gold-700 uppercase md:col-span-5"><span class="grid size-6 place-items-center rounded-full bg-gold-500 text-[11px] text-white">{{ $i }}</span> Peran {{ $i }}</p>
                                    <div x-data="charCount(255)" class="md:col-span-2">
                                        <div class="flex items-baseline justify-between gap-3"><label for="f-profil_peran_{{ $i }}_title" class="label">Judul</label><span class="text-[11px] tabular-nums" :class="tone" x-text="text"></span></div>
                                        <input id="f-profil_peran_{{ $i }}_title" name="profil_peran_{{ $i }}_title" type="text" maxlength="255" value="{{ $val('profil_peran_'.$i.'_title', $default) }}" class="input @error('profil_peran_'.$i.'_title') input-error @enderror">
                                        @error('profil_peran_'.$i.'_title')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                                    </div>
                                    <div x-data="charCount()" class="md:col-span-3">
                                        <div class="flex items-baseline justify-between gap-3"><label for="f-profil_peran_{{ $i }}_desc" class="label">Deskripsi</label><span class="text-[11px] tabular-nums" :class="tone" x-text="text"></span></div>
                                        <textarea id="f-profil_peran_{{ $i }}_desc" name="profil_peran_{{ $i }}_desc" rows="3" class="input resize-y leading-relaxed @error('profil_peran_'.$i.'_desc') input-error @enderror">{{ $val('profil_peran_'.$i.'_desc') }}</textarea>
                                        @error('profil_peran_'.$i.'_desc')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <section class="card">
                        <header class="flex items-start gap-3 border-b border-stone-100 px-5 py-4 sm:px-6">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100"><x-icon name="list-checks" class="size-5" /></span>
                            <div><h3 class="font-semibold text-ink-900">Tugas pokok &amp; fungsi MUI</h3><p class="mt-0.5 text-xs text-stone-500">Ditampilkan sebagai daftar poin pada halaman Profil MUI.</p></div>
                        </header>
                        <div class="space-y-4 p-5 sm:p-6">
                            <div class="flex gap-3 rounded-xl bg-sky-50 px-4 py-3 text-xs leading-relaxed text-sky-900 ring-1 ring-sky-100">
                                <x-icon name="info" class="mt-0.5 size-4 shrink-0 text-sky-600" />
                                <p>Tulis <b>satu poin per baris</b> dengan format <code class="rounded bg-white px-1.5 py-0.5 font-mono text-[11px] text-sky-800 ring-1 ring-sky-200">Judul: penjelasan</code>. Teks sebelum tanda titik dua (:) pertama menjadi judul poin. Contoh: <code class="rounded bg-white px-1.5 py-0.5 font-mono text-[11px] text-sky-800 ring-1 ring-sky-200">Pemberi Fatwa: Merumuskan fatwa hukum syariah…</code></p>
                            </div>
                            <div x-data="charCount()">
                                <div class="flex items-baseline justify-between gap-3"><label for="f-profil_tugas_pokok" class="label">Daftar tugas pokok</label><span class="text-[11px] tabular-nums" :class="tone" x-text="text"></span></div>
                                <textarea id="f-profil_tugas_pokok" name="profil_tugas_pokok" rows="7" x-model.fill="v.profil_tugas_pokok" placeholder="Judul poin: penjelasan poin" class="input resize-y leading-relaxed @error('profil_tugas_pokok') input-error @enderror">{{ $val('profil_tugas_pokok') }}</textarea>
                                @error('profil_tugas_pokok')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                            </div>
                            <div x-data="{ open: true }" class="rounded-2xl bg-sand-100/70 p-4 ring-1 ring-sand-200">
                                <button type="button" @click="open = !open" :aria-expanded="open" class="flex w-full items-center justify-between gap-3 text-left text-xs font-bold tracking-wider text-stone-500 uppercase">
                                    <span class="flex items-center gap-2"><x-icon name="eye" class="size-4 text-brand-600" /> Pratinjau · <span x-text="parse(v.profil_tugas_pokok, ':').length"></span> poin</span>
                                    <x-icon name="chevron-down" class="size-4 transition" ::class="open && 'rotate-180'" />
                                </button>
                                <div x-show="open" x-collapse>
                                    <div class="scrollbar-thin mt-3 max-h-96 overflow-y-auto pr-1" x-html="listHtml(v.profil_tugas_pokok, ':', 'Belum ada poin tugas pokok.')"></div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="card">
                        <header class="flex items-start gap-3 border-b border-stone-100 px-5 py-4 sm:px-6">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100"><x-icon name="building-2" class="size-5" /></span>
                            <div><h3 class="font-semibold text-ink-900">Informasi lembaga</h3><p class="mt-0.5 text-xs text-stone-500">Kartu identitas singkat di samping halaman Profil MUI.</p></div>
                        </header>
                        <div class="grid grid-cols-1 gap-5 p-5 sm:p-6 md:grid-cols-2">
                            <div x-data="charCount(255)">
                                <div class="flex items-baseline justify-between gap-3"><label for="f-profil_tgl_berdiri" class="label">Tanggal berdiri</label><span class="text-[11px] tabular-nums" :class="tone" x-text="text"></span></div>
                                <input id="f-profil_tgl_berdiri" name="profil_tgl_berdiri" type="text" maxlength="255" value="{{ $val('profil_tgl_berdiri') }}" placeholder="cth: 26 Juli 1975 (7 Rajab 1395 H)" class="input @error('profil_tgl_berdiri') input-error @enderror">
                                @error('profil_tgl_berdiri')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                            </div>
                            <div x-data="charCount(255)">
                                <div class="flex items-baseline justify-between gap-3"><label for="f-profil_sifat_lembaga" class="label">Sifat lembaga</label><span class="text-[11px] tabular-nums" :class="tone" x-text="text"></span></div>
                                <input id="f-profil_sifat_lembaga" name="profil_sifat_lembaga" type="text" maxlength="255" value="{{ $val('profil_sifat_lembaga') }}" placeholder="cth: Lembaga Keagamaan Independen" class="input @error('profil_sifat_lembaga') input-error @enderror">
                                @error('profil_sifat_lembaga')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                            </div>
                            <div class="md:col-span-2">
                                <label for="f-profil_alamat_kantor" class="label">Alamat kantor</label>
                                <input id="f-profil_alamat_kantor" name="profil_alamat_kantor" type="text" value="{{ $val('profil_alamat_kantor') }}" placeholder="Alamat sekretariat MUI" class="input @error('profil_alamat_kantor') input-error @enderror">
                                @error('profil_alamat_kantor')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                                <p class="mt-1.5 text-xs text-stone-500">Juga dipakai sebagai alamat cadangan bila alamat pada bagian Kontak kosong.</p>
                            </div>
                        </div>
                    </section>
                </form>

                {{-- ============ 2. VISI & MISI ============ --}}
                <form id="form-visi_misi" method="POST" action="{{ route('admin.pengaturan.update') }}" role="tabpanel" aria-labelledby="tab-visi_misi"
                      x-show="tab === 'visi_misi'" @if ($active !== 'visi_misi') x-cloak @endif @submit="submit($event)" class="space-y-6">
                    @csrf
                    <input type="hidden" name="_section" value="visi_misi">

                    <section class="card">
                        <header class="flex items-start gap-3 border-b border-stone-100 px-5 py-4 sm:px-6">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100"><x-icon name="heading" class="size-5" /></span>
                            <div><h3 class="font-semibold text-ink-900">Judul &amp; pengantar halaman</h3><p class="mt-0.5 text-xs text-stone-500">Teks utama pada banner halaman Visi &amp; Misi.</p></div>
                        </header>
                        <div class="grid grid-cols-1 gap-6 p-5 sm:p-6 xl:grid-cols-5">
                            <div class="space-y-5 xl:col-span-3">
                                <div x-data="charCount(255)">
                                    <div class="flex items-baseline justify-between gap-3"><label for="f-visi_title" class="label">Judul halaman <span class="text-red-500">*</span></label><span class="text-[11px] tabular-nums" :class="tone" x-text="text"></span></div>
                                    <input id="f-visi_title" name="visi_title" type="text" maxlength="255" required value="{{ $val('visi_title', 'Visi & Misi MUI') }}" x-model.fill="v.visi_title" class="input @error('visi_title') input-error @enderror">
                                    @error('visi_title')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                                </div>
                                <div x-data="charCount()">
                                    <div class="flex items-baseline justify-between gap-3"><label for="f-visi_subtitle" class="label">Subjudul banner</label><span class="text-[11px] tabular-nums" :class="tone" x-text="text"></span></div>
                                    <textarea id="f-visi_subtitle" name="visi_subtitle" rows="3" x-model.fill="v.visi_subtitle" class="input resize-y leading-relaxed @error('visi_subtitle') input-error @enderror">{{ $val('visi_subtitle') }}</textarea>
                                    @error('visi_subtitle')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                                </div>
                            </div>
                            <div class="xl:col-span-2">
                                <p class="label flex items-center gap-1.5"><x-icon name="eye" class="size-3.5 text-stone-400" /> Pratinjau banner</p>
                                <div class="bg-gradient-brand relative overflow-hidden rounded-2xl px-5 py-6 text-white">
                                    <div class="pattern-islamic absolute inset-0"></div>
                                    <div class="absolute -top-10 -right-10 size-32 rounded-full bg-gold-400/20 blur-2xl"></div>
                                    <div class="relative">
                                        <p class="text-[10px] font-bold tracking-[.25em] text-gold-300 uppercase">Profil</p>
                                        <p class="mt-2 font-display text-xl leading-tight font-semibold text-balance text-white" x-text="v.visi_title || 'Visi & Misi MUI'"></p>
                                        <p class="mt-2 line-clamp-4 text-xs leading-relaxed text-white/70" x-show="v.visi_subtitle" x-text="v.visi_subtitle"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="card">
                        <header class="flex items-start gap-3 border-b border-stone-100 px-5 py-4 sm:px-6">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100"><x-icon name="eye" class="size-5" /></span>
                            <div><h3 class="font-semibold text-ink-900">Rumusan visi</h3><p class="mt-0.5 text-xs text-stone-500">Ditampilkan sebagai kutipan utama halaman Visi &amp; Misi.</p></div>
                        </header>
                        <div class="grid grid-cols-1 gap-6 p-5 sm:p-6 xl:grid-cols-5">
                            <div x-data="charCount()" class="xl:col-span-3">
                                <div class="flex items-baseline justify-between gap-3"><label for="f-visi_text" class="label">Teks visi <span class="text-red-500">*</span></label><span class="text-[11px] tabular-nums" :class="tone" x-text="text"></span></div>
                                <textarea id="f-visi_text" name="visi_text" rows="5" required x-model.fill="v.visi_text" class="input resize-y leading-relaxed @error('visi_text') input-error @enderror">{{ $val('visi_text') }}</textarea>
                                @error('visi_text')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                            </div>
                            <div class="xl:col-span-2">
                                <p class="label flex items-center gap-1.5"><x-icon name="eye" class="size-3.5 text-stone-400" /> Pratinjau kutipan</p>
                                <blockquote class="relative rounded-2xl border-l-4 border-gold-400 bg-gradient-to-r from-gold-50 to-white px-5 py-4">
                                    <x-icon name="quote" class="absolute top-3 right-3 size-6 text-gold-300" />
                                    <p class="pr-6 font-display text-[15px] leading-relaxed text-stone-800 italic" x-text="v.visi_text || 'Teks visi belum diisi.'"></p>
                                </blockquote>
                            </div>
                        </div>
                    </section>

                    <section class="card">
                        <header class="flex items-start gap-3 border-b border-stone-100 px-5 py-4 sm:px-6">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100"><x-icon name="target" class="size-5" /></span>
                            <div><h3 class="font-semibold text-ink-900">Butir misi</h3><p class="mt-0.5 text-xs text-stone-500">Ditampilkan berurutan sebagai daftar misi bernomor.</p></div>
                        </header>
                        <div class="space-y-4 p-5 sm:p-6">
                            <div class="flex gap-3 rounded-xl bg-sky-50 px-4 py-3 text-xs leading-relaxed text-sky-900 ring-1 ring-sky-100">
                                <x-icon name="info" class="mt-0.5 size-4 shrink-0 text-sky-600" />
                                <p>Tulis <b>satu butir per baris</b> dengan format <code class="rounded bg-white px-1.5 py-0.5 font-mono text-[11px] text-sky-800 ring-1 ring-sky-200">Judul misi | uraian misi</code>. Teks sebelum garis tegak (|) pertama menjadi judul butir.</p>
                            </div>
                            <div x-data="charCount()">
                                <div class="flex items-baseline justify-between gap-3"><label for="f-misi_list" class="label">Daftar misi</label><span class="text-[11px] tabular-nums" :class="tone" x-text="text"></span></div>
                                <textarea id="f-misi_list" name="misi_list" rows="10" x-model.fill="v.misi_list" placeholder="Judul misi | uraian misi" class="input resize-y font-mono text-[13px] leading-relaxed @error('misi_list') input-error @enderror">{{ $val('misi_list') }}</textarea>
                                @error('misi_list')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                            </div>
                            <div x-data="{ open: true }" class="rounded-2xl bg-sand-100/70 p-4 ring-1 ring-sand-200">
                                <button type="button" @click="open = !open" :aria-expanded="open" class="flex w-full items-center justify-between gap-3 text-left text-xs font-bold tracking-wider text-stone-500 uppercase">
                                    <span class="flex items-center gap-2"><x-icon name="eye" class="size-4 text-brand-600" /> Pratinjau · <span x-text="parse(v.misi_list, '|').length"></span> butir</span>
                                    <x-icon name="chevron-down" class="size-4 transition" ::class="open && 'rotate-180'" />
                                </button>
                                <div x-show="open" x-collapse>
                                    <div class="scrollbar-thin mt-3 max-h-96 overflow-y-auto pr-1" x-html="listHtml(v.misi_list, '|', 'Belum ada butir misi.')"></div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="card">
                        <header class="flex items-start gap-3 border-b border-stone-100 px-5 py-4 sm:px-6">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100"><x-icon name="scale" class="size-5" /></span>
                            <div><h3 class="font-semibold text-ink-900">Prinsip Islam Wasathiyah</h3><p class="mt-0.5 text-xs text-stone-500">Kartu prinsip moderasi beragama di samping halaman.</p></div>
                        </header>
                        <div class="grid grid-cols-1 gap-5 p-5 sm:p-6 md:grid-cols-5">
                            <div x-data="charCount(255)" class="md:col-span-2">
                                <div class="flex items-baseline justify-between gap-3"><label for="f-wasathiyah_title" class="label">Judul prinsip</label><span class="text-[11px] tabular-nums" :class="tone" x-text="text"></span></div>
                                <input id="f-wasathiyah_title" name="wasathiyah_title" type="text" maxlength="255" value="{{ $val('wasathiyah_title', 'Prinsip Islam Wasathiyah') }}" class="input @error('wasathiyah_title') input-error @enderror">
                                @error('wasathiyah_title')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                            </div>
                            <div x-data="charCount()" class="md:col-span-3">
                                <div class="flex items-baseline justify-between gap-3"><label for="f-wasathiyah_desc" class="label">Penjelasan</label><span class="text-[11px] tabular-nums" :class="tone" x-text="text"></span></div>
                                <textarea id="f-wasathiyah_desc" name="wasathiyah_desc" rows="4" class="input resize-y leading-relaxed @error('wasathiyah_desc') input-error @enderror">{{ $val('wasathiyah_desc') }}</textarea>
                                @error('wasathiyah_desc')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                            </div>
                        </div>
                    </section>
                </form>

                {{-- ============ 3. STRUKTUR ORGANISASI ============ --}}
                <form id="form-struktur" method="POST" action="{{ route('admin.pengaturan.update') }}" enctype="multipart/form-data" role="tabpanel" aria-labelledby="tab-struktur"
                      x-show="tab === 'struktur'" @if ($active !== 'struktur') x-cloak @endif @submit="submit($event)" class="space-y-6">
                    @csrf
                    <input type="hidden" name="_section" value="struktur">

                    <section class="card">
                        <header class="flex items-start gap-3 border-b border-stone-100 px-5 py-4 sm:px-6">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100"><x-icon name="heading" class="size-5" /></span>
                            <div><h3 class="font-semibold text-ink-900">Judul &amp; pengantar halaman</h3><p class="mt-0.5 text-xs text-stone-500">Teks utama pada banner halaman Struktur Organisasi.</p></div>
                        </header>
                        <div class="grid grid-cols-1 gap-6 p-5 sm:p-6 xl:grid-cols-5">
                            <div class="space-y-5 xl:col-span-3">
                                <div x-data="charCount(255)">
                                    <div class="flex items-baseline justify-between gap-3"><label for="f-struktur_title" class="label">Judul halaman <span class="text-red-500">*</span></label><span class="text-[11px] tabular-nums" :class="tone" x-text="text"></span></div>
                                    <input id="f-struktur_title" name="struktur_title" type="text" maxlength="255" required value="{{ $val('struktur_title', 'Struktur Organisasi MUI') }}" x-model.fill="v.struktur_title" class="input @error('struktur_title') input-error @enderror">
                                    @error('struktur_title')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                                </div>
                                <div x-data="charCount()">
                                    <div class="flex items-baseline justify-between gap-3"><label for="f-struktur_subtitle" class="label">Subjudul banner</label><span class="text-[11px] tabular-nums" :class="tone" x-text="text"></span></div>
                                    <textarea id="f-struktur_subtitle" name="struktur_subtitle" rows="3" x-model.fill="v.struktur_subtitle" class="input resize-y leading-relaxed @error('struktur_subtitle') input-error @enderror">{{ $val('struktur_subtitle') }}</textarea>
                                    @error('struktur_subtitle')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                                </div>
                            </div>
                            <div class="xl:col-span-2">
                                <p class="label flex items-center gap-1.5"><x-icon name="eye" class="size-3.5 text-stone-400" /> Pratinjau banner</p>
                                <div class="bg-gradient-brand relative overflow-hidden rounded-2xl px-5 py-6 text-white">
                                    <div class="pattern-islamic absolute inset-0"></div>
                                    <div class="absolute -top-10 -right-10 size-32 rounded-full bg-gold-400/20 blur-2xl"></div>
                                    <div class="relative">
                                        <p class="text-[10px] font-bold tracking-[.25em] text-gold-300 uppercase">Profil</p>
                                        <p class="mt-2 font-display text-xl leading-tight font-semibold text-balance text-white" x-text="v.struktur_title || 'Struktur Organisasi MUI'"></p>
                                        <p class="mt-2 line-clamp-4 text-xs leading-relaxed text-white/70" x-show="v.struktur_subtitle" x-text="v.struktur_subtitle"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="card">
                        <header class="flex items-start gap-3 border-b border-stone-100 px-5 py-4 sm:px-6">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100"><x-icon name="image" class="size-5" /></span>
                            <div><h3 class="font-semibold text-ink-900">Bagan struktur organisasi <span class="font-normal text-stone-400">(opsional)</span></h3><p class="mt-0.5 text-xs text-stone-500">Gambar bagan kepengurusan yang dapat diperbesar &amp; diunduh pengunjung.</p></div>
                        </header>
                        <div class="p-5 sm:p-6" x-data="baganPicker(@js($bagan))">
                            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                                <label for="f-bagan" @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="drop($event)"
                                       class="flex min-h-52 cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed p-6 text-center transition has-[:focus-visible]:ring-4 has-[:focus-visible]:ring-brand-500/20"
                                       :class="dragging ? 'border-brand-500 bg-brand-50' : 'border-stone-300 hover:border-brand-400 hover:bg-stone-50'">
                                    <span class="grid size-12 place-items-center rounded-2xl bg-brand-50 text-brand-700 ring-1 ring-brand-100"><x-icon name="image-up" class="size-6" /></span>
                                    <span class="mt-3 text-sm font-semibold text-ink-900" x-text="name ? 'Ganti dengan gambar lain' : 'Pilih atau seret gambar ke sini'">Pilih atau seret gambar ke sini</span>
                                    <span class="mt-1 text-xs text-stone-500">JPG, PNG, atau WEBP · maks. 4 MB</span>
                                    <span x-show="name" x-cloak class="mt-3 inline-flex max-w-full items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700 ring-1 ring-brand-100"><x-icon name="paperclip" class="size-3.5" /> <span class="truncate" x-text="name + ' · ' + size"></span></span>
                                    <input id="f-bagan" x-ref="file" type="file" name="struktur_bagan_gambar" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="pick($event)">
                                </label>
                                <div class="flex flex-col">
                                    <p class="label">Pratinjau</p>
                                    <div class="grid min-h-40 flex-1 place-items-center overflow-hidden rounded-2xl bg-stone-50 p-3 ring-1 ring-stone-200">
                                        <img x-show="preview" :src="preview || ''" @if (! $bagan) x-cloak @endif src="{{ $bagan }}" alt="Pratinjau bagan struktur" class="max-h-56 w-auto rounded-lg object-contain">
                                        <div x-show="!preview" @if ($bagan) x-cloak @endif class="px-4 text-center text-xs text-stone-500">
                                            <x-icon name="network" class="mx-auto size-8 text-stone-300" />
                                            <p class="mt-2" x-text="hapus ? 'Bagan akan dihapus saat disimpan.' : 'Belum ada bagan. Halaman struktur hanya menampilkan susunan pengurus.'">Belum ada bagan.</p>
                                        </div>
                                    </div>
                                    <p x-show="tooBig" x-cloak class="field-error"><x-icon name="circle-alert" class="size-3.5" /> Ukuran berkas melebihi 4 MB — pilih gambar yang lebih kecil.</p>
                                    <div x-show="name" x-cloak class="mt-2 flex justify-end">
                                        <button type="button" @click="clearFile()" class="btn btn-ghost btn-sm"><x-icon name="x" class="size-4" /> Batalkan pilihan</button>
                                    </div>
                                    @if ($bagan)
                                        <label class="mt-3 flex cursor-pointer items-center justify-between gap-3 rounded-xl border px-4 py-3 transition" :class="hapus ? 'border-red-300 bg-red-50' : 'border-stone-200 hover:border-red-200'">
                                            <span class="min-w-0 text-sm">
                                                <span class="block font-semibold" :class="hapus ? 'text-red-700' : 'text-ink-900'">Hapus bagan saat ini</span>
                                                <span class="block truncate text-xs text-stone-500">{{ basename($baganFile) }}</span>
                                            </span>
                                            <span class="relative inline-flex shrink-0">
                                                <input type="checkbox" name="hapus_bagan_gambar" value="1" x-model="hapus" @change="toggleHapus()" class="peer sr-only">
                                                <span class="h-6 w-11 rounded-full bg-stone-300 transition peer-checked:bg-red-600 peer-focus-visible:ring-4 peer-focus-visible:ring-red-500/20"></span>
                                                <span class="absolute top-0.5 left-0.5 size-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                                            </span>
                                        </label>
                                    @endif
                                </div>
                            </div>
                            @error('struktur_bagan_gambar')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                        </div>
                    </section>

                    <section class="card">
                        <header class="flex items-start gap-3 border-b border-stone-100 px-5 py-4 sm:px-6">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100"><x-icon name="crown" class="size-5" /></span>
                            <div><h3 class="font-semibold text-ink-900">Dewan Pertimbangan</h3><p class="mt-0.5 text-xs text-stone-500">Keterangan fungsi, ketua, dan anggota dewan pertimbangan.</p></div>
                        </header>
                        <div class="grid grid-cols-1 gap-5 p-5 sm:p-6 md:grid-cols-2">
                            <div x-data="charCount()" class="md:col-span-2">
                                <div class="flex items-baseline justify-between gap-3"><label for="f-struktur_dewan_pertimbangan_desc" class="label">Deskripsi dewan pertimbangan</label><span class="text-[11px] tabular-nums" :class="tone" x-text="text"></span></div>
                                <textarea id="f-struktur_dewan_pertimbangan_desc" name="struktur_dewan_pertimbangan_desc" rows="3" class="input resize-y leading-relaxed @error('struktur_dewan_pertimbangan_desc') input-error @enderror">{{ $val('struktur_dewan_pertimbangan_desc') }}</textarea>
                                @error('struktur_dewan_pertimbangan_desc')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                            </div>
                            <div x-data="charCount(255)">
                                <div class="flex items-baseline justify-between gap-3"><label for="f-struktur_ketua_pertimbangan" class="label">Ketua dewan pertimbangan</label><span class="text-[11px] tabular-nums" :class="tone" x-text="text"></span></div>
                                <input id="f-struktur_ketua_pertimbangan" name="struktur_ketua_pertimbangan" type="text" maxlength="255" value="{{ $val('struktur_ketua_pertimbangan') }}" class="input @error('struktur_ketua_pertimbangan') input-error @enderror">
                                @error('struktur_ketua_pertimbangan')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                            </div>
                            <div x-data="charCount(255)">
                                <div class="flex items-baseline justify-between gap-3"><label for="f-struktur_anggota_pertimbangan" class="label">Wakil ketua &amp; anggota</label><span class="text-[11px] tabular-nums" :class="tone" x-text="text"></span></div>
                                <input id="f-struktur_anggota_pertimbangan" name="struktur_anggota_pertimbangan" type="text" maxlength="255" value="{{ $val('struktur_anggota_pertimbangan') }}" class="input @error('struktur_anggota_pertimbangan') input-error @enderror">
                                @error('struktur_anggota_pertimbangan')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                            </div>
                        </div>
                    </section>

                    <section class="card">
                        <header class="flex items-start gap-3 border-b border-stone-100 px-5 py-4 sm:px-6">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100"><x-icon name="users" class="size-5" /></span>
                            <div><h3 class="font-semibold text-ink-900">Dewan Pimpinan Harian</h3><p class="mt-0.5 text-xs text-stone-500">Pimpinan operasional harian beserta keterangan perannya.</p></div>
                        </header>
                        <div class="space-y-5 p-5 sm:p-6">
                            <div x-data="charCount()">
                                <div class="flex items-baseline justify-between gap-3"><label for="f-struktur_pimpinan_harian_desc" class="label">Deskripsi dewan pimpinan harian</label><span class="text-[11px] tabular-nums" :class="tone" x-text="text"></span></div>
                                <textarea id="f-struktur_pimpinan_harian_desc" name="struktur_pimpinan_harian_desc" rows="3" class="input resize-y leading-relaxed @error('struktur_pimpinan_harian_desc') input-error @enderror">{{ $val('struktur_pimpinan_harian_desc') }}</textarea>
                                @error('struktur_pimpinan_harian_desc')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                            </div>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                @foreach ($pimpinan as $key => [$label, $icon])
                                    <div @class(['space-y-3 rounded-2xl bg-sand-100/60 p-4 ring-1 ring-sand-200', 'md:col-span-2' => $loop->last])>
                                        <p class="flex items-center gap-2 text-sm font-semibold text-ink-900"><span class="grid size-8 place-items-center rounded-lg bg-white text-gold-600 ring-1 ring-sand-200"><x-icon :name="$icon" class="size-4" /></span> {{ $label }}</p>
                                        <div x-data="charCount(255)">
                                            <div class="flex items-baseline justify-between gap-3"><label for="f-{{ $key }}" class="label">Nama / jabatan yang ditampilkan</label><span class="text-[11px] tabular-nums" :class="tone" x-text="text"></span></div>
                                            <input id="f-{{ $key }}" name="{{ $key }}" type="text" maxlength="255" value="{{ $val($key) }}" placeholder="{{ $label }}" class="input @error($key) input-error @enderror">
                                            @error($key)<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                                        </div>
                                        <div x-data="charCount()">
                                            <div class="flex items-baseline justify-between gap-3"><label for="f-{{ $key }}_desc" class="label">Keterangan / peran</label><span class="text-[11px] tabular-nums" :class="tone" x-text="text"></span></div>
                                            <textarea id="f-{{ $key }}_desc" name="{{ $key }}_desc" rows="2" class="input resize-y leading-relaxed @error($key.'_desc') input-error @enderror">{{ $val($key.'_desc') }}</textarea>
                                            @error($key.'_desc')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </section>

                    <section class="card">
                        <header class="flex items-start gap-3 border-b border-stone-100 px-5 py-4 sm:px-6">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100"><x-icon name="layers" class="size-5" /></span>
                            <div><h3 class="font-semibold text-ink-900">Komisi, badan &amp; lembaga otonom</h3><p class="mt-0.5 text-xs text-stone-500">Daftar komisi beserta deskripsi singkat tugasnya.</p></div>
                        </header>
                        <div class="space-y-4 p-5 sm:p-6">
                            <div class="flex gap-3 rounded-xl bg-sky-50 px-4 py-3 text-xs leading-relaxed text-sky-900 ring-1 ring-sky-100">
                                <x-icon name="info" class="mt-0.5 size-4 shrink-0 text-sky-600" />
                                <p>Tulis <b>satu komisi per baris</b> dengan format <code class="rounded bg-white px-1.5 py-0.5 font-mono text-[11px] text-sky-800 ring-1 ring-sky-200">Nama komisi | penjelasan tugas</code>. Teks sebelum garis tegak (|) pertama menjadi nama komisi.</p>
                            </div>
                            <div x-data="charCount()">
                                <div class="flex items-baseline justify-between gap-3"><label for="f-struktur_komisi_list" class="label">Daftar komisi &amp; lembaga</label><span class="text-[11px] tabular-nums" :class="tone" x-text="text"></span></div>
                                <textarea id="f-struktur_komisi_list" name="struktur_komisi_list" rows="8" x-model.fill="v.struktur_komisi_list" placeholder="Nama komisi | penjelasan tugas" class="input resize-y font-mono text-[13px] leading-relaxed @error('struktur_komisi_list') input-error @enderror">{{ $val('struktur_komisi_list') }}</textarea>
                                @error('struktur_komisi_list')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                            </div>
                            <div x-data="{ open: true }" class="rounded-2xl bg-sand-100/70 p-4 ring-1 ring-sand-200">
                                <button type="button" @click="open = !open" :aria-expanded="open" class="flex w-full items-center justify-between gap-3 text-left text-xs font-bold tracking-wider text-stone-500 uppercase">
                                    <span class="flex items-center gap-2"><x-icon name="eye" class="size-4 text-brand-600" /> Pratinjau · <span x-text="parse(v.struktur_komisi_list, '|').length"></span> komisi</span>
                                    <x-icon name="chevron-down" class="size-4 transition" ::class="open && 'rotate-180'" />
                                </button>
                                <div x-show="open" x-collapse>
                                    <div class="scrollbar-thin mt-3 max-h-96 overflow-y-auto pr-1" x-html="listHtml(v.struktur_komisi_list, '|', 'Belum ada komisi.')"></div>
                                </div>
                            </div>
                        </div>
                    </section>
                </form>

                {{-- ============ 4. KONTAK & MEDIA SOSIAL ============ --}}
                <form id="form-kontak" method="POST" action="{{ route('admin.pengaturan.update') }}" role="tabpanel" aria-labelledby="tab-kontak"
                      x-show="tab === 'kontak'" @if ($active !== 'kontak') x-cloak @endif @submit="submit($event)" class="space-y-6">
                    @csrf
                    <input type="hidden" name="_section" value="kontak">

                    <section class="card">
                        <header class="flex items-start gap-3 border-b border-stone-100 px-5 py-4 sm:px-6">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100"><x-icon name="heading" class="size-5" /></span>
                            <div><h3 class="font-semibold text-ink-900">Judul &amp; pengantar halaman</h3><p class="mt-0.5 text-xs text-stone-500">Teks utama pada banner halaman Kontak.</p></div>
                        </header>
                        <div class="grid grid-cols-1 gap-6 p-5 sm:p-6 xl:grid-cols-5">
                            <div class="space-y-5 xl:col-span-3">
                                <div x-data="charCount(255)">
                                    <div class="flex items-baseline justify-between gap-3"><label for="f-kontak_title" class="label">Judul halaman <span class="text-red-500">*</span></label><span class="text-[11px] tabular-nums" :class="tone" x-text="text"></span></div>
                                    <input id="f-kontak_title" name="kontak_title" type="text" maxlength="255" required value="{{ $val('kontak_title', 'Hubungi MUI Batanghari') }}" x-model.fill="v.kontak_title" class="input @error('kontak_title') input-error @enderror">
                                    @error('kontak_title')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                                </div>
                                <div x-data="charCount()">
                                    <div class="flex items-baseline justify-between gap-3"><label for="f-kontak_subtitle" class="label">Subjudul banner</label><span class="text-[11px] tabular-nums" :class="tone" x-text="text"></span></div>
                                    <textarea id="f-kontak_subtitle" name="kontak_subtitle" rows="3" x-model.fill="v.kontak_subtitle" class="input resize-y leading-relaxed @error('kontak_subtitle') input-error @enderror">{{ $val('kontak_subtitle') }}</textarea>
                                    @error('kontak_subtitle')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                                </div>
                            </div>
                            <div class="xl:col-span-2">
                                <p class="label flex items-center gap-1.5"><x-icon name="eye" class="size-3.5 text-stone-400" /> Pratinjau banner</p>
                                <div class="bg-gradient-brand relative overflow-hidden rounded-2xl px-5 py-6 text-white">
                                    <div class="pattern-islamic absolute inset-0"></div>
                                    <div class="absolute -top-10 -right-10 size-32 rounded-full bg-gold-400/20 blur-2xl"></div>
                                    <div class="relative">
                                        <p class="text-[10px] font-bold tracking-[.25em] text-gold-300 uppercase">Kontak</p>
                                        <p class="mt-2 font-display text-xl leading-tight font-semibold text-balance text-white" x-text="v.kontak_title || 'Hubungi Kami'"></p>
                                        <p class="mt-2 line-clamp-4 text-xs leading-relaxed text-white/70" x-show="v.kontak_subtitle" x-text="v.kontak_subtitle"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="card">
                        <header class="flex items-start gap-3 border-b border-stone-100 px-5 py-4 sm:px-6">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100"><x-icon name="map-pin" class="size-5" /></span>
                            <div><h3 class="font-semibold text-ink-900">Informasi kontak &amp; alamat</h3><p class="mt-0.5 text-xs text-stone-500">Ditampilkan di halaman Kontak, kaki halaman, dan tombol WhatsApp.</p></div>
                        </header>
                        <div class="grid grid-cols-1 gap-6 p-5 sm:p-6 xl:grid-cols-5">
                            <div class="grid grid-cols-1 content-start gap-5 sm:grid-cols-2 xl:col-span-3">
                                <div class="sm:col-span-2">
                                    <label for="f-kontak_alamat" class="label">Alamat kantor lengkap</label>
                                    <textarea id="f-kontak_alamat" name="kontak_alamat" rows="2" x-model.fill="v.kontak_alamat" placeholder="Jalan, kecamatan, kabupaten, kode pos" class="input resize-y @error('kontak_alamat') input-error @enderror">{{ $val('kontak_alamat') }}</textarea>
                                    @error('kontak_alamat')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="f-kontak_telepon" class="label">Telepon kantor</label>
                                    <div class="relative">
                                        <x-icon name="phone" class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-stone-400" />
                                        <input id="f-kontak_telepon" name="kontak_telepon" type="text" maxlength="255" inputmode="tel" value="{{ $val('kontak_telepon') }}" x-model.fill="v.kontak_telepon" placeholder="(0743) 21123" class="input pl-10 @error('kontak_telepon') input-error @enderror">
                                    </div>
                                    @error('kontak_telepon')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="f-kontak_whatsapp" class="label">WhatsApp pelayanan</label>
                                    <div class="relative">
                                        <x-icon name="whatsapp" class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-[#128C4B]" />
                                        <input id="f-kontak_whatsapp" name="kontak_whatsapp" type="text" maxlength="255" inputmode="tel" value="{{ $val('kontak_whatsapp') }}" x-model.fill="v.kontak_whatsapp" placeholder="62812xxxxxxx" class="input pl-10 @error('kontak_whatsapp') input-error @enderror">
                                    </div>
                                    @error('kontak_whatsapp')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                                    <p x-show="waDigits" x-cloak class="mt-1.5 truncate text-xs text-stone-500">Tautan: <a :href="'https://wa.me/' + waDigits" target="_blank" rel="noopener" class="font-medium text-brand-700 hover:underline" x-text="'wa.me/' + waDigits"></a></p>
                                    <p x-show="waDigits.startsWith('0')" x-cloak class="mt-1 text-xs text-gold-700">Saran: awali dengan kode negara 62 agar tombol WhatsApp langsung terhubung.</p>
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="f-kontak_email" class="label">Email resmi</label>
                                    <div class="relative">
                                        <x-icon name="mail" class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-stone-400" />
                                        <input id="f-kontak_email" name="kontak_email" type="email" maxlength="255" value="{{ $val('kontak_email') }}" x-model.fill="v.kontak_email" placeholder="sekretariat@domain.id" class="input pl-10 @error('kontak_email') input-error @enderror">
                                    </div>
                                    @error('kontak_email')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="f-kontak_jam_layanan" class="label">Hari &amp; jam layanan</label>
                                    <div class="relative">
                                        <x-icon name="clock" class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-stone-400" />
                                        <input id="f-kontak_jam_layanan" name="kontak_jam_layanan" type="text" maxlength="255" value="{{ $val('kontak_jam_layanan') }}" x-model.fill="v.kontak_jam_layanan" placeholder="Senin – Jumat: 08.00 – 16.00 WIB" class="input pl-10 @error('kontak_jam_layanan') input-error @enderror">
                                    </div>
                                    @error('kontak_jam_layanan')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                                </div>
                            </div>
                            <div class="xl:col-span-2">
                                <p class="label flex items-center gap-1.5"><x-icon name="eye" class="size-3.5 text-stone-400" /> Pratinjau kartu kontak</p>
                                <ul class="divide-y divide-stone-100 rounded-2xl bg-white ring-1 ring-stone-200">
                                    <li class="flex gap-3 p-3.5"><span class="grid size-9 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700"><x-icon name="map-pin" class="size-4" /></span><span class="min-w-0 text-sm"><span class="block text-[11px] font-semibold tracking-wider text-stone-400 uppercase">Alamat sekretariat</span><span class="block text-stone-700" x-text="v.kontak_alamat || 'Memakai alamat kantor dari bagian Profil'"></span></span></li>
                                    <li x-show="v.kontak_telepon" class="flex gap-3 p-3.5"><span class="grid size-9 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700"><x-icon name="phone" class="size-4" /></span><span class="min-w-0 text-sm"><span class="block text-[11px] font-semibold tracking-wider text-stone-400 uppercase">Telepon</span><span class="block text-stone-700" x-text="v.kontak_telepon"></span></span></li>
                                    <li x-show="waDigits" class="flex gap-3 p-3.5"><span class="grid size-9 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700"><x-icon name="whatsapp" class="size-4" /></span><span class="min-w-0 text-sm"><span class="block text-[11px] font-semibold tracking-wider text-stone-400 uppercase">WhatsApp</span><span class="block text-stone-700" x-text="v.kontak_whatsapp"></span></span></li>
                                    <li x-show="v.kontak_email" class="flex gap-3 p-3.5"><span class="grid size-9 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700"><x-icon name="mail" class="size-4" /></span><span class="min-w-0 text-sm"><span class="block text-[11px] font-semibold tracking-wider text-stone-400 uppercase">Email</span><span class="block truncate text-stone-700" x-text="v.kontak_email"></span></span></li>
                                    <li class="flex gap-3 p-3.5"><span class="grid size-9 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700"><x-icon name="clock" class="size-4" /></span><span class="min-w-0 text-sm"><span class="block text-[11px] font-semibold tracking-wider text-stone-400 uppercase">Jam layanan</span><span class="block text-stone-700" x-text="v.kontak_jam_layanan || 'Senin – Jumat: 08.00 – 16.00 WIB'"></span></span></li>
                                </ul>
                            </div>
                        </div>
                    </section>

                    <section class="card">
                        <header class="flex items-start gap-3 border-b border-stone-100 px-5 py-4 sm:px-6">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100"><x-icon name="map-pinned" class="size-5" /></span>
                            <div><h3 class="font-semibold text-ink-900">Peta lokasi (Google Maps)</h3><p class="mt-0.5 text-xs text-stone-500">Peta interaktif di halaman Kontak. Kosongkan untuk menampilkan tombol “Buka di Google Maps”.</p></div>
                        </header>
                        <div class="space-y-4 p-5 sm:p-6">
                            <div>
                                <label for="f-kontak_maps_embed" class="label">URL embed peta</label>
                                <textarea id="f-kontak_maps_embed" name="kontak_maps_embed" rows="3" x-model.fill="v.kontak_maps_embed" @input="mapsInput()" placeholder="https://www.google.com/maps/embed?pb=…" spellcheck="false" class="input resize-y font-mono text-[12px] leading-relaxed break-all @error('kontak_maps_embed') input-error @enderror">{{ $val('kontak_maps_embed') }}</textarea>
                                @error('kontak_maps_embed')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                                <p class="mt-1.5 text-xs text-stone-500">Di Google Maps: <b>Bagikan → Sematkan peta → Salin HTML</b>, lalu tempel di sini. Kode <code class="font-mono">&lt;iframe&gt;</code> otomatis diubah menjadi URL-nya saja.</p>
                                <p x-show="v.kontak_maps_embed && !mapValid" x-cloak class="mt-1 flex items-center gap-1 text-xs font-medium text-gold-700"><x-icon name="triangle-alert" class="size-3.5" /> Alamat ini bukan URL embed Google Maps — peta mungkin tidak tampil.</p>
                            </div>
                            <div x-show="v.kontak_maps_embed" x-cloak>
                                <button type="button" @click="mapPreview = !mapPreview" class="btn btn-outline btn-sm">
                                    <x-icon name="map" class="size-4" /> <span x-text="mapPreview ? 'Sembunyikan pratinjau peta' : 'Tampilkan pratinjau peta'"></span>
                                </button>
                                <template x-if="mapPreview">
                                    <iframe :src="v.kontak_maps_embed" title="Pratinjau peta lokasi" class="mt-3 h-72 w-full rounded-2xl ring-1 ring-stone-200" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                                </template>
                            </div>
                        </div>
                    </section>

                    <section class="card">
                        <header class="flex items-start gap-3 border-b border-stone-100 px-5 py-4 sm:px-6">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100"><x-icon name="share-2" class="size-5" /></span>
                            <div><h3 class="font-semibold text-ink-900">Akun media sosial resmi</h3><p class="mt-0.5 text-xs text-stone-500">Isi @username, nama halaman, atau tautan lengkap. Kolom kosong tidak ditampilkan.</p></div>
                        </header>
                        <div class="grid grid-cols-1 gap-5 p-5 sm:p-6 md:grid-cols-2">
                            @foreach ($socials as $key => [$label, $icon, $placeholder, $profileBase, $searchBase])
                                <div>
                                    <label for="f-{{ $key }}" class="label">{{ $label }}</label>
                                    <div class="relative">
                                        <x-icon :name="$icon" class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-stone-500" />
                                        <input id="f-{{ $key }}" name="{{ $key }}" type="text" maxlength="255" value="{{ $val($key) }}" x-model.fill="v.{{ $key }}" placeholder="{{ $placeholder }}" class="input pl-10 @error($key) input-error @enderror">
                                    </div>
                                    @error($key)<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                                    <p x-show="v.{{ $key }}" x-cloak class="mt-1.5 flex min-w-0 items-center gap-1 text-xs">
                                        <template x-if="social(v.{{ $key }}, @js($profileBase), @js($searchBase))">
                                            <span class="flex min-w-0 items-center gap-1 text-stone-500"><x-icon name="link-2" class="size-3.5 shrink-0" /> <a :href="social(v.{{ $key }}, @js($profileBase), @js($searchBase))" target="_blank" rel="noopener" class="truncate font-medium text-brand-700 hover:underline" x-text="social(v.{{ $key }}, @js($profileBase), @js($searchBase))"></a></span>
                                        </template>
                                        <template x-if="!social(v.{{ $key }}, @js($profileBase), @js($searchBase))">
                                            <span class="flex items-center gap-1 font-medium text-gold-700"><x-icon name="triangle-alert" class="size-3.5 shrink-0" /> Nama berspasi tidak dapat dijadikan tautan {{ $label }} — gunakan @username atau tautan.</span>
                                        </template>
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </section>
                </form>

                {{-- ============ 5. LIVE CHAT & JAM KERJA ============ --}}
                <form id="form-livechat" method="POST" action="{{ route('admin.pengaturan.update') }}" role="tabpanel" aria-labelledby="tab-livechat"
                      x-show="tab === 'livechat'" @if ($active !== 'livechat') x-cloak @endif @submit="submit($event)" class="space-y-6">
                    @csrf
                    <input type="hidden" name="_section" value="livechat">

                    <section class="card">
                        <header class="flex items-start gap-3 border-b border-stone-100 px-5 py-4 sm:px-6">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100"><x-icon name="power" class="size-5" /></span>
                            <div><h3 class="font-semibold text-ink-900">Status fitur live chat</h3><p class="mt-0.5 text-xs text-stone-500">Mengatur tampil-tidaknya widget obrolan di seluruh halaman publik.</p></div>
                        </header>
                        <div class="space-y-4 p-5 sm:p-6">
                            <label class="flex cursor-pointer items-center justify-between gap-4 rounded-2xl border p-4 transition sm:p-5" :class="v.chat_is_enabled ? 'border-brand-300 bg-brand-50/50' : 'border-stone-200 bg-stone-50'">
                                <span class="flex items-start gap-3.5">
                                    <span class="grid size-11 shrink-0 place-items-center rounded-xl transition" :class="v.chat_is_enabled ? 'bg-brand-700 text-white' : 'bg-stone-200 text-stone-500'"><x-icon name="messages-square" class="size-5" /></span>
                                    <span>
                                        <span class="block text-sm font-semibold text-ink-900">Aktifkan live chat di website</span>
                                        <span class="mt-0.5 block text-xs text-stone-500" x-text="v.chat_is_enabled ? 'Widget obrolan tampil di seluruh halaman publik.' : 'Widget obrolan disembunyikan dari pengunjung.'"></span>
                                    </span>
                                </span>
                                <span class="relative inline-flex shrink-0">
                                    <input type="checkbox" name="chat_is_enabled" value="1" @checked($chatEnabled) x-model.fill="v.chat_is_enabled" class="peer sr-only" aria-label="Aktifkan live chat">
                                    <span class="h-7 w-12 rounded-full bg-stone-300 transition peer-checked:bg-brand-600 peer-focus-visible:ring-4 peer-focus-visible:ring-brand-500/20"></span>
                                    <span class="absolute top-1 left-1 size-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                                </span>
                            </label>
                            <p class="flex items-center gap-2 rounded-xl px-4 py-3 text-xs font-medium ring-1" :class="chatNow.open ? 'bg-brand-50 text-brand-800 ring-brand-100' : 'bg-gold-50 text-gold-800 ring-gold-200'">
                                <span class="relative flex size-2.5"><span class="absolute inline-flex size-full animate-ping rounded-full opacity-60" :class="chatNow.open ? 'bg-brand-500' : 'bg-gold-500'"></span><span class="relative inline-flex size-2.5 rounded-full" :class="chatNow.open ? 'bg-brand-600' : 'bg-gold-500'"></span></span>
                                <span x-text="chatNow.text"></span>
                            </p>
                        </div>
                    </section>

                    <section class="card">
                        <header class="flex items-start gap-3 border-b border-stone-100 px-5 py-4 sm:px-6">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100"><x-icon name="calendar-clock" class="size-5" /></span>
                            <div><h3 class="font-semibold text-ink-900">Jam &amp; hari operasional petugas</h3><p class="mt-0.5 text-xs text-stone-500">Di luar jadwal ini, pengunjung otomatis dilayani asisten virtual (chatbot FAQ 24 jam).</p></div>
                        </header>
                        <div class="space-y-5 p-5 sm:p-6">
                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                                <div>
                                    <label for="f-chat_operational_start" class="label">Jam mulai <span class="text-red-500">*</span></label>
                                    <input id="f-chat_operational_start" name="chat_operational_start" type="time" required value="{{ $timeVal('chat_operational_start', '08:00') }}" x-model.fill="v.chat_operational_start" class="input @error('chat_operational_start') input-error @enderror">
                                    @error('chat_operational_start')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                                    <p class="mt-1.5 text-xs text-stone-500">Format 24 jam (WIB)</p>
                                </div>
                                <div>
                                    <label for="f-chat_operational_end" class="label">Jam selesai <span class="text-red-500">*</span></label>
                                    <input id="f-chat_operational_end" name="chat_operational_end" type="time" required value="{{ $timeVal('chat_operational_end', '16:00') }}" x-model.fill="v.chat_operational_end" class="input @error('chat_operational_end') input-error @enderror">
                                    @error('chat_operational_end')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                                    <p x-show="v.chat_operational_start && v.chat_operational_end && v.chat_operational_end <= v.chat_operational_start" x-cloak class="mt-1.5 text-xs font-medium text-gold-700">Jam selesai sebaiknya setelah jam mulai.</p>
                                </div>
                                <div>
                                    <label for="f-chat_avg_wait_minutes" class="label">Estimasi waktu tunggu <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <input id="f-chat_avg_wait_minutes" name="chat_avg_wait_minutes" type="number" min="1" max="60" required value="{{ $val('chat_avg_wait_minutes', '4') }}" x-model.fill="v.chat_avg_wait_minutes" class="input pr-28 @error('chat_avg_wait_minutes') input-error @enderror">
                                        <span class="pointer-events-none absolute top-1/2 right-3.5 -translate-y-1/2 text-xs text-stone-400">menit / antrian</span>
                                    </div>
                                    @error('chat_avg_wait_minutes')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                                    <p class="mt-1.5 text-xs text-stone-500">Untuk menghitung perkiraan waktu tunggu.</p>
                                </div>
                            </div>
                            <div>
                                <p class="label">Hari operasional</p>
                                <div class="flex flex-wrap gap-2" role="group" aria-label="Hari operasional petugas">
                                    @foreach ($dayNames as $num => $name)
                                        <button type="button" @click="toggleDay(@js((string) $num))" :aria-pressed="days.includes(@js((string) $num))" class="chip" :class="days.includes(@js((string) $num)) && 'active'">
                                            <x-icon name="check" class="size-3.5" x-show="days.includes({{ Js::from((string) $num) }})" />
                                            {{ $name }}
                                        </button>
                                    @endforeach
                                </div>
                                <input type="hidden" name="chat_operational_days" x-ref="daysInput" value="{{ implode(',', $days) }}" :value="daysValue">
                                @error('chat_operational_days')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                                <p class="mt-3 flex items-start gap-2 rounded-xl bg-stone-50 px-4 py-3 text-sm text-stone-700 ring-1 ring-stone-200">
                                    <x-icon name="clock" class="mt-0.5 size-4 shrink-0 text-brand-600" />
                                    <span x-show="days.length">Petugas melayani <b x-text="daysText"></b>, pukul <b x-text="(v.chat_operational_start || '–') + '–' + (v.chat_operational_end || '–')"></b> WIB.</span>
                                    <span x-show="!days.length" x-cloak class="text-gold-800">Tidak ada hari layanan — pengunjung selalu dilayani asisten virtual.</span>
                                </p>
                            </div>
                        </div>
                    </section>

                    <section class="card">
                        <header class="flex items-start gap-3 border-b border-stone-100 px-5 py-4 sm:px-6">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100"><x-icon name="bot" class="size-5" /></span>
                            <div><h3 class="font-semibold text-ink-900">Pesan &amp; sapaan chatbot</h3><p class="mt-0.5 text-xs text-stone-500">Teks pembuka dan respons otomatis saat petugas tidak tersedia.</p></div>
                        </header>
                        <div class="grid grid-cols-1 gap-6 p-5 sm:p-6 xl:grid-cols-5">
                            <div class="space-y-5 xl:col-span-3">
                                <div x-data="charCount()">
                                    <div class="flex items-baseline justify-between gap-3"><label for="f-chat_bot_greeting" class="label">Sapaan awal (jam kerja) <span class="text-red-500">*</span></label><span class="text-[11px] tabular-nums" :class="tone" x-text="text"></span></div>
                                    <textarea id="f-chat_bot_greeting" name="chat_bot_greeting" rows="3" required x-model.fill="v.chat_bot_greeting" class="input resize-y leading-relaxed @error('chat_bot_greeting') input-error @enderror">{{ $val('chat_bot_greeting', 'Assalamu\'alaikum! Selamat datang di Layanan Bantuan Online MUI Batanghari. Ada yang bisa kami bantu?') }}</textarea>
                                    @error('chat_bot_greeting')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                                </div>
                                <div x-data="charCount()">
                                    <div class="flex items-baseline justify-between gap-3"><label for="f-chat_offline_message" class="label">Pesan di luar jam operasional <span class="text-red-500">*</span></label><span class="text-[11px] tabular-nums" :class="tone" x-text="text"></span></div>
                                    <textarea id="f-chat_offline_message" name="chat_offline_message" rows="5" required x-model.fill="v.chat_offline_message" class="input resize-y leading-relaxed @error('chat_offline_message') input-error @enderror">{{ $val('chat_offline_message', 'Mohon maaf, saat ini kantor MUI Batanghari sedang di luar jam operasional (Jam kerja: Senin - Jumat 08.00 - 16.00 WIB). Silakan pilih pertanyaan umum di bawah ini atau tinggalkan pesan untuk petugas kami.') }}</textarea>
                                    @error('chat_offline_message')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                                </div>
                            </div>
                            <div class="xl:col-span-2">
                                <p class="label flex items-center gap-1.5"><x-icon name="eye" class="size-3.5 text-stone-400" /> Pratinjau obrolan</p>
                                <div class="overflow-hidden rounded-2xl bg-sand-50 ring-1 ring-stone-200">
                                    <div class="bg-gradient-brand relative flex items-center gap-3 px-4 py-3 text-white">
                                        <div class="pattern-islamic absolute inset-0"></div>
                                        <img src="{{ $site['logo_url'] }}" alt="" class="relative size-9 rounded-full bg-white object-contain p-0.5">
                                        <div class="relative min-w-0">
                                            <p class="truncate text-sm font-semibold text-white">Layanan Bantuan {{ $site['site_short'] }}</p>
                                            <p class="flex items-center gap-1.5 text-[11px] text-white/70"><span class="size-2 rounded-full" :class="chatNow.open ? 'bg-brand-300' : 'bg-gold-300'"></span> <span x-text="chatNow.open ? 'Petugas online' : 'Asisten virtual 24 jam'"></span></p>
                                        </div>
                                    </div>
                                    <div class="space-y-4 p-4">
                                        <div>
                                            <p class="mb-1.5 text-[10px] font-bold tracking-wider text-stone-400 uppercase">Dalam jam layanan</p>
                                            <div class="max-w-[90%] rounded-2xl rounded-tl-sm bg-white px-3.5 py-2.5 text-[13px] leading-relaxed whitespace-pre-line text-stone-700 shadow-sm ring-1 ring-stone-200/70" x-text="v.chat_bot_greeting || '—'"></div>
                                        </div>
                                        <div>
                                            <p class="mb-1.5 text-[10px] font-bold tracking-wider text-stone-400 uppercase">Di luar jam layanan</p>
                                            <div class="max-w-[90%] rounded-2xl rounded-tl-sm bg-gold-50 px-3.5 py-2.5 text-[13px] leading-relaxed whitespace-pre-line text-stone-700 shadow-sm ring-1 ring-gold-200" x-text="v.chat_offline_message || '—'"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </form>

                {{-- Bilah simpan (menempel di bawah) --}}
                <div class="sticky bottom-4 z-20 mt-6">
                    <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-stone-200 bg-white/95 px-4 py-3 shadow-[var(--shadow-lift)] backdrop-blur sm:px-5">
                        <span class="grid size-9 shrink-0 place-items-center rounded-xl transition" :class="dirty[tab] ? 'bg-gold-50 text-gold-700' : 'bg-brand-50 text-brand-700'">
                            <x-icon name="pencil-line" class="size-4" x-show="dirty[tab]" x-cloak />
                            <x-icon name="circle-check" class="size-4" x-show="!dirty[tab]" />
                        </span>
                        <div class="min-w-0 grow basis-48">
                            <p class="text-sm font-semibold text-ink-900" x-text="dirty[tab] ? 'Ada perubahan yang belum disimpan' : 'Tidak ada perubahan'">Tidak ada perubahan</p>
                            <p class="truncate text-xs text-stone-500">Bagian: <span class="font-medium text-stone-700" x-text="labels[tab]">{{ $sections[$active]['label'] }}</span></p>
                        </div>
                        <div class="ml-auto flex flex-wrap justify-end gap-2">
                            <button type="button" x-show="dirty[tab]" x-cloak @click="revert()" class="btn btn-ghost btn-sm"><x-icon name="undo-2" class="size-4" /> Urungkan</button>
                            <button type="submit" form="form-{{ $active }}" :form="'form-' + tab" class="btn btn-primary btn-sm" :disabled="saving" data-action="simpan-pengaturan">
                                <x-icon name="loader-circle" class="size-4 animate-spin" x-show="saving" x-cloak />
                                <x-icon name="save" class="size-4" x-show="!saving" />
                                <span>Simpan <span class="hidden sm:inline" x-text="labels[tab]">{{ $sections[$active]['label'] }}</span></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                /** Halaman pengaturan: tab, penanda perubahan, simpan per bagian & pratinjau langsung. */
                Alpine.data('settingsPage', (config) => ({
                    tab: config.active,
                    labels: config.labels,
                    links: config.links,
                    days: [...config.days],
                    initialDays: [...config.days],
                    v: {},
                    dirty: {},
                    snapshots: {},
                    saving: false,
                    leaving: false,
                    mapPreview: false,
                    init() {
                        this.$nextTick(() => {
                            Object.keys(this.labels).forEach((key) => {
                                const form = this.form(key);
                                if (!form) return;
                                this.snapshots[key] = this.serialize(form);
                                this.dirty[key] = false;
                                const check = () => { this.dirty[key] = this.serialize(form) !== this.snapshots[key]; };
                                form.addEventListener('input', check);
                                form.addEventListener('change', check);
                            });
                            this.revealTab(this.tab, 'auto');
                            document.querySelector('form[id^="form-"] .input-error')?.focus();
                        });
                        window.addEventListener('beforeunload', (e) => {
                            if (!this.leaving && Object.values(this.dirty).some(Boolean)) {
                                e.preventDefault();
                                e.returnValue = '';
                            }
                        });
                    },
                    form(key) {
                        return document.getElementById(`form-${key}`);
                    },
                    go(key) {
                        this.tab = key;
                        try {
                            const url = new URL(location.href);
                            url.searchParams.set('tab', key);
                            history.replaceState(null, '', url);
                        } catch { /* abaikan */ }
                        this.revealTab(key);
                        const top = this.$refs.content.getBoundingClientRect().top;
                        if (top < 0) window.scrollTo({ top: window.scrollY + top - 96, behavior: 'smooth' });
                    },
                    /** Di layar kecil, geser deretan tab agar tab aktif berada di tengah. */
                    revealTab(key, behavior = 'smooth') {
                        const btn = document.getElementById(`tab-${key}`);
                        const strip = btn?.parentElement;
                        if (!btn || !strip || strip.scrollWidth <= strip.clientWidth) return;
                        strip.scrollTo({ left: btn.offsetLeft - (strip.clientWidth - btn.offsetWidth) / 2, behavior });
                    },
                    move(step) {
                        const keys = Object.keys(this.labels);
                        const next = keys[(keys.indexOf(this.tab) + step + keys.length) % keys.length];
                        this.go(next);
                        this.$nextTick(() => document.getElementById(`tab-${next}`)?.focus());
                    },
                    serialize(form) {
                        return [...new FormData(form).entries()]
                            .filter(([key]) => key !== '_token')
                            .map(([key, value]) => `${key}=${value instanceof File ? `file:${value.name}:${value.size}` : value}`)
                            .join('&');
                    },
                    async submit(e) {
                        const form = e.target;
                        const others = Object.keys(this.dirty).filter((k) => k !== this.tab && this.dirty[k]);
                        if (form.dataset.confirmed || !others.length) {
                            this.saving = true;
                            this.leaving = true;
                            return;
                        }
                        e.preventDefault();
                        const ok = await MUIAdmin.confirmAction({
                            title: 'Simpan bagian ini saja?',
                            message: `Perubahan pada bagian ${others.map((k) => this.labels[k]).join(', ')} belum disimpan dan akan hilang setelah halaman dimuat ulang.`,
                            confirmText: 'Tetap simpan',
                            tone: 'primary',
                        });
                        if (!ok) return;
                        form.dataset.confirmed = '1';
                        form.requestSubmit();
                    },
                    revert() {
                        const form = this.form(this.tab);
                        if (!form) return;
                        form.reset();
                        if (this.tab === 'livechat') this.days = [...this.initialDays];
                        this.mapPreview = false;
                        this.$nextTick(() => {
                            form.querySelectorAll('input:not([type=hidden]):not([type=file]), textarea, select').forEach((el) => {
                                el.dispatchEvent(new Event(el.type === 'checkbox' || el.tagName === 'SELECT' ? 'change' : 'input', { bubbles: true }));
                            });
                            this.$nextTick(() => { this.dirty[this.tab] = this.serialize(form) !== this.snapshots[this.tab]; });
                        });
                    },

                    /* ---------- Daftar per baris ---------- */
                    parse(text, sep) {
                        return String(text || '').split(/\r\n|\r|\n/).map((l) => l.trim()).filter(Boolean).map((line) => {
                            const i = line.indexOf(sep);
                            return i === -1 ? { judul: line, isi: '' } : { judul: line.slice(0, i).trim(), isi: line.slice(i + 1).trim() };
                        });
                    },
                    listHtml(text, sep, emptyText) {
                        const items = this.parse(text, sep);
                        const esc = MUIAdmin.esc;
                        if (!items.length) {
                            return `<p class="rounded-xl border border-dashed border-stone-300 bg-white/70 px-4 py-6 text-center text-xs text-stone-500">${esc(emptyText)}</p>`;
                        }
                        return '<ol class="space-y-2">' + items.map((it, i) => `
                            <li class="flex gap-3 rounded-xl bg-white p-3 ring-1 ring-stone-200/70">
                                <span class="grid size-6 shrink-0 place-items-center rounded-full bg-brand-700 text-[11px] font-bold text-white">${i + 1}</span>
                                <span class="min-w-0 text-sm">
                                    <span class="block font-semibold text-ink-900">${esc(it.judul)}</span>
                                    ${it.isi
                                        ? `<span class="mt-0.5 block text-xs leading-relaxed text-stone-600">${esc(it.isi)}</span>`
                                        : `<span class="mt-1 inline-flex items-center rounded-full bg-gold-50 px-2 py-0.5 text-[11px] font-semibold text-gold-700 ring-1 ring-gold-600/20">Tanpa uraian — tambahkan pemisah “${esc(sep)}”</span>`}
                                </span>
                            </li>`).join('') + '</ol>';
                    },

                    /* ---------- Kontak ---------- */
                    get waDigits() {
                        return String(this.v.kontak_whatsapp || '').replace(/\D/g, '');
                    },
                    get mapValid() {
                        return /^https:\/\/(www\.)?google\.[a-z.]+\/maps\/embed/i.test(String(this.v.kontak_maps_embed || '').trim());
                    },
                    mapsInput() {
                        const raw = String(this.v.kontak_maps_embed || '');
                        const m = raw.match(/<iframe[^>]*\ssrc=["']([^"']+)["']/i);
                        if (m) {
                            this.v.kontak_maps_embed = m[1].replace(/&amp;/g, '&');
                            MUIAdmin.toast('URL peta diambil otomatis dari kode iframe.', 'info');
                        }
                    },
                    /** Sama dengan Setting::socialUrl(): URL utuh, @handle, atau nama (pencarian). */
                    social(value, profileBase, searchBase = null) {
                        const val = String(value || '').trim();
                        if (!val) return null;
                        if (/^https?:\/\//i.test(val)) return val;
                        if (val.includes(' ')) return searchBase ? searchBase + encodeURIComponent(val) : null;
                        return profileBase + val.replace(/^@+/, '');
                    },

                    /* ---------- Live chat ---------- */
                    toggleDay(day) {
                        this.days = this.days.includes(day) ? this.days.filter((d) => d !== day) : [...this.days, day];
                        this.$nextTick(() => this.$refs.daysInput.dispatchEvent(new Event('change', { bubbles: true })));
                    },
                    get daysValue() {
                        return [...this.days].sort().join(',');
                    },
                    get daysText() {
                        const names = ['', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
                        const d = this.days.map(Number).sort((a, b) => a - b);
                        if (d.length === 7) return 'setiap hari';
                        const runs = d.length > 2 && d.every((x, i) => i === 0 || x === d[i - 1] + 1);
                        return runs ? `${names[d[0]]} – ${names[d[d.length - 1]]}` : d.map((x) => names[x]).join(', ');
                    },
                    get chatNow() {
                        if (!this.v.chat_is_enabled) return { open: false, text: 'Live chat nonaktif — widget obrolan tidak tampil di website.' };
                        const now = new Date(new Date().toLocaleString('en-US', { timeZone: 'Asia/Jakarta' }));
                        const day = String(((now.getDay() + 6) % 7) + 1);
                        const minutes = now.getHours() * 60 + now.getMinutes();
                        const toMin = (t) => { const [h, m] = String(t || '').split(':').map(Number); return (h || 0) * 60 + (m || 0); };
                        const open = this.days.includes(day) && minutes >= toMin(this.v.chat_operational_start) && minutes <= toMin(this.v.chat_operational_end);
                        return { open, text: open ? 'Saat ini dalam jam layanan — pengunjung dapat terhubung dengan petugas.' : 'Saat ini di luar jam layanan — pengunjung dilayani asisten virtual.' };
                    },
                }));

                /** Penghitung karakter untuk satu kolom isian (maks opsional). */
                Alpine.data('charCount', (max = 0) => ({
                    len: 0,
                    max,
                    init() {
                        const field = this.$el.querySelector('textarea, input:not([type=hidden]):not([type=checkbox])');
                        if (!field) return;
                        const update = () => { this.len = field.value.length; };
                        field.addEventListener('input', update);
                        update();
                        this.$nextTick(update);
                    },
                    get text() {
                        return this.max ? `${this.len}/${this.max}` : `${this.len.toLocaleString('id-ID')} karakter`;
                    },
                    get tone() {
                        if (this.max && this.len >= this.max) return 'font-semibold text-red-600';
                        return this.max && this.len > this.max * 0.9 ? 'text-gold-700' : 'text-stone-400';
                    },
                }));

                /** Unggah bagan struktur: pratinjau, seret-lepas, batal, hapus bagan lama. */
                Alpine.data('baganPicker', (initial) => ({
                    initial,
                    preview: initial,
                    name: null,
                    size: null,
                    dragging: false,
                    tooBig: false,
                    hapus: false,
                    init() {
                        this.$el.closest('form')?.addEventListener('reset', () => setTimeout(() => {
                            Object.assign(this, { name: null, size: null, tooBig: false, hapus: false, preview: this.initial });
                        }));
                    },
                    pick(e) {
                        const file = e.target.files?.[0];
                        if (!file) return;
                        this.name = file.name;
                        this.size = file.size > 1048576 ? `${(file.size / 1048576).toFixed(1)} MB` : `${Math.ceil(file.size / 1024)} KB`;
                        this.tooBig = file.size > 4 * 1048576;
                        this.preview = file.type.startsWith('image/') ? URL.createObjectURL(file) : null;
                        this.hapus = false;
                    },
                    drop(e) {
                        this.dragging = false;
                        const file = e.dataTransfer.files?.[0];
                        if (!file) return;
                        const dt = new DataTransfer();
                        dt.items.add(file);
                        this.$refs.file.files = dt.files;
                        this.$refs.file.dispatchEvent(new Event('change', { bubbles: true }));
                    },
                    clearFile() {
                        this.$refs.file.value = '';
                        Object.assign(this, { name: null, size: null, tooBig: false, preview: this.hapus ? null : this.initial });
                        this.$refs.file.dispatchEvent(new Event('change', { bubbles: true }));
                    },
                    toggleHapus() {
                        if (this.hapus) {
                            this.$refs.file.value = '';
                            Object.assign(this, { name: null, size: null, tooBig: false, preview: null });
                        } else {
                            this.preview = this.name ? this.preview : this.initial;
                        }
                    },
                }));
            });

            @if (session('pesan'))
                document.addEventListener('alpine:initialized', () => window.MUIAdmin?.toast(@js(session('pesan'))));
            @endif
        </script>
    @endpush
</x-layouts.admin>
