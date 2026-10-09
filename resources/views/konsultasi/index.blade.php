@php
    $isOperator = auth()->user()->isOperator();
    $base = $isOperator ? route('operator.konsultasi.index') : route('admin.konsultasi.index');
    $statusFilters = ['' => 'Semua', 'pending' => 'Menunggu', 'dijawab' => 'Dijawab', 'ditolak' => 'Ditolak'];
@endphp

<x-layouts.admin title="Tanya Ulama" header="Konsultasi syariah & pertanyaan keagamaan dari masyarakat">
    <div x-data="konsultasiPage">
        <div x-data="serverTable({ url: @js($base), columns: ['id', 'created_at', 'nama', 'kategori', 'pertanyaan', 'status'], order: [1, 'desc'], filters: { filter_status: '', filter_kategori: '' } })">
            <x-admin.page-header eyebrow="Layanan Umat" title="Konsultasi Syariah" description="Pertanyaan masyarakat dari formulir Tanya Ulama. Jawaban berstatus “Dijawab” tampil di halaman publik.">
                <x-slot:actions>
                    @if ($isOperator)
                        <span class="badge badge-gold px-3 py-1.5 text-xs"><x-icon name="pen-line" class="size-3.5" /> Operator · Akses Penuh (dapat membalas)</span>
                    @else
                        <span class="badge badge-gray px-3 py-1.5 text-xs"><x-icon name="eye" class="size-3.5" /> Admin · Mode Lihat Saja</span>
                    @endif
                    <a href="{{ route('konsultasi.list') }}" target="_blank" rel="noopener" class="btn btn-outline"><x-icon name="external-link" class="size-4" /> Halaman publik</a>
                </x-slot:actions>
            </x-admin.page-header>

            @unless ($isOperator)
                <div class="mt-6 flex items-start gap-3 rounded-2xl border border-sky-200 bg-sky-50/70 px-4 py-3 text-sm text-sky-900">
                    <x-icon name="info" class="mt-0.5 size-5 text-sky-600" />
                    <p><strong>Perhatian:</strong> akun Admin memiliki hak akses <em>read-only</em> (hanya melihat konsultasi). Pembalasan tanggapan syariah dilakukan oleh akun <strong>Operator</strong>.</p>
                </div>
            @endunless

            {{-- Statistik (klik untuk menyaring) --}}
            <div class="mt-6 hidden gap-4 sm:grid sm:grid-cols-2 xl:grid-cols-4">
                <x-stat-card label="Total masuk" icon="messages-square" :value="$total" bind="meta.stats?.total ?? {{ $total }}" note="Seluruh pertanyaan"
                             role="button" tabindex="0" class="cursor-pointer" x-on:click="filters.filter_status = ''" x-on:keydown.enter="filters.filter_status = ''"
                             x-bind:class="filters.filter_status === '' && 'ring-2 ring-brand-500/40'" />
                <x-stat-card label="Menunggu jawaban" icon="hourglass" tone="gold" :value="$pending" bind="meta.stats?.pending ?? {{ $pending }}" note="Perlu ditindaklanjuti"
                             role="button" tabindex="0" class="cursor-pointer" x-on:click="filters.filter_status = 'pending'" x-on:keydown.enter="filters.filter_status = 'pending'"
                             x-bind:class="filters.filter_status === 'pending' && 'ring-2 ring-gold-400/60'" />
                <x-stat-card label="Telah dijawab" icon="circle-check-big" :value="$dijawab" bind="meta.stats?.dijawab ?? {{ $dijawab }}" note="Tampil di halaman publik"
                             bind-note="meta.stats ? Math.round(meta.stats.dijawab / Math.max(meta.stats.total, 1) * 100) + '% dari total pertanyaan' : 'Tampil di halaman publik'"
                             role="button" tabindex="0" class="cursor-pointer" x-on:click="filters.filter_status = 'dijawab'" x-on:keydown.enter="filters.filter_status = 'dijawab'"
                             x-bind:class="filters.filter_status === 'dijawab' && 'ring-2 ring-brand-500/40'" />
                <x-stat-card label="Ditolak" icon="circle-x" tone="red" :value="$ditolak" bind="meta.stats?.ditolak ?? {{ $ditolak }}" note="Tidak relevan / melanggar ketentuan"
                             role="button" tabindex="0" class="cursor-pointer" x-on:click="filters.filter_status = 'ditolak'" x-on:keydown.enter="filters.filter_status = 'ditolak'"
                             x-bind:class="filters.filter_status === 'ditolak' && 'ring-2 ring-red-400/50'" />
            </div>

            <div class="mt-6 sm:mt-8" role="group" aria-label="Filter status konsultasi">
                <div class="grid w-full grid-cols-4 gap-1 rounded-xl border border-stone-200 bg-white p-1 shadow-[var(--shadow-soft)] sm:inline-flex sm:w-auto">
                    @foreach ($statusFilters as $value => $label)
                        <button type="button" @click="filters.filter_status = @js($value)" :aria-pressed="filters.filter_status === @js($value)"
                                class="flex flex-col items-center justify-center gap-1 rounded-lg px-1 py-1.5 text-[12.5px] font-semibold whitespace-nowrap transition sm:flex-row sm:gap-1.5 sm:px-3.5 sm:py-2 sm:text-[13px]"
                                :class="filters.filter_status === @js($value) ? 'bg-brand-700 text-white shadow-sm' : 'text-stone-500 hover:bg-stone-100 hover:text-stone-800'">
                            {{ $label }}
                            <span class="rounded-full px-1.5 text-[11px] font-bold tabular-nums"
                                  :class="filters.filter_status === @js($value) ? 'bg-white/20 text-white' : 'bg-stone-100 text-stone-500'"
                                  x-show="meta.stats" x-text="meta.stats?.{{ $value === '' ? 'total' : $value }}"></span>
                        </button>
                    @endforeach
                </div>
            </div>

            <x-admin.table class="mt-3" colspan="7" empty="Belum ada konsultasi" empty-icon="message-circle-question" search-placeholder="Cari penanya, email, daerah, kategori, atau isi pertanyaan…">
                <x-slot:filters>
                    <select x-model="filters.filter_kategori" class="input w-auto max-w-64" aria-label="Filter kategori">
                        <option value="">Semua kategori</option>
                        @foreach ($kategoriList as $kat)
                            <option value="{{ $kat }}">{{ $kat }}</option>
                        @endforeach
                    </select>
                </x-slot:filters>
                <x-slot:actions>
                    <button type="button" @click="load()" :disabled="loading" class="grid size-11 shrink-0 place-items-center rounded-xl border border-stone-300 bg-white text-stone-600 transition hover:border-brand-600 hover:text-brand-700 disabled:opacity-60" title="Muat ulang" aria-label="Muat ulang data konsultasi">
                        <x-icon name="refresh-cw" class="size-4" ::class="loading && 'animate-spin'" />
                    </button>
                </x-slot:actions>
                <x-slot:head>
                    <th class="hidden w-12 sm:table-cell">#</th>
                    <x-admin.th col="1" class="hidden sm:table-cell">Tanggal</x-admin.th>
                    <x-admin.th col="2">Penanya</x-admin.th>
                    <x-admin.th col="3" class="hidden lg:table-cell">Kategori</x-admin.th>
                    <th class="hidden md:table-cell">Pertanyaan</th>
                    <x-admin.th col="5" class="hidden md:table-cell">Status</x-admin.th>
                    <th class="text-right">Aksi</th>
                </x-slot:head>
                <x-slot:row>
                    <tr :class="row.status === 'pending' && 'bg-gold-50/40'">
                        <td class="hidden text-stone-400 tabular-nums sm:table-cell" x-text="rowNumber(index)"></td>
                        <td class="hidden whitespace-nowrap sm:table-cell">
                            <p class="font-medium text-stone-700" x-text="tanggal(row.created_at)"></p>
                            <p class="text-xs text-stone-400" x-text="jam(row.created_at)"></p>
                        </td>
                        <td>
                            <button type="button" @click="$dispatch('konsultasi:detail', row)" class="group flex items-start gap-3 text-left" :aria-label="'Detail konsultasi dari ' + row.nama">
                                <span class="mt-0.5 grid size-9 shrink-0 place-items-center rounded-full bg-brand-50 text-xs font-bold text-brand-700 ring-1 ring-brand-100" x-text="inisial(row.nama)"></span>
                                <span class="min-w-0">
                                    <span class="block font-semibold text-ink-900 group-hover:text-brand-700 sm:whitespace-nowrap" x-text="row.nama"></span>
                                    <span class="block text-xs text-stone-500 sm:whitespace-nowrap" x-text="metaPenanya(row)"></span>
                                    <span class="mt-1.5 flex flex-wrap items-center gap-1.5 lg:hidden">
                                        <span class="badge md:hidden" :class="statusBadge(row.status)" x-text="statusLabel(row.status)"></span>
                                        <span class="badge badge-green" x-text="row.kategori"></span>
                                        <span class="text-[11px] text-stone-400 sm:hidden" x-text="tanggal(row.created_at) + ' · ' + jam(row.created_at)"></span>
                                    </span>
                                    <span class="mt-1.5 line-clamp-2 block text-[13px] text-stone-600 md:hidden" x-text="row.pertanyaan"></span>
                                </span>
                            </button>
                        </td>
                        <td class="hidden lg:table-cell"><span class="badge badge-green" x-text="row.kategori"></span></td>
                        <td class="hidden max-w-sm min-w-56 md:table-cell"><p class="line-clamp-2 text-stone-600" :title="row.pertanyaan" x-text="row.pertanyaan"></p></td>
                        <td class="hidden whitespace-nowrap md:table-cell">
                            <span class="badge" :class="statusBadge(row.status)">
                                <x-icon name="hourglass" class="size-3" x-show="row.status === 'pending'" />
                                <x-icon name="circle-check-big" class="size-3" x-show="row.status === 'dijawab'" />
                                <x-icon name="circle-x" class="size-3" x-show="row.status === 'ditolak'" />
                                <span x-text="statusLabel(row.status)"></span>
                            </span>
                            <p class="mt-1 text-[11px] text-stone-400" x-show="row.penjawab" x-text="'oleh ' + row.penjawab"></p>
                        </td>
                        <td>
                            <div class="flex flex-col items-end gap-1.5 sm:flex-row sm:items-center sm:justify-end">
                                <button type="button" @click="$dispatch('konsultasi:detail', row)" class="grid size-8 place-items-center rounded-lg text-stone-500 hover:bg-brand-50 hover:text-brand-700" title="Lihat detail" aria-label="Lihat detail konsultasi"><x-icon name="eye" class="size-4" /></button>
                                @if ($isOperator)
                                    <button type="button" x-show="row.status === 'pending'" @click="$dispatch('konsultasi:jawab', row)" class="btn btn-primary btn-sm px-2.5" title="Balas pertanyaan" aria-label="Balas konsultasi"><x-icon name="reply" class="size-4" /> <span class="hidden sm:inline">Balas</span></button>
                                    <button type="button" x-show="row.status !== 'pending'" @click="$dispatch('konsultasi:jawab', row)" class="grid size-8 place-items-center rounded-lg text-stone-500 hover:bg-brand-50 hover:text-brand-700" title="Ubah jawaban" aria-label="Ubah jawaban konsultasi"><x-icon name="pencil" class="size-4" /></button>
                                    <button type="button" @click="MUIAdmin.destroy(@js($base) + '/' + row.id, { title: 'Hapus konsultasi?', message: `Konsultasi dari “${row.nama}” akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.` })" class="grid size-8 place-items-center rounded-lg text-stone-500 hover:bg-red-50 hover:text-red-600" title="Hapus" aria-label="Hapus konsultasi"><x-icon name="trash-2" class="size-4" /></button>
                                @endif
                            </div>
                        </td>
                    </tr>
                </x-slot:row>
            </x-admin.table>
        </div>

        {{-- Detail konsultasi (admin & operator) --}}
        <div x-data="konsultasiDetail({ base: @js($base), publicBase: @js(url('konsultasi')) })">
            <x-admin.modal title="'Detail Konsultasi'" icon="message-circle-question" size="max-w-3xl">
                <div class="flex min-h-0 flex-1 flex-col">
                    <div class="scrollbar-thin flex-1 overflow-y-auto">
                        <div x-show="loading" class="space-y-4 p-6" aria-busy="true">
                            <div class="flex items-center gap-4"><div class="skeleton size-12 rounded-2xl!"></div><div class="flex-1 space-y-2"><div class="skeleton h-5 w-1/3"></div><div class="skeleton h-4 w-1/4"></div></div></div>
                            <div class="skeleton h-20 w-full"></div>
                            <div class="skeleton h-32 w-full"></div>
                        </div>
                        <template x-if="item && !loading">
                            <div class="space-y-6 p-6">
                                <div class="flex items-start gap-4">
                                    <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-brand-700 text-sm font-bold text-gold-300" x-text="inisial(item.nama)"></span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-lg leading-tight font-bold text-ink-900" x-text="item.nama"></p>
                                        <a :href="'mailto:' + item.email" class="mt-0.5 inline-flex max-w-full items-center gap-1.5 text-sm text-brand-700 hover:underline"><x-icon name="mail" class="size-3.5" /><span class="break-all" x-text="item.email"></span></a>
                                        <p class="mt-2 sm:hidden"><span class="badge" :class="statusBadge(item.status)" x-text="item.status === 'pending' ? 'Menunggu jawaban' : statusLabel(item.status)"></span></p>
                                    </div>
                                    <span class="badge hidden shrink-0 px-3 py-1 text-xs sm:inline-flex" :class="statusBadge(item.status)">
                                        <x-icon name="hourglass" class="size-3.5" x-show="item.status === 'pending'" />
                                        <x-icon name="circle-check-big" class="size-3.5" x-show="item.status === 'dijawab'" />
                                        <x-icon name="circle-x" class="size-3.5" x-show="item.status === 'ditolak'" />
                                        <span x-text="item.status === 'pending' ? 'Menunggu jawaban' : statusLabel(item.status)"></span>
                                    </span>
                                </div>

                                <dl class="grid grid-cols-2 gap-x-4 gap-y-3 rounded-2xl border border-stone-200 bg-stone-50/60 p-4 text-sm sm:grid-cols-4">
                                    <div><dt class="text-[11px] font-bold tracking-wider text-stone-400 uppercase">Usia</dt><dd class="mt-0.5 font-medium text-stone-800" x-text="item.usia ? item.usia + ' tahun' : '—'"></dd></div>
                                    <div><dt class="text-[11px] font-bold tracking-wider text-stone-400 uppercase">Jenis kelamin</dt><dd class="mt-0.5 font-medium text-stone-800" x-text="item.jenis_kelamin || '—'"></dd></div>
                                    <div><dt class="text-[11px] font-bold tracking-wider text-stone-400 uppercase">Asal daerah</dt><dd class="mt-0.5 font-medium text-stone-800" x-text="item.kab_kota || '—'"></dd></div>
                                    <div><dt class="text-[11px] font-bold tracking-wider text-stone-400 uppercase">Kategori</dt><dd class="mt-1"><span class="badge badge-green" x-text="item.kategori"></span></dd></div>
                                </dl>

                                <section>
                                    <h3 class="text-[11px] font-bold tracking-wider text-stone-400 uppercase">Pertanyaan dari masyarakat</h3>
                                    <div class="mt-2 rounded-2xl border-l-4 border-gold-400 bg-gold-50/60 p-4 text-[14.5px] leading-relaxed whitespace-pre-line text-stone-800" x-text="item.pertanyaan"></div>
                                    <p class="mt-2 flex items-center gap-1.5 text-xs text-stone-500"><x-icon name="clock" class="size-3.5" /> Diajukan pada <span x-text="item.created_at || '—'"></span></p>
                                </section>

                                <section>
                                    <h3 class="text-[11px] font-bold tracking-wider text-stone-400 uppercase" x-text="item.status === 'ditolak' ? 'Tanggapan / alasan penolakan' : 'Jawaban / fatwa dewan ulama'"></h3>
                                    <template x-if="item.jawaban">
                                        <div>
                                            <div class="mt-2 rounded-2xl border-l-4 p-4 text-[14.5px] leading-relaxed whitespace-pre-line text-stone-800"
                                                 :class="item.status === 'ditolak' ? 'border-red-400 bg-red-50/60' : 'border-brand-500 bg-brand-50/60'" x-text="item.jawaban"></div>
                                            <p class="mt-2 flex flex-wrap items-center gap-1.5 text-xs text-stone-500">
                                                <x-icon name="user-check" class="size-3.5" /> Dijawab oleh <strong class="text-stone-700" x-text="item.penjawab || 'Operator MUI'"></strong>
                                                <span x-show="item.answered_at">· <span x-text="item.answered_at"></span></span>
                                            </p>
                                        </div>
                                    </template>
                                    <template x-if="!item.jawaban">
                                        <p class="mt-2 flex items-center gap-2 rounded-xl bg-gold-50 px-4 py-3 text-sm text-gold-800 ring-1 ring-gold-200"><x-icon name="circle-alert" class="size-4" /> Pertanyaan ini belum dijawab.</p>
                                    </template>
                                </section>

                                @unless ($isOperator)
                                    <p class="flex items-start gap-2 rounded-xl border border-stone-200 px-4 py-3 text-xs text-stone-500">
                                        <x-icon name="lock" class="mt-px size-3.5" />
                                        <span>Anda dalam mode <strong class="text-stone-700">Admin (hanya melihat)</strong>. Tombol balas hanya aktif untuk akun <strong class="text-stone-700">Operator</strong>.</span>
                                    </p>
                                @endunless
                            </div>
                        </template>
                    </div>
                    <footer class="flex shrink-0 flex-wrap items-center gap-2 border-t border-stone-100 bg-stone-50/60 px-6 py-4">
                        <a x-show="item" :href="publicUrl" target="_blank" rel="noopener" class="btn btn-ghost btn-sm"><x-icon name="external-link" class="size-4" /> Halaman publik</a>
                        <div class="ml-auto flex gap-2">
                            <button type="button" @click="close()" class="btn btn-outline">Tutup</button>
                            @if ($isOperator)
                                <button type="button" x-show="item && !loading" @click="reply()" class="btn btn-primary">
                                    <x-icon name="reply" class="size-4" /> <span x-text="item?.jawaban ? 'Ubah Jawaban' : 'Balas Sekarang'">Balas Sekarang</span>
                                </button>
                            @endif
                        </div>
                    </footer>
                </div>
            </x-admin.modal>
        </div>

        {{-- Formulir jawaban (khusus operator) --}}
        @if ($isOperator)
            <div x-data="konsultasiJawab({ base: @js($base) })">
                <x-admin.modal title="item?.jawaban ? 'Ubah Jawaban Konsultasi' : 'Balas Konsultasi Syariah'" icon="reply" size="max-w-5xl">
                    <form x-ref="form" @submit.prevent="submit()" class="flex min-h-0 flex-1 flex-col" novalidate>
                        <div class="scrollbar-thin flex-1 overflow-y-auto p-6">
                            <div class="grid gap-6 lg:grid-cols-5">
                                {{-- Data penanya & pertanyaan --}}
                                <aside class="space-y-4 lg:col-span-2">
                                    <div class="rounded-2xl border border-stone-200 bg-stone-50/60 p-4">
                                        <div class="flex items-center gap-3">
                                            <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-brand-700 text-sm font-bold text-gold-300" x-text="inisial(item?.nama)"></span>
                                            <div class="min-w-0">
                                                <p class="truncate font-bold text-ink-900" x-text="item?.nama"></p>
                                                <p class="truncate text-xs text-stone-500" x-text="item?.email"></p>
                                            </div>
                                        </div>
                                        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                                            <div><dt class="text-[11px] font-bold tracking-wider text-stone-400 uppercase">Usia / JK</dt><dd class="mt-0.5 text-stone-800" x-text="(item?.usia ? item.usia + ' th' : '—') + ' · ' + (item?.jenis_kelamin || '—')"></dd></div>
                                            <div><dt class="text-[11px] font-bold tracking-wider text-stone-400 uppercase">Asal daerah</dt><dd class="mt-0.5 text-stone-800" x-text="item?.kab_kota || '—'"></dd></div>
                                            <div class="col-span-2"><dt class="text-[11px] font-bold tracking-wider text-stone-400 uppercase">Kategori</dt><dd class="mt-1"><span class="badge badge-green" x-text="item?.kategori"></span></dd></div>
                                        </dl>
                                    </div>
                                    <div>
                                        <p class="label">Pertanyaan</p>
                                        <div class="scrollbar-thin max-h-72 overflow-y-auto rounded-2xl border-l-4 border-gold-400 bg-gold-50/60 p-4 text-sm leading-relaxed whitespace-pre-line text-stone-800" x-text="item?.pertanyaan"></div>
                                        <p class="mt-2 text-xs text-stone-500">Diajukan <span x-text="item?.created_at || '—'"></span></p>
                                    </div>
                                </aside>

                                {{-- Isian jawaban --}}
                                <div class="space-y-5 lg:col-span-3">
                                    <fieldset>
                                        <legend class="label">Status tanggapan <span class="text-red-500">*</span></legend>
                                        <div class="grid gap-3 sm:grid-cols-2">
                                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border p-3.5 transition" :class="form.status === 'dijawab' ? 'border-brand-500 bg-brand-50/60 ring-4 ring-brand-500/10' : 'border-stone-200 hover:border-brand-300'">
                                                <input type="radio" name="status" value="dijawab" x-model="form.status" class="mt-0.5 size-4 accent-brand-600">
                                                <span>
                                                    <span class="flex items-center gap-1.5 text-sm font-semibold text-ink-900"><x-icon name="circle-check-big" class="size-4 text-brand-600" /> Dijawab</span>
                                                    <span class="mt-0.5 block text-xs text-stone-500">Terbitkan jawaban ke portal publik.</span>
                                                </span>
                                            </label>
                                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border p-3.5 transition" :class="form.status === 'ditolak' ? 'border-red-400 bg-red-50/60 ring-4 ring-red-500/10' : 'border-stone-200 hover:border-red-300'">
                                                <input type="radio" name="status" value="ditolak" x-model="form.status" class="mt-0.5 size-4 accent-red-600">
                                                <span>
                                                    <span class="flex items-center gap-1.5 text-sm font-semibold text-ink-900"><x-icon name="circle-x" class="size-4 text-red-600" /> Ditolak</span>
                                                    <span class="mt-0.5 block text-xs text-stone-500">Tidak relevan / melanggar ketentuan.</span>
                                                </span>
                                            </label>
                                        </div>
                                        <p class="field-error" x-show="error('status')" x-text="error('status')"></p>
                                    </fieldset>

                                    <div>
                                        <label for="j-jawaban" class="label"><span x-text="form.status === 'ditolak' ? 'Alasan penolakan / tanggapan' : 'Jawaban / penjelasan syariah'"></span> <span class="text-red-500">*</span></label>
                                        <textarea id="j-jawaban" x-ref="jawaban" name="jawaban" x-model="form.jawaban" rows="12" required
                                                  placeholder="Tuliskan jawaban atau penjelasan syariah dengan bahasa yang santun, jelas, dan berlandaskan dalil…"
                                                  class="input min-h-56 leading-relaxed" :class="error('jawaban') && 'input-error'"></textarea>
                                        <div class="mt-1.5 flex flex-wrap justify-between gap-2 text-xs text-stone-500">
                                            <span>Berikan penjelasan lengkap beserta dalil yang mendukung.</span>
                                            <span class="tabular-nums" :class="form.jawaban.trim().length && form.jawaban.trim().length < 5 && 'font-semibold text-red-600'"><span x-text="form.jawaban.length"></span> karakter</span>
                                        </div>
                                        <p class="field-error" x-show="error('jawaban')" x-text="error('jawaban')"></p>
                                    </div>

                                    <p class="flex items-start gap-2 rounded-xl bg-brand-50/70 px-4 py-3 text-xs leading-relaxed text-brand-900 ring-1 ring-brand-100">
                                        <x-icon name="shield-check" class="mt-px size-4 text-brand-600" />
                                        <span>Jawaban disimpan atas nama <strong>{{ auth()->user()->name_gelar ?: auth()->user()->name }}</strong>. Status <strong>Dijawab</strong> langsung dipublikasikan di halaman tanya jawab umat.</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <footer class="flex shrink-0 justify-end gap-2 border-t border-stone-100 bg-stone-50/60 px-6 py-4">
                            <button type="button" @click="close()" class="btn btn-outline">Batal</button>
                            <button type="submit" class="btn btn-primary" :disabled="saving">
                                <x-icon name="loader-circle" class="size-4 animate-spin" x-show="saving" x-cloak />
                                <x-icon name="send" class="size-4" x-show="!saving" /> Simpan &amp; Kirim Jawaban
                            </button>
                        </footer>
                    </form>
                </x-admin.modal>
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                const STATUS = {
                    pending: { label: 'Menunggu', badge: 'badge-gold' },
                    dijawab: { label: 'Dijawab', badge: 'badge-green' },
                    ditolak: { label: 'Ditolak', badge: 'badge-red' },
                };

                /** Terima "d/m/Y H:i" (tabel), ISO (respons simpan), atau teks siap tampil (detail). */
                const parseDate = (value) => {
                    if (!value) return null;
                    const s = String(value);
                    const dmy = s.match(/^(\d{2})\/(\d{2})\/(\d{4})(?:\s+(\d{2}):(\d{2}))?/);
                    if (dmy) return new Date(+dmy[3], +dmy[2] - 1, +dmy[1], +(dmy[4] ?? 0), +(dmy[5] ?? 0));
                    if (/^\d{4}-\d{2}-\d{2}/.test(s)) {
                        const d = new Date(s.replace(' ', 'T'));
                        return Number.isNaN(d.getTime()) ? null : d;
                    }
                    return null;
                };
                const jamDari = (d) => d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }).replace('.', ':');
                const lengkap = (value) => {
                    const d = parseDate(value);
                    return d ? `${d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}, ${jamDari(d)} WIB` : (value || null);
                };
                const normalize = (r = {}) => ({
                    ...r,
                    penjawab: r.penjawab && typeof r.penjawab === 'object' ? r.penjawab.name : (r.penjawab ?? r.penjawab_nama ?? null),
                    created_at: lengkap(r.created_at),
                    answered_at: lengkap(r.answered_at),
                });

                const helpers = {
                    statusLabel: (s) => STATUS[s]?.label ?? s,
                    statusBadge: (s) => STATUS[s]?.badge ?? 'badge-gray',
                    inisial: (nama = '') => String(nama || '').trim().split(/\s+/).slice(0, 2).map((w) => w.charAt(0).toUpperCase()).join('') || '?',
                    metaPenanya: (r) => [r.kab_kota, r.jenis_kelamin, r.usia ? `${r.usia} th` : null].filter(Boolean).join(' · ') || '—',
                    tanggal: (v) => { const d = parseDate(v); return d ? d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : (v || '—'); },
                    jam: (v) => { const d = parseDate(v); return d ? jamDari(d) : ''; },
                };

                Alpine.data('konsultasiPage', () => ({ ...helpers }));

                Alpine.data('konsultasiDetail', ({ base, publicBase }) => ({
                    ...helpers,
                    open: false,
                    loading: false,
                    item: null,
                    init() {
                        window.addEventListener('konsultasi:detail', (e) => this.show(e.detail));
                        window.addEventListener('konsultasi:saved', (e) => {
                            if (this.item && e.detail && e.detail.id === this.item.id) this.item = normalize({ ...this.item, ...e.detail });
                        });
                        // Tautan notifikasi: ?detail_id={id} langsung membuka detail konsultasi.
                        const id = new URLSearchParams(location.search).get('detail_id');
                        if (id) this.fetchDetail(id);
                    },
                    get publicUrl() {
                        return this.item ? `${publicBase}/${this.item.id}` : '#';
                    },
                    show(row) {
                        if (!row || row.id === undefined || row.id === null) return;
                        this.loading = false;
                        this.item = normalize(row);
                        this.open = true;
                    },
                    async fetchDetail(id) {
                        this.item = null;
                        this.loading = true;
                        this.open = true;
                        try {
                            this.item = normalize(await MUIAdmin.http(`${base}/${encodeURIComponent(id)}`));
                        } catch (e) {
                            this.open = false;
                            this.clearParam();
                            MUIAdmin.toast(e.status === 404 ? 'Konsultasi tidak ditemukan atau sudah dihapus.' : e.message, 'error');
                        } finally {
                            this.loading = false;
                        }
                    },
                    close() {
                        this.open = false;
                        this.clearParam();
                    },
                    clearParam() {
                        const url = new URL(location.href);
                        if (!url.searchParams.has('detail_id')) return;
                        url.searchParams.delete('detail_id');
                        history.replaceState(history.state, '', url);
                    },
                    reply() {
                        const item = this.item;
                        this.close();
                        window.dispatchEvent(new CustomEvent('konsultasi:jawab', { detail: item }));
                    },
                }));

                Alpine.data('konsultasiJawab', ({ base }) => ({
                    ...helpers,
                    open: false,
                    saving: false,
                    errors: {},
                    item: null,
                    form: { status: 'dijawab', jawaban: '' },
                    init() {
                        window.addEventListener('konsultasi:jawab', (e) => this.show(e.detail));
                    },
                    show(row) {
                        if (!row || row.id === undefined || row.id === null) return;
                        this.item = normalize(row);
                        this.errors = {};
                        this.form = { status: row.status === 'ditolak' ? 'ditolak' : 'dijawab', jawaban: row.jawaban || '' };
                        this.open = true;
                        // Hindari keyboard layar sentuh langsung muncul; fokus otomatis hanya untuk perangkat ber-pointer halus.
                        if (matchMedia('(pointer: fine)').matches) this.$nextTick(() => this.$refs.jawaban?.focus());
                    },
                    close() {
                        this.open = false;
                    },
                    error(field) {
                        return this.errors?.[field]?.[0] ?? null;
                    },
                    async submit() {
                        if (this.saving || !this.item) return;
                        this.saving = true;
                        this.errors = {};
                        try {
                            const res = await MUIAdmin.http(`${base}/${this.item.id}/jawab`, { method: 'POST', body: { jawaban: this.form.jawaban, status: this.form.status } });
                            MUIAdmin.toast(res.message || 'Jawaban konsultasi berhasil disimpan.');
                            this.open = false;
                            MUIAdmin.reloadTables();
                            window.dispatchEvent(new CustomEvent('konsultasi:saved', { detail: res.data ?? { id: this.item.id } }));
                        } catch (e) {
                            this.errors = e.data?.errors ?? {};
                            MUIAdmin.toast(e.message, 'error');
                        } finally {
                            this.saving = false;
                        }
                    },
                }));
            });
        </script>
    @endpush
</x-layouts.admin>
