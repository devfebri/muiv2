<?php

namespace Database\Seeders;

use App\Models\ChatFaq;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class LiveChatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Default Operational Settings
        $defaultSettings = [
            'chat_is_enabled' => '1',
            'chat_operational_start' => '08:00',
            'chat_operational_end' => '16:00',
            'chat_operational_days' => '1,2,3,4,5', // Senin - Jumat
            'chat_avg_wait_minutes' => '4',
            'chat_bot_greeting' => 'Assalamu\'alaikum Warahmatullahi Wabarakatuh. Selamat datang di Layanan Bantuan Online MUI Batanghari. Ada yang bisa kami bantu?',
            'chat_offline_message' => 'Mohon maaf, saat ini kantor MUI Batanghari sedang di luar jam operasional (Jam kerja: Senin - Jumat 08.00 - 16.00 WIB). Silakan pilih pertanyaan umum di bawah ini atau tinggalkan pesan untuk petugas kami.',
        ];

        foreach ($defaultSettings as $key => $value) {
            if (Setting::get($key) === null) {
                Setting::set($key, $value, 'livechat');
            }
        }

        // 2. Default Chatbot FAQs
        $faqs = [
            [
                'pertanyaan' => 'Bagaimana cara mengajukan konsultasi syariah / tanya ulama?',
                'jawaban' => "Untuk mengajukan konsultasi syariah, Anda dapat:\n1. Menggunakan formulir 'Tanya Ulama' di menu Layanan website ini.\n2. Atau berkonsultasi langsung via Live Chat ini pada hari & jam kerja.\n3. Pertanyaan Anda akan dijawab oleh Komisi Fatwa MUI Batanghari secara amanah dan rahasia.",
                'kategori' => 'Konsultasi Syariah',
                'urutan' => 1,
            ],
            [
                'pertanyaan' => 'Bagaimana prosedur permohonan rekomendasi sertifikasi halal?',
                'jawaban' => "Prosedur rekomendasi sertifikasi halal produk:\n1. Pemohon menyiapkan data produk, bahan baku, dan proses produksi.\n2. Melampirkan identitas usaha / NIB.\n3. Mengajukan surat permohonan ke Sekretariat MUI Batanghari atau mendaftar melalui aplikasi SIHALAL BPJPH Kemenag dengan memilih pendamping PPH / LPH MUI Batanghari.",
                'kategori' => 'Sertifikasi Halal',
                'urutan' => 2,
            ],
            [
                'pertanyaan' => 'Di mana alamat kantor dan nomor kontak resmi MUI Batanghari?',
                'jawaban' => "Kantor MUI Batanghari beralamat di Komplek Islamic Center Muara Bulian, Kab. Batanghari, Jambi.\nTelepon / WhatsApp: 0812-7483-9201\nEmail: sekretariat@muibatanghari.or.id\nJam pelayanan kantor: Senin - Jumat, 08:00 - 16:00 WIB.",
                'kategori' => 'Kontak & Alamat',
                'urutan' => 3,
            ],
            [
                'pertanyaan' => 'Bagaimana cara mendapatkan arsip surat atau naskah fatwa resmi?',
                'jawaban' => "Naskah fatwa resmi dan arsip surat keputusan MUI Batanghari dapat diunduh langsung melalui menu 'Fatwa' dan 'Surat' pada halaman portal ini secara gratis dalam format dokumen resmi.",
                'kategori' => 'Fatwa & Dokumen',
                'urutan' => 4,
            ],
            [
                'pertanyaan' => 'Kapan jadwal pelayanan tatap muka dengan pengurus MUI?',
                'jawaban' => 'Pelayanan tatap muka dengan Dewan Pengurus dan Komisi Fatwa dibuka setiap hari kerja (Senin s.d. Jumat pukul 09.00 - 15.00 WIB) di Kantor MUI Batanghari dengan konfirmasi terlebih dahulu melalui WhatsApp resmi.',
                'kategori' => 'Layanan Umum',
                'urutan' => 5,
            ],
        ];

        foreach ($faqs as $item) {
            ChatFaq::firstOrCreate(
                ['pertanyaan' => $item['pertanyaan']],
                $item
            );
        }
    }
}
