<x-layouts.admin title="Ubah Berita" :header="Str::limit($berita->judul, 90)">
    @include('berita.partials.editor', ['berita' => $berita])
</x-layouts.admin>
