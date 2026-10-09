@extends('errors.layout')
@section('code', '403')
@section('title', 'Akses Ditolak')
@section('message', 'Anda tidak memiliki izin untuk membuka halaman ini. Jika Anda pengurus MUI, silakan masuk dengan akun yang sesuai.')
@section('extra')
    @guest
        <a href="{{ route('login') }}" class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-gold-300 hover:text-gold-200">Masuk ke Portal Pengurus →</a>
    @endguest
@endsection
