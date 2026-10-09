<?php

namespace App\Http\Controllers;

use App\Models\Berita;
use App\Models\Fatwa;
use App\Models\Konsultasi;
use App\Models\Surat;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    /**
     * Pencarian terpadu atas berita, fatwa, arsip surat, dan tanya jawab yang sudah dijawab.
     */
    public function __invoke(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $results = null;

        if (mb_strlen($q) >= 2) {
            $like = '%'.$q.'%';

            $beritaQuery = Berita::where('status', 'published')
                ->where(fn ($query) => $query->where('judul', 'like', $like)->orWhere('isi', 'like', $like));

            $fatwaQuery = Fatwa::where('publikasi', 1)
                ->where(fn ($query) => $query->where('judul', 'like', $like)->orWhere('keterangan', 'like', $like));

            $suratQuery = Surat::query()
                ->where(fn ($query) => $query->where('perihal', 'like', $like)->orWhere('nomor_surat', 'like', $like));

            $konsultasiQuery = Konsultasi::where('status', 'dijawab')
                ->where(fn ($query) => $query->where('pertanyaan', 'like', $like)->orWhere('jawaban', 'like', $like));

            $results = [
                'berita' => ['total' => (clone $beritaQuery)->count(), 'items' => $beritaQuery->orderByDesc('published_at')->take(6)->get()],
                'fatwa' => ['total' => (clone $fatwaQuery)->count(), 'items' => $fatwaQuery->with('kategori')->latest()->take(5)->get()],
                'surat' => ['total' => (clone $suratQuery)->count(), 'items' => $suratQuery->latest('tanggal_surat')->take(5)->get()],
                'konsultasi' => ['total' => (clone $konsultasiQuery)->count(), 'items' => $konsultasiQuery->latest('answered_at')->take(5)->get()],
            ];
        }

        $total = $results ? collect($results)->sum('total') : 0;

        return view('pages.search', compact('q', 'results', 'total'));
    }
}
