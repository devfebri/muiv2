@php
    // Ikon pada User::OPERATOR_PERMISSIONS memakai kelas MDI; dipetakan ke ikon Lucide (selaras dengan menu samping).
    $mdiToLucide = [
        'mdi mdi-newspaper' => 'newspaper',
        'mdi mdi-tag-multiple' => 'tag',
        'mdi mdi-email-outline' => 'folder-archive',
        'mdi mdi-book-open-variant' => 'scale',
        'mdi mdi-label-outline' => 'tags',
        'mdi mdi-chat-processing-outline' => 'messages-square',
        'mdi mdi-forum' => 'message-circle-question',
    ];
    $groupMeta = ['Konten' => ['file-pen-line', 'Konten Website'], 'Arsip' => ['archive', 'Arsip Digital'], 'Layanan' => ['headset', 'Layanan Umat']];
    $perms = collect($allPermissions)->map(fn ($p) => $p + ['lucide' => $mdiToLucide[$p['icon']] ?? 'circle-check']);
    $groups = $perms->groupBy('group', true);
    $keys = $perms->keys()->all();
    $total = count($keys);
    $defaults = \App\Models\User::DEFAULT_OPERATOR_PERMISSIONS;
    $displayName = $user->name_gelar ?: $user->name;
    $initials = collect(preg_split('/\s+/', trim($user->name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    // Pratinjau menu samping operator: [judul bagian, [[label, ikon, izin|null], ...]]
    $sidebar = [
        ['Utama', [['Dashboard', 'layout-dashboard', null]]],
        ['Layanan Umat', [['Live Chat', 'messages-square', 'livechat'], ['Tanya Ulama', 'message-circle-question', 'konsultasi']]],
        ['Arsip Digital', [['Arsip Surat', 'folder-archive', 'surat'], ['Fatwa MUI', 'scale', 'fatwa'], ['Kategori Fatwa', 'tags', 'kategori-fatwa']]],
        ['Konten Website', [['Berita & Artikel', 'newspaper', 'berita'], ['Kategori Berita', 'tag', 'kategori']]],
        ['Sistem', [['Profil Akun', 'circle-user-round', null]]],
    ];
@endphp

<x-layouts.admin title="Atur Hak Akses" :header="'Operator: '.$displayName">
    <div x-data="permissionEditor({ initial: @js(array_values($assignedPermissions)), keys: @js($keys) })">
        <a href="{{ route('admin.operator-permissions.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-stone-500 transition hover:text-brand-700">
            <x-icon name="arrow-left" class="size-4" /> Kembali ke daftar hak akses
        </a>

        {{-- Identitas operator --}}
        <section class="bg-gradient-brand relative mt-4 overflow-hidden rounded-3xl text-white shadow-[var(--shadow-lift)]">
            <div class="pattern-islamic absolute inset-0"></div>
            <div class="relative flex flex-col gap-6 p-6 sm:flex-row sm:items-center sm:p-8">
                @if ($user->foto_url)
                    <img src="{{ $user->foto_url }}" alt="Foto {{ $user->name }}" class="size-20 shrink-0 rounded-2xl object-cover ring-4 ring-white/15">
                @else
                    <span class="grid size-20 shrink-0 place-items-center rounded-2xl bg-white/10 font-display text-3xl font-semibold text-gold-300 ring-4 ring-white/15">{{ $initials }}</span>
                @endif
                <div class="min-w-0 flex-1">
                    <p class="text-[11px] font-bold tracking-[.2em] text-gold-300 uppercase">Pembagian tugas operator</p>
                    <h2 class="mt-1 font-display text-2xl leading-tight font-semibold text-white sm:text-3xl">{{ $displayName }}</h2>
                    <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-sm text-white/75">
                        <span class="flex items-center gap-1.5"><x-icon name="at-sign" class="size-4 text-gold-300" /> {{ $user->username }}</span>
                        <span class="flex min-w-0 items-center gap-1.5"><x-icon name="mail" class="size-4 text-gold-300" /> <span class="truncate">{{ $user->email }}</span></span>
                        @if ($user->nohp)
                            <span class="flex items-center gap-1.5"><x-icon name="whatsapp" class="size-4 text-gold-300" /> {{ $user->nohp }}</span>
                        @endif
                        @if ($user->created_at)
                            <span class="flex items-center gap-1.5"><x-icon name="calendar" class="size-4 text-gold-300" /> Bergabung {{ $user->created_at->translatedFormat('d M Y') }}</span>
                        @endif
                    </div>
                </div>
                <div class="shrink-0 rounded-2xl bg-white/10 px-6 py-4 text-center ring-1 ring-white/15 backdrop-blur sm:min-w-40">
                    <p class="text-[11px] font-semibold tracking-wider text-white/60 uppercase">Menu aktif</p>
                    <p class="mt-1 font-display text-4xl font-semibold text-white tabular-nums"><span x-text="selected.length">{{ count($assignedPermissions) }}</span><span class="text-xl text-white/45">/{{ $total }}</span></p>
                    <p class="mt-1 text-xs font-semibold" :class="selected.length === 0 ? 'text-red-300' : (selected.length >= keys.length ? 'text-brand-200' : 'text-gold-300')" x-text="statusText"></p>
                </div>
            </div>
        </section>

        <div class="mt-6 grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
            <form id="form-akses" method="POST" action="{{ route('admin.operator-permissions.update', $user) }}" @submit="submitting = true" class="min-w-0 space-y-6 lg:col-span-8">
                @csrf
                @method('PUT')

                <div class="card flex flex-wrap items-center justify-between gap-3 p-4 sm:px-5">
                    <div>
                        <p class="text-sm font-semibold text-ink-900">Pilih menu yang diberikan</p>
                        <p class="text-xs text-stone-500">Klik kartu menu untuk menyalakan atau mematikan aksesnya.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" @click="preset(@js($defaults))" class="btn btn-outline btn-sm"><x-icon name="star" class="size-4 text-gold-500" /> Bawaan (Berita &amp; Layanan)</button>
                        <button type="button" @click="preset(keys)" class="btn btn-outline btn-sm"><x-icon name="square-check-big" class="size-4 text-brand-600" /> Pilih semua</button>
                        <button type="button" @click="preset([])" class="btn btn-outline btn-sm"><x-icon name="square-x" class="size-4 text-red-500" /> Kosongkan</button>
                    </div>
                </div>

                @error('permissions')<p class="field-error" role="alert"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                @error('permissions.*')<p class="field-error" role="alert"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror

                @foreach ($groups as $group => $items)
                    @php $groupKeys = array_keys($items->all()); @endphp
                    <section class="card overflow-hidden">
                        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-stone-100 px-5 py-4">
                            <div class="flex items-center gap-3">
                                <span class="grid size-10 place-items-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100"><x-icon :name="$groupMeta[$group][0] ?? 'circle'" class="size-5" /></span>
                                <div>
                                    <h3 class="font-semibold text-ink-900">Bidang {{ $group }}</h3>
                                    <p class="text-xs text-stone-500"><span x-text="countIn(@js($groupKeys))">0</span> dari {{ count($groupKeys) }} menu dipilih</p>
                                </div>
                            </div>
                            <button type="button" @click="toggleGroup(@js($groupKeys))" class="btn btn-ghost btn-sm" x-text="countIn(@js($groupKeys)) === {{ count($groupKeys) }} ? 'Lepas semua' : 'Pilih semua'">Pilih semua</button>
                        </header>
                        <div class="grid grid-cols-1 gap-3 p-5 sm:grid-cols-2">
                            @foreach ($items as $key => $p)
                                <label class="flex cursor-pointer items-start gap-3.5 rounded-2xl border p-4 transition has-[:focus-visible]:ring-4 has-[:focus-visible]:ring-brand-500/20"
                                       :class="has(@js($key)) ? 'border-brand-500 bg-brand-50/50 shadow-sm' : 'border-stone-200 bg-white hover:border-brand-300'">
                                    <span class="grid size-10 shrink-0 place-items-center rounded-xl transition" :class="has(@js($key)) ? 'bg-brand-700 text-white' : 'bg-stone-100 text-stone-500'"><x-icon :name="$p['lucide']" class="size-5" /></span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-semibold text-ink-900">{{ $p['label'] }}</span>
                                        <span class="mt-0.5 block text-xs leading-relaxed text-stone-500">{{ $p['description'] }}</span>
                                    </span>
                                    <span class="relative mt-0.5 inline-flex shrink-0">
                                        <input type="checkbox" name="permissions[]" value="{{ $key }}" x-model="selected" @checked(in_array($key, $assignedPermissions, true)) class="peer sr-only" aria-label="Akses {{ $p['label'] }}">
                                        <span class="h-6 w-11 rounded-full bg-stone-300 transition peer-checked:bg-brand-600 peer-focus-visible:ring-4 peer-focus-visible:ring-brand-500/20"></span>
                                        <span class="absolute top-0.5 left-0.5 size-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </section>
                @endforeach

                {{-- Bilah simpan --}}
                <div class="sticky bottom-4 z-20">
                    <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-stone-200 bg-white/95 px-4 py-3 shadow-[var(--shadow-lift)] backdrop-blur sm:px-5">
                        <span class="grid size-9 shrink-0 place-items-center rounded-xl" :class="dirty ? 'bg-gold-50 text-gold-700' : 'bg-brand-50 text-brand-700'">
                            <x-icon name="pencil-line" class="size-4" x-show="dirty" x-cloak />
                            <x-icon name="circle-check" class="size-4" x-show="!dirty" />
                        </span>
                        <div class="min-w-0 grow basis-48">
                            <p class="text-sm font-semibold text-ink-900" x-text="dirty ? 'Ada perubahan yang belum disimpan' : 'Belum ada perubahan'">Belum ada perubahan</p>
                            <p class="text-xs text-stone-500"><span x-text="selected.length">{{ count($assignedPermissions) }}</span> dari {{ $total }} menu dipilih</p>
                        </div>
                        <div class="ml-auto flex flex-wrap justify-end gap-2">
                            <button type="button" x-show="dirty" x-cloak @click="preset(initial)" class="btn btn-ghost btn-sm"><x-icon name="undo-2" class="size-4" /> Urungkan</button>
                            <a href="{{ route('admin.operator-permissions.index') }}" class="btn btn-outline btn-sm">Batal</a>
                            <button type="submit" class="btn btn-primary btn-sm" :disabled="submitting">
                                <x-icon name="loader-circle" class="size-4 animate-spin" x-show="submitting" x-cloak />
                                <x-icon name="save" class="size-4" x-show="!submitting" /> Simpan Hak Akses
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <aside class="space-y-6 lg:sticky lg:top-24 lg:col-span-4">
                {{-- Pratinjau menu samping --}}
                <section class="card overflow-hidden">
                    <header class="border-b border-stone-100 px-5 py-4">
                        <h3 class="flex items-center gap-2 font-semibold text-ink-900"><x-icon name="panel-left" class="size-4 text-brand-600" /> Pratinjau menu operator</h3>
                        <p class="mt-0.5 text-xs text-stone-500">Menu samping yang akan dilihat operator ini.</p>
                    </header>
                    <div class="p-4">
                        <div class="bg-gradient-brand relative overflow-hidden rounded-2xl p-3">
                            <div class="pattern-islamic pointer-events-none absolute inset-0"></div>
                            <div class="relative flex items-center gap-2.5 border-b border-white/10 px-2 pb-3">
                                <img src="{{ $site['logo_url'] }}" alt="" class="size-8 rounded-full bg-white object-contain p-0.5 ring-2 ring-gold-300/60">
                                <span class="leading-tight">
                                    <span class="block font-display text-sm font-semibold text-white">Panel Operator</span>
                                    <span class="block text-[9px] font-bold tracking-[.2em] text-gold-300 uppercase">{{ $site['site_short'] }}</span>
                                </span>
                            </div>
                            <nav class="relative mt-3 space-y-3" aria-label="Pratinjau menu">
                                @foreach ($sidebar as [$section, $items])
                                    @php $sectionKeys = collect($items)->pluck(2)->filter()->values()->all(); @endphp
                                    <div @if ($sectionKeys) x-show="@js($sectionKeys).some((k) => has(k))" x-transition @endif>
                                        <p class="px-2 text-[9px] font-bold tracking-[.2em] text-white/40 uppercase">{{ $section }}</p>
                                        <ul class="mt-1 space-y-0.5">
                                            @foreach ($items as [$label, $icon, $perm])
                                                <li @if ($perm) x-show="has(@js($perm))" x-transition @endif class="flex items-center gap-2.5 rounded-lg px-2 py-1.5 text-[12.5px] font-medium text-white/75">
                                                    <x-icon :name="$icon" class="size-4" /> {{ $label }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endforeach
                                <p x-show="selected.length === 0" x-cloak class="rounded-lg bg-white/5 px-3 py-2 text-[11px] leading-relaxed text-white/60">Tanpa menu operasional — operator hanya melihat Dashboard dan Profil Akun.</p>
                            </nav>
                        </div>
                    </div>
                </section>

                {{-- Aksi cepat langsung tersimpan --}}
                <section class="card p-5">
                    <h3 class="flex items-center gap-2 font-semibold text-ink-900"><x-icon name="zap" class="size-4 text-gold-500" /> Aksi cepat</h3>
                    <p class="mt-1 text-xs text-stone-500">Langsung tersimpan tanpa menekan tombol Simpan.</p>
                    <div class="mt-4 grid gap-2">
                        <form method="POST" action="{{ route('admin.operator-permissions.grant-all', $user) }}"
                              data-confirm="Operator “{{ $displayName }}” akan dapat membuka seluruh {{ $total }} menu operasional." data-confirm-title="Beri akses penuh?" data-confirm-button="Ya, beri akses penuh" data-confirm-tone="primary"
                              @submit="if ($el.dataset.confirmed) submitting = true">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-3 rounded-xl border border-stone-200 px-4 py-3 text-left transition hover:border-brand-400 hover:bg-brand-50/50">
                                <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-brand-50 text-brand-700"><x-icon name="check-check" class="size-4" /></span>
                                <span><span class="block text-sm font-semibold text-ink-900">Beri akses penuh</span><span class="block text-xs text-stone-500">Aktifkan seluruh {{ $total }} menu</span></span>
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.operator-permissions.revoke-all', $user) }}"
                              data-confirm="Seluruh menu operasional untuk “{{ $displayName }}” akan dicabut. Operator hanya dapat membuka Dashboard dan Profil Akun." data-confirm-title="Cabut semua akses?" data-confirm-button="Ya, cabut semua"
                              @submit="if ($el.dataset.confirmed) submitting = true">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-3 rounded-xl border border-stone-200 px-4 py-3 text-left transition hover:border-red-300 hover:bg-red-50/60">
                                <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-red-50 text-red-600"><x-icon name="shield-off" class="size-4" /></span>
                                <span><span class="block text-sm font-semibold text-ink-900">Cabut semua akses</span><span class="block text-xs text-stone-500">Nonaktifkan seluruh menu operasional</span></span>
                            </button>
                        </form>
                    </div>
                    <p class="mt-4 flex gap-2 border-t border-stone-100 pt-4 text-xs leading-relaxed text-stone-500"><x-icon name="info" class="mt-0.5 size-3.5 shrink-0 text-sky-600" /> Perubahan berlaku saat operator memuat ulang halaman panel.</p>
                </section>
            </aside>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('permissionEditor', ({ initial, keys }) => ({
                    initial: [...initial],
                    selected: [...initial],
                    keys,
                    submitting: false,
                    init() {
                        window.addEventListener('beforeunload', (e) => {
                            if (this.dirty && !this.submitting) {
                                e.preventDefault();
                                e.returnValue = '';
                            }
                        });
                    },
                    get dirty() {
                        return [...this.selected].sort().join() !== [...this.initial].sort().join();
                    },
                    get statusText() {
                        const n = this.selected.length;
                        return n === 0 ? 'Belum ada tugas' : (n >= this.keys.length ? 'Akses penuh' : 'Akses sebagian');
                    },
                    has(key) {
                        return this.selected.includes(key);
                    },
                    countIn(group) {
                        return group.filter((k) => this.has(k)).length;
                    },
                    preset(list) {
                        this.selected = this.keys.filter((k) => list.includes(k));
                    },
                    toggleGroup(group) {
                        this.selected = this.countIn(group) === group.length
                            ? this.selected.filter((k) => !group.includes(k))
                            : this.keys.filter((k) => this.has(k) || group.includes(k));
                    },
                }));
            });
        </script>
    @endpush
</x-layouts.admin>
