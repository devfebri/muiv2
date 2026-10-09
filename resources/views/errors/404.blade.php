@extends('errors.layout')
@section('code', '404')
@section('title', 'Halaman Tidak Ditemukan')
@section('message', 'Halaman yang Anda cari mungkin telah dipindahkan, dihapus, atau alamatnya keliru. Coba cari konten yang Anda butuhkan:')
@section('extra')
    <form action="{{ route('search') }}" class="relative mx-auto mt-6 max-w-md" role="search">
        <input name="q" type="search" minlength="2" required placeholder="Cari berita, fatwa, surat…" aria-label="Kata kunci pencarian" class="w-full rounded-2xl border border-white/15 bg-white/10 py-3.5 pr-24 pl-5 text-white backdrop-blur placeholder:text-white/50 focus:border-gold-400 focus:outline-none">
        <button class="btn btn-gold btn-sm absolute top-1/2 right-2 -translate-y-1/2">Cari</button>
    </form>
@endsection
