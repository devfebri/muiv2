<?php

namespace Database\Seeders;

use App\Models\Konsultasi;
use App\Models\User;
use Illuminate\Database\Seeder;

class KonsultasiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first() ?? User::first();
        $adminId = $admin ? $admin->id : 1;

        $konsultasiList = [
            [
                'nama' => 'Ahmad Fauzi',
                'email' => 'ahmad.fauzi@gmail.com',
                'usia' => 34,
                'jenis_kelamin' => 'Laki-laki',
                'kab_kota' => 'Jakarta Selatan',
                'kategori' => 'Ibadah',
                'pertanyaan' => 'Bagaimana hukum shalat di atas kendaraan yang sedang melaju jika tidak memungkinkan menghadap kiblat secara tepat sepanjang shalat?',
                'status' => 'dijawab',
                'jawaban' => 'Berdasarkan tuntunan syariat, jika memungkinkan menghadap kiblat saat takbiratul ihram maka hendaknya dilakukan, kemudian mengikuti arah laju kendaraan. Namun jika kondisi benar-benar tidak memungkinkan karena keterbatasan ruang gerak, shalat tetap sah dikerjakan sesuai kemampuan berdasarkan firman Allah SWT: "Bertaqwalah kepada Allah menurut kesanggupanmu" (QS. At-Taghabun: 16).',
                'penjawab_id' => $adminId,
                'answered_at' => now()->subDays(1),
            ],
            [
                'nama' => 'Siti Rahmawati',
                'email' => 'siti.rahmawati@yahoo.com',
                'usia' => 28,
                'jenis_kelamin' => 'Perempuan',
                'kab_kota' => 'Surabaya',
                'kategori' => 'Muamalah',
                'pertanyaan' => 'Apakah sistem transaksi bayar nanti (paylater) yang mengenakan denda atau bunga persentase keterlambatan termasuk kategori riba?',
                'status' => 'dijawab',
                'jawaban' => 'Skema paylater yang mengenakan tambahan bunga dari pokok utang atau denda finansial keterlambatan yang berlipat dikategorikan sebagai riba nasiah yang diharamkan syariat. Transaksi pembiayaan digital hanya diperbolehkan jika menggunakan akad yang sesuai syariah (seperti murabahah atau ijarah ujrah tetap) yang telah memperoleh rekomendasi/pengawasan dari DSN-MUI.',
                'penjawab_id' => $adminId,
                'answered_at' => now()->subDays(2),
            ],
            [
                'nama' => 'Budi Santoso',
                'email' => 'budi.santoso@gmail.com',
                'usia' => 45,
                'jenis_kelamin' => 'Laki-laki',
                'kab_kota' => 'Bandung',
                'kategori' => 'Keluarga',
                'pertanyaan' => 'Bagaimana kedudukan hak waris bagi cucu yang orang tuanya telah meninggal dunia terlebih dahulu sebelum kakeknya wafat?',
                'status' => 'dijawab',
                'jawaban' => 'Dalam Kompilasi Hukum Islam (KHI) di Indonesia Pasal 185, anak dari ahli waris yang meninggal lebih dahulu dapat berkedudukan sebagai ahli waris pengganti (ahli waris mawali). Bagian ahli waris pengganti tidak boleh melebihi bagian ahli waris yang sederajat dengan yang digantikannya.',
                'penjawab_id' => $adminId,
                'answered_at' => now()->subDays(3),
            ],
            [
                'nama' => 'Nur Aini',
                'email' => 'nuraini@outlook.com',
                'usia' => 25,
                'jenis_kelamin' => 'Perempuan',
                'kab_kota' => 'Yogyakarta',
                'kategori' => 'Makanan & Minuman',
                'pertanyaan' => 'Bagaimana hukum mengonsumsi makanan yang menggunakan bahan perisa (essence) dengan kandungan alkohol sintetis berkadar sangat rendah?',
                'status' => 'dijawab',
                'jawaban' => 'Berdasarkan Fatwa MUI No. 10 Tahun 2018 tentang Produk Makanan dan Minuman yang Mengandung Alkohol/Etanol, penggunaan etanol non-khamr (hasil sintesis kimia atau industri kimia) sebagai pelarut bahan perisa diperbolehkan selama tidak terdeteksi secara organoleptik dan tidak membahayakan kesehatan (kadar akhir pada produk konsumsi memenuhi standar keamanan LPPOM MUI).',
                'penjawab_id' => $adminId,
                'answered_at' => now()->subDays(4),
            ],
            [
                'nama' => 'Rian Hidayat',
                'email' => 'rian.hidayat@gmail.com',
                'usia' => 31,
                'jenis_kelamin' => 'Laki-laki',
                'kab_kota' => 'Medan',
                'kategori' => 'Sosial Keagamaan',
                'pertanyaan' => 'Bagaimana batasan toleransi dalam menghadiri perayaan hari besar teman kerja yang berbeda keyakinan?',
                'status' => 'dijawab',
                'jawaban' => 'Islam sangat menganjurkan toleransi, tolong-menolong, dan menjaga silaturrahim sosial dengan sesama anak bangsa. Namun batasannya adalah tidak boleh ikut serta dalam ritual peribadatan dan seremonial keagamaan teologis yang bertentangan dengan prinsip akidah tauhid Islam.',
                'penjawab_id' => $adminId,
                'answered_at' => now()->subDays(5),
            ],
            [
                'nama' => 'Hj. Mardiah',
                'email' => 'hj.mardiah@gmail.com',
                'usia' => 56,
                'jenis_kelamin' => 'Perempuan',
                'kab_kota' => 'Semarang',
                'kategori' => 'Zakat & Wakaf',
                'pertanyaan' => 'Bolehkah menyalurkan zakat maal langsung kepada kerabat atau saudara kandung yang kehidupannya miskin?',
                'status' => 'dijawab',
                'jawaban' => 'Menyalurkan zakat kepada kerabat dekat yang termasuk asnaf fakir/miskin hukumnya boleh bahkan sangat dianjurkan karena bernilai dua kebaikan: pahala zakat dan pahala mempererat tali kekerabatan (silaturrahim). Syaratnya adalah kerabat tersebut bukan orang yang menjadi tanggungan nafkah wajib muzakki (seperti ayah, ibu, anak kandung, atau istri).',
                'penjawab_id' => $adminId,
                'answered_at' => now()->subDays(6),
            ],
            [
                'nama' => 'Muhammad Yusuf',
                'email' => 'yusuf.m@gmail.com',
                'usia' => 29,
                'jenis_kelamin' => 'Laki-laki',
                'kab_kota' => 'Makassar',
                'kategori' => 'Ekonomi Syariah',
                'pertanyaan' => 'Apakah jual beli emas secara digital melalui aplikasi fintech diperbolehkan dalam pandangan syariat?',
                'status' => 'dijawab',
                'jawaban' => 'Jual beli emas digital diperbolehkan berdasarkan Fatwa DSN-MUI No. 77/DSN-MUI/V/2010, dengan syarat emas fisik yang ditransaksikan benar-benar ada (underlying asset nyata), harga tunai jelas, dan nasabah berhak mencetak fisik emas tersebut sewaktu-waktu sesuai ketentuan akad.',
                'penjawab_id' => $adminId,
                'answered_at' => now()->subDays(7),
            ],
            [
                'nama' => 'Dewi Lestari',
                'email' => 'dewi.lestari@gmail.com',
                'usia' => 23,
                'jenis_kelamin' => 'Perempuan',
                'kab_kota' => 'Malang',
                'kategori' => 'Ibadah',
                'pertanyaan' => 'Apakah sah wudhu seorang wanita jika masih menggunakan kutek atau cat kuku?',
                'status' => 'dijawab',
                'jawaban' => 'Syarat sah wudhu adalah air wudhu harus membasahi seluruh permukaan kulit dan kuku anggota wudhu. Jika cat kuku kedap air sehingga menghalangi sampainya air ke kuku, maka wudhu tidak sah. Dianjurkan menggunakan pewarna alami seperti pacar/henna atau kutek yang telah teruji klinis dan tersertifikasi tembus air (breathable).',
                'penjawab_id' => $adminId,
                'answered_at' => now()->subDays(8),
            ],
            [
                'nama' => 'Faisal Akbar',
                'email' => 'faisal.akbar@gmail.com',
                'usia' => 37,
                'jenis_kelamin' => 'Laki-laki',
                'kab_kota' => 'Palembang',
                'kategori' => 'Kesehatan',
                'pertanyaan' => 'Bagaimana pandangan ulama mengenai donor organ tubuh setelah pendonor dinyatakan meninggal dunia?',
                'status' => 'dijawab',
                'jawaban' => 'Fatwa MUI memperbolehkan wasiat donor organ tubuh setelah meninggal dunia atas dasar niat kemanusiaan untuk menyelamatkan jiwa orang lain (ihya\'un nafs), dengan persetujuan ahli waris dan tidak ada unsur komersialisasi atau jual beli organ tubuh.',
                'penjawab_id' => $adminId,
                'answered_at' => now()->subDays(9),
            ],
            [
                'nama' => 'Rina Amalia',
                'email' => 'rina.amalia@gmail.com',
                'usia' => 30,
                'jenis_kelamin' => 'Perempuan',
                'kab_kota' => 'Samarinda',
                'kategori' => 'Muamalah',
                'pertanyaan' => 'Apakah diskon atau cashback dari dompet digital termasuk riba?',
                'status' => 'dijawab',
                'jawaban' => 'Berdasarkan Fatwa DSN-MUI No. 116/2017, saldo dompet digital berakad wadi\'ah (titipan). Promo diskon atau cashback diperbolehkan sepanjang murni merupakan promosi/hibah dari pihak penyelenggara aplikasi dan tidak ada skema pinjaman berbunga yang merugikan pihak lain.',
                'penjawab_id' => $adminId,
                'answered_at' => now()->subDays(10),
            ],
            [
                'nama' => 'Hendra Gunawan',
                'email' => 'hendra.gunawan@gmail.com',
                'usia' => 42,
                'jenis_kelamin' => 'Laki-laki',
                'kab_kota' => 'Banjarmasin',
                'kategori' => 'Ibadah',
                'pertanyaan' => 'Kapan batas akhir waktu shalat subuh dan bagaimana hukumnya jika seseorang terbangun setelah matahari terbit?',
                'status' => 'pending',
                'jawaban' => null,
                'penjawab_id' => null,
                'answered_at' => null,
            ],
            [
                'nama' => 'Fitriani',
                'email' => 'fitriani@gmail.com',
                'usia' => 26,
                'jenis_kelamin' => 'Perempuan',
                'kab_kota' => 'Denpasar',
                'kategori' => 'Keluarga',
                'pertanyaan' => 'Bagaimana hukum mahar pernikahan berupa saham syariah atau aset kripto?',
                'status' => 'pending',
                'jawaban' => null,
                'penjawab_id' => null,
                'answered_at' => null,
            ],
        ];

        foreach ($konsultasiList as $item) {
            Konsultasi::updateOrCreate(
                [
                    'email' => $item['email'],
                    'pertanyaan' => $item['pertanyaan'],
                ],
                $item
            );
        }
    }
}
