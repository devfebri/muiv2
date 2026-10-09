<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaults = [
            // PROFIL MUI
            'profil_title' => 'Profil Majelis Ulama Indonesia',
            'profil_subtitle' => 'Wadah musyawarah para ulama, zuama, dan cendekiawan muslim di Indonesia yang berkhidmat membimbing, membina, dan mengayomi umat.',
            'profil_sekilas_1' => 'Majelis Ulama Indonesia (MUI) adalah Lembaga Swadaya Masyarakat yang mewadahi para ulama, zu\'ama, dan cendekiawan Islam di Indonesia untuk membimbing, membina, dan mengayomi kaum muslimin di seluruh Indonesia.',
            'profil_sekilas_2' => 'MUI berdiri pada tanggal 7 Rajab 1395 Hijriah atau bertepatan dengan tanggal 26 Juli 1975 di Jakarta, sebagai hasil dari pertemuan para ulama, cendekiawan, dan tokoh dari berbagai organisasi kemasyarakatan Islam di tanah air.',
            'profil_sekilas_3' => 'Dalam perjalanannya, MUI terus berdiri di garda terdepan sebagai tenda besar umat Islam Indonesia, merajut persatuan di tengah kebhinekaan serta senantiasa memberikan panduan moral dan syariah bagi masyarakat dan negara.',
            'profil_peran_1_title' => 'Khadimul Ummah',
            'profil_peran_1_desc' => 'Pelayan umat yang senantiasa hadir memberikan bimbingan, perlindungan, dan solusi syariah atas berbagai persoalan umat.',
            'profil_peran_2_title' => 'Himayatul Ummah',
            'profil_peran_2_desc' => 'Penjaga dan benteng akidah umat dari pemikiran, aliran menyimpang, dan pengaruh negatif yang merusak moralitas bangsa.',
            'profil_peran_3_title' => 'Shodiqul Hukumah',
            'profil_peran_3_desc' => 'Mitra kritis dan konstruktif pemerintah dalam mewujudkan kemaslahatan masyarakat dan kebijakan bangsa yang bermartabat.',
            'profil_tugas_pokok' => "Pemberi Fatwa dan Panduan Hukum Islam: Merumuskan fatwa hukum syariah terhadap masalah-masalah kontemporer keagamaan, sosial, dan ekonomi syariah.\nPerekat Ukhuwah Islamiyah & Kebangsaan: Memperkuat persatuan antar umat Islam (Ukhuwah Islamiyah), persaudaraan kebangsaan (Ukhuwah Wathaniyah), dan kemanusiaan (Ukhuwah Insaniyah).\nJaminan Produk Halal: Mengawal kepastian kehalalan produk pangan, obat-obatan, dan kosmetika untuk ketenangan masyarakat muslim Indonesia.\nPengembangan Ekonomi Syariah: Memberikan panduan prinsip-prinsip syariah pada industri keuangan, perbankan, pasar modal, dan bisnis syariah melalui DSN-MUI.",
            'profil_tgl_berdiri' => '26 Juli 1975 (7 Rajab 1395 H)',
            'profil_sifat_lembaga' => 'Lembaga Keagamaan Independen',
            'profil_alamat_kantor' => 'Jl. Jenderal Sudirman, Muara Bulian, Kab. Batanghari, Jambi',

            // VISI & MISI
            'visi_title' => 'Visi & Misi MUI',
            'visi_subtitle' => 'Arah, cita-cita luhur, dan komitmen pengabdian Majelis Ulama Indonesia bagi kemaslahatan umat dan bangsa.',
            'visi_text' => 'Terciptanya kondisi kehidupan kemasyarakatan, kebangsaan dan kenegaraan yang baik, memperoleh ridha dan ampunan Allah SWT (Baldatun Thayyibatun Wa Rabbun Ghafur) menuju masyarakat berkualitas (Khaira Ummah) demi terwujudnya kejayaan Islam dan kaum muslimin (Izzul Islam wal Muslimin) dalam wadah Negara Kesatuan Republik Indonesia.',
            'misi_list' => "Menggerakkan Kepemimpinan Keumatan yang Efektif | Menggerakkan kepemimpinan dan kelembagaan umat secara efektif dengan menjadikan ulama sebagai panutan (qudwah hasanah) dalam membimbing umat.\nMemperkuat Ukhuwah Islamiyah, Wathaniyah & Insaniyah | Menjadi tenda besar pemersatu umat Islam dalam memelihara dan menegakkan ukhuwah Islamiyah, kerukunan kebangsaan, dan persaudaraan kemanusiaan.\nMengembangkan Dakwah Amar Ma'ruf Nahi Munkar | Melaksanakan bimbingan dan dakwah Islamiyah dengan hikmah, mau'izhah hasanah, dan dialog cerdas demi perbaikan akhlak dan peradaban bangsa.\nMemberikan Fatwa & Panduan Hukum Syariah Terpercaya | Menetapkan fatwa hukum syariah yang mendalam, kontekstual, dan solutif terhadap dinamika keagamaan serta tuntutan zaman.\nMendorong Pertumbuhan Ekonomi Syariah & Produk Halal | Mengembangkan perekonomian syariah yang inklusif serta menjamin ketersediaan produk halal dan tayyib bagi segenap masyarakat.\nMenjaga Kemurnian Akidah & Melindungi Umat | Membentengi akidah umat Islam dari pengaruh paham keagamaan yang menyimpang, ekstremisme, terorisme, dan sekularisme radikal.\nMenjalin Kemitraan Bersama Pemerintah & Dunia Internasional | Menjalin kemitraan konstruktif dengan pemerintah dalam kebijakan publik serta memperkuat diplomasi Islam wasathiyah di kancah internasional.",
            'wasathiyah_title' => 'Prinsip Islam Wasathiyah',
            'wasathiyah_desc' => 'MUI senantiasa mengedepankan corak keislaman yang moderat (tawasuth), berimbang (tawazun), adil (i\'tidal), dan toleran (tasamuh) dalam setiap bimbingan fatwa dan gerak langkahnya.',

            // STRUKTUR ORGANISASI
            'struktur_title' => 'Struktur Organisasi MUI',
            'struktur_subtitle' => 'Susunan kepengurusan Dewan Pertimbangan, Dewan Pimpinan Harian, Komisi-Komisi, serta Lembaga/Badan Otonom Majelis Ulama Indonesia.',
            'struktur_dewan_pertimbangan_desc' => 'Dewan Pertimbangan berwenang memberikan arahan, fatwa pertimbangan strategis, serta nasihat kepada Dewan Pimpinan Harian dalam penetapan garis kebijakan organisasi.',
            'struktur_ketua_pertimbangan' => 'Prof. Dr. KH. Ma\'ruf Amin',
            'struktur_anggota_pertimbangan' => 'Tokoh & Ulama Ormas Islam',
            'struktur_pimpinan_harian_desc' => 'Dewan Pimpinan Harian bertanggung jawab menjalankan roda organisasi, kepemimpinan operasional, serta representasi resmi Majelis Ulama Indonesia.',
            'struktur_ketua_umum' => 'Ketua Umum Dewan Pimpinan MUI',
            'struktur_ketua_umum_desc' => 'Memimpin seluruh pelaksanaan ketetapan Munas dan kebijakan Dewan Pimpinan.',
            'struktur_wakil_ketua_umum' => 'Wakil Ketua Umum',
            'struktur_wakil_ketua_umum_desc' => 'Membantu pelaksanaan tugas dan wewenang Ketua Umum dalam bidang-bidang strategis.',
            'struktur_sekjen' => 'Sekretaris Jenderal (Sekjen)',
            'struktur_sekjen_desc' => 'Memimpin tata kelola administrasi, koordinasi komisi, dan operasional kesekretariatan.',
            'struktur_bendahara_umum' => 'Bendahara Umum',
            'struktur_bendahara_umum_desc' => 'Mengelola perbendaharaan, transparansi keuangan, dan akuntabilitas anggaran lembaga.',
            'struktur_ketua_bidang' => 'Ketua-Ketua Bidang',
            'struktur_ketua_bidang_desc' => 'Mengoordinasikan komisi fatwa, dakwah, ukhuwah, hukum, infokom, dan luar negeri.',
            'struktur_bagan_gambar' => null,
            'struktur_komisi_list' => "Komisi Fatwa | Merumuskan dan mengeluarkan fatwa-fatwa hukum syariah kontemporer.\nKomisi Dakwah & Pengembangan Masyarakat | Mengoordinasikan program standardisasi da'i dan pemberdayaan umat.\nKomisi Ukhuwah Islamiyah | Merajut kesatuan dan kerukunan antar organisasi kemasyarakatan Islam.\nKomisi Hukum & Hak Asasi Manusia | Advokasi hukum, perlindungan umat, dan kajian legislasi kebangsaan.\nDewan Syariah Nasional (DSN-MUI) | Menetapkan fatwa ekonomi, perbankan, dan keuangan syariah nasional.\nLembaga Pengkajian POM (LPPOM-MUI) | Pemeriksaan kepatuhan halal produk pangan, obat, dan kosmetika.",

            // KONTAK
            'kontak_title' => 'Hubungi MUI Batanghari',
            'kontak_subtitle' => 'Sekretariat Majelis Ulama Indonesia Kabupaten Batanghari siap melayani permohonan informasi, aspirasi keumatan, dan konsultasi keagamaan Anda.',
            'kontak_alamat' => 'Jl. Jenderal Sudirman, Muara Bulian, Kab. Batanghari, Jambi 36613',
            'kontak_telepon' => '(0743) 21123',
            'kontak_whatsapp' => '0812-3456-7890',
            'kontak_email' => 'sekretariat@muibatanghari.or.id',
            'kontak_jam_layanan' => 'Senin – Jumat: 08.00 – 16.00 WIB',
            'kontak_maps_embed' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d127641.5173199856!2d103.200155!3d-1.724654!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e2f698e6c1e5555%3A0x63390c50a1dbbe47!2sMuara%20Bulian%2C%20Batang%20Hari%20Regency%2C%20Jambi!5e0!3m2!1sid!2sid!4v1700000000000!5m2!1sid!2sid',
            'kontak_instagram' => '@muibatanghari',
            'kontak_youtube' => 'MUI Batanghari Official',
            'kontak_facebook' => 'MUI Kabupaten Batanghari',
            'kontak_twitter' => '@muibatanghari',
            'kontak_tiktok' => '@muibatanghari',
        ];

        foreach ($defaults as $key => $value) {
            $group = 'general';
            if (str_starts_with($key, 'profil_')) {
                $group = 'profil';
            } elseif (str_starts_with($key, 'visi_') || str_starts_with($key, 'misi_') || str_starts_with($key, 'wasathiyah_')) {
                $group = 'visi_misi';
            } elseif (str_starts_with($key, 'struktur_')) {
                $group = 'struktur';
            } elseif (str_starts_with($key, 'kontak_')) {
                $group = 'kontak';
            }

            Setting::firstOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => $group]
            );
        }

        Setting::clearCache();
    }
}
