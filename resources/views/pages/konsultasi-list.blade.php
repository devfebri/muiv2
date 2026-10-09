@php
    $statusTabs = ['' => ['Semua', $totalSemua], 'dijawab' => ['Dijawab', $totalDijawab], 'pending' => ['Menunggu', $totalPending]];
    $query = fn (array $extra) => array_filter(array_merge(['q' => $search, 'kategori' => $kategoriAktif, 'status' => $statusAktif], $extra), fn ($v) => filled($v));
@endphp

<x-layouts.site :title="$kategoriAktif ? 'Tanya Jawab: '.$kategoriAktif : 'Tanya Jawab Umat'" description="Kumpulan pertanyaan keagamaan masyarakat beserta jawaban ulama MUI Kabupaten Batanghari.">
    <x-page-hero title="Tanya Jawab Umat" eyebrow="Tanya Ulama" :crumbs="['Layanan' => null, 'Tanya Jawab' => null]"
                 subtitle="Pertanyaan keagamaan dari masyarakat beserta jawaban para ulama Majelis Ulama Indonesia Kabupaten Batanghari.">
        <div class="mt-8 flex max-w-3xl flex-col gap-3 sm:flex-row">
            <form class="relative flex-1" role="search">
                @foreach (array_filter(['kategori' => $kategoriAktif, 'status' => $statusAktif]) as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
                <x-icon name="search" class="pointer-events-none absolute top-1/2 left-4 z-10 size-5 -translate-y-1/2 text-white/60" />
                <input name="q" value="{{ $search }}" type="search" placeholder="Cari pertanyaan atau jawaban…" aria-label="Cari tanya jawab" class="w-full rounded-2xl border border-white/15 bg-white/10 py-4 pr-24 pl-12 text-white backdrop-blur placeholder:text-white/50 focus:border-gold-400 focus:outline-none">
                <button class="btn btn-glass btn-sm absolute top-1/2 right-2 -translate-y-1/2">Cari</button>
            </form>
            <a href="{{ route('tanya-ulama') }}" class="btn btn-gold btn-lg shrink-0"><x-icon name="file-pen-line" class="size-4" /> Ajukan Pertanyaan</a>
        </div>
    </x-page-hero>

    <div class="container-x mt-10">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <nav class="flex gap-1 rounded-2xl bg-white p-1 ring-1 ring-stone-200" aria-label="Status jawaban">
                @foreach ($statusTabs as $key => [$label, $jumlah])
                    <a href="{{ route('konsultasi.list', $query(['status' => $key ?: null])) }}" @class([
                        'flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold transition',
                        'bg-brand-700 text-white shadow-sm' => (string) $statusAktif === (string) $key,
                        'text-stone-600 hover:text-brand-700' => (string) $statusAktif !== (string) $key,
                    ])>{{ $label }} <span class="text-xs opacity-70">{{ $jumlah }}</span></a>
                @endforeach
            </nav>
            <form class="flex items-center gap-2" x-data>
                @foreach (array_filter(['q' => $search, 'status' => $statusAktif]) as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
                <label for="f-kategori" class="text-sm font-semibold whitespace-nowrap text-stone-600">Kategori</label>
                <select id="f-kategori" name="kategori" class="input w-auto min-w-56" @change="$el.form.submit()">
                    <option value="">Semua kategori</option>
                    @foreach ($kategoriList as $kategori)
                        <option value="{{ $kategori }}" @selected($kategoriAktif === $kategori)>{{ $kategori }}{{ isset($statKategori[$kategori]) ? ' ('.$statKategori[$kategori].')' : '' }}</option>
                    @endforeach
                </select>
                <noscript><button class="btn btn-outline btn-sm">Terapkan</button></noscript>
            </form>
        </div>

        <div class="mt-8 flex flex-wrap items-end justify-between gap-2">
            <h2 class="font-display text-2xl font-semibold text-ink-900">Daftar Konsultasi Syariah</h2>
            <p class="text-sm text-stone-600">
                @if ($search)
                    Hasil pencarian <strong class="text-ink-900">“{{ $search }}”</strong> —
                @endif
                <strong class="text-ink-900">{{ $konsultasis->total() }}</strong> pertanyaan
            </p>
        </div>

        <div class="mt-5 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($konsultasis as $tanya)
                <article class="group card card-hover relative flex flex-col p-6">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="badge badge-green">{{ $tanya->kategori }}</span>
                        @if ($tanya->status === 'dijawab')
                            <span class="badge badge-gold"><x-icon name="circle-check-big" class="size-3" /> Dijawab</span>
                        @else
                            <span class="badge badge-gray"><x-icon name="hourglass" class="size-3" /> Menunggu</span>
                        @endif
                    </div>
                    <h2 class="mt-4 flex gap-3 leading-snug font-semibold text-ink-900">
                        <span class="grid size-7 shrink-0 place-items-center rounded-lg bg-gold-100 font-display text-sm text-gold-700">T</span>
                        <a href="{{ route('konsultasi.detail', $tanya) }}" class="line-clamp-3 after:absolute after:inset-0 group-hover:text-brand-700">{{ $tanya->pertanyaan }}</a>
                    </h2>
                    @if ($tanya->status === 'dijawab' && $tanya->jawaban)
                        <p class="mt-3 flex gap-3 text-sm leading-relaxed text-stone-600">
                            <span class="grid size-7 shrink-0 place-items-center rounded-lg bg-brand-50 font-display text-sm text-brand-700">J</span>
                            <span class="line-clamp-3">{{ Str::limit($tanya->jawaban, 200) }}</span>
                        </p>
                    @else
                        <p class="mt-3 rounded-xl bg-sand-100 px-4 py-3 text-xs text-stone-500">Pertanyaan sedang ditelaah oleh ulama MUI.</p>
                    @endif
                    <div class="mt-auto flex items-center justify-between gap-3 pt-5 text-xs text-stone-500">
                        <span class="flex min-w-0 items-center gap-1.5"><x-icon name="user-round" class="size-3.5 shrink-0" /> <span class="truncate">{{ $tanya->nama_samaran }}{{ $tanya->kab_kota ? ', '.$tanya->kab_kota : '' }}</span></span>
                        <span class="shrink-0">{{ ($tanya->answered_at ?? $tanya->created_at)?->translatedFormat('d M Y') }}</span>
                    </div>
                </article>
            @empty
                <x-empty-state class="md:col-span-2 xl:col-span-3" icon="message-circle-question" title="Belum ada pertanyaan" message="Belum ada tanya jawab yang sesuai dengan filter Anda.">
                    <a href="{{ route('tanya-ulama') }}" class="btn btn-primary btn-sm mt-5">Ajukan Pertanyaan</a>
                </x-empty-state>
            @endforelse
        </div>
        <div class="mt-10">{{ $konsultasis->links() }}</div>
    </div>
</x-layouts.site>
