<?php

namespace Database\Seeders;

use App\Models\Surat;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class SuratSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first() ?? User::first();
        $adminId = $admin ? $admin->id : 1;

        $suratUploadDir = public_path('uploads/surat');
        if (! File::exists($suratUploadDir)) {
            File::makeDirectory($suratUploadDir, 0755, true);
        }

        // Cari file PDF yang sudah ada di storage/surat lalu salin jika belum ada di uploads
        $storageSuratDir = storage_path('app/public/surat');
        if (File::exists($storageSuratDir)) {
            foreach (File::glob($storageSuratDir.'/*.pdf') as $oldPdf) {
                $dest = $suratUploadDir.'/'.basename($oldPdf);
                if (! File::exists($dest)) {
                    File::copy($oldPdf, $dest);
                }
            }
        }

        // Cari file PDF di direktori surat atau fallback ke fatwa
        $existingPdfs = File::glob($suratUploadDir.'/*.pdf');
        if (empty($existingPdfs)) {
            $fatwaPdfs = File::glob(public_path('uploads/fatwa/*.pdf'));
            if (empty($fatwaPdfs)) {
                $fatwaPdfs = File::glob(storage_path('app/public/fatwa/*.pdf'));
            }
            if (! empty($fatwaPdfs)) {
                $targetPdf = $suratUploadDir.'/sample-dokumen-surat.pdf';
                File::copy($fatwaPdfs[0], $targetPdf);
                $existingPdfs = [$targetPdf];
            }
        }

        $samplePdfs = [];
        if (! empty($existingPdfs)) {
            foreach ($existingPdfs as $pdf) {
                $samplePdfs[] = basename($pdf);
            }
        } else {
            $samplePdfPath = $suratUploadDir.'/sample-dokumen-surat.pdf';
            $minimalPdf = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Count 1/Kids[3 0 R]>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<<>>>>endobj\nxref\n0 4\n0000000000 65535 f \n0000000009 00000 n \n0000000052 00000 n \n0000000101 00000 n \ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n178\n%%EOF";
            File::put($samplePdfPath, $minimalPdf);
            $samplePdfs[] = 'sample-dokumen-surat.pdf';
        }

        $suratList = [
            [
                'nomor_surat' => '012/DP-MUI/IX/2026',
                'perihal' => 'Undangan Rapat Koordinasi Nasional Komisi Fatwa se-Indonesia Tahun 2026',
                'tanggal_surat' => '2026-09-10',
            ],
            [
                'nomor_surat' => '015/DP-MUI/IX/2026',
                'perihal' => 'Surat Edaran Panduan Pelaksanaan Ibadah Shalat Gerhana Bulan dan Khutbah Khusuf',
                'tanggal_surat' => '2026-09-15',
            ],
            [
                'nomor_surat' => '018/DP-MUI/IX/2026',
                'perihal' => 'Himbauan Penggalangan Dana Peduli Kemanusiaan Korban Bencana Alam Nasional',
                'tanggal_surat' => '2026-09-18',
            ],
            [
                'nomor_surat' => '021/DP-MUI/IX/2026',
                'perihal' => 'Rekomendasi Pembentukan Satuan Tugas Halal Tingkat Kecamatan dan Pendampingan UMKM',
                'tanggal_surat' => '2026-09-21',
            ],
            [
                'nomor_surat' => '025/DP-MUI/IX/2026',
                'perihal' => 'Surat Tugas Delegasi Narasumber Halaqah Ulama dan Cendekiawan Muslim Asia',
                'tanggal_surat' => '2026-09-25',
            ],
            [
                'nomor_surat' => '029/DP-MUI/IX/2026',
                'perihal' => 'Pemberitahuan Audit Kepatuhan Syariah Lembaga Keuangan Mikro Syariah',
                'tanggal_surat' => '2026-09-28',
            ],
            [
                'nomor_surat' => '032/DP-MUI/IX/2026',
                'perihal' => 'Instruksi Pelaksanaan Khutbah Jumat Serentak Bertema Menjaga Kerukunan Bangsa',
                'tanggal_surat' => '2026-09-30',
            ],
            [
                'nomor_surat' => '003/DP-MUI/VIII/2026',
                'perihal' => 'Permohonan Kerjasama Sosialisasi Sertifikasi Halal bersama Kementerian Agama RI',
                'tanggal_surat' => '2026-08-14',
            ],
            [
                'nomor_surat' => '008/DP-MUI/VIII/2026',
                'perihal' => 'Surat Keputusan Penetapan Susunan Pengurus Komisi Ukhuwah Islamiyah Periode 2026-2030',
                'tanggal_surat' => '2026-08-22',
            ],
            [
                'nomor_surat' => '011/DP-MUI/VIII/2026',
                'perihal' => 'Surat Keterangan Terdaftar Ormas Islam Mitra Strategis Majelis Ulama Indonesia',
                'tanggal_surat' => '2026-08-28',
            ],
            [
                'nomor_surat' => '001/DP-MUI/VII/2026',
                'perihal' => 'Penyampaian Risalah Hasil Musyawarah Nasional Komisi Pemberdayaan Perempuan & Remaja',
                'tanggal_surat' => '2026-07-20',
            ],
        ];

        foreach ($suratList as $index => $item) {
            $pdfIndex = $index % count($samplePdfs);
            $fileSurat = $samplePdfs[$pdfIndex];

            Surat::updateOrCreate(
                ['nomor_surat' => $item['nomor_surat']],
                [
                    'user_id' => $adminId,
                    'perihal' => $item['perihal'],
                    'tanggal_surat' => $item['tanggal_surat'],
                    'file_surat' => $fileSurat,
                ]
            );
        }
    }
}
