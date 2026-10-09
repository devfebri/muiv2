@php
    $status = $fatwa->status_fatwa ?? 'aktif';
    $crumbs = ['Fatwa' => route('fatwa')];
    if ($fatwa->kategori) {
        $crumbs[$fatwa->kategori->nama] = route('fatwa', ['kategori' => $fatwa->kategori->slug]);
    }
    $crumbs[Str::limit($fatwa->judul, 40)] = null;
@endphp

<x-layouts.site :title="$fatwa->judul" :description="$fatwa->keterangan ?: $fatwa->judul">
    <x-page-hero :title="$fatwa->judul" :eyebrow="$fatwa->kategori?->nama ?? 'Fatwa MUI'" :crumbs="$crumbs">
        <div class="mt-6 flex flex-wrap items-center gap-3">
            <x-fatwa-status :status="$status" class="bg-white/90!" />
            <span class="flex items-center gap-1.5 text-sm text-white/70"><x-icon name="calendar-days" class="size-4 text-gold-400" /> Dipublikasikan {{ $fatwa->created_at?->translatedFormat('d F Y') }}</span>
            <span class="flex items-center gap-1.5 text-sm text-white/70"><x-icon name="eye" class="size-4 text-gold-400" /> {{ number_format($fatwa->views ?? 0, 0, ',', '.') }} kali dilihat</span>
        </div>
    </x-page-hero>

    <div class="container-x mt-10 grid gap-10 lg:grid-cols-12">
        <div class="min-w-0 space-y-8 lg:col-span-8">
            @if ($status === 'digantikan')
                <div class="flex items-start gap-3 rounded-2xl border border-gold-300 bg-gold-50 p-5 text-sm text-gold-900">
                    <x-icon name="circle-alert" class="mt-0.5 size-5 text-gold-600" />
                    <div>
                        <p class="font-semibold">Fatwa ini telah digantikan.</p>
                        <p class="mt-1">Ketentuan terbaru terdapat pada fatwa pengganti. Gunakan dokumen ini sebagai rujukan sejarah hukum.</p>
                    </div>
                </div>
            @elseif ($status === 'direvisi')
                <div class="flex items-start gap-3 rounded-2xl border border-gold-300 bg-gold-50 p-5 text-sm text-gold-900">
                    <x-icon name="pencil" class="mt-0.5 size-5 text-gold-600" /> Fatwa ini telah mengalami revisi. Pastikan merujuk pada ketentuan terbaru.
                </div>
            @endif

            @if ($fatwa->keterangan)
                <section class="card p-6 sm:p-8">
                    <h2 class="flex items-center gap-2 text-sm font-bold tracking-wider text-gold-600 uppercase"><x-icon name="sparkles" class="size-4" /> Ringkasan</h2>
                    <p class="mt-3 font-display text-lg leading-relaxed text-stone-700">{{ $fatwa->keterangan }}</p>
                </section>
            @endif

            @if ($fatwa->file_url)
                <x-pdf-viewer :url="$fatwa->file_url" :title="$fatwa->judul" :download-name="Str::slug(Str::limit($fatwa->judul, 80, '')).'.pdf'" />
            @else
                <x-empty-state icon="file-x" title="Berkas PDF belum tersedia" message="Dokumen lengkap fatwa ini belum diunggah oleh pengelola." />
            @endif
        </div>

        <aside class="space-y-6 lg:col-span-4">
            <div class="card overflow-hidden">
                <div class="bg-gradient-brand relative p-6 text-white">
                    <div class="pattern-islamic absolute inset-0"></div>
                    <p class="relative text-xs font-bold tracking-[.2em] text-gold-300 uppercase">Informasi Dokumen</p>
                </div>
                <dl class="divide-y divide-stone-100 text-sm">
                    @foreach ([
                        'Jenis' => 'Fatwa',
                        'Kategori' => $fatwa->kategori?->nama ?? '—',
                        'Status' => \App\Models\Fatwa::STATUSES[$status] ?? ucfirst($status),
                        'Dipublikasikan' => $fatwa->created_at?->translatedFormat('d F Y') ?? '—',
                        'Diperbarui' => $fatwa->updated_at?->translatedFormat('d F Y') ?? '—',
                        'Dilihat' => number_format($fatwa->views ?? 0, 0, ',', '.').' kali',
                    ] as $label => $value)
                        <div class="flex justify-between gap-4 px-6 py-3.5">
                            <dt class="text-stone-500">{{ $label }}</dt>
                            <dd class="text-right font-semibold text-ink-900">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
                @if ($fatwa->file_url)
                    <div class="border-t border-stone-100 p-5">
                        <a href="{{ $fatwa->file_url }}" download class="btn btn-primary w-full"><x-icon name="download" class="size-4" /> Unduh PDF</a>
                    </div>
                @endif
            </div>

            <div x-data="share(@js($fatwa->judul), @js(route('fatwa.detail', $fatwa)))" class="card flex items-center gap-2 p-4">
                <span class="mr-auto pl-2 text-sm font-semibold">Bagikan</span>
                <a :href="link('whatsapp')" target="_blank" rel="noopener" class="grid size-9 place-items-center rounded-lg bg-stone-100 text-stone-600 hover:bg-[#25D366] hover:text-white" aria-label="Bagikan ke WhatsApp"><x-icon name="whatsapp" class="size-4" /></a>
                <a :href="link('facebook')" target="_blank" rel="noopener" class="grid size-9 place-items-center rounded-lg bg-stone-100 text-stone-600 hover:bg-[#1877F2] hover:text-white" aria-label="Bagikan ke Facebook"><x-icon name="facebook" class="size-4" /></a>
                <a :href="link('telegram')" target="_blank" rel="noopener" class="grid size-9 place-items-center rounded-lg bg-stone-100 text-stone-600 hover:bg-sky-500 hover:text-white" aria-label="Bagikan ke Telegram"><x-icon name="send" class="size-4" /></a>
                <button type="button" @click="copy()" class="grid size-9 place-items-center rounded-lg bg-stone-100 text-stone-600 hover:bg-brand-700 hover:text-white" :title="copied ? 'Tersalin' : 'Salin tautan'" aria-label="Salin tautan">
                    <x-icon name="link" class="size-4" x-show="!copied" /><x-icon name="check" class="size-4" x-show="copied" x-cloak />
                </button>
            </div>

            @if ($terkait->isNotEmpty())
                <div class="card p-6">
                    <h3 class="font-bold text-ink-900">Fatwa terkait</h3>
                    <div class="mt-4 space-y-3">
                        @foreach ($terkait as $item)
                            <a href="{{ route('fatwa.detail', $item) }}" class="group block rounded-xl border border-stone-100 p-3 hover:border-brand-200 hover:bg-brand-50/40">
                                <p class="text-[11px] font-semibold text-stone-400">{{ $item->created_at?->translatedFormat('d M Y') }} · {{ number_format($item->views ?? 0, 0, ',', '.') }} dilihat</p>
                                <p class="mt-1 line-clamp-2 text-sm font-semibold text-ink-900 group-hover:text-brand-700">{{ $item->judul }}</p>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="bg-gradient-brand relative overflow-hidden rounded-2xl p-6 text-white">
                <div class="pattern-islamic absolute inset-0"></div>
                <div class="relative">
                    <x-icon name="file-pen-line" class="size-8 text-gold-300" />
                    <h3 class="mt-3 font-display text-xl font-semibold text-white">Masih ada yang belum jelas?</h3>
                    <p class="mt-2 text-sm text-white/70">Ajukan pertanyaan seputar fatwa ini kepada ulama MUI Kabupaten Batanghari.</p>
                    <a href="{{ route('tanya-ulama') }}" class="btn btn-gold btn-sm mt-5">Tanya Ulama</a>
                </div>
            </div>
        </aside>
    </div>
</x-layouts.site>
