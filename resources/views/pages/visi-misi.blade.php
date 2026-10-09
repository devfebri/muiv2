@php
    $s = fn (string $key, ?string $default = null) => filled($settings[$key] ?? null) ? $settings[$key] : $default;
    $misi = collect(preg_split('/\r\n|\r|\n/', (string) $s('misi_list', '')))->map(fn ($l) => trim($l))->filter()->map(function ($line) {
        [$judul, $isi] = array_pad(explode('|', $line, 2), 2, null);

        return ['judul' => trim($judul), 'isi' => trim((string) $isi)];
    });
    $prinsip = [
        ['Tawassuth', 'Moderat, mengambil jalan tengah', 'scale'],
        ['Tawazun', 'Berimbang dalam segala aspek', 'git-compare-arrows'],
        ['I’tidal', 'Lurus, tegas, dan adil', 'ruler'],
        ['Tasamuh', 'Toleran dan menghargai perbedaan', 'handshake'],
    ];
@endphp

<x-layouts.site :title="$s('visi_title', 'Visi & Misi')" :description="$s('visi_subtitle')">
    <x-page-hero :title="$s('visi_title', 'Visi & Misi MUI')" eyebrow="Profil" :crumbs="['Profil' => route('profilemui'), 'Visi & Misi' => null]" :subtitle="$s('visi_subtitle')" />

    <div class="container-x mt-10">
        <x-profile-nav />

        {{-- Visi --}}
        <section class="reveal bg-gradient-brand relative mt-10 overflow-hidden rounded-[2rem] px-6 py-14 text-center sm:px-16 sm:py-20">
            <div class="pattern-islamic absolute inset-0"></div>
            <div class="absolute -top-24 -left-24 size-80 rounded-full bg-gold-400/15 blur-3xl"></div>
            <div class="absolute -right-24 -bottom-24 size-80 rounded-full bg-brand-400/20 blur-3xl"></div>
            <div class="relative mx-auto max-w-4xl">
                <p class="eyebrow justify-center text-gold-300!">Visi</p>
                <x-icon name="quote" class="mx-auto mt-6 size-10 text-gold-300" />
                <p class="mt-5 font-display text-xl leading-relaxed text-white italic sm:text-[1.7rem] sm:leading-[1.6]">
                    {{ $s('visi_text', 'Terciptanya kondisi kehidupan kemasyarakatan, kebangsaan dan kenegaraan yang baik, memperoleh ridha dan ampunan Allah SWT (Baldatun Thayyibatun wa Rabbun Ghafur).') }}
                </p>
                <p class="arabic mt-8 text-center text-2xl text-gold-300">بَلْدَةٌ طَيِّبَةٌ وَرَبٌّ غَفُورٌ</p>
            </div>
        </section>

        {{-- Misi --}}
        @if ($misi->isNotEmpty())
            <section class="mt-24">
                <div class="reveal flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="eyebrow">Misi</p>
                        <h2 class="section-title mt-3">Langkah pengabdian untuk umat dan bangsa</h2>
                    </div>
                    <p class="text-sm text-stone-500">{{ $misi->count() }} butir misi</p>
                </div>
                <div class="reveal mt-10 grid gap-5 md:grid-cols-2">
                    @foreach ($misi as $item)
                        <article class="group card card-hover flex gap-5 p-6">
                            <span class="font-display text-4xl leading-none font-bold text-gold-400/80 transition group-hover:text-gold-500">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <div>
                                <h3 class="leading-snug font-semibold text-ink-900">{{ $item['judul'] }}</h3>
                                @if ($item['isi'])
                                    <p class="mt-2 text-sm leading-relaxed text-stone-600">{{ $item['isi'] }}</p>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Wasathiyah --}}
        <section class="mt-24 grid gap-10 lg:grid-cols-12 lg:items-center">
            <div class="reveal lg:col-span-5">
                <p class="eyebrow">Manhaj</p>
                <h2 class="section-title mt-3">{{ $s('wasathiyah_title', 'Prinsip Islam Wasathiyah') }}</h2>
                <p class="mt-5 leading-[1.85] text-stone-600">{{ $s('wasathiyah_desc', 'MUI senantiasa mengedepankan corak keislaman yang moderat, berimbang, adil, dan toleran.') }}</p>
            </div>
            <div class="reveal grid gap-4 sm:grid-cols-2 lg:col-span-7">
                @foreach ($prinsip as [$nama, $arti, $ikon])
                    <div class="card flex items-start gap-4 p-5">
                        <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100"><x-icon :name="$ikon" class="size-5" /></span>
                        <div>
                            <p class="font-display text-xl font-semibold text-ink-900">{{ $nama }}</p>
                            <p class="mt-0.5 text-sm text-stone-500">{{ $arti }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</x-layouts.site>
