<?php

namespace App\Http\Controllers;

use App\Models\Berita;
use App\Models\Fatwa;
use App\Models\KategoriFatwa;
use App\Models\Konsultasi;
use App\Models\Setting;
use App\Models\Surat;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WelcomeController extends Controller
{
    /**
     * Tampilkan halaman utama (welcome) dengan data dinamis real-time dari database.
     */
    public function index(Request $request): View
    {
        // 1. Data Berita
        $beritas = Berita::where('status', 'published')
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->take(14)
            ->get();

        // Fallback jika belum ada berita berstatus published
        if ($beritas->isEmpty()) {
            $beritas = Berita::orderByDesc('created_at')->take(14)->get();
        }

        // Slide hero: berita terbaru yang memiliki gambar
        $heroBeritas = $beritas->filter(fn (Berita $berita) => filled($berita->gambar))->take(4)->values();
        if ($heroBeritas->isEmpty()) {
            $heroBeritas = $beritas->take(3)->values();
        }

        $beritaLanjutan = $beritas->reject(fn (Berita $berita) => $heroBeritas->contains('id', $berita->id))->values();
        $beritaUtama = $beritaLanjutan->first() ?? $beritas->first();
        $beritaLain = $beritaLanjutan->slice(1, 4)->values();

        // Berita terpopuler berdasarkan jumlah dilihat
        $beritaPopuler = Berita::where('status', 'published')
            ->orderByDesc('views')
            ->orderByDesc('published_at')
            ->take(5)
            ->get();

        // Berita kategori kajian: Khutbah / Bimbingan / Opini / Fatwa
        $beritaKhutbah = Berita::where('status', 'published')
            ->whereIn('kategori', ['Khutbah', 'Bimbingan', 'Tuntunan Ibadah', 'Opini', 'Fatwa'])
            ->orderByDesc('published_at')
            ->take(3)
            ->get();

        if ($beritaKhutbah->isEmpty()) {
            $beritaKhutbah = $beritas->take(3);
        }

        // 2. Data Fatwa
        $fatwaTerbaru = Fatwa::where('publikasi', 1)
            ->with('kategori')
            ->latest('created_at')
            ->take(5)
            ->get();

        $kategoriFatwa = KategoriFatwa::where('aktif', true)
            ->withCount(['fatwas' => fn ($query) => $query->where('publikasi', 1)])
            ->orderByDesc('fatwas_count')
            ->take(6)
            ->get();

        // 3. Tanya jawab yang sudah dijawab ulama
        $tanyaJawab = Konsultasi::where('status', 'dijawab')
            ->latest('answered_at')
            ->take(4)
            ->get();

        // 4. Statistik Ringkas
        $stats = [
            'fatwa' => Fatwa::where('publikasi', 1)->count(),
            'berita' => Berita::where('status', 'published')->count(),
            'surat' => Surat::count(),
            'konsultasi' => Konsultasi::where('status', 'dijawab')->count(),
        ];

        // 5. Profil singkat dari pengaturan
        $profil = [
            'sekilas' => Setting::get('profil_sekilas_1'),
            'berdiri' => Setting::get('profil_tgl_berdiri', '26 Juli 1975'),
            'peran' => collect([1, 2, 3])
                ->map(fn (int $i) => [
                    'title' => Setting::get("profil_peran_{$i}_title"),
                    'desc' => Setting::get("profil_peran_{$i}_desc"),
                ])
                ->filter(fn (array $peran) => filled($peran['title']))
                ->values(),
        ];

        return view('welcome', compact(
            'heroBeritas',
            'beritaUtama',
            'beritaLain',
            'beritaPopuler',
            'beritaKhutbah',
            'fatwaTerbaru',
            'kategoriFatwa',
            'tanyaJawab',
            'stats',
            'profil'
        ));
    }
}
