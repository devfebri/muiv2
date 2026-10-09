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
    $defaults = \App\Models\User::DEFAULT_OPERATOR_PERMISSIONS;
    $base = route('admin.operator-permissions.index');
    $initialSearch = is_string(request('search')) ? trim(request('search')) : '';
@endphp

<x-layouts.admin title="Hak Akses Operator" header="Pembagian tugas & menu operasional untuk setiap operator">
    <div x-data="opOverview({ base: @js($base), keys: @js($keys), defaultKeys: @js($defaults) })">
        <div x-data="serverTable({ url: @js($base), columns: ['id', 'name', 'username', 'email', 'created_at'], order: [1, 'asc'], perPage: 10 })" x-init="search = @js($initialSearch)">
            <x-admin.page-header eyebrow="Sistem" title="Hak Akses & Tugas Operator" description="Tentukan menu operasional yang dapat dibuka setiap operator. Klik menu pada kartu untuk menyalakan atau mematikannya, lalu simpan. Administrator selalu memiliki akses penuh.">
                <x-slot:actions>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline"><x-icon name="users" class="size-4" /> Kelola Pengguna</a>
                    <button type="button" @click="$dispatch('operator:open')" class="btn btn-primary"><x-icon name="user-plus" class="size-4" /> Tambah Operator</button>
                </x-slot:actions>
            </x-admin.page-header>

            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-stat-card label="Total operator" icon="users" :value="$totalOperators" bind="stats.total ?? {{ (int) $totalOperators }}" note="Akun dengan peran operator" />
                <x-stat-card label="Akses penuh" icon="shield-check" bind="stats.full ?? '–'" :note="'Memegang seluruh '.count($keys).' menu'" />
                <x-stat-card label="Akses sebagian" icon="sliders-horizontal" tone="gold" bind="stats.partial ?? '–'" note="Sesuai pembagian tugas" />
                <x-stat-card label="Belum ada tugas" icon="shield-off" tone="red" bind="stats.none ?? '–'" note="Hanya Dashboard & Profil Akun" />
            </div>

            <div class="mt-6 grid grid-cols-1 items-start gap-6 xl:grid-cols-12">
                <div class="min-w-0 xl:col-span-8">
                    {{-- Bilah alat --}}
                    <div class="card flex flex-wrap items-center gap-3 p-4">
                        <div class="relative min-w-52 flex-1">
                            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-stone-400" />
                            <input type="search" x-model="search" placeholder="Cari nama, username, email, atau no. HP…" aria-label="Cari operator" class="input pl-10">
                        </div>
                        <select class="input w-auto" aria-label="Urutkan operator" @change="const [c, d] = $event.target.value.split(':'); sort = { col: Number(c), dir: d }; page = 1; load()">
                            <option value="1:asc">Nama A–Z</option>
                            <option value="1:desc">Nama Z–A</option>
                            <option value="2:asc">Username A–Z</option>
                            <option value="3:asc">Email A–Z</option>
                            <option value="4:desc">Terbaru ditambahkan</option>
                            <option value="4:asc">Terlama ditambahkan</option>
                        </select>
                        <select x-model.number="perPage" class="input w-auto" aria-label="Jumlah per halaman">
                            <option value="10">10 / hal</option>
                            <option value="20">20 / hal</option>
                            <option value="50">50 / hal</option>
                        </select>
                    </div>
                    <p x-show="search.trim().length > 0 && search.trim().length < 3" x-cloak class="mt-2 flex items-center gap-1.5 px-1 text-xs font-medium text-gold-700"><x-icon name="info" class="size-3.5" /> Ketik minimal 3 huruf untuk mulai mencari.</p>

                    {{-- Kartu operator --}}
                    <div class="relative mt-4">
                        <div x-show="loading && rows.length" x-cloak class="absolute inset-x-0 -top-2 h-0.5 overflow-hidden rounded-full bg-brand-100">
                            <div class="h-full w-1/3 animate-[shimmer_1.2s_linear_infinite] bg-brand-500"></div>
                        </div>
                        <div id="operators-table" class="grid grid-cols-1 gap-4 md:grid-cols-2" :class="loading && rows.length && 'opacity-70'">
                            <template x-for="row in rows" :key="row.id + ':' + (row.assigned_permissions || []).join(',')">
                                <article x-data="operatorCard(row)" :data-dirty="String(dirty)" :data-operator="row.username"
                                         class="card flex flex-col transition" :class="dirty ? 'ring-2 ring-gold-300' : 'hover:shadow-[var(--shadow-lift)]'">
                                    <header class="flex items-start gap-3 p-5 pb-4">
                                        <template x-if="row.foto_url"><img :src="row.foto_url" alt="" class="size-12 shrink-0 rounded-2xl object-cover ring-1 ring-stone-200"></template>
                                        <template x-if="!row.foto_url"><span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-brand-50 text-sm font-bold text-brand-700 ring-1 ring-brand-100" x-text="initials(row.name)"></span></template>
                                        <div class="min-w-0 flex-1">
                                            <h3 class="truncate font-semibold text-ink-900" x-text="row.name_gelar || row.name"></h3>
                                            <p class="truncate font-mono text-xs text-stone-500" x-text="'@' + row.username"></p>
                                            <p class="mt-1.5 flex items-center gap-1.5 text-xs text-stone-500"><x-icon name="mail" class="size-3.5 text-stone-400" /> <span class="truncate" x-text="row.email"></span></p>
                                            <p x-show="row.nohp" class="mt-0.5 flex items-center gap-1.5 text-xs text-stone-500"><x-icon name="whatsapp" class="size-3.5 text-brand-600" /> <span x-text="row.nohp"></span></p>
                                        </div>
                                        <div class="relative" x-data="{ menu: false }" @click.outside="menu = false" @keydown.escape="menu = false">
                                            <button type="button" @click="menu = !menu" :aria-expanded="menu" class="grid size-8 place-items-center rounded-lg text-stone-500 hover:bg-stone-100 hover:text-ink-900" aria-label="Aksi lainnya"><x-icon name="ellipsis-vertical" class="size-4" /></button>
                                            <div x-show="menu" x-cloak x-transition.origin.top.right class="absolute right-0 z-20 mt-1 w-56 overflow-hidden rounded-xl border border-stone-200 bg-white py-1 shadow-[var(--shadow-lift)]">
                                                <a :href="base + '/' + row.id + '/edit'" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-stone-700 hover:bg-stone-50"><x-icon name="sliders-horizontal" class="size-4 text-stone-400" /> Halaman pengaturan</a>
                                                <button type="button" @click="menu = false; bulk('grant-all')" class="flex w-full items-center gap-2.5 px-4 py-2.5 text-left text-sm text-brand-700 hover:bg-brand-50"><x-icon name="check-check" class="size-4" /> Beri akses penuh</button>
                                                <button type="button" @click="menu = false; bulk('revoke-all')" class="flex w-full items-center gap-2.5 px-4 py-2.5 text-left text-sm text-red-600 hover:bg-red-50"><x-icon name="shield-off" class="size-4" /> Cabut semua akses</button>
                                            </div>
                                        </div>
                                    </header>

                                    <div class="mx-5 flex items-center gap-3">
                                        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-stone-100" role="progressbar" :aria-valuenow="selected.length" aria-valuemin="0" :aria-valuemax="keys.length" aria-label="Jumlah menu aktif">
                                            <div class="h-full rounded-full transition-all duration-300" :class="selected.length === 0 ? 'bg-red-400' : (selected.length >= keys.length ? 'bg-brand-600' : 'bg-gold-500')" :style="`width: ${Math.max(selected.length / keys.length * 100, 0)}%`"></div>
                                        </div>
                                        <span class="badge" :class="selected.length === 0 ? 'badge-red' : (selected.length >= keys.length ? 'badge-green' : 'badge-gold')" x-text="statusText"></span>
                                    </div>

                                    <div class="flex-1 space-y-3.5 p-5">
                                        @foreach ($groups as $group => $items)
                                            <div>
                                                <p class="mb-1.5 flex items-center gap-1.5 text-[10px] font-bold tracking-[.15em] text-stone-400 uppercase"><x-icon :name="$groupMeta[$group][0] ?? 'circle'" class="size-3.5" /> {{ $groupMeta[$group][1] ?? $group }}</p>
                                                <div class="flex flex-wrap gap-1.5">
                                                    @foreach ($items as $key => $p)
                                                        <label title="{{ $p['description'] }}"
                                                               class="inline-flex cursor-pointer items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-semibold transition select-none has-[:focus-visible]:ring-4 has-[:focus-visible]:ring-brand-500/25"
                                                               :class="selected.includes(@js($key)) ? 'border-brand-700 bg-brand-700 text-white shadow-sm shadow-brand-900/20' : 'border-stone-200 bg-white text-stone-500 hover:border-brand-400 hover:text-brand-700'">
                                                            <input type="checkbox" value="{{ $key }}" x-model="selected" class="sr-only">
                                                            <x-icon name="check" class="size-3.5" x-show="selected.includes({{ Js::from($key) }})" />
                                                            <x-icon :name="$p['lucide']" class="size-3.5" x-show="!selected.includes({{ Js::from($key) }})" />
                                                            {{ $p['label'] }}
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <footer class="flex min-h-[57px] flex-wrap items-center gap-2 rounded-b-2xl border-t border-stone-100 bg-stone-50/70 px-5 py-2.5">
                                        <div x-show="!dirty" class="flex flex-1 flex-wrap items-center gap-1">
                                            <span class="mr-1 text-[11px] font-semibold text-stone-400">Pilih cepat:</span>
                                            <button type="button" @click="selected = [...defaultKeys]" class="rounded-lg px-2 py-1 text-xs font-semibold text-stone-600 hover:bg-white hover:text-brand-700 hover:shadow-sm">Bawaan</button>
                                            <button type="button" @click="selected = [...keys]" class="rounded-lg px-2 py-1 text-xs font-semibold text-stone-600 hover:bg-white hover:text-brand-700 hover:shadow-sm">Semua</button>
                                            <button type="button" @click="selected = []" class="rounded-lg px-2 py-1 text-xs font-semibold text-stone-600 hover:bg-white hover:text-red-600 hover:shadow-sm">Kosongkan</button>
                                        </div>
                                        <div x-show="dirty" x-cloak class="flex flex-1 flex-wrap items-center justify-between gap-2">
                                            <span class="flex items-center gap-2 text-xs font-semibold text-gold-700"><span class="size-2 animate-pulse rounded-full bg-gold-500"></span> Belum disimpan</span>
                                            <div class="flex gap-1.5">
                                                <button type="button" @click="reset()" class="btn btn-ghost btn-sm">Batal</button>
                                                <button type="button" @click="save()" :disabled="saving" class="btn btn-primary btn-sm" data-action="simpan-akses">
                                                    <x-icon name="loader-circle" class="size-4 animate-spin" x-show="saving" x-cloak />
                                                    <x-icon name="save" class="size-4" x-show="!saving" /> Simpan
                                                </button>
                                            </div>
                                        </div>
                                    </footer>
                                </article>
                            </template>

                            <template x-if="loading && !rows.length">
                                <div class="contents">
                                    <template x-for="i in 2" :key="i">
                                        <div class="card space-y-4 p-5">
                                            <div class="flex gap-3"><div class="skeleton size-12 rounded-2xl"></div><div class="flex-1 space-y-2"><div class="skeleton h-4 w-2/3"></div><div class="skeleton h-3 w-1/3"></div></div></div>
                                            <div class="skeleton h-2 w-full"></div>
                                            <div class="flex flex-wrap gap-2"><div class="skeleton h-7 w-28 rounded-full"></div><div class="skeleton h-7 w-24 rounded-full"></div><div class="skeleton h-7 w-20 rounded-full"></div></div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>

                        <div x-show="!loading && !rows.length" x-cloak class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-stone-300 bg-white/60 px-6 py-14 text-center">
                            <span class="grid size-14 place-items-center rounded-2xl bg-brand-50 text-brand-600 ring-8 ring-brand-50/50"><x-icon name="users" class="size-7" /></span>
                            <p class="mt-5 font-semibold text-ink-900" x-text="error ? 'Gagal memuat data operator' : (search.trim().length >= 3 ? 'Tidak ada operator untuk “' + search.trim() + '”' : 'Belum ada akun operator')"></p>
                            <p class="mt-1.5 max-w-sm text-sm text-stone-500" x-text="error || (search.trim().length >= 3 ? 'Coba kata kunci lain atau hapus pencarian.' : 'Tambahkan operator untuk mulai membagi tugas pengelolaan website.')"></p>
                            <button type="button" x-show="!error && search.trim().length < 3" @click="$dispatch('operator:open')" class="btn btn-primary btn-sm mt-5"><x-icon name="user-plus" class="size-4" /> Tambah Operator</button>
                            <button type="button" x-show="search" @click="search = ''" class="btn btn-outline btn-sm mt-5"><x-icon name="x" class="size-4" /> Hapus pencarian</button>
                        </div>

                        {{-- Navigasi halaman --}}
                        <div x-show="rows.length" class="mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row">
                            <p class="text-xs text-stone-500">
                                Menampilkan <b class="text-stone-700" x-text="from"></b>–<b class="text-stone-700" x-text="to"></b> dari <b class="text-stone-700" x-text="filtered"></b> operator
                                <span x-show="filtered !== total" x-cloak>(disaring dari <span x-text="total"></span>)</span>
                            </p>
                            <nav class="flex items-center gap-1" aria-label="Navigasi halaman operator">
                                <button type="button" @click="go(page - 1)" :disabled="page <= 1" class="grid size-9 place-items-center rounded-lg border border-stone-200 bg-white text-stone-600 transition hover:border-brand-500 hover:text-brand-700 disabled:opacity-40" aria-label="Sebelumnya"><x-icon name="chevron-left" class="size-4" /></button>
                                <template x-for="(p, i) in pageList" :key="i + '-' + p">
                                    <button type="button" @click="go(p)" :disabled="p === '…'" x-text="p"
                                            class="hidden min-w-9 rounded-lg px-2 py-1.5 text-sm font-semibold transition sm:block"
                                            :class="p === page ? 'bg-brand-700 text-white shadow-sm' : (p === '…' ? 'text-stone-400' : 'border border-stone-200 bg-white text-stone-600 hover:border-brand-500 hover:text-brand-700')"></button>
                                </template>
                                <span class="px-2 text-sm font-semibold text-stone-600 sm:hidden"><span x-text="page"></span> / <span x-text="pages"></span></span>
                                <button type="button" @click="go(page + 1)" :disabled="page >= pages" class="grid size-9 place-items-center rounded-lg border border-stone-200 bg-white text-stone-600 transition hover:border-brand-500 hover:text-brand-700 disabled:opacity-40" aria-label="Berikutnya"><x-icon name="chevron-right" class="size-4" /></button>
                            </nav>
                        </div>
                    </div>
                </div>

                {{-- Ringkasan cakupan --}}
                <aside class="space-y-6 xl:col-span-4">
                    <section class="card overflow-hidden">
                        <header class="border-b border-stone-100 px-5 py-4">
                            <h3 class="flex items-center gap-2 font-semibold text-ink-900"><x-icon name="layout-dashboard" class="size-4 text-brand-600" /> Cakupan menu</h3>
                            <p class="mt-0.5 text-xs text-stone-500">Jumlah operator yang memegang tiap menu operasional.</p>
                        </header>
                        <div class="space-y-5 p-5">
                            @foreach ($groups as $group => $items)
                                <div>
                                    <p class="text-[10px] font-bold tracking-[.15em] text-stone-400 uppercase">{{ $groupMeta[$group][1] ?? $group }}</p>
                                    <ul class="mt-2 space-y-3">
                                        @foreach ($items as $key => $p)
                                            <li>
                                                <div class="flex items-center justify-between gap-3 text-sm">
                                                    <span class="flex min-w-0 items-center gap-2.5 text-stone-700">
                                                        <span class="grid size-7 shrink-0 place-items-center rounded-lg bg-brand-50 text-brand-700"><x-icon :name="$p['lucide']" class="size-3.5" /></span>
                                                        <span class="truncate">{{ $p['label'] }}</span>
                                                    </span>
                                                    <span class="shrink-0 text-xs font-semibold tabular-nums" :class="loaded && !coverage[{{ Js::from($key) }}] ? 'text-red-600' : 'text-stone-500'"
                                                          x-text="!loaded ? '–' : (coverage[{{ Js::from($key) }}] ? coverage[{{ Js::from($key) }}] + ' operator' : 'Belum ada')"></span>
                                                </div>
                                                <div class="mt-1.5 ml-[38px] h-1.5 overflow-hidden rounded-full bg-stone-100">
                                                    <div class="h-full rounded-full bg-brand-500 transition-all duration-500" :style="`width: ${pct({{ Js::from($key) }})}%`"></div>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <section class="card p-5">
                        <h3 class="flex items-center gap-2 font-semibold text-ink-900"><x-icon name="sparkles" class="size-4 text-gold-500" /> Akses bawaan operator baru</h3>
                        <p class="mt-1 text-xs text-stone-500">Diberikan otomatis bila akun operator dibuat tanpa pilihan menu.</p>
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            @foreach ($defaults as $key)
                                @isset($perms[$key])
                                    <span class="badge badge-green py-1"><x-icon :name="$perms[$key]['lucide']" class="size-3" /> {{ $perms[$key]['label'] }}</span>
                                @endisset
                            @endforeach
                        </div>
                        <ul class="mt-4 space-y-2.5 border-t border-stone-100 pt-4 text-xs leading-relaxed text-stone-600">
                            <li class="flex gap-2"><x-icon name="layout-dashboard" class="mt-0.5 size-3.5 shrink-0 text-brand-600" /> Dashboard dan Profil Akun selalu dapat dibuka oleh setiap operator.</li>
                            <li class="flex gap-2"><x-icon name="refresh-cw" class="mt-0.5 size-3.5 shrink-0 text-brand-600" /> Perubahan hak akses berlaku saat operator memuat ulang halaman panel.</li>
                            <li class="flex gap-2"><x-icon name="search" class="mt-0.5 size-3.5 shrink-0 text-brand-600" /> Pencarian membutuhkan minimal 3 huruf (nama, username, email, atau no. HP).</li>
                        </ul>
                    </section>
                </aside>
            </div>
        </div>

        {{-- Modal tambah operator --}}
        <div x-data="crudForm({ name: 'operator', storeUrl: @js(route('admin.users.store')), updateUrl: @js(route('admin.users.index').'/:id'), defaults: { name: '', name_gelar: '', username: '', email: '', nohp: '', jk: '', password: '', password_confirmation: '', menu_permissions: @js($defaults) } })">
            <x-admin.modal title="'Tambah Akun Operator'" icon="user-plus" size="max-w-3xl">
                <form x-ref="form" @submit.prevent="submit()" class="flex min-h-0 flex-1 flex-col" autocomplete="off">
                    <input type="hidden" name="role" value="operator">
                    <div class="scrollbar-thin flex-1 space-y-6 overflow-y-auto p-6">
                        <p class="text-sm text-stone-500">Buat akun operator dan tetapkan pembagian tugas awalnya. Data lengkap dapat diubah kemudian di menu Pengguna.</p>
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div>
                                <label for="op-name" class="label">Nama lengkap <span class="text-red-500">*</span></label>
                                <input id="op-name" x-ref="first" name="name" x-model="data.name" type="text" maxlength="100" required placeholder="cth: Ahmad Fauzi" class="input" :class="error('name') && 'input-error'">
                                <p class="field-error" x-show="error('name')" x-text="error('name')"></p>
                            </div>
                            <div>
                                <label for="op-gelar" class="label">Nama beserta gelar</label>
                                <input id="op-gelar" name="name_gelar" x-model="data.name_gelar" type="text" maxlength="100" placeholder="cth: Ahmad Fauzi, S.Pd.I." class="input" :class="error('name_gelar') && 'input-error'">
                                <p class="field-error" x-show="error('name_gelar')" x-text="error('name_gelar')"></p>
                            </div>
                            <div>
                                <label for="op-username" class="label">Username <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-sm text-stone-400">@</span>
                                    <input id="op-username" name="username" x-model="data.username" type="text" maxlength="20" required autocapitalize="none" spellcheck="false" placeholder="username_login" class="input pl-8" :class="error('username') && 'input-error'">
                                </div>
                                <p class="field-error" x-show="error('username')" x-text="error('username')"></p>
                            </div>
                            <div>
                                <label for="op-email" class="label">Email <span class="text-red-500">*</span></label>
                                <input id="op-email" name="email" x-model="data.email" type="email" maxlength="255" required placeholder="alamat@email.com" class="input" :class="error('email') && 'input-error'">
                                <p class="field-error" x-show="error('email')" x-text="error('email')"></p>
                            </div>
                            <div>
                                <label for="op-nohp" class="label">No. WhatsApp / HP</label>
                                <input id="op-nohp" name="nohp" x-model="data.nohp" type="tel" maxlength="15" inputmode="tel" placeholder="0812xxxxxxxx" class="input" :class="error('nohp') && 'input-error'">
                                <p class="field-error" x-show="error('nohp')" x-text="error('nohp')"></p>
                            </div>
                            <div>
                                <label for="op-jk" class="label">Jenis kelamin</label>
                                <select id="op-jk" name="jk" x-model="data.jk" class="input">
                                    <option value="">Pilih jenis kelamin</option>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                            <div>
                                <label for="op-password" class="label">Kata sandi <span class="text-red-500">*</span></label>
                                <input id="op-password" name="password" x-model="data.password" type="password" minlength="8" required autocomplete="new-password" placeholder="Minimal 8 karakter" class="input" :class="error('password') && 'input-error'">
                                <p class="field-error" x-show="error('password')" x-text="error('password')"></p>
                            </div>
                            <div>
                                <label for="op-password2" class="label">Konfirmasi kata sandi <span class="text-red-500">*</span></label>
                                <input id="op-password2" name="password_confirmation" x-model="data.password_confirmation" type="password" minlength="8" required autocomplete="new-password" placeholder="Ulangi kata sandi" class="input">
                                <p x-show="data.password_confirmation" x-cloak class="mt-1.5 text-xs font-medium" :class="data.password === data.password_confirmation ? 'text-brand-700' : 'text-red-600'" x-text="data.password === data.password_confirmation ? 'Kata sandi cocok' : 'Belum cocok dengan kata sandi'"></p>
                            </div>
                        </div>

                        <fieldset class="rounded-2xl border border-stone-200 p-4 sm:p-5">
                            <legend class="px-1 text-sm font-semibold text-ink-900">Pembagian tugas awal</legend>
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-xs text-stone-500"><b class="text-brand-700" x-text="data.menu_permissions.length"></b> dari {{ count($keys) }} menu dipilih</p>
                                <div class="flex gap-1">
                                    <button type="button" @click="data.menu_permissions = [...defaultKeys]" class="rounded-lg px-2 py-1 text-xs font-semibold text-stone-600 hover:bg-stone-100 hover:text-brand-700">Bawaan</button>
                                    <button type="button" @click="data.menu_permissions = [...keys]" class="rounded-lg px-2 py-1 text-xs font-semibold text-stone-600 hover:bg-stone-100 hover:text-brand-700">Semua</button>
                                    <button type="button" @click="data.menu_permissions = []" class="rounded-lg px-2 py-1 text-xs font-semibold text-stone-600 hover:bg-stone-100 hover:text-red-600">Kosongkan</button>
                                </div>
                            </div>
                            <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                @foreach ($perms as $key => $p)
                                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border p-3 transition has-[:focus-visible]:ring-4 has-[:focus-visible]:ring-brand-500/20"
                                           :class="data.menu_permissions.includes({{ Js::from($key) }}) ? 'border-brand-500 bg-brand-50/60' : 'border-stone-200 hover:border-brand-300'">
                                        <input type="checkbox" name="menu_permissions[]" value="{{ $key }}" x-model="data.menu_permissions" class="checkbox mt-0.5">
                                        <span class="min-w-0">
                                            <span class="flex items-center gap-1.5 text-sm font-semibold text-ink-900"><x-icon :name="$p['lucide']" class="size-4 text-brand-600" /> {{ $p['label'] }}</span>
                                            <span class="mt-0.5 block text-xs text-stone-500">{{ $p['description'] }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            <p x-show="!data.menu_permissions.length" x-cloak class="mt-3 flex items-center gap-1.5 text-xs text-gold-700"><x-icon name="info" class="size-3.5" /> Bila tidak ada yang dipilih, akses bawaan akan diterapkan otomatis.</p>
                        </fieldset>
                    </div>
                    <footer class="flex shrink-0 justify-end gap-2 border-t border-stone-100 bg-stone-50/60 px-6 py-4">
                        <button type="button" @click="close()" class="btn btn-outline">Batal</button>
                        <button type="submit" class="btn btn-primary" :disabled="saving">
                            <x-icon name="loader-circle" class="size-4 animate-spin" x-show="saving" x-cloak />
                            <x-icon name="circle-check" class="size-4" x-show="!saving" /> Simpan Operator
                        </button>
                    </footer>
                </form>
            </x-admin.modal>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                /** Ringkasan seluruh operator (statistik & cakupan menu), dimuat ulang setiap tabel berubah. */
                Alpine.data('opOverview', ({ base, keys, defaultKeys }) => ({
                    base,
                    keys,
                    defaultKeys,
                    loaded: false,
                    stats: { total: null, full: null, partial: null, none: null },
                    coverage: {},
                    init() {
                        this.loadOverview();
                        window.addEventListener('table:reload', () => this.loadOverview());
                        window.addEventListener('beforeunload', (e) => {
                            if (document.querySelector('[data-dirty="true"]')) {
                                e.preventDefault();
                                e.returnValue = '';
                            }
                        });
                    },
                    async loadOverview() {
                        try {
                            let all = [];
                            let total = 1;
                            while (all.length < total) {
                                const q = new URLSearchParams({ draw: '1', start: String(all.length), length: '100', 'order[0][column]': '0', 'order[0][dir]': 'asc' });
                                const res = await MUIAdmin.http(`${this.base}?${q}`);
                                total = res.recordsTotal ?? 0;
                                if (!res.data?.length) break;
                                all = all.concat(res.data);
                            }
                            const owned = all.map((op) => (op.assigned_permissions || []).filter((k) => this.keys.includes(k)));
                            this.stats = {
                                total: all.length,
                                full: owned.filter((p) => p.length >= this.keys.length).length,
                                partial: owned.filter((p) => p.length > 0 && p.length < this.keys.length).length,
                                none: owned.filter((p) => p.length === 0).length,
                            };
                            this.coverage = Object.fromEntries(this.keys.map((k) => [k, owned.filter((p) => p.includes(k)).length]));
                            this.loaded = true;
                        } catch { /* biarkan nilai sebelumnya */ }
                    },
                    pct(key) {
                        return this.stats.total ? Math.round(((this.coverage[key] || 0) / this.stats.total) * 100) : 0;
                    },
                    initials(name) {
                        return String(name || '?').trim().split(/\s+/).filter(Boolean).slice(0, 2).map((w) => w[0].toUpperCase()).join('') || '?';
                    },
                }));

                /** Kartu satu operator: pilihan menu lokal, simpan (PUT), beri/cabut semua (POST). */
                Alpine.data('operatorCard', (row) => ({
                    original: [...(row.assigned_permissions || [])],
                    selected: [...(row.assigned_permissions || [])],
                    saving: false,
                    get dirty() {
                        return [...this.selected].sort().join() !== [...this.original].sort().join();
                    },
                    get statusText() {
                        const n = this.selected.length;
                        const t = this.keys.length;
                        return n === 0 ? 'Belum ada tugas' : (n >= t ? 'Akses penuh' : `${n}/${t} menu`);
                    },
                    reset() {
                        this.selected = [...this.original];
                    },
                    async save() {
                        if (this.saving) return;
                        this.saving = true;
                        try {
                            const res = await MUIAdmin.http(`${this.base}/${row.id}`, { method: 'PUT', body: { permissions: this.keys.filter((k) => this.selected.includes(k)) } });
                            this.original = [...(res.user?.permissions ?? this.selected)];
                            MUIAdmin.toast(res.message || 'Hak akses berhasil disimpan.');
                            MUIAdmin.reloadTables();
                        } catch (e) {
                            MUIAdmin.toast(e.message, 'error');
                        } finally {
                            this.saving = false;
                        }
                    },
                    async bulk(action) {
                        const name = row.name_gelar || row.name;
                        const grant = action === 'grant-all';
                        const ok = await MUIAdmin.confirmAction(grant
                            ? { title: 'Beri akses penuh?', message: `Operator “${name}” akan dapat membuka seluruh ${this.keys.length} menu operasional.`, confirmText: 'Ya, beri akses penuh', tone: 'primary' }
                            : { title: 'Cabut semua akses?', message: `Seluruh menu operasional untuk “${name}” akan dicabut. Operator hanya dapat membuka Dashboard dan Profil Akun.`, confirmText: 'Ya, cabut semua' });
                        if (!ok) return;
                        try {
                            const res = await MUIAdmin.http(`${this.base}/${row.id}/${action}`, { method: 'POST' });
                            MUIAdmin.toast(res.message, grant ? 'success' : 'warning');
                            MUIAdmin.reloadTables();
                        } catch (e) {
                            MUIAdmin.toast(e.message, 'error');
                        }
                    },
                }));
            });
        </script>
    @endpush
</x-layouts.admin>
