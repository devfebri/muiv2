@php
    $s = fn (string $key, ?string $default = null) => filled($settings[$key] ?? null) ? $settings[$key] : $default;
    $sekilas = collect([1, 2, 3])->map(fn ($i) => $s("profil_sekilas_{$i}"))->filter();
    $peran = collect([1, 2, 3])->map(fn ($i) => ['title' => $s("profil_peran_{$i}_title"), 'desc' => $s("profil_peran_{$i}_desc")])->filter(fn ($p) => filled($p['title']));
    $peranIkon = ['hand-heart', 'shield-check', 'handshake'];
    $tugas = collect(preg_split('/\r\n|\r|\n/', (string) $s('profil_tugas_pokok', '')))->map(fn ($l) => trim($l))->filter()->map(function ($line) {
        [$judul, $isi] = array_pad(explode(':', $line, 2), 2, null);

        return ['judul' => trim($judul), 'isi' => trim((string) $isi)];
    });
    $tugasIkon = ['scale', 'heart-handshake', 'badge-check', 'landmark'];
@endphp

<x-layouts.site :title="$s('profil_title', 'Profil MUI')" :description="$s('profil_subtitle')">
    <x-page-hero :title="$s('profil_title', 'Profil Majelis Ulama Indonesia')" eyebrow="Profil" :crumbs="['Profil' => null, 'Profil MUI' => null]" :subtitle="$s('profil_subtitle')" />

    <div class="container-x mt-10">
        <x-profile-nav />

        <section class="mt-10 grid gap-10 lg:grid-cols-12">
            <div class="reveal lg:col-span-8">
                <p class="eyebrow">Sekilas MUI</p>
                <h2 class="section-title mt-3">Tenda besar umat Islam Indonesia</h2>
                <div class="prose-mui mt-6">
                    @forelse ($sekilas as $paragraf)
                        <p>{{ $paragraf }}</p>
                    @empty
                        <p>Majelis Ulama Indonesia (MUI) adalah wadah musyawarah para ulama, zu’ama, dan cendekiawan muslim dalam membimbing, membina, dan mengayomi umat Islam di Indonesia.</p>
                    @endforelse
                </div>
            </div>
            <aside class="reveal lg:col-span-4">
                <div class="card overflow-hidden lg:sticky lg:top-24">
                    <div class="bg-gradient-brand relative p-6 text-white">
                        <div class="pattern-islamic absolute inset-0"></div>
                        <div class="relative flex items-center gap-4">
                            <img src="{{ $site['logo_url'] }}" alt="" class="size-14 rounded-full bg-white object-contain p-0.5 ring-2 ring-gold-300/70">
                            <div>
                                <p class="font-display text-lg font-semibold text-white">{{ $site['site_short'] }}</p>
                                <p class="text-xs font-semibold tracking-wider text-gold-300 uppercase">Identitas lembaga</p>
                            </div>
                        </div>
                    </div>
                    <dl class="divide-y divide-stone-100 text-sm">
                        @foreach ([
                            ['Tanggal berdiri', $s('profil_tgl_berdiri', '26 Juli 1975'), 'calendar-days'],
                            ['Sifat lembaga', $s('profil_sifat_lembaga', 'Lembaga Keagamaan Independen'), 'landmark'],
                            ['Alamat kantor', $s('profil_alamat_kantor', $site['address']), 'map-pin'],
                        ] as [$label, $value, $icon])
                            <div class="flex gap-3 px-6 py-4">
                                <x-icon :name="$icon" class="mt-0.5 size-4 text-gold-500" />
                                <div>
                                    <dt class="text-xs text-stone-500">{{ $label }}</dt>
                                    <dd class="mt-0.5 font-semibold text-ink-900">{{ $value }}</dd>
                                </div>
                            </div>
                        @endforeach
                    </dl>
                    <div class="grid grid-cols-2 gap-2 border-t border-stone-100 p-4">
                        <a href="{{ route('visi-misi') }}" class="btn btn-outline btn-sm">Visi & Misi</a>
                        <a href="{{ route('struktur-organisasi') }}" class="btn btn-primary btn-sm">Struktur</a>
                    </div>
                </div>
            </aside>
        </section>

        @if ($peran->isNotEmpty())
            <section class="mt-24">
                <div class="reveal text-center">
                    <p class="eyebrow justify-center">Khidmah</p>
                    <h2 class="section-title mt-3">Peran Majelis Ulama Indonesia</h2>
                </div>
                <div class="reveal mt-10 grid gap-6 md:grid-cols-3">
                    @foreach ($peran as $item)
                        <article class="group card card-hover relative overflow-hidden p-7">
                            <span class="absolute -top-6 -right-4 font-display text-[110px] leading-none font-bold text-brand-50 transition group-hover:text-gold-50">0{{ $loop->iteration }}</span>
                            <span class="relative grid size-12 place-items-center rounded-2xl bg-brand-700 text-gold-300 shadow-sm"><x-icon :name="$peranIkon[$loop->index] ?? 'star'" class="size-6" /></span>
                            <h3 class="relative mt-5 font-display text-2xl font-semibold text-ink-900">{{ $item['title'] }}</h3>
                            <p class="relative mt-3 text-sm leading-relaxed text-stone-600">{{ $item['desc'] }}</p>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($tugas->isNotEmpty())
            <section class="bg-gradient-brand relative mt-24 overflow-hidden rounded-[2rem] px-6 py-14 sm:px-12">
                <div class="pattern-islamic absolute inset-0"></div>
                <div class="absolute -right-24 -bottom-24 size-80 rounded-full bg-gold-400/15 blur-3xl"></div>
                <div class="relative">
                    <p class="eyebrow text-gold-300!">Amanah</p>
                    <h2 class="mt-3 font-display text-3xl font-semibold text-white sm:text-4xl">Tugas Pokok</h2>
                    <div class="mt-10 grid gap-5 md:grid-cols-2">
                        @foreach ($tugas as $item)
                            <div class="flex gap-4 rounded-2xl border border-white/10 bg-white/[.06] p-5 backdrop-blur">
                                <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-gold-400/15 text-gold-300 ring-1 ring-gold-400/30"><x-icon :name="$tugasIkon[$loop->index] ?? 'circle-check-big'" class="size-5" /></span>
                                <div>
                                    <h3 class="font-semibold text-white">{{ $item['judul'] }}</h3>
                                    @if ($item['isi'])
                                        <p class="mt-1.5 text-sm leading-relaxed text-white/70">{{ $item['isi'] }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    </div>
</x-layouts.site>
