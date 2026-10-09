<?php

namespace Database\Seeders;

use App\Models\KategoriFatwa;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class KategoriFatwaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kategoriFatwaData = [
            [
                'nama' => 'Ibadah',
                'deskripsi' => 'Fatwa seputar tata cara ibadah mahdhah seperti shalat, puasa, zakat, haji, dan thaharah.',
            ],
            [
                'nama' => 'Ekonomi & Keuangan Syariah',
                'deskripsi' => 'Ketetapan hukum perbankan syariah, asuransi syariah, pasar modal, dan fintech.',
            ],
            [
                'nama' => 'Makanan, Minuman, Obat & Kosmetik',
                'deskripsi' => 'Fatwa kehalalan dan kesucian produk pangan, obat-obatan, vaksin, serta kosmetika.',
            ],
            [
                'nama' => 'Sosial & Kemasyarakatan',
                'deskripsi' => 'Fatwa hubungan antarumat beragama, etika bermedia sosial, toleransi, dan kemanusiaan.',
            ],
            [
                'nama' => 'Sains, Medis & Teknologi',
                'deskripsi' => 'Fatwa penanganan wabah, transplantasi organ, rekayasa genetika, dan kecerdasan buatan.',
            ],
            [
                'nama' => 'Keluarga & Pernikahan',
                'deskripsi' => 'Hukum pernikahan, perceraian, hak asuh anak, kewarisan, dan ketahanan keluarga.',
            ],
            [
                'nama' => 'Muamalah & Bisnis',
                'deskripsi' => 'Hukum transaksi jual beli online, uang elektronik, aset kripto, dan skema pembiayaan.',
            ],
            [
                'nama' => 'Aqidah & Aliran Keagamaan',
                'deskripsi' => 'Fatwa penjagaan kemurnian aqidah Islam, kriteria aliran sesat, dan faham keagamaan.',
            ],
            [
                'nama' => 'Lingkungan Hidup & Kebencanaan',
                'deskripsi' => 'Fatwa pelestarian satwa langka, larangan pembakaran hutan, dan pengelolaan sampah.',
            ],
            [
                'nama' => 'Politik & Kebangsaan',
                'deskripsi' => 'Fatwa panduan hak pilih dalam pemilu, kepemimpinan berintegritas, dan persatuan NKRI.',
            ],
        ];

        foreach ($kategoriFatwaData as $item) {
            KategoriFatwa::updateOrCreate(
                ['nama' => $item['nama']],
                [
                    'slug' => Str::slug($item['nama']),
                    'deskripsi' => $item['deskripsi'],
                    'aktif' => true,
                ]
            );
        }
    }
}
