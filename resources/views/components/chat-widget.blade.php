@props(['embedded' => false])

<div x-data="chatWidget({ embedded: {{ $embedded ? 'true' : 'false' }} })" @class(['relative h-full' => $embedded])>
    @unless ($embedded)
        {{-- Tombol melayang --}}
        <button type="button" @click="toggle()" x-show="!open" x-transition
                class="group fixed right-5 bottom-6 z-40 hidden items-center gap-3 rounded-full bg-brand-800 py-2 pr-5 pl-2 text-white shadow-[var(--shadow-lift)] ring-1 ring-white/10 transition hover:bg-brand-900 lg:flex"
                aria-label="Buka konsultasi online">
            <span class="relative grid size-11 place-items-center rounded-full bg-gradient-to-br from-gold-300 to-gold-500 text-brand-950">
                <x-icon name="messages-square" class="size-5" />
                <span class="absolute -top-0.5 -right-0.5 flex size-3.5">
                    <span class="absolute inline-flex size-full animate-ping rounded-full opacity-75" :class="operational ? 'bg-emerald-400' : 'bg-gold-300'"></span>
                    <span class="relative inline-flex size-3.5 rounded-full border-2 border-brand-800" :class="operational ? 'bg-emerald-400' : 'bg-gold-300'"></span>
                </span>
            </span>
            <span class="text-left leading-tight">
                <span class="block text-sm font-bold">Konsultasi Online</span>
                <span class="block text-[11px] text-white/60" x-text="operational ? 'Petugas MUI siap melayani' : 'Asisten virtual 24 jam'">Tanya ulama & petugas MUI</span>
            </span>
            <span x-show="unread" x-cloak x-text="unread" class="absolute -top-2 -right-1 grid min-w-6 place-items-center rounded-full bg-red-500 px-1.5 text-xs font-bold"></span>
        </button>
    @endunless

    {{-- Panel chat --}}
    <section x-show="open" x-cloak
             @unless ($embedded)
                 x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-y-6 opacity-0 sm:scale-95" x-transition:leave="transition duration-200" x-transition:leave-end="translate-y-6 opacity-0"
                 @keydown.escape.window="toggle(false)"
             @endunless
             @class([
                 'flex flex-col overflow-hidden bg-white',
                 'h-full rounded-3xl border border-stone-200 shadow-[var(--shadow-lift)]' => $embedded,
                 'fixed inset-0 z-[65] sm:inset-auto sm:right-5 sm:bottom-5 sm:h-[640px] sm:max-h-[calc(100vh-2.5rem)] sm:w-[400px] sm:rounded-3xl sm:shadow-2xl sm:ring-1 sm:ring-stone-200' => !$embedded,
             ])
             role="dialog" aria-label="Jendela konsultasi online">

        {{-- Kepala --}}
        <header class="bg-gradient-brand relative shrink-0 overflow-hidden px-5 pt-5 pb-4 text-white">
            <div class="pattern-islamic absolute inset-0"></div>
            <div class="relative flex items-start justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="relative grid size-11 place-items-center rounded-2xl bg-white/10 ring-1 ring-white/20">
                        <img src="{{ $site['logo_url'] }}" alt="" class="size-8 rounded-full bg-white object-contain p-px">
                        <span class="absolute -right-0.5 -bottom-0.5 size-3.5 rounded-full border-2 border-brand-900"
                              :class="session?.status === 'aktif' || (!session && operational) ? 'bg-emerald-400' : 'bg-gold-400'"></span>
                    </span>
                    <div>
                        <h2 class="font-display text-lg leading-tight font-semibold text-white">Konsultasi Online</h2>
                        <p class="text-xs text-white/70" x-text="statusText"></p>
                    </div>
                </div>
                <div class="flex items-center gap-1">
                    <button type="button" x-show="session && !closed" @click="end()" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-white/80 hover:bg-white/10" title="Akhiri sesi">Akhiri</button>
                    @unless ($embedded)
                        <button type="button" @click="toggle(false)" class="grid size-9 place-items-center rounded-xl text-white/80 hover:bg-white/10" aria-label="Tutup"><x-icon name="x" class="size-5" /></button>
                    @endunless
                </div>
            </div>
        </header>

        {{-- Tahap perkenalan --}}
        <div x-show="stage === 'intro'" class="scrollbar-thin flex-1 overflow-y-auto p-5">
            <div class="rounded-2xl bg-sand-100 p-4">
                <p class="arabic text-right text-xl text-brand-800">السَّلَامُ عَلَيْكُمْ وَرَحْمَةُ اللهِ</p>
                <p class="mt-1 text-sm leading-relaxed text-stone-600" x-text="greeting || 'Silakan sampaikan pertanyaan seputar keagamaan, fatwa, sertifikasi halal, atau layanan MUI.'"></p>
            </div>

            <p x-show="ready && !operational" class="mt-3 flex items-start gap-2 rounded-xl bg-gold-50 p-3 text-xs leading-relaxed text-gold-800 ring-1 ring-gold-200">
                <x-icon name="clock" class="mt-0.5 size-4" />
                <span>Di luar jam layanan petugas<span x-show="scheduleText"> (<span x-text="scheduleText"></span>)</span>. Asisten virtual tetap siap menjawab pertanyaan umum.</span>
            </p>
            <p x-show="ready && operational" class="mt-3 flex items-start gap-2 rounded-xl bg-brand-50 p-3 text-xs leading-relaxed text-brand-800 ring-1 ring-brand-100">
                <x-icon name="headset" class="mt-0.5 size-4" />
                <span>Petugas MUI sedang bertugas. Isi data singkat di bawah untuk masuk antrian layanan.</span>
            </p>

            <template x-if="faqs.length">
                <div class="mt-5">
                    <p class="text-[11px] font-bold tracking-wider text-stone-400 uppercase">Pertanyaan populer</p>
                    <div class="mt-2.5 flex flex-col gap-2">
                        <template x-for="faq in faqs.slice(0, 4)" :key="faq.id">
                            <button type="button" @click="askFaq(faq)" :disabled="sending"
                                    class="flex items-center justify-between gap-3 rounded-xl border border-stone-200 px-3.5 py-2.5 text-left text-[13px] font-medium text-stone-700 transition hover:border-brand-300 hover:bg-brand-50/60 disabled:opacity-60">
                                <span x-text="faq.pertanyaan"></span>
                                <x-icon name="chevron-right" class="size-4 text-stone-400" />
                            </button>
                        </template>
                    </div>
                </div>
            </template>

            <form @submit.prevent="start()" class="mt-6 space-y-3 border-t border-stone-100 pt-5">
                <p class="text-[11px] font-bold tracking-wider text-stone-400 uppercase" x-text="operational ? 'Bicara dengan petugas' : 'Mulai percakapan'"></p>
                <div>
                    <label class="label" for="chat-name">Nama lengkap <span class="text-red-500">*</span></label>
                    <input id="chat-name" x-model="form.nama_pengunjung" type="text" class="input" placeholder="Nama Anda" maxlength="150" required autocomplete="name">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="label" for="chat-phone">No. WhatsApp</label>
                        <input id="chat-phone" x-model="form.nohp_pengunjung" type="tel" class="input" placeholder="opsional" maxlength="30" autocomplete="tel">
                    </div>
                    <div>
                        <label class="label" for="chat-topic">Topik</label>
                        <select id="chat-topic" x-model="form.topik" class="input">
                            <option value="">Pilih…</option>
                            <option value="Konsultasi Syariah">Konsultasi Syariah</option>
                            <option value="Fatwa & Sertifikasi Halal">Fatwa & Halal</option>
                            <option value="Surat Rekomendasi">Surat & Rekomendasi</option>
                            <option value="Informasi Umum">Informasi Umum</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="label" for="chat-message">Pesan awal</label>
                    <textarea id="chat-message" x-model="form.pesan_awal" rows="2" maxlength="2000" class="input resize-none" placeholder="Tuliskan pertanyaan Anda (opsional)"></textarea>
                </div>
                <p x-show="error" x-text="error" class="field-error"></p>
                <button type="submit" class="btn btn-primary w-full" :disabled="starting">
                    <x-icon name="loader-circle" class="size-4 animate-spin" x-show="starting" />
                    <x-icon name="send" class="size-4" x-show="!starting" />
                    <span x-text="operational ? 'Masuk Antrian Petugas' : 'Mulai Percakapan'">Mulai Percakapan</span>
                </button>
                <p class="text-center text-[11px] leading-relaxed text-stone-400">Percakapan disimpan untuk peningkatan layanan MUI.</p>
            </form>
        </div>

        {{-- Tahap antrian --}}
        <div x-show="stage === 'queue'" class="flex flex-1 flex-col items-center justify-center p-6 text-center">
            <span class="relative grid size-20 place-items-center rounded-3xl bg-brand-50 text-brand-700 ring-8 ring-brand-50/60">
                <x-icon name="hourglass" class="size-9" />
                <span class="absolute -top-1 -right-1 flex size-4">
                    <span class="absolute inline-flex size-full animate-ping rounded-full bg-gold-400 opacity-75"></span>
                    <span class="relative inline-flex size-4 rounded-full bg-gold-400"></span>
                </span>
            </span>
            <p class="mt-6 text-xs font-bold tracking-[.2em] text-gold-600 uppercase">Nomor antrian Anda</p>
            <p class="mt-1 font-display text-5xl font-semibold text-brand-800" x-text="'#' + (session?.antrian_nomor ?? '-')"></p>
            <div class="mt-6 grid w-full grid-cols-2 gap-3">
                <div class="rounded-2xl border border-stone-200 p-3">
                    <p class="text-lg font-bold text-ink-900" x-text="'Ke-' + (session?.antrian_position || 1)"></p>
                    <p class="text-[11px] text-stone-500">Urutan antrian</p>
                </div>
                <div class="rounded-2xl border border-stone-200 p-3">
                    <p class="text-lg font-bold text-ink-900" x-text="'±' + (session?.estimasi_tunggu || 2) + ' mnt'"></p>
                    <p class="text-[11px] text-stone-500">Estimasi tunggu</p>
                </div>
            </div>
            <p class="mt-5 max-w-xs text-xs leading-relaxed text-stone-500">Mohon tetap di halaman ini. Jendela akan berpindah otomatis ketika petugas mulai membalas.</p>
            <button type="button" @click="end()" class="btn btn-outline btn-sm mt-5"><x-icon name="circle-x" class="size-4" /> Batalkan antrian</button>
        </div>

        {{-- Tahap percakapan --}}
        <div x-show="stage === 'chat'" class="flex min-h-0 flex-1 flex-col">
            <div x-ref="messages" class="scrollbar-thin pattern-islamic-dark flex-1 space-y-3 overflow-y-auto bg-sand-50 px-4 py-5" aria-live="polite">
                <template x-for="m in messages" :key="m.id">
                    <div>
                        <template x-if="m.sender_type === 'system'">
                            <p class="mx-auto max-w-[90%] rounded-2xl bg-white/90 px-3 py-1.5 text-center text-[11.5px] leading-relaxed text-stone-500 ring-1 ring-stone-200" x-html="m.html"></p>
                        </template>
                        <template x-if="m.sender_type !== 'system'">
                            <div class="flex items-end gap-2" :class="m.sender_type === 'pengunjung' ? 'flex-row-reverse' : ''">
                                <span x-show="m.sender_type !== 'pengunjung'" class="grid size-7 shrink-0 place-items-center rounded-full text-[10px] font-bold"
                                      :class="m.sender_type === 'bot' ? 'bg-gold-100 text-gold-700' : 'bg-brand-700 text-white'"
                                      x-text="m.sender_type === 'bot' ? 'AI' : (m.sender_name || 'P').charAt(0)"></span>
                                <div class="max-w-[80%]">
                                    <p x-show="m.sender_type !== 'pengunjung'" class="mb-1 ml-1 text-[10.5px] font-semibold text-stone-500" x-text="m.sender_name"></p>
                                    <div class="rounded-2xl px-3.5 py-2.5 text-[13.5px] leading-relaxed break-words shadow-sm"
                                         :class="m.sender_type === 'pengunjung' ? 'rounded-br-md bg-brand-700 text-white' : (m.sender_type === 'bot' ? 'rounded-bl-md bg-white text-stone-700 ring-1 ring-gold-200' : 'rounded-bl-md bg-white text-stone-700 ring-1 ring-stone-200')"
                                         x-html="m.html"></div>
                                    <p class="mt-1 text-[10px] text-stone-400" :class="m.sender_type === 'pengunjung' ? 'mr-1 text-right' : 'ml-1'" x-text="m.time"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <div x-show="sending" class="flex items-center gap-1.5 pl-9">
                    <span class="size-2 animate-bounce rounded-full bg-brand-400"></span>
                    <span class="size-2 animate-bounce rounded-full bg-brand-400 [animation-delay:.15s]"></span>
                    <span class="size-2 animate-bounce rounded-full bg-brand-400 [animation-delay:.3s]"></span>
                </div>

                <div x-show="closed" class="rounded-2xl bg-white p-4 text-center ring-1 ring-stone-200">
                    <span class="mx-auto grid size-10 place-items-center rounded-full bg-brand-50 text-brand-700"><x-icon name="circle-check-big" class="size-5" /></span>
                    <p class="mt-2 text-sm font-semibold text-ink-900">Sesi konsultasi telah selesai</p>
                    <p class="mt-0.5 text-xs text-stone-500">Jazakumullah khairan atas kepercayaan Anda.</p>
                    <button type="button" @click="reset()" class="btn btn-outline btn-sm mt-3">Mulai percakapan baru</button>
                </div>
            </div>

            <div x-show="!closed" class="shrink-0 border-t border-stone-100 bg-white">
                <div x-show="session?.status === 'bot' && faqs.length" class="scrollbar-none flex gap-2 overflow-x-auto px-4 pt-3">
                    <template x-for="faq in faqs" :key="faq.id">
                        <button type="button" @click="askFaq(faq)" :disabled="sending" class="shrink-0 rounded-full border border-brand-200 bg-brand-50 px-3 py-1.5 text-xs font-medium text-brand-800 hover:bg-brand-100 disabled:opacity-60" x-text="faq.pertanyaan"></button>
                    </template>
                </div>
                <p x-show="error" x-text="error" class="field-error px-4 pt-2"></p>
                <form @submit.prevent="send()" class="flex items-end gap-2 p-3">
                    <textarea x-ref="input" x-model="input" rows="1" maxlength="2000" placeholder="Tulis pesan…" aria-label="Tulis pesan"
                              @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); send(); }"
                              @input="$el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 120) + 'px'"
                              class="input max-h-[120px] min-h-[44px] flex-1 resize-none py-2.5"></textarea>
                    <button type="submit" class="grid size-11 shrink-0 place-items-center rounded-xl bg-brand-700 text-white transition hover:bg-brand-800 disabled:opacity-50" :disabled="!input.trim() || sending" aria-label="Kirim">
                        <x-icon name="send" class="size-5" />
                    </button>
                </form>
            </div>
        </div>
    </section>
</div>
