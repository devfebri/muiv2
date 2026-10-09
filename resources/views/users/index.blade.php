@php
    $base = route('admin.users.index');
    $permBase = url('admin/operator-permissions');
    $permLabels = collect(\App\Models\User::OPERATOR_PERMISSIONS)->only(\App\Models\User::DEFAULT_OPERATOR_PERMISSIONS)->pluck('label')->implode(', ');
@endphp

<x-layouts.admin title="Pengguna" header="Kelola akun administrator & operator panel">
    <div x-data="usersPage({ url: @js($base), permBase: @js($permBase), authId: @js(auth()->id()), defaults: @js(\App\Models\User::DEFAULT_OPERATOR_PERMISSIONS), permTotal: @js(count(\App\Models\User::OPERATOR_PERMISSIONS)) })">
        <div x-data="serverTable({ url: @js($base), columns: ['id', 'name', 'username', 'email', 'role', 'created_at'], order: [5, 'desc'] })">
            <x-admin.page-header eyebrow="Sistem" title="Pengguna Panel" description="Akun yang dapat masuk ke panel admin. Administrator memiliki akses penuh, sedangkan operator hanya membuka menu yang dibagikan kepadanya.">
                <x-slot:actions>
                    <a href="{{ route('admin.operator-permissions.index') }}" class="btn btn-outline"><x-icon name="shield-check" class="size-4" /> Hak Akses Operator</a>
                    <button type="button" @click="$dispatch('pengguna:open')" class="btn btn-primary"><x-icon name="user-plus" class="size-4" /> Tambah Pengguna</button>
                </x-slot:actions>
            </x-admin.page-header>

            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-stat-card label="Total pengguna" icon="users" bind="stats.total ?? '–'" note="Seluruh akun yang terdaftar" />
                <x-stat-card label="Administrator" icon="shield-check" tone="gold" bind="stats.admin ?? '–'" note="Akses penuh ke semua menu" />
                <x-stat-card label="Operator" icon="user-round-cog" tone="blue" bind="stats.operator ?? '–'" note="Akses sesuai pembagian tugas" />
            </div>

            <x-admin.table class="mt-6" colspan="7" empty="Belum ada pengguna" empty-icon="users" search-placeholder="Cari nama, username, email, atau peran…">
                <x-slot:filters>
                    <span x-show="search.trim().length > 0 && search.trim().length < 3" x-cloak class="badge badge-gold py-1"><x-icon name="info" class="size-3.5" /> Ketik minimal 3 huruf</span>
                </x-slot:filters>
                <x-slot:head>
                    <th class="w-12">#</th>
                    <x-admin.th col="1">Pengguna</x-admin.th>
                    <x-admin.th col="2">Username</x-admin.th>
                    <x-admin.th col="3">Kontak</x-admin.th>
                    <x-admin.th col="4">Peran</x-admin.th>
                    <x-admin.th col="5">Bergabung</x-admin.th>
                    <th class="text-right">Aksi</th>
                </x-slot:head>
                <x-slot:row>
                    <tr class="[&>td]:align-middle">
                        <td class="text-stone-400 tabular-nums" x-text="rowNumber(index)"></td>
                        <td>
                            <div class="flex items-center gap-3">
                                <span class="grid size-10 shrink-0 place-items-center rounded-xl text-xs font-bold" :class="row.role === 'admin' ? 'bg-brand-700 text-gold-300' : 'bg-brand-50 text-brand-700 ring-1 ring-brand-100'" x-text="initials(row.name)"></span>
                                <div class="min-w-0">
                                    <p class="flex flex-wrap items-center gap-x-2 gap-y-1 font-semibold text-ink-900">
                                        <span x-text="row.name"></span>
                                        <span x-show="row.id === authId" class="badge badge-gold">Anda</span>
                                    </p>
                                    <p class="text-xs text-stone-500" x-show="row.name_gelar" x-text="row.name_gelar"></p>
                                </div>
                            </div>
                        </td>
                        <td><span class="font-mono text-[13px] text-stone-600" x-text="'@' + row.username"></span></td>
                        <td>
                            <p class="flex items-center gap-1.5 text-stone-700"><x-icon name="mail" class="size-3.5 text-stone-400" /> <span x-text="row.email"></span></p>
                            <p class="mt-1 flex items-center gap-1.5 text-xs text-stone-500" x-show="row.nohp"><x-icon name="phone" class="size-3.5 text-stone-400" /> <span x-text="row.nohp"></span></p>
                        </td>
                        <td>
                            <span class="badge" :class="row.role === 'admin' ? 'badge-gold' : 'badge-green'">
                                <x-icon name="shield-check" class="size-3" x-show="row.role === 'admin'" />
                                <x-icon name="user-round-cog" class="size-3" x-show="row.role !== 'admin'" />
                                <span x-text="row.role === 'admin' ? 'Administrator' : 'Operator'"></span>
                            </span>
                            <p class="mt-1.5 text-[11px] text-stone-500" x-text="row.role === 'admin' ? 'Akses penuh' : permCount(row) + ' dari ' + permTotal + ' menu'"></p>
                        </td>
                        <td class="whitespace-nowrap text-stone-500" x-text="MUIAdmin.formatDate(row.created_at)"></td>
                        <td>
                            <div class="flex justify-end gap-1.5">
                                <a x-show="row.role === 'operator'" :href="permBase + '/' + row.id + '/edit'" class="grid size-8 place-items-center rounded-lg text-stone-500 hover:bg-gold-50 hover:text-gold-700" title="Atur tugas & hak akses" aria-label="Atur hak akses operator"><x-icon name="shield-check" class="size-4" /></a>
                                <button type="button" @click="$dispatch('pengguna:open', row)" class="grid size-8 place-items-center rounded-lg text-stone-500 hover:bg-brand-50 hover:text-brand-700" title="Ubah" aria-label="Ubah pengguna"><x-icon name="pencil" class="size-4" /></button>
                                <button type="button" :disabled="row.id === authId"
                                        @click="MUIAdmin.destroy(url + '/' + row.id, { title: 'Hapus pengguna?', message: `Akun “${row.name}” (@${row.username}) akan dihapus permanen dan tidak dapat lagi masuk ke panel.` })"
                                        class="grid size-8 place-items-center rounded-lg text-stone-500 hover:bg-red-50 hover:text-red-600 disabled:cursor-not-allowed disabled:opacity-35 disabled:hover:bg-transparent disabled:hover:text-stone-500"
                                        :title="row.id === authId ? 'Akun yang sedang digunakan tidak dapat dihapus' : 'Hapus'" aria-label="Hapus pengguna"><x-icon name="trash-2" class="size-4" /></button>
                            </div>
                        </td>
                    </tr>
                </x-slot:row>
            </x-admin.table>
        </div>

        {{-- Formulir tambah / ubah pengguna --}}
        <div x-data="crudForm({ name: 'pengguna', storeUrl: @js($base), updateUrl: @js($base.'/:id'), defaults: { name: '', name_gelar: '', username: '', email: '', role: 'operator', jk: '', nohp: '', alamat: '', password: '', password_confirmation: '' } })"
             @pengguna:open.window="$nextTick(() => { data.jk = normalizeJk(data.jk); showPass = false })">
            <x-admin.modal title="mode === 'edit' ? 'Ubah Pengguna' : 'Tambah Pengguna'" icon="user-round-cog" size="max-w-3xl">
                <form x-ref="form" @submit.prevent="submit()" class="flex min-h-0 flex-1 flex-col" autocomplete="off">
                    <div class="scrollbar-thin flex-1 space-y-7 overflow-y-auto p-6">
                        {{-- Identitas --}}
                        <section>
                            <h3 class="flex items-center gap-2 text-xs font-bold tracking-wider text-stone-500 uppercase"><x-icon name="id-card" class="size-4 text-gold-500" /> Identitas</h3>
                            <div class="mt-3 grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <div>
                                    <label for="u-name" class="label">Nama lengkap <span class="text-red-500">*</span></label>
                                    <input id="u-name" x-ref="first" name="name" x-model="data.name" type="text" maxlength="100" required placeholder="cth: Ahmad Fauzi" class="input" :class="error('name') && 'input-error'">
                                    <p class="field-error" x-show="error('name')" x-text="error('name')"></p>
                                </div>
                                <div>
                                    <label for="u-gelar" class="label">Nama beserta gelar <span class="font-normal text-stone-400">(opsional)</span></label>
                                    <input id="u-gelar" name="name_gelar" x-model="data.name_gelar" type="text" maxlength="100" placeholder="cth: Dr. H. Ahmad Fauzi, M.Ag." class="input" :class="error('name_gelar') && 'input-error'">
                                    <p class="field-error" x-show="error('name_gelar')" x-text="error('name_gelar')"></p>
                                </div>
                                <div>
                                    <label for="u-jk" class="label">Jenis kelamin</label>
                                    <select id="u-jk" name="jk" x-model="data.jk" class="input" :class="error('jk') && 'input-error'">
                                        <option value="">Pilih jenis kelamin</option>
                                        <option value="L">Laki-laki</option>
                                        <option value="P">Perempuan</option>
                                    </select>
                                    <p class="field-error" x-show="error('jk')" x-text="error('jk')"></p>
                                </div>
                                <div>
                                    <label for="u-nohp" class="label">No. HP / WhatsApp</label>
                                    <input id="u-nohp" name="nohp" x-model="data.nohp" type="tel" maxlength="15" inputmode="tel" placeholder="cth: 081234567890" class="input" :class="error('nohp') && 'input-error'">
                                    <p class="field-error" x-show="error('nohp')" x-text="error('nohp')"></p>
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="u-alamat" class="label">Alamat <span class="font-normal text-stone-400">(opsional)</span></label>
                                    <textarea id="u-alamat" name="alamat" x-model="data.alamat" rows="2" placeholder="Alamat domisili atau kantor" class="input resize-none" :class="error('alamat') && 'input-error'"></textarea>
                                    <p class="field-error" x-show="error('alamat')" x-text="error('alamat')"></p>
                                </div>
                            </div>
                        </section>

                        {{-- Akun & peran --}}
                        <section class="border-t border-stone-100 pt-6">
                            <h3 class="flex items-center gap-2 text-xs font-bold tracking-wider text-stone-500 uppercase"><x-icon name="key-round" class="size-4 text-gold-500" /> Akun &amp; peran</h3>
                            <div class="mt-3 grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <div>
                                    <label for="u-username" class="label">Username <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <span class="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-sm text-stone-400">@</span>
                                        <input id="u-username" name="username" x-model="data.username" type="text" maxlength="20" required autocapitalize="none" spellcheck="false" placeholder="username_login" class="input pl-8" :class="error('username') && 'input-error'">
                                    </div>
                                    <p class="field-error" x-show="error('username')" x-text="error('username')"></p>
                                    <p class="mt-1.5 text-xs text-stone-500" x-show="!error('username')">Dipakai untuk masuk. Maksimal 20 karakter &amp; harus unik.</p>
                                </div>
                                <div>
                                    <label for="u-email" class="label">Email <span class="text-red-500">*</span></label>
                                    <input id="u-email" name="email" x-model="data.email" type="email" maxlength="255" required placeholder="nama@email.com" class="input" :class="error('email') && 'input-error'">
                                    <p class="field-error" x-show="error('email')" x-text="error('email')"></p>
                                </div>
                            </div>

                            <fieldset class="mt-5">
                                <legend class="label">Peran <span class="text-red-500">*</span></legend>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition has-[:focus-visible]:ring-4 has-[:focus-visible]:ring-brand-500/20"
                                           :class="data.role === 'operator' ? 'border-brand-500 bg-brand-50/60' : 'border-stone-200 hover:border-brand-300'">
                                        <input type="radio" name="role" value="operator" x-model="data.role" class="sr-only">
                                        <span class="grid size-9 shrink-0 place-items-center rounded-lg" :class="data.role === 'operator' ? 'bg-brand-700 text-white' : 'bg-stone-100 text-stone-500'"><x-icon name="user-round-cog" class="size-4" /></span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block text-sm font-semibold text-ink-900">Operator</span>
                                            <span class="mt-0.5 block text-xs text-stone-500">Hanya membuka menu yang dibagikan admin.</span>
                                        </span>
                                        <x-icon name="circle-check" class="size-5 text-brand-600" x-show="data.role === 'operator'" />
                                    </label>
                                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition has-[:focus-visible]:ring-4 has-[:focus-visible]:ring-brand-500/20"
                                           :class="data.role === 'admin' ? 'border-gold-400 bg-gold-50/70' : 'border-stone-200 hover:border-gold-300'">
                                        <input type="radio" name="role" value="admin" x-model="data.role" class="sr-only">
                                        <span class="grid size-9 shrink-0 place-items-center rounded-lg" :class="data.role === 'admin' ? 'bg-gold-500 text-white' : 'bg-stone-100 text-stone-500'"><x-icon name="shield-check" class="size-4" /></span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block text-sm font-semibold text-ink-900">Administrator</span>
                                            <span class="mt-0.5 block text-xs text-stone-500">Akses penuh, termasuk pengguna &amp; pengaturan.</span>
                                        </span>
                                        <x-icon name="circle-check" class="size-5 text-gold-600" x-show="data.role === 'admin'" />
                                    </label>
                                </div>
                                <p class="field-error" x-show="error('role')" x-text="error('role')"></p>
                            </fieldset>

                            <p x-show="mode === 'create' && data.role === 'operator'" x-cloak class="mt-4 flex gap-2.5 rounded-xl bg-sky-50 px-4 py-3 text-xs leading-relaxed text-sky-800 ring-1 ring-sky-100">
                                <x-icon name="info" class="mt-0.5 size-4 shrink-0" />
                                <span>Operator baru otomatis mendapat akses bawaan: <b>{{ $permLabels }}</b>. Pembagian tugas dapat diubah di menu Hak Akses Operator.</span>
                            </p>
                            <p x-show="mode === 'edit' && data.id === authId && data.role !== 'admin'" x-cloak class="mt-4 flex gap-2.5 rounded-xl bg-red-50 px-4 py-3 text-xs leading-relaxed text-red-700 ring-1 ring-red-100">
                                <x-icon name="triangle-alert" class="mt-0.5 size-4 shrink-0" />
                                <span>Anda sedang mengubah peran akun Anda sendiri. Setelah disimpan, akses administrator Anda akan hilang.</span>
                            </p>
                        </section>

                        {{-- Kata sandi --}}
                        <section class="border-t border-stone-100 pt-6">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <h3 class="flex items-center gap-2 text-xs font-bold tracking-wider text-stone-500 uppercase"><x-icon name="lock-keyhole" class="size-4 text-gold-500" /> Kata sandi</h3>
                                <button type="button" @click="showPass = !showPass" class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-700 hover:text-brand-900">
                                    <x-icon name="eye" class="size-3.5" x-show="!showPass" /><x-icon name="eye-off" class="size-3.5" x-show="showPass" x-cloak />
                                    <span x-text="showPass ? 'Sembunyikan' : 'Tampilkan'"></span>
                                </button>
                            </div>
                            <p class="mt-1 text-xs text-stone-500" x-text="mode === 'edit' ? 'Kosongkan kedua kolom bila kata sandi tidak ingin diubah.' : 'Minimal 8 karakter. Sampaikan kata sandi awal kepada pemilik akun secara pribadi.'"></p>
                            <div class="mt-3 grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <div>
                                    <label for="u-password" class="label">Kata sandi <span class="text-red-500" x-show="mode === 'create'">*</span></label>
                                    <input id="u-password" name="password" x-model="data.password" :type="showPass ? 'text' : 'password'" minlength="8" :required="mode === 'create'" autocomplete="new-password" placeholder="Minimal 8 karakter" class="input" :class="error('password') && 'input-error'">
                                    <p class="field-error" x-show="error('password')" x-text="error('password')"></p>
                                    <div x-show="data.password" x-cloak class="mt-2 flex items-center gap-2">
                                        <div class="flex flex-1 gap-1">
                                            <template x-for="i in 4" :key="i">
                                                <span class="h-1.5 flex-1 rounded-full transition" :class="i <= strength(data.password) ? ['bg-red-500', 'bg-gold-500', 'bg-brand-500', 'bg-brand-700'][strength(data.password) - 1] : 'bg-stone-200'"></span>
                                            </template>
                                        </div>
                                        <span class="w-16 text-right text-[11px] font-semibold text-stone-500" x-text="['Lemah', 'Cukup', 'Baik', 'Kuat'][Math.max(strength(data.password), 1) - 1]"></span>
                                    </div>
                                </div>
                                <div>
                                    <label for="u-password2" class="label">Konfirmasi kata sandi <span class="text-red-500" x-show="mode === 'create'">*</span></label>
                                    <input id="u-password2" name="password_confirmation" x-model="data.password_confirmation" :type="showPass ? 'text' : 'password'" minlength="8" :required="mode === 'create' || !!data.password" autocomplete="new-password" placeholder="Ulangi kata sandi" class="input">
                                    <p x-show="data.password_confirmation" x-cloak class="mt-1.5 flex items-center gap-1 text-xs font-medium" :class="data.password === data.password_confirmation ? 'text-brand-700' : 'text-red-600'">
                                        <x-icon name="circle-check" class="size-3.5" x-show="data.password === data.password_confirmation" />
                                        <x-icon name="circle-alert" class="size-3.5" x-show="data.password !== data.password_confirmation" />
                                        <span x-text="data.password === data.password_confirmation ? 'Kata sandi cocok' : 'Belum cocok dengan kata sandi'"></span>
                                    </p>
                                </div>
                            </div>
                        </section>
                    </div>
                    <footer class="flex shrink-0 flex-wrap items-center justify-end gap-2 border-t border-stone-100 bg-stone-50/60 px-6 py-4">
                        <p class="mr-auto hidden text-xs text-stone-500 sm:block"><span class="text-red-500">*</span> wajib diisi</p>
                        <button type="button" @click="close()" class="btn btn-outline">Batal</button>
                        <button type="submit" class="btn btn-primary" :disabled="saving">
                            <x-icon name="loader-circle" class="size-4 animate-spin" x-show="saving" x-cloak />
                            <x-icon name="save" class="size-4" x-show="!saving" />
                            <span x-text="mode === 'edit' ? 'Simpan Perubahan' : 'Tambah Pengguna'"></span>
                        </button>
                    </footer>
                </form>
            </x-admin.modal>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('usersPage', (config) => ({
                    url: config.url,
                    permBase: config.permBase,
                    authId: config.authId,
                    permTotal: config.permTotal,
                    defaultPerms: config.defaults,
                    stats: { total: null, admin: null, operator: null },
                    showPass: false,
                    init() {
                        this.loadStats();
                        window.addEventListener('table:reload', () => this.loadStats());
                    },
                    /** Hitung jumlah akun per peran dari seluruh data (dibaca per 100 baris). */
                    async loadStats() {
                        try {
                            let all = [];
                            let total = 1;
                            while (all.length < total) {
                                const q = new URLSearchParams({ draw: '1', start: String(all.length), length: '100', 'order[0][column]': '0', 'order[0][dir]': 'asc' });
                                const res = await MUIAdmin.http(`${this.url}?${q}`);
                                total = res.recordsTotal ?? 0;
                                if (!res.data?.length) break;
                                all = all.concat(res.data);
                            }
                            this.stats = {
                                total: all.length,
                                admin: all.filter((u) => u.role === 'admin').length,
                                operator: all.filter((u) => u.role === 'operator').length,
                            };
                        } catch { /* biarkan tanda strip */ }
                    },
                    initials(name) {
                        return String(name || '?').trim().split(/\s+/).filter(Boolean).slice(0, 2).map((w) => w[0].toUpperCase()).join('') || '?';
                    },
                    permCount(row) {
                        return Array.isArray(row.menu_permissions) ? row.menu_permissions.length : this.defaultPerms.length;
                    },
                    /** Nilai lama "Laki-laki"/"Perempuan" diseragamkan dengan formulir profil (L/P). */
                    normalizeJk(value) {
                        return ({ 'Laki-laki': 'L', 'Perempuan': 'P' })[value] ?? (value || '');
                    },
                    strength(value = '') {
                        let score = 0;
                        if (value.length >= 8) score++;
                        if (value.length >= 12) score++;
                        if (/[a-z]/.test(value) && /[A-Z]/.test(value)) score++;
                        if (/\d/.test(value) && /[^A-Za-z0-9]/.test(value)) score++;
                        return Math.min(Math.max(score, value ? 1 : 0), 4);
                    },
                }));
            });
        </script>
    @endpush
</x-layouts.admin>
