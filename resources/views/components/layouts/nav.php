<?php

/**
 * Struktur menu navigasi situs publik.
 *
 * Item anak dengan 'chat' => true membuka widget Konsultasi Online.
 *
 * @return array<int, array<string, mixed>>
 */
return [
    ['label' => 'Beranda', 'url' => route('home.public'), 'active' => 'home.public'],
    ['label' => 'Profil', 'active' => ['profilemui', 'visi-misi', 'struktur-organisasi'], 'children' => [
        ['label' => 'Profil MUI', 'desc' => 'Sejarah, peran & khidmah majelis', 'icon' => 'landmark', 'url' => route('profilemui')],
        ['label' => 'Visi & Misi', 'desc' => 'Arah gerak & cita-cita organisasi', 'icon' => 'compass', 'url' => route('visi-misi')],
        ['label' => 'Struktur Organisasi', 'desc' => 'Susunan pengurus & komisi', 'icon' => 'users', 'url' => route('struktur-organisasi')],
    ]],
    ['label' => 'Kabar', 'active' => 'berita.*', 'children' => [
        ['label' => 'Semua Berita', 'desc' => 'Kegiatan & informasi terbaru', 'icon' => 'newspaper', 'url' => route('berita.list')],
        ['label' => 'Khutbah & Bimbingan', 'desc' => 'Naskah khutbah & tuntunan umat', 'icon' => 'book-open', 'url' => route('berita.list', ['kategori' => 'Khutbah'])],
        ['label' => 'Halal', 'desc' => 'Informasi produk & sertifikasi halal', 'icon' => 'badge-check', 'url' => route('berita.list', ['kategori' => 'Halal'])],
        ['label' => 'Kabar Daerah', 'desc' => 'Kiprah MUI di tingkat daerah', 'icon' => 'map-pin', 'url' => route('berita.list', ['kategori' => 'Kabar Daerah'])],
    ]],
    ['label' => 'Fatwa & Arsip', 'active' => ['fatwa', 'surat'], 'children' => [
        ['label' => 'Fatwa MUI', 'desc' => 'Keputusan hukum Komisi Fatwa', 'icon' => 'scale', 'url' => route('fatwa')],
        ['label' => 'Arsip Surat', 'desc' => 'Surat resmi & keputusan majelis', 'icon' => 'file-badge', 'url' => route('surat')],
    ]],
    ['label' => 'Layanan', 'active' => ['tanya-ulama', 'konsultasi.*'], 'children' => [
        ['label' => 'Tanya Ulama', 'desc' => 'Ajukan pertanyaan keagamaan', 'icon' => 'file-pen-line', 'url' => route('tanya-ulama')],
        ['label' => 'Tanya Jawab Umat', 'desc' => 'Jawaban ulama atas pertanyaan umat', 'icon' => 'messages-square', 'url' => route('konsultasi.list')],
        ['label' => 'Konsultasi Online', 'desc' => 'Live chat dengan petugas MUI', 'icon' => 'headset', 'url' => '#', 'chat' => true],
    ]],
    ['label' => 'Kontak', 'url' => route('kontak'), 'active' => 'kontak'],
];
