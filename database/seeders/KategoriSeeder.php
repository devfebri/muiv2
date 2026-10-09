<?php

namespace Database\Seeders;

use App\Models\Kategori;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class KategoriSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kategoriData = [
            [
                'nama' => 'Berita Utama',
                'warna' => '#007f5f',
                'deskripsi' => 'Berita dan informasi penting terkini seputar kegiatan dan kebijakan MUI.',
            ],
            [
                'nama' => 'Fatwa',
                'warna' => '#2b9348',
                'deskripsi' => 'Ketetapan hukum syariat dan sosialisasi fatwa MUI untuk umat.',
            ],
            [
                'nama' => 'Halal',
                'warna' => '#10b981',
                'deskripsi' => 'Informasi sertifikasi halal, panduan produk halal, dan berita LPPOM MUI.',
            ],
            [
                'nama' => 'Bimbingan',
                'warna' => '#059669',
                'deskripsi' => 'Bimbingan ibadah, akhlak, dan tuntunan hidup sehari-hari bagi muslim.',
            ],
            [
                'nama' => 'Khutbah',
                'warna' => '#047857',
                'deskripsi' => 'Naskah khutbah Jumat, Idul Fitri, dan Idul Adha terstandar syariat.',
            ],
            [
                'nama' => 'Opini',
                'warna' => '#0d9488',
                'deskripsi' => 'Artikel gagasan dan sudut pandang para ulama serta cendekiawan muslim.',
            ],
            [
                'nama' => 'Nasional',
                'warna' => '#2563eb',
                'deskripsi' => 'Peristiwa dan dinamika keumatan berskala nasional di Indonesia.',
            ],
            [
                'nama' => 'Internasional',
                'warna' => '#4f46e5',
                'deskripsi' => 'Kabar dunia Islam internasional dan hubungan persaudaraan global.',
            ],
            [
                'nama' => 'Ekonomi',
                'warna' => '#c9a84c',
                'deskripsi' => 'Perkembangan ekonomi syariah, perbankan, pasar modal, dan bisnis halal.',
            ],
            [
                'nama' => 'Teknologi',
                'warna' => '#0891b2',
                'deskripsi' => 'Pemanfaatan sains, digitalisasi, dan teknologi dalam kemaslahatan dakwah.',
            ],
            [
                'nama' => 'Sosial',
                'warna' => '#d97706',
                'deskripsi' => 'Kegiatan kepedulian sosial, kemanusiaan, penanggulangan bencana, dan pemberdayaan.',
            ],
            [
                'nama' => 'Kabar Daerah',
                'warna' => '#8b5cf6',
                'deskripsi' => 'Aktivitas pengurus MUI tingkat provinsi, kabupaten, dan kota se-Indonesia.',
            ],
        ];

        foreach ($kategoriData as $index => $item) {
            Kategori::updateOrCreate(
                ['nama' => $item['nama']],
                [
                    'slug' => Str::slug($item['nama']),
                    'warna' => $item['warna'],
                    'deskripsi' => $item['deskripsi'],
                    'aktif' => true,
                    'urutan' => $index + 1,
                ]
            );
        }
    }
}
