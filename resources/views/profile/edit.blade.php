@php
    $isAdmin = $user->isAdmin();
    $initials = collect(preg_split('/\s+/', trim($user->name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    // Nilai lama "Laki-laki"/"Perempuan" (dari formulir pengguna versi lama) diseragamkan ke L/P.
    $jk = old('jk', match ($user->jk) { 'Laki-laki' => 'L', 'Perempuan' => 'P', default => $user->jk });
    $mdiToLucide = [
        'mdi mdi-newspaper' => 'newspaper',
        'mdi mdi-tag-multiple' => 'tag',
        'mdi mdi-email-outline' => 'folder-archive',
        'mdi mdi-book-open-variant' => 'scale',
        'mdi mdi-label-outline' => 'tags',
        'mdi mdi-chat-processing-outline' => 'messages-square',
        'mdi mdi-forum' => 'message-circle-question',
    ];
    $menus = $isAdmin ? collect() : collect(\App\Models\User::OPERATOR_PERMISSIONS)->only($user->getAssignedPermissions());
@endphp

<x-layouts.admin title="Profil Akun" header="Data diri, foto profil, dan keamanan akun Anda">
    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" x-data="profileForm()" @submit="saving = true">
        @csrf

        <x-admin.page-header eyebrow="Akun Saya" title="Profil Akun" description="Perbarui data diri, foto profil, dan kata sandi akun yang sedang Anda gunakan untuk masuk ke panel." />

        @if ($errors->any())
            <div role="alert" class="mt-6 flex gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                <x-icon name="circle-alert" class="mt-0.5 size-5 shrink-0 text-red-600" />
                <div>
                    <p class="font-semibold">Profil belum tersimpan. Periksa isian berikut:</p>
                    <ul class="mt-1.5 list-disc space-y-0.5 pl-5 text-red-700">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="mt-6 grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
            {{-- Kolom kiri: foto & ringkasan akun --}}
            <aside class="space-y-6 lg:col-span-4">
                <section class="card overflow-hidden" x-data="avatarPicker(@js($user->foto_url))">
                    <div class="bg-gradient-brand relative h-24">
                        <div class="pattern-islamic absolute inset-0"></div>
                        <div class="absolute -top-8 -right-8 size-32 rounded-full bg-gold-400/20 blur-2xl"></div>
                    </div>
                    <div class="-mt-14 px-6 pb-6 text-center">
                        <div class="relative mx-auto size-28" @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="drop($event)">
                            <img x-show="preview" :src="preview || ''" src="{{ $user->foto_url }}" @if (! $user->foto_url) x-cloak @endif alt="Foto profil {{ $user->name }}" class="size-28 rounded-full bg-white object-cover shadow-lg ring-4 ring-white">
                            <span x-show="!preview" @if ($user->foto_url) x-cloak @endif class="grid size-28 place-items-center rounded-full bg-gradient-to-br from-gold-200 via-gold-300 to-gold-500 font-display text-4xl font-semibold text-brand-950 shadow-lg ring-4 ring-white" x-text="initials(v.name) || @js($initials)">{{ $initials }}</span>
                            <label for="foto-input" class="absolute right-0.5 bottom-0.5 grid size-10 cursor-pointer place-items-center rounded-full bg-brand-700 text-white shadow ring-4 ring-white transition hover:bg-brand-800" title="Pilih foto baru">
                                <x-icon name="camera" class="size-4" /><span class="sr-only">Pilih foto baru</span>
                            </label>
                            <div x-show="dragging" x-cloak class="pointer-events-none absolute inset-0 grid place-items-center rounded-full bg-brand-900/75 text-xs font-semibold text-white">Lepaskan foto</div>
                        </div>
                        <input id="foto-input" x-ref="file" type="file" name="foto" accept="image/png,image/jpeg,image/webp" class="sr-only" @change="pick($event)">

                        <h2 class="mt-4 font-display text-xl leading-tight font-semibold text-ink-900" x-text="v.name_gelar || v.name || @js($user->name)">{{ $user->name_gelar ?: $user->name }}</h2>
                        <p class="mt-1 text-sm text-stone-500">{{ '@'.$user->username }}</p>
                        <span @class(['badge mt-3 py-1', 'badge-gold' => $isAdmin, 'badge-green' => ! $isAdmin])>
                            <x-icon :name="$isAdmin ? 'shield-check' : 'user-round-cog'" class="size-3.5" /> {{ $isAdmin ? 'Administrator' : 'Operator' }}
                        </span>

                        <div class="mt-5 flex flex-wrap justify-center gap-2">
                            <label for="foto-input" class="btn btn-outline btn-sm cursor-pointer"><x-icon name="upload" class="size-4" /> <span x-text="name ? 'Ganti pilihan' : 'Pilih foto baru'">Pilih foto baru</span></label>
                            <button type="button" x-show="name" x-cloak @click="cancel()" class="btn btn-ghost btn-sm"><x-icon name="x" class="size-4" /> Batalkan</button>
                        </div>
                        <p x-show="name" x-cloak class="mt-2 truncate text-xs font-medium text-brand-700" x-text="name + ' · ' + size"></p>
                        <p x-show="tooBig" x-cloak class="field-error justify-center"><x-icon name="circle-alert" class="size-3.5" /> Ukuran foto melebihi 2 MB.</p>
                        <p x-show="badType" x-cloak class="field-error justify-center"><x-icon name="circle-alert" class="size-3.5" /> Format harus JPG, PNG, atau WEBP.</p>
                        @error('foto')<p class="field-error justify-center"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror

                        @if ($user->foto)
                            <label class="mt-4 flex cursor-pointer items-center justify-between gap-3 rounded-xl border px-4 py-3 text-left transition" :class="hapus ? 'border-red-300 bg-red-50' : 'border-stone-200 hover:border-red-200'">
                                <span class="text-sm">
                                    <span class="block font-semibold" :class="hapus ? 'text-red-700' : 'text-ink-900'">Hapus foto profil</span>
                                    <span class="block text-xs text-stone-500">Avatar inisial nama akan dipakai.</span>
                                </span>
                                <span class="relative inline-flex shrink-0">
                                    <input type="checkbox" id="hapus_foto" name="hapus_foto" value="1" x-model="hapus" @change="toggleHapus()" class="peer sr-only">
                                    <span class="h-6 w-11 rounded-full bg-stone-300 transition peer-checked:bg-red-600 peer-focus-visible:ring-4 peer-focus-visible:ring-red-500/20"></span>
                                    <span class="absolute top-0.5 left-0.5 size-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                                </span>
                            </label>
                        @endif

                        <ul class="mt-5 space-y-1.5 rounded-xl bg-stone-50 p-4 text-left text-xs leading-relaxed text-stone-600 ring-1 ring-stone-200">
                            <li class="flex gap-2"><x-icon name="image" class="mt-0.5 size-3.5 shrink-0 text-brand-600" /> Format JPG, JPEG, PNG, atau WEBP</li>
                            <li class="flex gap-2"><x-icon name="hard-drive" class="mt-0.5 size-3.5 shrink-0 text-brand-600" /> Ukuran maksimal 2 MB</li>
                            <li class="flex gap-2"><x-icon name="circle-user-round" class="mt-0.5 size-3.5 shrink-0 text-brand-600" /> Tanpa foto, avatar inisial nama dipakai otomatis</li>
                        </ul>
                    </div>
                </section>

                <section class="card p-5">
                    <h3 class="flex items-center gap-2 font-semibold text-ink-900"><x-icon name="shield-check" class="size-4 text-brand-600" /> Status akun</h3>
                    <dl class="mt-2 divide-y divide-stone-100 text-sm">
                        <div class="flex items-center justify-between gap-3 py-2.5"><dt class="text-stone-500">ID pengguna</dt><dd class="font-semibold text-ink-900 tabular-nums">#{{ $user->id }}</dd></div>
                        <div class="flex items-center justify-between gap-3 py-2.5"><dt class="text-stone-500">Hak akses</dt><dd class="font-semibold text-brand-700">{{ $isAdmin ? 'Administrator' : 'Operator' }}</dd></div>
                        <div class="flex items-center justify-between gap-3 py-2.5"><dt class="text-stone-500">Terdaftar sejak</dt><dd class="text-stone-700">{{ $user->created_at ? $user->created_at->translatedFormat('d M Y') : '—' }}</dd></div>
                        <div class="flex items-center justify-between gap-3 py-2.5"><dt class="text-stone-500">Terakhir diperbarui</dt><dd class="text-stone-700">{{ $user->updated_at ? $user->updated_at->diffForHumans() : '—' }}</dd></div>
                    </dl>
                </section>

                @unless ($isAdmin)
                    <section class="card p-5">
                        <h3 class="flex items-center gap-2 font-semibold text-ink-900"><x-icon name="layout-dashboard" class="size-4 text-brand-600" /> Menu yang dapat Anda akses</h3>
                        <p class="mt-1 text-xs text-stone-500">Diatur oleh administrator sesuai pembagian tugas.</p>
                        @if ($menus->isNotEmpty())
                            <div class="mt-3 flex flex-wrap gap-1.5">
                                @foreach ($menus as $menu)
                                    <span class="badge badge-green py-1"><x-icon :name="$mdiToLucide[$menu['icon']] ?? 'circle-check'" class="size-3" /> {{ $menu['label'] }}</span>
                                @endforeach
                            </div>
                        @else
                            <p class="mt-3 rounded-xl bg-stone-50 px-3 py-2.5 text-xs text-stone-600 ring-1 ring-stone-200">Belum ada menu operasional. Anda dapat membuka Dashboard dan Profil Akun.</p>
                        @endif
                    </section>
                @endunless
            </aside>

            {{-- Kolom kanan: data diri & keamanan --}}
            <div class="min-w-0 space-y-6 lg:col-span-8">
                <section class="card">
                    <header class="flex items-start gap-3 border-b border-stone-100 px-5 py-4 sm:px-6">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100"><x-icon name="id-card" class="size-5" /></span>
                        <div><h3 class="font-semibold text-ink-900">Data pribadi &amp; kontak</h3><p class="mt-0.5 text-xs text-stone-500">Nama dan kontak yang tercatat pada akun Anda.</p></div>
                    </header>
                    <div class="grid grid-cols-1 gap-5 p-5 sm:grid-cols-2 sm:p-6">
                        <div>
                            <label for="name" class="label">Nama lengkap <span class="text-red-500">*</span></label>
                            <input id="name" name="name" type="text" maxlength="255" required autocomplete="name" value="{{ old('name', $user->name) }}" x-model.fill="v.name" placeholder="cth: Ahmad Fauzi" class="input @error('name') input-error @enderror">
                            @error('name')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="name_gelar" class="label">Nama beserta gelar</label>
                            <input id="name_gelar" name="name_gelar" type="text" maxlength="255" value="{{ old('name_gelar', $user->name_gelar) }}" x-model.fill="v.name_gelar" placeholder="cth: Dr. H. Ahmad Fauzi, M.Ag." class="input @error('name_gelar') input-error @enderror">
                            @error('name_gelar')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                            <p class="mt-1.5 text-xs text-stone-500">Ditampilkan di kartu profil bila diisi.</p>
                        </div>
                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <label for="username" class="label">Username login</label>
                                <span class="mb-1.5 inline-flex items-center gap-1 rounded-full bg-stone-100 px-2 py-0.5 text-[10px] font-semibold text-stone-500"><x-icon name="lock" class="size-3" /> Tidak dapat diubah</span>
                            </div>
                            <div class="relative">
                                <x-icon name="at-sign" class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-stone-400" />
                                <input id="username" type="text" value="{{ $user->username }}" readonly tabindex="-1" aria-readonly="true" class="input cursor-not-allowed bg-stone-100 pl-10 font-semibold text-stone-600">
                            </div>
                            <p class="mt-1.5 text-xs text-stone-500">Unik &amp; permanen untuk masuk ke panel.</p>
                        </div>
                        <div>
                            <label for="email" class="label">Alamat email <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <x-icon name="mail" class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-stone-400" />
                                <input id="email" name="email" type="email" maxlength="255" required autocomplete="email" value="{{ old('email', $user->email) }}" class="input pl-10 @error('email') input-error @enderror">
                            </div>
                            @error('email')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="nohp" class="label">No. telepon / WhatsApp</label>
                            <div class="relative">
                                <x-icon name="phone" class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-stone-400" />
                                <input id="nohp" name="nohp" type="tel" maxlength="30" inputmode="tel" autocomplete="tel" value="{{ old('nohp', $user->nohp) }}" placeholder="cth: 081234567890" class="input pl-10 @error('nohp') input-error @enderror">
                            </div>
                            @error('nohp')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="jk" class="label">Jenis kelamin</label>
                            <select id="jk" name="jk" class="input @error('jk') input-error @enderror">
                                <option value="">Pilih jenis kelamin</option>
                                <option value="L" @selected($jk === 'L')>Laki-laki</option>
                                <option value="P" @selected($jk === 'P')>Perempuan</option>
                            </select>
                            @error('jk')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="alamat" class="label">Alamat lengkap</label>
                            <textarea id="alamat" name="alamat" rows="3" autocomplete="street-address" placeholder="Alamat domisili atau kantor" class="input resize-y @error('alamat') input-error @enderror">{{ old('alamat', $user->alamat) }}</textarea>
                            @error('alamat')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

                <section class="card" id="keamanan">
                    <header class="flex flex-wrap items-start justify-between gap-3 border-b border-stone-100 px-5 py-4 sm:px-6">
                        <div class="flex items-start gap-3">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100"><x-icon name="lock-keyhole" class="size-5" /></span>
                            <div><h3 class="font-semibold text-ink-900">Keamanan akun</h3><p class="mt-0.5 text-xs text-stone-500">Ganti kata sandi secara berkala dan jangan bagikan kepada siapa pun.</p></div>
                        </div>
                        <button type="button" @click="showPass = !showPass" class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-xs font-semibold text-brand-700 hover:bg-brand-50">
                            <x-icon name="eye" class="size-3.5" x-show="!showPass" /><x-icon name="eye-off" class="size-3.5" x-show="showPass" x-cloak />
                            <span x-text="showPass ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'">Tampilkan kata sandi</span>
                        </button>
                    </header>
                    <div class="space-y-5 p-5 sm:p-6">
                        <p class="flex gap-2.5 rounded-xl bg-sky-50 px-4 py-3 text-xs leading-relaxed text-sky-900 ring-1 ring-sky-100">
                            <x-icon name="info" class="mt-0.5 size-4 shrink-0 text-sky-600" />
                            <span>Biarkan kolom di bawah kosong bila tidak ingin mengganti kata sandi. Untuk mengganti, masukkan kata sandi saat ini sebagai verifikasi.</span>
                        </p>
                        <div>
                            <label for="password_current" class="label">Kata sandi saat ini</label>
                            <input id="password_current" name="password_current" :type="showPass ? 'text' : 'password'" type="password" autocomplete="current-password" x-model="pwCurrent" placeholder="Masukkan kata sandi saat ini untuk verifikasi" class="input @error('password_current') input-error @enderror">
                            @error('password_current')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                            <p x-show="pw && !pwCurrent" x-cloak class="mt-1.5 text-xs font-medium text-gold-700">Wajib diisi untuk mengganti kata sandi.</p>
                        </div>
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div>
                                <label for="password" class="label">Kata sandi baru</label>
                                <input id="password" name="password" :type="showPass ? 'text' : 'password'" type="password" autocomplete="new-password" x-model="pw" placeholder="Minimal 8 karakter" class="input @error('password') input-error @enderror">
                                @error('password')<p class="field-error"><x-icon name="circle-alert" class="size-3.5" /> {{ $message }}</p>@enderror
                                <div x-show="pw" x-cloak class="mt-2 flex items-center gap-2">
                                    <div class="flex flex-1 gap-1">
                                        <template x-for="i in 4" :key="i">
                                            <span class="h-1.5 flex-1 rounded-full transition" :class="i <= strength ? ['bg-red-500', 'bg-gold-500', 'bg-brand-500', 'bg-brand-700'][strength - 1] : 'bg-stone-200'"></span>
                                        </template>
                                    </div>
                                    <span class="w-14 text-right text-[11px] font-semibold text-stone-500" x-text="['Lemah', 'Cukup', 'Baik', 'Kuat'][Math.max(strength, 1) - 1]"></span>
                                </div>
                                <p x-show="pw && pw.length < 8" x-cloak class="mt-1 text-xs text-gold-700">Minimal 8 karakter (<span x-text="pw.length"></span>/8).</p>
                            </div>
                            <div>
                                <label for="password_confirmation" class="label">Konfirmasi kata sandi baru</label>
                                <input id="password_confirmation" name="password_confirmation" :type="showPass ? 'text' : 'password'" type="password" autocomplete="new-password" x-model="pw2" placeholder="Ulangi kata sandi baru" class="input">
                                <p x-show="pw2" x-cloak class="mt-1.5 flex items-center gap-1 text-xs font-medium" :class="pw === pw2 ? 'text-brand-700' : 'text-red-600'">
                                    <x-icon name="circle-check" class="size-3.5" x-show="pw === pw2" />
                                    <x-icon name="circle-alert" class="size-3.5" x-show="pw !== pw2" />
                                    <span x-text="pw === pw2 ? 'Kata sandi cocok' : 'Belum cocok dengan kata sandi baru'"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Bilah simpan --}}
                <div class="sticky bottom-4 z-20">
                    <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-stone-200 bg-white/95 px-4 py-3 shadow-[var(--shadow-lift)] backdrop-blur sm:px-5">
                        <span class="grid size-9 shrink-0 place-items-center rounded-xl transition" :class="dirty ? 'bg-gold-50 text-gold-700' : 'bg-brand-50 text-brand-700'">
                            <x-icon name="pencil-line" class="size-4" x-show="dirty" x-cloak />
                            <x-icon name="circle-check" class="size-4" x-show="!dirty" />
                        </span>
                        <div class="min-w-0 grow basis-48">
                            <p class="text-sm font-semibold text-ink-900" x-text="dirty ? 'Ada perubahan yang belum disimpan' : 'Tidak ada perubahan'">Tidak ada perubahan</p>
                            <p class="truncate text-xs text-stone-500" x-text="pw ? 'Kata sandi juga akan diganti.' : 'Kata sandi tidak diubah.'">Kata sandi tidak diubah.</p>
                        </div>
                        <div class="ml-auto flex flex-wrap justify-end gap-2">
                            <button type="button" x-show="dirty" x-cloak @click="revert()" class="btn btn-ghost btn-sm"><x-icon name="undo-2" class="size-4" /> Urungkan</button>
                            <button type="submit" class="btn btn-primary btn-sm" :disabled="saving" data-action="simpan-profil">
                                <x-icon name="loader-circle" class="size-4 animate-spin" x-show="saving" x-cloak />
                                <x-icon name="save" class="size-4" x-show="!saving" /> Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('profileForm', () => ({
                    v: {},
                    pw: '',
                    pw2: '',
                    pwCurrent: '',
                    showPass: false,
                    saving: false,
                    dirty: false,
                    snapshot: '',
                    init() {
                        this.$nextTick(() => {
                            this.snapshot = this.serialize();
                            const check = () => { this.dirty = this.serialize() !== this.snapshot; };
                            this.$el.addEventListener('input', check);
                            this.$el.addEventListener('change', check);
                            this.$el.querySelector('.input-error')?.focus();
                        });
                        window.addEventListener('beforeunload', (e) => {
                            if (this.dirty && !this.saving) {
                                e.preventDefault();
                                e.returnValue = '';
                            }
                        });
                    },
                    serialize() {
                        return [...new FormData(this.$el).entries()]
                            .filter(([key]) => key !== '_token')
                            .map(([key, value]) => `${key}=${value instanceof File ? `file:${value.name}:${value.size}` : value}`)
                            .join('&');
                    },
                    revert() {
                        this.$el.reset();
                        this.pw = this.pw2 = this.pwCurrent = '';
                        this.$nextTick(() => {
                            this.$el.querySelectorAll('input:not([type=hidden]):not([type=file]), textarea, select').forEach((el) => {
                                el.dispatchEvent(new Event(el.type === 'checkbox' || el.tagName === 'SELECT' ? 'change' : 'input', { bubbles: true }));
                            });
                            this.$nextTick(() => { this.dirty = this.serialize() !== this.snapshot; });
                        });
                    },
                    initials(name) {
                        return String(name || '').trim().split(/\s+/).filter(Boolean).slice(0, 2).map((w) => w[0].toUpperCase()).join('');
                    },
                    get strength() {
                        const value = this.pw || '';
                        let score = 0;
                        if (value.length >= 8) score++;
                        if (value.length >= 12) score++;
                        if (/[a-z]/.test(value) && /[A-Z]/.test(value)) score++;
                        if (/\d/.test(value) && /[^A-Za-z0-9]/.test(value)) score++;
                        return Math.min(Math.max(score, value ? 1 : 0), 4);
                    },
                }));

                /** Foto profil: pratinjau, seret-lepas, batal pilih, dan hapus foto lama. */
                Alpine.data('avatarPicker', (initial) => ({
                    initial,
                    preview: initial,
                    name: null,
                    size: null,
                    tooBig: false,
                    badType: false,
                    hapus: false,
                    dragging: false,
                    init() {
                        this.$el.closest('form')?.addEventListener('reset', () => setTimeout(() => {
                            Object.assign(this, { name: null, size: null, tooBig: false, badType: false, hapus: false, preview: this.initial });
                        }));
                    },
                    pick(e) {
                        const file = e.target.files?.[0];
                        if (!file) return;
                        this.name = file.name;
                        this.size = file.size > 1048576 ? `${(file.size / 1048576).toFixed(1)} MB` : `${Math.ceil(file.size / 1024)} KB`;
                        this.tooBig = file.size > 2 * 1048576;
                        this.badType = !['image/jpeg', 'image/png', 'image/webp'].includes(file.type);
                        if (file.type.startsWith('image/')) this.preview = URL.createObjectURL(file);
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
                    cancel() {
                        this.$refs.file.value = '';
                        Object.assign(this, { name: null, size: null, tooBig: false, badType: false, preview: this.hapus ? null : this.initial });
                        this.$refs.file.dispatchEvent(new Event('change', { bubbles: true }));
                    },
                    toggleHapus() {
                        if (this.hapus) {
                            this.$refs.file.value = '';
                            Object.assign(this, { name: null, size: null, tooBig: false, badType: false, preview: null });
                        } else {
                            this.preview = this.initial;
                        }
                    },
                }));
            });
        </script>
    @endpush
</x-layouts.admin>
