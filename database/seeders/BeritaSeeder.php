<?php

namespace Database\Seeders;

use App\Models\Berita;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class BeritaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first() ?? User::first();
        $adminId = $admin ? $admin->id : 1;

        // Siapkan direktori upload berita
        $beritaUploadDir = public_path('uploads/berita');
        if (! File::exists($beritaUploadDir)) {
            File::makeDirectory($beritaUploadDir, 0755, true);
        }

        // Kumpulan file template gambar yang tersedia
        $templateImages = [
            public_path('template/assets/img/news/news_card.jpg'),
            public_path('template/assets/img/news/recent1.jpg'),
            public_path('template/assets/img/news/recent2.jpg'),
            public_path('template/assets/img/news/recent3.jpg'),
            public_path('template/assets/img/news/weeklyNews1.jpg'),
            public_path('template/assets/img/news/weeklyNews2.jpg'),
            public_path('template/assets/img/news/weeklyNews3.jpg'),
            public_path('template/assets/img/news/whatNews1.jpg'),
            public_path('template/assets/img/news/whatNews2.jpg'),
            public_path('template/assets/img/news/whatNews3.jpg'),
            public_path('template/assets/img/trending/trending_top.jpg'),
            public_path('template/assets/img/trending/trending_bottom1.jpg'),
        ];

        // Cari gambar yang sudah ada di uploads atau salin dari template
        $sampleImages = [];
        foreach ($templateImages as $i => $sourcePath) {
            $targetFilename = 'sample_berita_'.($i + 1).'.jpg';
            $targetPath = $beritaUploadDir.'/'.$targetFilename;

            if (! File::exists($targetPath) && File::exists($sourcePath)) {
                File::copy($sourcePath, $targetPath);
            }

            if (File::exists($targetPath)) {
                $sampleImages[] = $targetFilename;
            }
        }

        // Fallback jika tidak ada template
        if (empty($sampleImages)) {
            $existing = File::glob($beritaUploadDir.'/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
            if (! empty($existing)) {
                $sampleImages = array_map(fn ($p) => basename($p), $existing);
            } else {
                // Buat dummy 1x1 pixel image fallback
                $fallbackFile = $beritaUploadDir.'/sample_default.jpg';
                File::put($fallbackFile, base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA='));
                $sampleImages[] = 'sample_default.jpg';
            }
        }

        $beritaList = [
            [
                'judul' => 'MUI Tegaskan Pentingnya Sertifikasi Halal bagi Seluruh Pelaku UMKM Kuliner',
                'kategori' => 'Halal',
                'views' => 450,
                'published_at' => now()->subDays(1),
                'isi' => '<p>Majelis Ulama Indonesia (MUI) kembali mengingatkan pentingnya percepatan sertifikasi halal bagi seluruh pelaku usaha mikro, kecil, dan menengah (UMKM) sektor makanan dan minuman di tanah air.</p><p>Ketua Bidang Fatwa MUI menyampaikan bahwa sertifikasi halal bukan sekadar kewajiban administratif, melainkan wujud perlindungan nyata bagi hak-hak konsumen muslim serta meningkatkan daya saing produk lokal di kancah perdagangan internasional.</p><blockquote>"Konsumen berhak mendapatkan kepastian bahwa makanan yang dikonsumsi halal dan thayyib. Halal adalah standar mutu dan keberkahan," tegasnya.</blockquote><p>MUI bersama BPJPH terus memberikan asistensi dan pendampingan self-declare bagi para pengusaha mikro agar proses sertifikasi berjalan cepat, mudah, dan transparan.</p>',
            ],
            [
                'judul' => 'Ketua Umum MUI Buka Halaqah Da\'wah Nasional 2026: Perkuat Moderasi dan Persatuan',
                'kategori' => 'Berita Utama',
                'views' => 620,
                'published_at' => now()->subDays(2),
                'isi' => '<p>Ketua Umum Majelis Ulama Indonesia secara resmi membuka acara <strong>Halaqah Da\'wah Nasional 2026</strong> yang dihadiri oleh pimpinan ormas Islam, akademisi, dan dai dari 38 provinsi di seluruh Nusantara.</p><p>Dalam pidato pembukaannya, beliau menekankan pentingnya peran strategis para ulama dalam menjaga ukhuwah Islamiyah, ukhuwah wathaniyah, dan ukhuwah insaniyah di tengah arus perubahan zaman dan era kecerdasan digital.</p><p>Halaqah ini menghasilkan resolusi dakwah ramah lingkungan, etika digital kebangsaan, dan program pemberdayaan ekonomi umat berbasis masjid.</p>',
            ],
            [
                'judul' => 'Komisi Fatwa MUI Susun Panduan Etika Pemanfaatan Artificial Intelligence (AI)',
                'kategori' => 'Fatwa',
                'views' => 915,
                'published_at' => now()->subDays(3),
                'isi' => '<p>Perkembangan teknologi kecerdasan buatan (Artificial Intelligence) yang begitu pesat memicu respons konstruktif dari Komisi Fatwa MUI. Sidang pleno komisi fatwa mulai membahas panduan etika syariat pemanfaatan AI.</p><p>Pokok bahasan mencakup batasan pembuatan konten berbasis AI, perlindungan hak cipta digital, pencegahan konten manipulatif (deepfake), serta pemanfaatan otomasi dalam membantu pekerjaan manusia tanpa melanggar prinsip keadilan dan kemaslahatan.</p><p>Diharapkan panduan ini menjadi kompas moral bagi para ilmuwan, pengembang teknologi, dan masyarakat muslim di era digital.</p>',
            ],
            [
                'judul' => 'MUI Salurkan Bantuan Kemanusiaan dan Bangun Fasilitas Air Bersih di Wilayah Terdampak',
                'kategori' => 'Sosial',
                'views' => 310,
                'published_at' => now()->subDays(4),
                'isi' => '<p>Lembaga Pelayanan Masyarakat MUI bersama perwakilan pimpinan daerah menyalurkan bantuan logistik kemanusiaan serta meresmikan program sumur bor air bersih di kawasan yang dilanda bencana kekeringan.</p><p>Bantuan ini mencakup paket sembako, obat-obatan, serta pendampingan psikososial bagi ratusan kepala keluarga. Program ini didanai melalui donasi umat yang dikelola secara amanah dan akuntabel.</p>',
            ],
            [
                'judul' => 'Kiat Memilih Produk Halal dan Thayyib di Era Perdagangan Digital',
                'kategori' => 'Bimbingan',
                'views' => 480,
                'published_at' => now()->subDays(5),
                'isi' => '<p>Di era belanja daring dan e-commerce lintas negara, masyarakat diimbau lebih teliti dalam memeriksa komposisi dan label sertifikat halal pada kemasan produk makanan, minuman, dan kosmetik.</p><p>Prinsip utama yang diajarkan Islam adalah <em>Halalan Thayyiban</em>: halal secara zat dan prosesnya, serta thayyib (baik, sehat, bergizi, dan aman bagi tubuh). Konsumen dianjurkan memanfaatkan aplikasi resmi cek halal untuk memverifikasi nomor registrasi produk sebelum membeli.</p>',
            ],
            [
                'judul' => 'Naskah Khutbah Jumat: Merawat Persaudaraan dan Menjaga Lisan di Ruang Publik',
                'kategori' => 'Khutbah',
                'views' => 740,
                'published_at' => now()->subDays(6),
                'isi' => '<p>Naskah khutbah edisi pekan ini mengangkat tema <strong>"Menjaga Lisan dan Jari-Jemari di Tengah Arus Informasi"</strong>. Khutbah mengajak seluruh jamaah untuk senantiasa bertabayyun sebelum menyebarkan kabar berita.</p><p>Rasulullah SAW bersabda bahwa seorang muslim yang sejati adalah sosok yang orang lain selamat dari gangguan lisan dan tangannya. Khutbah ini dapat diunduh dan digunakan oleh para khatib di seluruh Indonesia.</p>',
            ],
            [
                'judul' => 'Perbankan Syariah Catatkan Pertumbuhan Aset Signifikan Sepanjang Tahun Ini',
                'kategori' => 'Ekonomi',
                'views' => 280,
                'published_at' => now()->subDays(7),
                'isi' => '<p>Dewan Syariah Nasional Majelis Ulama Indonesia (DSN-MUI) menyambut baik laporan pertumbuhan industri perbankan syariah yang terus mencatatkan kinerja positif dan ekspansi pembiayaan produktif.</p><p>Peningkatan literasi keuangan syariah di kalangan generasi muda dinilai menjadi pendorong utama lonjakan pengguna produk tabungan, investasi sukuk, dan pembiayaan perumahan berbasis akad syariah.</p>',
            ],
            [
                'judul' => 'MUI dan Kemenkes Selenggarakan Sosialisasi Hidup Sehat Berbasis Nilai Islam',
                'kategori' => 'Nasional',
                'views' => 395,
                'published_at' => now()->subDays(8),
                'isi' => '<p>Kolaborasi antara Majelis Ulama Indonesia dan Kementerian Kesehatan terus diperkuat melalui sosialisasi gerakan masyarakat hidup sehat (GERMAS) yang dipadukan dengan nilai-nilai thaharah dan kebersihan dalam Islam.</p><p>Kegiatan ini mencakup edukasi gizi seimbang balita untuk pencegahan stunting, imunisasi halal yang aman, dan kebersihan sanitasi lingkungan pesantren.</p>',
            ],
            [
                'judul' => 'Konferensi Ulama Asia Tenggara di Jakarta Serukan Solidaritas Perdamaian Dunia',
                'kategori' => 'Internasional',
                'views' => 520,
                'published_at' => now()->subDays(9),
                'isi' => '<p>Jakarta menjadi tuan rumah Konferensi Ulama dan Cendekiawan Muslim Asia Tenggara. Pertemuan strategis ini menghasilkan Deklarasi Jakarta yang menekankan komitmen bersama dalam membela kemerdekaan Palestina dan menyelesaikan krisis kemanusiaan global.</p><p>Delegasi dari berbagai negara sepakat mempererat jejaring diplomasi ulama demi terwujudnya perdamaian dunia yang berkeadilan.</p>',
            ],
            [
                'judul' => 'Digitalisasi Sistem Pelayanan Rekomendasi Surat MUI Permudah Layanan Publik',
                'kategori' => 'Teknologi',
                'views' => 340,
                'published_at' => now()->subDays(10),
                'isi' => '<p>Peluncuran portal terpadu MUI Batanghari menandai era baru dalam percepatan administrasi persuratan dan pelayanan publik di lingkungan Majelis Ulama Indonesia.</p><p>Kini permohonan surat rekomendasi, konsultasi keagamaan, dan penelusuran dokumen fatwa dapat diakses secara daring dengan fitur pelacakan waktu nyata (real-time tracking).</p>',
            ],
            [
                'judul' => 'MUI Daerah Gelar Pelatihan Manajemen Pengelolaan Wakaf Produktif Berkelanjutan',
                'kategori' => 'Kabar Daerah',
                'views' => 210,
                'published_at' => now()->subDays(11),
                'isi' => '<p>Pengurus MUI tingkat daerah menyelenggarakan lokakarya tata kelola wakaf produktif yang diikuti oleh puluhan nadzir masjid dan yayasan sosial Islam se-provinsi.</p><p>Pelatihan bertujuan mengoptimalkan aset tanah wakaf agar dapat menghasilkan manfaat ekonomi berkelanjutan bagi pemberdayaan fakir miskin dan beasiswa pendidikan anak yatim.</p>',
            ],
            [
                'judul' => 'Opini: Membangun Ketahanan Keluarga Muslim Menghadapi Disrupsi Zaman',
                'kategori' => 'Opini',
                'views' => 430,
                'published_at' => now()->subDays(12),
                'isi' => '<p>Keluarga merupakan fondasi utama peradaban umat. Dalam artikel opini ini, dibahas langkah konkret memperkuat komunikasi antara orang tua dan anak, menanamkan nilai-nilai tauhid sejak dini, serta menyaring pengaruh negatif media sosial di lingkungan rumah tangga.</p><p>Keluarga yang sakinah, mawaddah, dan rahmah menjadi benteng utama lahirnya generasi saleh yang cerdas dan berkarakter mulia.</p>',
            ],
        ];

        foreach ($beritaList as $index => $item) {
            $imageIndex = $index % count($sampleImages);
            $gambarPath = $sampleImages[$imageIndex];

            Berita::updateOrCreate(
                ['slug' => Str::slug($item['judul'])],
                [
                    'user_id' => $adminId,
                    'judul' => $item['judul'],
                    'kategori' => $item['kategori'],
                    'isi' => $item['isi'],
                    'gambar' => $gambarPath,
                    'status' => 'published',
                    'views' => $item['views'],
                    'published_at' => $item['published_at'],
                ]
            );
        }
    }
}
