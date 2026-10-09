<?php

namespace Database\Seeders;

use App\Models\Fatwa;
use App\Models\KategoriFatwa;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class FatwaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fatwaUploadDir = public_path('uploads/fatwa');
        if (! File::exists($fatwaUploadDir)) {
            File::makeDirectory($fatwaUploadDir, 0755, true);
        }

        // Cari file PDF yang sudah ada di storage/fatwa lalu salin jika belum ada di uploads
        $storageFatwaDir = storage_path('app/public/fatwa');
        if (File::exists($storageFatwaDir)) {
            foreach (File::glob($storageFatwaDir.'/*.pdf') as $oldPdf) {
                $dest = $fatwaUploadDir.'/'.basename($oldPdf);
                if (! File::exists($dest)) {
                    File::copy($oldPdf, $dest);
                }
            }
        }

        // Cari file PDF yang sudah ada di direktori fatwa
        $existingPdfs = File::glob($fatwaUploadDir.'/*.pdf');
        $samplePdfs = [];

        if (! empty($existingPdfs)) {
            foreach ($existingPdfs as $pdf) {
                $samplePdfs[] = basename($pdf);
            }
        } else {
            // Buat file PDF dummy valid minimal jika belum ada sama sekali
            $samplePdfPath = $fatwaUploadDir.'/sample-fatwa-dokumen.pdf';
            $minimalPdf = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Count 1/Kids[3 0 R]>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<<>>>>endobj\nxref\n0 4\n0000000000 65535 f \n0000000009 00000 n \n0000000052 00000 n \n0000000101 00000 n \ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n178\n%%EOF";
            File::put($samplePdfPath, $minimalPdf);
            $samplePdfs[] = 'sample-fatwa-dokumen.pdf';
        }

        // Ambil mapping kategori fatwa
        $kategoriMap = KategoriFatwa::pluck('id', 'nama')->toArray();
        $fallbackKategoriId = KategoriFatwa::first()?->id ?? 1;

        $fatwaList = [
            [
                'judul' => 'Fatwa MUI No. 83 Tahun 2023 tentang Hukum Dukungan terhadap Perjuangan Palestina',
                'kategori_nama' => 'Politik & Kebangsaan',
                'keterangan' => 'Ketetapan syariat tentang kewajiban mendukung kemerdekaan Palestina dan haramnya mendukung agresi zionis Israel baik langsung maupun tidak langsung.',
                'status_fatwa' => Fatwa::STATUS_AKTIF,
                'views' => 1850,
            ],
            [
                'judul' => 'Fatwa MUI No. 24 Tahun 2017 tentang Hukum dan Pedoman Bermuamalah Melalui Media Sosial',
                'kategori_nama' => 'Sosial & Kemasyarakatan',
                'keterangan' => 'Pedoman moral dan hukum syariah dalam memproduksi, menyebarkan, dan merespons informasi di media sosial, termasuk larangan ghibah, fitnah, dan hoaks.',
                'status_fatwa' => Fatwa::STATUS_AKTIF,
                'views' => 1420,
            ],
            [
                'judul' => 'Fatwa MUI No. 13 Tahun 2021 tentang Hukum Vaksinasi COVID-19 Saat Berpuasa',
                'kategori_nama' => 'Ibadah',
                'keterangan' => 'Pemberian vaksin melalui suntikan intramuskular (otot) tidak membatalkan ibadah puasa, sepanjang tidak menyebabkan bahaya medis bagi penerima vaksin.',
                'status_fatwa' => Fatwa::STATUS_AKTIF,
                'views' => 1100,
            ],
            [
                'judul' => 'Fatwa DSN-MUI No. 116/DSN-MUI/IX/2017 tentang Uang Elektronik Syariah',
                'kategori_nama' => 'Ekonomi & Keuangan Syariah',
                'keterangan' => 'Prinsip, akad (wadi\'ah / qardh), dan batasan syariah dalam penerbitan serta penggunaan uang elektronik (e-money / e-wallet) syariah di Indonesia.',
                'status_fatwa' => Fatwa::STATUS_AKTIF,
                'views' => 970,
            ],
            [
                'judul' => 'Fatwa MUI No. 14 Tahun 2020 tentang Penyelenggaraan Ibadah dalam Situasi Terjadinya Wabah COVID-19',
                'kategori_nama' => 'Sains, Medis & Teknologi',
                'keterangan' => 'Panduan pelaksanaan shalat berjamaah, jumatan, dan shalat jenazah pada masa darurat pandemi untuk pencegahan penularan virus.',
                'status_fatwa' => Fatwa::STATUS_DIGANTIKAN,
                'views' => 3120,
            ],
            [
                'judul' => 'Fatwa MUI No. 33 Tahun 2018 tentang Penggunaan Vaksin MR (Measles Rubella) untuk Imunisasi',
                'kategori_nama' => 'Makanan, Minuman, Obat & Kosmetik',
                'keterangan' => 'Kebolehan penggunaan vaksin MR pada kondisi darurat (dharurat) dan hajat syar\'iyyah karena belum ditemukannya vaksin alternatif yang suci dan halal.',
                'status_fatwa' => Fatwa::STATUS_DIREVISI,
                'views' => 1650,
            ],
            [
                'judul' => 'Fatwa MUI No. 2 Tahun 2021 tentang Produk Vaksin COVID-19 dari Sinovac Biotech Ltd.',
                'kategori_nama' => 'Makanan, Minuman, Obat & Kosmetik',
                'keterangan' => 'Ketetapan hukum bahwa vaksin COVID-19 produksi Sinovac Biotech berstatus suci dan halal untuk digunakan masyarakat muslim Indonesia.',
                'status_fatwa' => Fatwa::STATUS_AKTIF,
                'views' => 2050,
            ],
            [
                'judul' => 'Fatwa MUI No. 22 Tahun 2014 tentang Pengelolaan Sampah untuk Mencegah Kerusakan Lingkungan',
                'kategori_nama' => 'Lingkungan Hidup & Kebencanaan',
                'keterangan' => 'Hukum wajib menjaga kebersihan dan kelestarian lingkungan serta haram membuang sampah sembarangan yang mengakibatkan mudharat dan pencemaran.',
                'status_fatwa' => Fatwa::STATUS_AKTIF,
                'views' => 620,
            ],
            [
                'judul' => 'Fatwa MUI No. 4 Tahun 2014 tentang Pelestarian Satwa Langka untuk Menjaga Keseimbangan Ekosistem',
                'kategori_nama' => 'Lingkungan Hidup & Kebencanaan',
                'keterangan' => 'Larangan perburuan liar, perdagangan ilegal, dan pemusnahan habitat satwa yang dilindungi dalam pandangan syariat Islam.',
                'status_fatwa' => Fatwa::STATUS_AKTIF,
                'views' => 740,
            ],
            [
                'judul' => 'Fatwa MUI No. 30 Tahun 2016 tentang Hukum Pembakaran Hutan dan Lahan serta Pengendaliannya',
                'kategori_nama' => 'Lingkungan Hidup & Kebencanaan',
                'keterangan' => 'Haram hukumnya melakukan pembakaran hutan dan lahan yang menimbulkan kerusakan lingkungan, kerugian ekonomi, dan gangguan kesehatan masyarakat.',
                'status_fatwa' => Fatwa::STATUS_AKTIF,
                'views' => 880,
            ],
            [
                'judul' => 'Fatwa DSN-MUI No. 112/DSN-MUI/IX/2017 tentang Akad Ijarah Mawsufah fi al-Dhimmah',
                'kategori_nama' => 'Muamalah & Bisnis',
                'keterangan' => 'Ketentuan dan batasan akad sewa-menyewa atas manfaat barang atau jasa yang dispesifikasikan dalam tanggungan pada industri keuangan syariah.',
                'status_fatwa' => Fatwa::STATUS_AKTIF,
                'views' => 540,
            ],
            [
                'judul' => 'Fatwa MUI No. 28 Tahun 2020 tentang Panduan Kaifiat Takbir dan Shalat Idul Fitri Saat Pandemi',
                'kategori_nama' => 'Ibadah',
                'keterangan' => 'Panduan tata cara takbiran dan pelaksanaan shalat Idul Fitri di rumah saat situasi pembatasan sosial berskala besar.',
                'status_fatwa' => Fatwa::STATUS_DIGANTIKAN,
                'views' => 1280,
            ],
        ];

        foreach ($fatwaList as $index => $item) {
            $pdfIndex = $index % count($samplePdfs);
            $filePdf = $samplePdfs[$pdfIndex];
            $kategoriId = $kategoriMap[$item['kategori_nama']] ?? $fallbackKategoriId;

            Fatwa::updateOrCreate(
                ['judul' => $item['judul']],
                [
                    'kategori_fatwa_id' => $kategoriId,
                    'keterangan' => $item['keterangan'],
                    'filepdf' => $filePdf,
                    'publikasi' => true,
                    'status_fatwa' => $item['status_fatwa'],
                    'views' => $item['views'],
                ]
            );
        }
    }
}
