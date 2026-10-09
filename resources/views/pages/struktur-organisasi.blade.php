@php
    $s = fn (string $key, ?string $default = null) => filled($settings[$key] ?? null) ? $settings[$key] : $default;
    $bagan = $s('struktur_bagan_gambar') ? asset('uploads/pengaturan/'.basename($s('struktur_bagan_gambar'))) : null;
    $harian = collect([
        ['wakil_ketua_umum', 'Wakil Ketua Umum', 'user-round-check'],
        ['sekjen', 'Sekretaris Jenderal', 'notebook-pen'],
        ['bendahara_umum', 'Bendahara Umum', 'wallet'],
        ['ketua_bidang', 'Ketua-Ketua Bidang', 'users'],
    ])->map(fn ($r) => ['jabatan' => $s("struktur_{$r[0]}", $r[1]), 'desc' => $s("struktur_{$r[0]}_desc"), 'icon' => $r[2]]);
    $komisi = collect(preg_split('/\r\n|\r|\n/', (string) $s('struktur_komisi_list', '')))->map(fn ($l) => trim($l))->filter()->map(function ($line) {
        [$nama, $isi] = array_pad(explode('|', $line, 2), 2, null);

        return ['nama' => trim($nama), 'isi' => trim((string) $isi)];
    });
@endphp

<x-layouts.site :title="$s('struktur_title', 'Struktur Organisasi')" :description="$s('struktur_subtitle')">
    <x-page-hero :title="$s('struktur_title', 'Struktur Organisasi MUI')" eyebrow="Profil" :crumbs="['Profil' => route('profilemui'), 'Struktur Organisasi' => null]" :subtitle="$s('struktur_subtitle')" />

    <div class="container-x mt-10">
        <x-profile-nav />

        @if ($bagan)
            <section class="reveal mt-10" x-data="{ zoom: false }">
                <div class="card overflow-hidden">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-stone-100 bg-sand-100/60 px-6 py-4">
                        <h2 class="flex items-center gap-2 font-bold text-ink-900"><x-icon name="network" class="size-5 text-gold-500" /> Bagan Struktur Organisasi</h2>
                        <div class="flex gap-2">
                            <button type="button" @click="zoom = true" class="btn btn-outline btn-sm"><x-icon name="zoom-in" class="size-4" /> Perbesar</button>
                            <a href="{{ $bagan }}" download class="btn btn-primary btn-sm"><x-icon name="download" class="size-4" /> Unduh</a>
                        </div>
                    </div>
                    <button type="button" @click="zoom = true" class="block w-full bg-white p-4 sm:p-6" aria-label="Perbesar bagan">
                        <img src="{{ $bagan }}" alt="Bagan struktur organisasi {{ $site['site_short'] }}" loading="lazy" class="mx-auto max-h-[640px] w-auto">
                    </button>
                </div>
                <template x-teleport="body">
                    <div x-show="zoom" x-cloak x-transition.opacity class="fixed inset-0 z-[90] flex items-center justify-center overflow-auto bg-black/90 p-4" @click="zoom = false" @keydown.escape.window="zoom = false" role="dialog" aria-modal="true">
                        <img src="{{ $bagan }}" alt="Bagan struktur organisasi" class="max-w-none rounded-xl bg-white sm:max-w-[95vw]" @click.stop>
                        <button type="button" class="absolute top-5 right-5 text-white" aria-label="Tutup"><x-icon name="x" class="size-8" /></button>
                    </div>
                </template>
            </section>
        @endif

        {{-- Dewan Pertimbangan --}}
        <section class="mt-16 grid gap-8 lg:grid-cols-12 lg:items-start">
            <div class="reveal lg:col-span-5">
                <p class="eyebrow">Dewan Pertimbangan</p>
                <h2 class="section-title mt-3">Penasihat & pemberi arahan strategis</h2>
                <p class="mt-4 leading-relaxed text-stone-600">{{ $s('struktur_dewan_pertimbangan_desc', 'Dewan Pertimbangan memberikan arahan dan nasihat kepada Dewan Pimpinan Harian.') }}</p>
            </div>
            <div class="reveal grid gap-4 sm:grid-cols-2 lg:col-span-7">
                <div class="bg-gradient-brand relative overflow-hidden rounded-2xl p-6 text-white">
                    <div class="pattern-islamic absolute inset-0"></div>
                    <div class="relative">
                        <span class="grid size-11 place-items-center rounded-xl bg-gold-400 text-brand-950"><x-icon name="crown" class="size-5" /></span>
                        <p class="mt-4 text-xs font-bold tracking-wider text-gold-300 uppercase">Ketua Dewan Pertimbangan</p>
                        <p class="mt-1 font-display text-xl font-semibold text-white">{{ $s('struktur_ketua_pertimbangan', '—') }}</p>
                    </div>
                </div>
                <div class="card p-6">
                    <span class="grid size-11 place-items-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100"><x-icon name="users-round" class="size-5" /></span>
                    <p class="mt-4 text-xs font-bold tracking-wider text-gold-600 uppercase">Anggota</p>
                    <p class="mt-1 font-display text-xl font-semibold text-ink-900">{{ $s('struktur_anggota_pertimbangan', '—') }}</p>
                </div>
            </div>
        </section>

        {{-- Dewan Pimpinan Harian --}}
        <section class="mt-24">
            <div class="reveal text-center">
                <p class="eyebrow justify-center">Dewan Pimpinan Harian</p>
                <h2 class="section-title mt-3">Penggerak roda organisasi</h2>
                <p class="mx-auto mt-4 max-w-2xl text-stone-600">{{ $s('struktur_pimpinan_harian_desc') }}</p>
            </div>

            <div class="reveal mx-auto mt-10 max-w-md">
                <div class="bg-gradient-brand relative overflow-hidden rounded-3xl p-7 text-center text-white shadow-[var(--shadow-lift)]">
                    <div class="pattern-islamic absolute inset-0"></div>
                    <div class="relative">
                        <img src="{{ $site['logo_url'] }}" alt="" class="mx-auto size-16 rounded-full bg-white object-contain p-0.5 ring-4 ring-gold-300/60">
                        <p class="mt-4 font-display text-2xl font-semibold text-white">{{ $s('struktur_ketua_umum', 'Ketua Umum') }}</p>
                        <p class="mt-2 text-sm text-white/70">{{ $s('struktur_ketua_umum_desc') }}</p>
                    </div>
                </div>
            </div>
            <div class="mx-auto h-10 w-px bg-gradient-to-b from-gold-400 to-stone-200" aria-hidden="true"></div>
            <div class="reveal grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($harian as $item)
                    <article class="card card-hover relative p-6 text-center">
                        <span class="mx-auto grid size-12 place-items-center rounded-2xl bg-brand-50 text-brand-700 ring-1 ring-brand-100"><x-icon :name="$item['icon']" class="size-5" /></span>
                        <h3 class="mt-4 font-semibold text-ink-900">{{ $item['jabatan'] }}</h3>
                        @if ($item['desc'])
                            <p class="mt-2 text-xs leading-relaxed text-stone-500">{{ $item['desc'] }}</p>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>

        {{-- Komisi & Lembaga --}}
        @if ($komisi->isNotEmpty())
            <section class="bg-gradient-brand relative mt-24 overflow-hidden rounded-[2rem] px-6 py-14 sm:px-12">
                <div class="pattern-islamic absolute inset-0"></div>
                <div class="absolute -top-24 -right-24 size-80 rounded-full bg-gold-400/15 blur-3xl"></div>
                <div class="relative">
                    <p class="eyebrow text-gold-300!">Perangkat Organisasi</p>
                    <h2 class="mt-3 font-display text-3xl font-semibold text-white sm:text-4xl">Komisi, Badan & Lembaga</h2>
                    <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($komisi as $item)
                            <div class="rounded-2xl border border-white/10 bg-white/[.06] p-5 backdrop-blur transition hover:border-gold-400/40 hover:bg-white/10">
                                <span class="font-display text-sm font-bold text-gold-300">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                <h3 class="mt-1 font-semibold text-white">{{ $item['nama'] }}</h3>
                                @if ($item['isi'])
                                    <p class="mt-1.5 text-sm leading-relaxed text-white/70">{{ $item['isi'] }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    </div>
</x-layouts.site>
