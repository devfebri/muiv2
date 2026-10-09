@php
    $s = fn (string $key, ?string $default = null) => filled($settings[$key] ?? null) ? $settings[$key] : $default;
    $waNumber = preg_replace('/\D/', '', (string) $site['whatsapp']);
    $waNumber = str_starts_with($waNumber, '0') ? '62'.substr($waNumber, 1) : $waNumber;
    $kanal = array_filter([
        ['Alamat Sekretariat', $site['address'], 'map-pin', null],
        $site['phone'] ? ['Telepon', $site['phone'], 'phone', 'tel:'.preg_replace('/[^0-9+]/', '', $site['phone'])] : null,
        $waNumber ? ['WhatsApp', $site['whatsapp'], 'whatsapp', 'https://wa.me/'.$waNumber] : null,
        $site['email'] ? ['Email', $site['email'], 'mail', 'mailto:'.$site['email']] : null,
        ['Jam Layanan', $site['office_hours'], 'clock', null],
    ]);
    $socials = collect(['facebook' => 'Facebook', 'instagram' => 'Instagram', 'youtube' => 'YouTube', 'tiktok' => 'TikTok', 'x-twitter' => 'X (Twitter)'])->filter(fn ($label, $key) => filled($site[$key]));
@endphp

<x-layouts.site :title="$s('kontak_title', 'Kontak Kami')" :description="$s('kontak_subtitle')">
    <x-page-hero :title="$s('kontak_title', 'Hubungi Kami')" eyebrow="Kontak" :crumbs="['Kontak' => null]" :subtitle="$s('kontak_subtitle')" />

    <div class="container-x mt-10">
        <x-profile-nav />

        <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-5">
            @foreach ($kanal as [$label, $nilai, $ikon, $tautan])
                <div @class(['reveal card relative p-5', 'sm:col-span-2 lg:col-span-1' => $loop->first])>
                    <span class="grid size-11 place-items-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100"><x-icon :name="$ikon" class="size-5" /></span>
                    <p class="mt-4 text-xs font-bold tracking-wider text-gold-600 uppercase">{{ $label }}</p>
                    @if ($tautan)
                        <a href="{{ $tautan }}" @if (str_starts_with($tautan, 'http')) target="_blank" rel="noopener" @endif class="mt-1 block text-sm font-semibold break-words text-ink-900 after:absolute after:inset-0 hover:text-brand-700">{{ $nilai }}</a>
                    @else
                        <p class="mt-1 text-sm font-semibold text-ink-900">{{ $nilai }}</p>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-10 grid gap-8 lg:grid-cols-12">
            {{-- Pesan cepat via WhatsApp --}}
            <section class="reveal lg:col-span-5">
                <form class="card p-6 sm:p-8" x-data="{ nama: '', topik: 'Informasi Umum', pesan: '' }"
                      @submit.prevent="window.open('https://wa.me/{{ $waNumber }}?text=' + encodeURIComponent(`Assalamu'alaikum, saya ${nama}.\nTopik: ${topik}\n\n${pesan}`), '_blank', 'noopener')">
                    <div class="flex items-start gap-4">
                        <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-[#25D366]/10 text-[#128C4B]"><x-icon name="whatsapp" class="size-6" /></span>
                        <div>
                            <h2 class="font-display text-2xl font-semibold text-ink-900">Kirim pesan</h2>
                            <p class="mt-1 text-sm text-stone-500">Pesan Anda akan diteruskan melalui WhatsApp Sekretariat.</p>
                        </div>
                    </div>
                    @if ($waNumber)
                        <div class="mt-6 space-y-4">
                            <div>
                                <label for="k-nama" class="label">Nama lengkap <span class="text-red-500">*</span></label>
                                <input id="k-nama" x-model="nama" type="text" class="input" required maxlength="100" autocomplete="name" placeholder="Nama Anda">
                            </div>
                            <div>
                                <label for="k-topik" class="label">Topik</label>
                                <select id="k-topik" x-model="topik" class="input">
                                    <option>Informasi Umum</option>
                                    <option>Permohonan Rekomendasi / Surat</option>
                                    <option>Sertifikasi Halal</option>
                                    <option>Aspirasi & Pengaduan Umat</option>
                                    <option>Kerja Sama & Undangan</option>
                                </select>
                            </div>
                            <div>
                                <label for="k-pesan" class="label">Pesan <span class="text-red-500">*</span></label>
                                <textarea id="k-pesan" x-model="pesan" rows="5" class="input resize-none" required minlength="5" maxlength="1500" placeholder="Tuliskan pesan Anda…"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-full"><x-icon name="send" class="size-4" /> Kirim via WhatsApp</button>
                            <p class="text-center text-xs text-stone-500">Untuk pertanyaan keagamaan, gunakan <a href="{{ route('tanya-ulama') }}" class="font-semibold text-brand-700 hover:underline">Tanya Ulama</a>.</p>
                        </div>
                    @else
                        <p class="mt-6 rounded-xl bg-sand-100 p-4 text-sm text-stone-600">Nomor WhatsApp sekretariat belum diatur. Silakan hubungi kami melalui telepon atau email.</p>
                    @endif
                </form>
            </section>

            {{-- Peta --}}
            <section class="reveal lg:col-span-7">
                <div class="card h-full overflow-hidden">
                    @if ($site['maps_embed'])
                        <iframe src="{{ $site['maps_embed'] }}" title="Peta lokasi sekretariat {{ $site['site_short'] }}" class="h-full min-h-[420px] w-full" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                    @else
                        <div class="bg-gradient-brand relative grid h-full min-h-[420px] place-items-center p-8 text-center text-white">
                            <div class="pattern-islamic absolute inset-0"></div>
                            <div class="relative">
                                <x-icon name="map-pinned" class="mx-auto size-10 text-gold-300" />
                                <p class="mt-4 font-display text-xl">{{ $site['address'] }}</p>
                                <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($site['address']) }}" target="_blank" rel="noopener" class="btn btn-gold btn-sm mt-6">Buka di Google Maps</a>
                            </div>
                        </div>
                    @endif
                </div>
            </section>
        </div>

        @if ($socials->isNotEmpty())
            <section class="reveal mt-10 flex flex-col items-center justify-between gap-5 rounded-2xl border border-stone-200 bg-white p-6 sm:flex-row">
                <div>
                    <h2 class="font-semibold text-ink-900">Ikuti kabar MUI Batanghari</h2>
                    <p class="text-sm text-stone-500">Dapatkan informasi kegiatan dan kajian terbaru melalui media sosial kami.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach ($socials as $key => $label)
                        @php $handle = $s('kontak_'.($key === 'x-twitter' ? 'twitter' : $key)); @endphp
                        <a href="{{ $site[$key] }}" target="_blank" rel="noopener" class="flex items-center gap-2.5 rounded-xl border border-stone-200 px-4 py-2.5 text-sm transition hover:border-brand-500 hover:text-brand-700">
                            <x-icon :name="$key" class="size-4 text-brand-700" />
                            <span class="leading-tight">
                                <span class="block font-semibold text-stone-800">{{ $label }}</span>
                                @if ($handle && !Str::startsWith($handle, 'http'))<span class="block text-xs text-stone-500">{{ $handle }}</span>@endif
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts.site>
