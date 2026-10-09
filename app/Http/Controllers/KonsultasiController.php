<?php

namespace App\Http\Controllers;

use App\Models\Konsultasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KonsultasiController extends Controller
{
    public const KATEGORI = [
        'Akidah dan Kepercayaan',
        'Ekonomi dan Keuangan Syariah',
        'Haji dan Umrah',
        'Ibadah Lainnya',
        'Jenazah dan Pemakaman',
        'Kelembagaan dan Fatwa MUI',
        'Kesehatan dan Kedokteran',
        'Kurban',
        'Perkawinan dan Rumah Tangga',
        'Puasa',
        'Shalat',
        'Sosial Kemasyarakatan',
        'Standar dan Produk Halal',
        'Tasawuf',
        'Thaharah (Bersuci) dan Najis',
        'Waqaf',
        'Waris',
        'Zakat',
    ];

    /**
     * Halaman publik daftar seluruh data konsultasi masyarakat.
     */
    public function list(Request $request): View
    {
        $kategoriList = self::KATEGORI;
        $search = trim($request->input('q', ''));
        $kategoriAktif = $request->input('kategori');
        $statusAktif = $request->input('status');

        $query = Konsultasi::with('penjawab')->latest('created_at');

        // Filter status
        if ($statusAktif === 'dijawab') {
            $query->where('status', 'dijawab');
        } elseif ($statusAktif === 'pending') {
            $query->where('status', 'pending');
        }

        // Filter kategori
        if ($kategoriAktif && in_array($kategoriAktif, self::KATEGORI, true)) {
            $query->where('kategori', $kategoriAktif);
        }

        // Pencarian
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('pertanyaan', 'like', "%{$search}%")
                    ->orWhere('jawaban', 'like', "%{$search}%")
                    ->orWhere('nama', 'like', "%{$search}%")
                    ->orWhere('kab_kota', 'like', "%{$search}%");
            });
        }

        $konsultasis = $query->paginate(9)->withQueryString();

        $totalSemua = Konsultasi::count();
        $totalDijawab = Konsultasi::where('status', 'dijawab')->count();
        $totalPending = Konsultasi::where('status', 'pending')->count();

        // Hitung konsultasi per kategori untuk badge / tab filter
        $statKategori = Konsultasi::selectRaw('kategori, count(*) as total')
            ->groupBy('kategori')
            ->pluck('total', 'kategori')
            ->toArray();

        return view('pages.konsultasi-list', compact(
            'konsultasis',
            'kategoriList',
            'kategoriAktif',
            'statusAktif',
            'search',
            'totalSemua',
            'totalDijawab',
            'totalPending',
            'statKategori'
        ));
    }

    /**
     * Halaman detail satu konsultasi publik.
     */
    public function detail(Konsultasi $konsultasi): View
    {
        $konsultasi->load('penjawab');

        $terkait = Konsultasi::where('id', '!=', $konsultasi->id)
            ->where('kategori', $konsultasi->kategori)
            ->where('status', 'dijawab')
            ->latest('answered_at')
            ->take(4)
            ->get();

        return view('pages.konsultasi-detail', compact('konsultasi', 'terkait'));
    }

    /**
     * Halaman publik formulir Tanya Ulama / Konsultasi Syariah.
     */
    public function index(): View
    {
        $kategoriList = self::KATEGORI;

        // Konsultasi yang sudah dijawab untuk referensi publik
        $konsultasiTerjawab = Konsultasi::with('penjawab')
            ->where('status', 'dijawab')
            ->latest('answered_at')
            ->take(6)
            ->get();

        return view('pages.tanya-ulama', compact('kategoriList', 'konsultasiTerjawab'));
    }

    /**
     * Simpan pertanyaan konsultasi baru dari publik.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:150',
            'email' => 'required|email|max:150',
            'usia' => 'required|integer|min:5|max:120',
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'kab_kota' => 'required|string|max:150',
            'kategori' => 'required|string|in:'.implode(',', self::KATEGORI),
            'pertanyaan' => 'required|string|min:10',
        ], [
            'nama.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'usia.required' => 'Usia wajib diisi.',
            'usia.integer' => 'Usia harus berupa angka.',
            'jenis_kelamin.required' => 'Pilih jenis kelamin Anda.',
            'kab_kota.required' => 'Provinsi / Kab. Kota wajib diisi.',
            'kategori.required' => 'Pilih kategori pertanyaan Anda.',
            'pertanyaan.required' => 'Isi pertanyaan wajib diisi.',
            'pertanyaan.min' => 'Isi pertanyaan minimal 10 karakter.',
        ]);

        $validated['status'] = 'pending';

        Konsultasi::create($validated);

        return redirect()->route('tanya-ulama')
            ->with('success', 'Alhamdulillah! Pertanyaan Anda telah berhasil dikirim dan akan segera ditinjau serta dijawab oleh Tim Ulama kami.');
    }
}
