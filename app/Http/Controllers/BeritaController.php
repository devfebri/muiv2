<?php

namespace App\Http\Controllers;

use App\Models\Berita;
use App\Models\Kategori;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BeritaController extends Controller
{
    private const KATEGORI = [
        'Berita Utama',
        'Fatwa',
        'Bimbingan',
        'Halal',
        'Khutbah',
        'Opini',
        'Nasional',
        'Internasional',
        'Ekonomi',
        'Teknologi',
        'Sosial',
        'Kabar Daerah',
    ];

    /**
     * Halaman publik daftar berita dengan filter kategori & pencarian.
     */
    public function list(Request $request): View
    {
        $kategoriAktif = $request->query('kategori');
        $search = $request->query('q');

        $query = Berita::with('penulis')
            ->where('status', 'published')
            ->orderByDesc('published_at');

        if ($kategoriAktif) {
            $query->where('kategori', $kategoriAktif);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                    ->orWhere('isi', 'like', "%{$search}%");
            });
        }

        $beritas = $query->paginate(12)->withQueryString();
        $beritaUtama = $beritas->first();

        // Berita per kategori (sidebar & tabs) dari tabel kategoris
        $kategoriList = Kategori::where('aktif', true)
            ->orderBy('urutan')
            ->orderBy('nama')
            ->pluck('nama')
            ->toArray();

        if (empty($kategoriList)) {
            $kategoriList = self::KATEGORI;
        }

        // Statistik per kategori
        $statKategori = Berita::where('status', 'published')
            ->selectRaw('kategori, COUNT(*) as jumlah')
            ->groupBy('kategori')
            ->pluck('jumlah', 'kategori');

        // Berita terpopuler (berdasarkan jumlah dilihat / views)
        $terpopuler = Berita::where('status', 'published')
            ->orderByDesc('views')
            ->orderByDesc('published_at')
            ->take(5)
            ->get();

        return view('berita.list', compact(
            'beritas',
            'beritaUtama',
            'kategoriList',
            'kategoriAktif',
            'statKategori',
            'terpopuler',
            'search',
        ));
    }

    /**
     * Halaman publik detail berita.
     */
    public function detail(string $slug): View
    {
        $berita = Berita::with('penulis')
            ->where('status', 'published')
            ->where(function ($q) use ($slug) {
                $q->where('slug', $slug)
                    ->orWhere('id', $slug);
            })
            ->firstOrFail();

        // Tambah jumlah dilihat (views)
        $berita->increment('views');

        // Berita terkait dalam kategori yang sama
        $beritaTerkait = Berita::where('status', 'published')
            ->where('id', '!=', $berita->id)
            ->where('kategori', $berita->kategori)
            ->orderByDesc('published_at')
            ->take(4)
            ->get();

        // Berita terpopuler berdasarkan views
        $terpopuler = Berita::where('status', 'published')
            ->where('id', '!=', $berita->id)
            ->orderByDesc('views')
            ->orderByDesc('published_at')
            ->take(5)
            ->get();

        $kategoriList = Kategori::where('aktif', true)
            ->orderBy('urutan')
            ->orderBy('nama')
            ->pluck('nama')
            ->toArray();

        if (empty($kategoriList)) {
            $kategoriList = self::KATEGORI;
        }

        return view('berita.detail', compact(
            'berita',
            'beritaTerkait',
            'terpopuler',
            'kategoriList',
        ));
    }

    /**
     * Tampilkan daftar berita (DataTables JSON atau view).
     */
    public function index(Request $request): mixed
    {
        if ($request->ajax()) {
            return $this->datatableResponse($request);
        }

        $kategoriList = Kategori::where('aktif', true)
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get();

        return view('berita.index', compact('kategoriList'));
    }

    /**
     * Form tambah berita (halaman penuh).
     */
    public function create(): View
    {
        $kategoriList = Kategori::where('aktif', true)
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get();

        return view('berita.create', compact('kategoriList'));
    }

    /**
     * Simpan berita baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'kategori' => 'required|string|max:100',
            'isi' => 'required|string',
            'status' => 'required|in:draft,published,archived',
            'published_at' => 'nullable|date',
            'gambar' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'gambar.required' => 'Gambar utama (thumbnail) wajib diunggah.',
            'gambar.image' => 'File thumbnail harus berupa gambar.',
            'gambar.mimes' => 'Format gambar harus JPEG, PNG, JPG, atau WEBP.',
            'gambar.max' => 'Ukuran gambar maksimal 2 MB.',
        ]);

        $validated['user_id'] = auth()->id();
        $validated['slug'] = Str::slug($validated['judul']);
        $validated['published_at'] = $validated['status'] === 'published'
            ? ($validated['published_at'] ?? now())
            : null;

        if ($request->hasFile('gambar')) {
            $file = $request->file('gambar');
            $filename = time().'_'.Str::random(16).'.'.strtolower($file->getClientOriginalExtension());
            File::ensureDirectoryExists(public_path('uploads/berita'));
            $file->move(public_path('uploads/berita'), $filename);
            $validated['gambar'] = $filename;
        }

        Berita::create($validated);

        return redirect()->route(auth()->user()->role.'.berita.index')
            ->with('success', 'Berita berhasil ditambahkan.');
    }

    /**
     * Form edit berita (halaman penuh).
     */
    public function edit(Berita $berita): View
    {
        $kategoriList = Kategori::where('aktif', true)
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get();

        return view('berita.edit', compact('berita', 'kategoriList'));
    }

    /**
     * Simpan perubahan berita.
     */
    public function update(Request $request, Berita $berita): RedirectResponse
    {
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'kategori' => 'required|string|max:100',
            'isi' => 'required|string',
            'status' => 'required|in:draft,published,archived',
            'published_at' => 'nullable|date',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'hapus_gambar' => 'nullable|boolean',
        ]);

        $validated['slug'] = Str::slug($validated['judul']);
        $validated['published_at'] = $validated['status'] === 'published'
            ? ($validated['published_at'] ?? $berita->published_at ?? now())
            : null;

        if ($request->hasFile('gambar')) {
            // Hapus gambar lama jika ada
            if ($berita->gambar) {
                $oldFilename = basename($berita->gambar);
                $oldPath = public_path('uploads/berita/'.$oldFilename);
                if (File::exists($oldPath)) {
                    File::delete($oldPath);
                }
                if (\Storage::disk('public')->exists('berita/'.$oldFilename)) {
                    \Storage::disk('public')->delete('berita/'.$oldFilename);
                }
            }

            $file = $request->file('gambar');
            $filename = time().'_'.Str::random(16).'.'.strtolower($file->getClientOriginalExtension());
            File::ensureDirectoryExists(public_path('uploads/berita'));
            $file->move(public_path('uploads/berita'), $filename);
            $validated['gambar'] = $filename;
        } elseif ($request->boolean('hapus_gambar') && $berita->gambar) {
            $oldFilename = basename($berita->gambar);
            $oldPath = public_path('uploads/berita/'.$oldFilename);
            if (File::exists($oldPath)) {
                File::delete($oldPath);
            }
            if (\Storage::disk('public')->exists('berita/'.$oldFilename)) {
                \Storage::disk('public')->delete('berita/'.$oldFilename);
            }
            $validated['gambar'] = null;
        }

        $berita->update($validated);

        return redirect()->route(auth()->user()->role.'.berita.index')
            ->with('success', 'Berita berhasil diperbarui.');
    }

    /**
     * Hapus berita (AJAX).
     */
    public function destroy(Berita $berita): JsonResponse
    {
        if ($berita->gambar) {
            $oldFilename = basename($berita->gambar);
            $oldPath = public_path('uploads/berita/'.$oldFilename);
            if (File::exists($oldPath)) {
                File::delete($oldPath);
            }
            if (\Storage::disk('public')->exists('berita/'.$oldFilename)) {
                \Storage::disk('public')->delete('berita/'.$oldFilename);
            }
        }

        $berita->delete();

        return response()->json([
            'message' => 'Berita berhasil dihapus.',
        ]);
    }

    /* ── Private ──────────────────────────────────────── */

    private function datatableResponse(Request $request): JsonResponse
    {
        $query = Berita::with('penulis:id,name')->select('beritas.*');

        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                    ->orWhere('kategori', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('filter_status')) {
            $query->where('status', $status);
        }

        if ($kategori = $request->input('filter_kategori')) {
            $query->where('kategori', $kategori);
        }

        $total = Berita::count();
        $filtered = $query->count();

        $orderCol = (int) $request->input('order.0.column', 6);
        $orderDir = $request->input('order.0.dir', 'desc');
        $cols = ['id', 'judul', 'kategori', 'status', 'published_at', 'penulis', 'created_at'];
        $col = $cols[$orderCol] ?? 'created_at';

        if ($col !== 'penulis') {
            $query->orderBy("beritas.{$col}", $orderDir);
        }

        // Urutan cadangan agar paginasi stabil saat nilai kolom urut sama.
        $query->orderByDesc('beritas.id');

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $data = $query->skip($start)->take($length)->get();

        $perStatus = Berita::selectRaw('status, COUNT(*) as jumlah')->groupBy('status')->pluck('jumlah', 'status');

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
            'stats' => [
                'total' => $total,
                'published' => (int) ($perStatus['published'] ?? 0),
                'draft' => (int) ($perStatus['draft'] ?? 0),
                'archived' => (int) ($perStatus['archived'] ?? 0),
            ],
        ]);
    }
}
