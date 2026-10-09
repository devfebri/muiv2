<x-layouts.site title="Tanya Ulama" description="Layanan tanya jawab keagamaan resmi bersama para ulama Majelis Ulama Indonesia Kabupaten Batanghari.">
    <x-page-hero title="Tanya Ulama" eyebrow="Layanan Umat" :crumbs="['Layanan' => null, 'Tanya Ulama' => null]"
                 subtitle="Sampaikan pertanyaan seputar akidah, ibadah, muamalah, keluarga, dan persoalan keagamaan lainnya. Pertanyaan Anda akan ditinjau dan dijawab oleh ulama MUI Kabupaten Batanghari." />

    <div class="container-x mt-10 grid gap-10 lg:grid-cols-12">
        <div class="lg:col-span-8">
            @if (session('success'))
                <div class="mb-6 flex items-start gap-4 rounded-2xl border border-brand-200 bg-brand-50 p-5">
                    <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-brand-700 text-gold-300"><x-icon name="circle-check-big" class="size-5" /></span>
                    <div>
                        <p class="font-semibold text-brand-900">Pertanyaan berhasil dikirim</p>
                        <p class="mt-1 text-sm leading-relaxed text-brand-800">{{ session('success') }}</p>
                        <a href="{{ route('konsultasi.list') }}" class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 hover:text-brand-900">Lihat tanya jawab umat <x-icon name="arrow-right" class="size-4" /></a>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('tanya-ulama.store') }}" class="card p-6 sm:p-8" x-data="{ panjang: {{ mb_strlen((string) old('pertanyaan', '')) }} }">
                @csrf
                <div class="flex items-start gap-4 border-b border-stone-100 pb-6">
                    <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-brand-50 text-brand-700 ring-1 ring-brand-100"><x-icon name="file-pen-line" class="size-6" /></span>
                    <div>
                        <h2 class="font-display text-2xl font-semibold text-ink-900">Formulir Tanya Ulama</h2>
                        <p class="mt-1 text-sm text-stone-500">Kolom bertanda <span class="text-red-500">*</span> wajib diisi.</p>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="mt-6 flex items-start gap-3 rounded-xl bg-red-50 p-4 text-sm text-red-700 ring-1 ring-red-100">
                        <x-icon name="circle-alert" class="mt-0.5 size-4" /> Mohon periksa kembali isian yang ditandai.
                    </div>
                @endif

                <div class="mt-6 grid gap-5 sm:grid-cols-2">
                    <x-field label="Nama lengkap" name="nama" required maxlength="150" autocomplete="name" placeholder="Nama Anda" />
                    <x-field label="Alamat email" name="email" type="email" required maxlength="150" autocomplete="email" placeholder="nama@email.com" hint="Tidak dipublikasikan. Untuk pemberitahuan jawaban." />
                    <div class="grid grid-cols-2 gap-4">
                        <x-field label="Usia" name="usia" type="number" required min="5" max="120" inputmode="numeric" placeholder="Tahun" />
                        <x-field label="Jenis kelamin" name="jenis_kelamin" type="select" required :options="['' => 'Pilih…', 'Laki-laki' => 'Laki-laki', 'Perempuan' => 'Perempuan']" />
                    </div>
                    <x-field label="Kabupaten / Kota" name="kab_kota" required maxlength="150" placeholder="Mis. Batanghari, Jambi" />
                    <x-field class="sm:col-span-2" label="Kategori pertanyaan" name="kategori" type="select" required
                             :options="['' => 'Pilih kategori…'] + array_combine($kategoriList, $kategoriList)" />
                    <div class="sm:col-span-2">
                        <x-field label="Pertanyaan" name="pertanyaan" type="textarea" rows="7" required minlength="10"
                                 placeholder="Tuliskan pertanyaan Anda dengan jelas dan lengkap…" @input="panjang = $event.target.value.length" />
                        <p class="mt-1.5 text-right text-xs text-stone-400"><span x-text="panjang">0</span> karakter · minimal 10</p>
                    </div>
                </div>

                <div class="mt-6 flex items-start gap-3 rounded-xl bg-sand-100 p-4 text-xs leading-relaxed text-stone-600">
                    <x-icon name="shield-check" class="mt-0.5 size-4 text-brand-600" />
                    <p>Pertanyaan yang telah dijawab dapat dipublikasikan sebagai pembelajaran bagi umat. Nama Anda akan <b>disamarkan</b> (mis. “Ahmad R.”) dan alamat email tidak akan ditampilkan.</p>
                </div>

                <div class="mt-6 flex flex-wrap items-center justify-between gap-4">
                    <p class="text-xs text-stone-500">Butuh jawaban lebih cepat? <button type="button" @click="$dispatch('open-chat')" class="font-semibold text-brand-700 hover:underline">Gunakan konsultasi online</button></p>
                    <button type="submit" class="btn btn-primary btn-lg"><x-icon name="send" class="size-4" /> Kirim Pertanyaan</button>
                </div>
            </form>
        </div>

        <aside class="space-y-6 lg:col-span-4">
            <div class="card p-6">
                <h2 class="flex items-center gap-2 font-bold text-ink-900"><x-icon name="route" class="size-4 text-gold-500" /> Alur layanan</h2>
                <ol class="mt-5 space-y-5">
                    @foreach ([
                        ['Kirim pertanyaan', 'Isi formulir dengan data dan pertanyaan yang jelas.'],
                        ['Ditinjau ulama', 'Tim Komisi Fatwa menelaah pertanyaan sesuai dalil dan fatwa MUI.'],
                        ['Dijawab & dipublikasikan', 'Jawaban ditampilkan di halaman Tanya Jawab Umat.'],
                    ] as [$judul, $keterangan])
                        <li class="flex gap-4">
                            <span class="grid size-8 shrink-0 place-items-center rounded-full bg-brand-700 text-xs font-bold text-gold-300">{{ $loop->iteration }}</span>
                            <div>
                                <p class="text-sm font-semibold text-ink-900">{{ $judul }}</p>
                                <p class="mt-0.5 text-xs leading-relaxed text-stone-500">{{ $keterangan }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>

            @if ($konsultasiTerjawab->isNotEmpty())
                <div class="card overflow-hidden">
                    <div class="flex items-center gap-3 border-b border-stone-100 bg-sand-100/60 px-6 py-4">
                        <span class="grid size-9 place-items-center rounded-xl bg-gold-400 text-brand-950"><x-icon name="messages-square" class="size-4" /></span>
                        <h2 class="font-bold text-ink-900">Baru dijawab</h2>
                    </div>
                    <ul class="divide-y divide-stone-100">
                        @foreach ($konsultasiTerjawab->take(5) as $item)
                            <li>
                                <a href="{{ route('konsultasi.detail', $item) }}" class="group block px-6 py-4 hover:bg-brand-50/40">
                                    <p class="text-[11px] font-bold tracking-wider text-gold-600 uppercase">{{ $item->kategori }}</p>
                                    <p class="mt-1 line-clamp-2 text-sm font-semibold text-ink-900 group-hover:text-brand-700">{{ $item->pertanyaan }}</p>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('konsultasi.list', ['status' => 'dijawab']) }}" class="block border-t border-stone-100 px-6 py-3.5 text-center text-sm font-semibold text-brand-700 hover:bg-brand-50">Lihat semua jawaban</a>
                </div>
            @endif

            <div class="bg-gradient-brand relative overflow-hidden rounded-2xl p-6 text-white">
                <div class="pattern-islamic absolute inset-0"></div>
                <div class="relative">
                    <p class="arabic text-right text-2xl text-gold-300">فَاسْأَلُوا أَهْلَ الذِّكْرِ إِنْ كُنْتُمْ لَا تَعْلَمُونَ</p>
                    <p class="mt-3 font-display text-lg text-white italic">“Maka bertanyalah kepada orang yang mempunyai pengetahuan jika kamu tidak mengetahui.”</p>
                    <p class="mt-1 text-xs tracking-wider text-white/50 uppercase">QS. An-Nahl [16]: 43</p>
                </div>
            </div>
        </aside>
    </div>
</x-layouts.site>
