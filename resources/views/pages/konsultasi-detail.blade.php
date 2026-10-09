@php $dijawab = $konsultasi->status === 'dijawab' && filled($konsultasi->jawaban); @endphp

<x-layouts.site :title="Str::limit($konsultasi->pertanyaan, 70)" :description="Str::limit($konsultasi->pertanyaan, 160)" og-type="article">
    @if ($dijawab)
        @push('head')
            <script type="application/ld+json">
                {!! json_encode([
                    '@context' => 'https://schema.org',
                    '@type' => 'QAPage',
                    'mainEntity' => [
                        '@type' => 'Question',
                        'name' => Str::limit($konsultasi->pertanyaan, 110),
                        'text' => $konsultasi->pertanyaan,
                        'dateCreated' => $konsultasi->created_at?->toIso8601String(),
                        'answerCount' => 1,
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => $konsultasi->jawaban,
                            'dateCreated' => $konsultasi->answered_at?->toIso8601String(),
                            'author' => ['@type' => 'Organization', 'name' => $site['site_short']],
                        ],
                    ],
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
            </script>
        @endpush
    @endif

    <x-page-hero :title="Str::limit($konsultasi->pertanyaan, 110)" :eyebrow="$konsultasi->kategori" :crumbs="['Tanya Jawab' => route('konsultasi.list'), $konsultasi->kategori => route('konsultasi.list', ['kategori' => $konsultasi->kategori]), 'Detail' => null]">
        <div class="mt-6 flex flex-wrap items-center gap-x-6 gap-y-3 text-sm text-white/70">
            <span class="flex items-center gap-1.5"><x-icon name="user-round" class="size-4 text-gold-400" /> {{ $konsultasi->nama_samaran }}{{ $konsultasi->kab_kota ? ', '.$konsultasi->kab_kota : '' }}</span>
            <span class="flex items-center gap-1.5"><x-icon name="calendar-days" class="size-4 text-gold-400" /> Ditanyakan {{ $konsultasi->created_at?->translatedFormat('d F Y') }}</span>
            @if ($dijawab)
                <span class="badge bg-gold-400 text-brand-950"><x-icon name="circle-check-big" class="size-3" /> Telah dijawab</span>
            @else
                <span class="badge bg-white/15 text-white"><x-icon name="hourglass" class="size-3" /> Menunggu jawaban</span>
            @endif
        </div>
    </x-page-hero>

    <div class="container-x mt-10 grid gap-10 lg:grid-cols-12">
        <div class="min-w-0 space-y-6 lg:col-span-8">
            <section class="card p-6 sm:p-8">
                <div class="flex items-center gap-3">
                    <span class="grid size-10 place-items-center rounded-xl bg-gold-100 font-display text-lg font-bold text-gold-700">T</span>
                    <div>
                        <h2 class="font-semibold text-ink-900">Pertanyaan</h2>
                        <p class="text-xs text-stone-500">{{ $konsultasi->nama_samaran }} · {{ $konsultasi->created_at?->translatedFormat('d F Y, H:i') }} WIB</p>
                    </div>
                </div>
                <p class="mt-5 text-[16.5px] leading-[1.85] whitespace-pre-line text-stone-700">{{ $konsultasi->pertanyaan }}</p>
            </section>

            @if ($dijawab)
                <section class="relative overflow-hidden rounded-2xl border border-brand-200 bg-white shadow-[var(--shadow-soft)]">
                    <div class="bg-gradient-brand relative px-6 py-5 sm:px-8">
                        <div class="pattern-islamic absolute inset-0"></div>
                        <div class="relative flex items-center gap-3">
                            <span class="grid size-10 place-items-center rounded-xl bg-gold-400 font-display text-lg font-bold text-brand-950">J</span>
                            <div>
                                <h2 class="font-semibold text-white">Jawaban Ulama</h2>
                                <p class="text-xs text-white/70">{{ $konsultasi->penjawab?->name ?? 'Tim Ulama MUI Batanghari' }} · {{ $konsultasi->answered_at?->translatedFormat('d F Y') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="prose-mui p-6 whitespace-pre-line sm:p-8">{{ $konsultasi->jawaban }}</div>
                    <p class="border-t border-stone-100 px-6 py-4 text-xs leading-relaxed text-stone-500 sm:px-8">Wallahu a’lam bish-shawab. Jawaban ini bersifat umum; untuk persoalan khusus silakan berkonsultasi langsung dengan ulama setempat.</p>
                </section>
            @else
                <x-empty-state icon="hourglass" title="Pertanyaan sedang ditelaah" message="Ulama MUI Kabupaten Batanghari sedang meninjau pertanyaan ini. Jawaban akan ditampilkan di halaman ini." />
            @endif

            <div x-data="share(@js(Str::limit($konsultasi->pertanyaan, 100)), @js(route('konsultasi.detail', $konsultasi)))" class="flex flex-wrap items-center gap-3 rounded-2xl border border-stone-200 bg-white p-5">
                <p class="mr-auto text-sm font-semibold text-ink-900">Bagikan ilmu ini</p>
                <a :href="link('whatsapp')" target="_blank" rel="noopener" class="grid size-10 place-items-center rounded-xl bg-[#25D366]/10 text-[#128C4B] hover:bg-[#25D366] hover:text-white" aria-label="Bagikan ke WhatsApp"><x-icon name="whatsapp" class="size-4" /></a>
                <a :href="link('facebook')" target="_blank" rel="noopener" class="grid size-10 place-items-center rounded-xl bg-[#1877F2]/10 text-[#1877F2] hover:bg-[#1877F2] hover:text-white" aria-label="Bagikan ke Facebook"><x-icon name="facebook" class="size-4" /></a>
                <button type="button" @click="copy()" class="flex h-10 items-center gap-2 rounded-xl bg-stone-100 px-4 text-sm font-semibold text-stone-700 hover:bg-stone-200">
                    <x-icon name="link" class="size-4" /> <span x-text="copied ? 'Tersalin!' : 'Salin tautan'">Salin tautan</span>
                </button>
            </div>

            <a href="{{ route('konsultasi.list') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-700 hover:text-brand-900"><x-icon name="arrow-left" class="size-4" /> Kembali ke tanya jawab</a>
        </div>

        <aside class="space-y-6 lg:col-span-4">
            @if ($terkait->isNotEmpty())
                <div class="card p-6">
                    <h2 class="font-bold text-ink-900">Pertanyaan terkait</h2>
                    <div class="mt-4 space-y-3">
                        @foreach ($terkait as $item)
                            <a href="{{ route('konsultasi.detail', $item) }}" class="group block rounded-xl border border-stone-100 p-3 hover:border-brand-200 hover:bg-brand-50/40">
                                <p class="text-[11px] font-semibold text-stone-400">{{ $item->answered_at?->translatedFormat('d M Y') }}</p>
                                <p class="mt-1 line-clamp-2 text-sm font-semibold text-ink-900 group-hover:text-brand-700">{{ $item->pertanyaan }}</p>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="bg-gradient-brand relative overflow-hidden rounded-2xl p-6 text-white" x-data>
                <div class="pattern-islamic absolute inset-0"></div>
                <div class="relative">
                    <x-icon name="file-pen-line" class="size-8 text-gold-300" />
                    <h3 class="mt-3 font-display text-xl font-semibold text-white">Punya pertanyaan lain?</h3>
                    <p class="mt-2 text-sm text-white/70">Kirim pertanyaan tertulis kepada ulama, atau berbincang langsung dengan petugas melalui konsultasi online.</p>
                    <div class="mt-5 flex flex-wrap gap-2">
                        <a href="{{ route('tanya-ulama') }}" class="btn btn-gold btn-sm">Tanya Ulama</a>
                        <button type="button" @click="$dispatch('open-chat')" class="btn btn-glass btn-sm">Konsultasi Online</button>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</x-layouts.site>
