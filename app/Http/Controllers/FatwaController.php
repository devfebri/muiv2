<?php

namespace App\Http\Controllers;

use App\Models\Fatwa;
use App\Models\KategoriFatwa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FatwaController extends Controller
{
    /** Tampilkan view atau JSON DataTables. */
    public function index(Request $request): mixed
    {
        if ($request->ajax() || $request->has('draw')) {
            return $this->datatableResponse($request);
        }

        $kategoriFatwas = KategoriFatwa::where('aktif', true)->orderBy('nama')->get();
        $statuses = Fatwa::STATUSES;

        return view('fatwa.index', compact('kategoriFatwas', 'statuses'));
    }

    /** Simpan fatwa baru dengan upload PDF. */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kategori_fatwa_id' => 'nullable|exists:kategori_fatwas,id',
            'judul' => 'required|string|max:255',
            'status_fatwa' => 'nullable|in:aktif,direvisi,digantikan',
            'keterangan' => 'nullable|string|max:1000',
            'filepdf' => 'required|file|mimes:pdf|max:10240',
            'publikasi' => 'required|boolean',
        ]);

        $validated['status_fatwa'] = $validated['status_fatwa'] ?? Fatwa::STATUS_AKTIF;
        if ($request->hasFile('filepdf')) {
            $file = $request->file('filepdf');
            $filename = time().'_'.Str::random(16).'.'.strtolower($file->getClientOriginalExtension());
            File::ensureDirectoryExists(public_path('uploads/fatwa'));
            $file->move(public_path('uploads/fatwa'), $filename);
            $validated['filepdf'] = $filename;
        }

        $fatwa = Fatwa::create($validated);

        return response()->json([
            'message' => 'Fatwa berhasil diunggah.',
            'data' => $fatwa->load('kategori'),
        ], 201);
    }

    /** Perbarui fatwa (PDF opsional). */
    public function update(Request $request, Fatwa $fatwa): JsonResponse
    {
        $validated = $request->validate([
            'kategori_fatwa_id' => 'nullable|exists:kategori_fatwas,id',
            'judul' => 'required|string|max:255',
            'status_fatwa' => 'nullable|in:aktif,direvisi,digantikan',
            'keterangan' => 'nullable|string|max:1000',
            'filepdf' => 'nullable|file|mimes:pdf|max:10240',
            'publikasi' => 'required|boolean',
        ]);

        $validated['status_fatwa'] = $validated['status_fatwa'] ?? $fatwa->status_fatwa ?? Fatwa::STATUS_AKTIF;

        if ($request->hasFile('filepdf')) {
            if ($fatwa->filepdf) {
                $oldFilename = basename($fatwa->filepdf);
                $oldPath = public_path('uploads/fatwa/'.$oldFilename);
                if (File::exists($oldPath)) {
                    File::delete($oldPath);
                }
                if (Storage::disk('public')->exists('fatwa/'.$oldFilename)) {
                    Storage::disk('public')->delete('fatwa/'.$oldFilename);
                }
            }

            $file = $request->file('filepdf');
            $filename = time().'_'.Str::random(16).'.'.strtolower($file->getClientOriginalExtension());
            File::ensureDirectoryExists(public_path('uploads/fatwa'));
            $file->move(public_path('uploads/fatwa'), $filename);
            $validated['filepdf'] = $filename;
        } else {
            unset($validated['filepdf']);
        }

        $fatwa->update($validated);

        return response()->json([
            'message' => 'Fatwa berhasil diperbarui.',
            'data' => $fatwa->fresh()->load('kategori'),
        ]);
    }

    /** Toggle status publikasi. */
    public function togglePublikasi(Fatwa $fatwa): JsonResponse
    {
        $fatwa->update(['publikasi' => ! $fatwa->publikasi]);

        return response()->json([
            'message' => 'Status diperbarui.',
            'publikasi' => $fatwa->publikasi,
        ]);
    }

    /** Hapus fatwa beserta file PDF-nya. */
    public function destroy(Fatwa $fatwa): JsonResponse
    {
        if ($fatwa->filepdf) {
            $oldFilename = basename($fatwa->filepdf);
            $oldPath = public_path('uploads/fatwa/'.$oldFilename);
            if (File::exists($oldPath)) {
                File::delete($oldPath);
            }
            if (Storage::disk('public')->exists('fatwa/'.$oldFilename)) {
                Storage::disk('public')->delete('fatwa/'.$oldFilename);
            }
        }

        $fatwa->delete();

        return response()->json(['message' => 'Fatwa berhasil dihapus.']);
    }

    /**
     * Halaman publik daftar & baca fatwa MUI.
     */
    public function publicList(Request $request): View
    {
        $search = trim($request->input('q', ''));
        $kategoriAktif = $request->input('kategori');
        $statusFatwaAktif = $request->input('status');

        $query = Fatwa::where('publikasi', 1)->with('kategori')->latest('created_at');

        if ($kategoriAktif !== null && $kategoriAktif !== '') {
            $query->where(function ($q) use ($kategoriAktif) {
                $q->where('kategori_fatwa_id', $kategoriAktif)
                    ->orWhereHas('kategori', function ($kq) use ($kategoriAktif) {
                        $kq->where('slug', $kategoriAktif);
                    });
            });
        }

        if ($statusFatwaAktif !== null && $statusFatwaAktif !== '' && in_array($statusFatwaAktif, ['aktif', 'direvisi', 'digantikan'], true)) {
            $query->where('status_fatwa', $statusFatwaAktif);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                    ->orWhere('keterangan', 'like', "%{$search}%");
            });
        }

        $fatwas = $query->paginate(9)->withQueryString();

        $kategoriList = KategoriFatwa::where('aktif', true)
            ->withCount(['fatwas' => function ($q) {
                $q->where('publikasi', 1);
            }])
            ->orderBy('nama')
            ->get();

        $totalSemua = Fatwa::where('publikasi', 1)->count();
        $statStatus = [
            'aktif' => Fatwa::where('publikasi', 1)->where('status_fatwa', 'aktif')->count(),
            'direvisi' => Fatwa::where('publikasi', 1)->where('status_fatwa', 'direvisi')->count(),
            'digantikan' => Fatwa::where('publikasi', 1)->where('status_fatwa', 'digantikan')->count(),
        ];

        return view('pages.fatwa-list', compact(
            'fatwas',
            'kategoriList',
            'kategoriAktif',
            'statusFatwaAktif',
            'search',
            'totalSemua',
            'statStatus'
        ));
    }

    /**
     * Tambah jumlah dibaca / dilihat pada dokumen fatwa.
     */
    /**
     * Halaman publik detail fatwa beserta penampil PDF.
     */
    public function publicDetail(Fatwa $fatwa): View
    {
        abort_unless($fatwa->publikasi, 404);

        $fatwa->increment('views');
        $fatwa->load('kategori');

        $terkait = Fatwa::where('publikasi', 1)
            ->where('id', '!=', $fatwa->id)
            ->when($fatwa->kategori_fatwa_id, fn ($query) => $query->where('kategori_fatwa_id', $fatwa->kategori_fatwa_id))
            ->latest()
            ->take(5)
            ->get();

        return view('pages.fatwa-detail', compact('fatwa', 'terkait'));
    }

    public function incrementViews(Fatwa $fatwa): JsonResponse
    {
        $fatwa->increment('views');

        return response()->json([
            'success' => true,
            'views' => $fatwa->views,
        ]);
    }

    /* ── Private ───────────────────────────────── */

    private function datatableResponse(Request $request): JsonResponse
    {
        $query = Fatwa::query()->with('kategori:id,nama,slug');

        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                    ->orWhere('keterangan', 'like', "%{$search}%");
            });
        }

        // Filter publikasi
        $filterPublikasi = $request->input('filter_publikasi');
        if ($filterPublikasi !== null && $filterPublikasi !== '') {
            $query->where('publikasi', (int) $filterPublikasi);
        }

        // Filter status fatwa
        $filterStatusFatwa = $request->input('filter_status_fatwa');
        if ($filterStatusFatwa !== null && $filterStatusFatwa !== '') {
            $query->where('status_fatwa', $filterStatusFatwa);
        }

        // Filter kategori fatwa
        $filterKategori = $request->input('filter_kategori_fatwa');
        if ($filterKategori !== null && $filterKategori !== '') {
            $query->where('kategori_fatwa_id', $filterKategori);
        }

        $total = Fatwa::count();
        $filtered = $query->count();

        $orderCol = (int) $request->input('order.0.column', 7);
        $orderDir = $request->input('order.0.dir', 'desc');
        $cols = ['id', 'judul', 'kategori_fatwa_id', 'status_fatwa', 'keterangan', 'filepdf', 'publikasi', 'created_at'];
        $col = $cols[$orderCol] ?? 'created_at';

        $query->orderBy($col, $orderDir);

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);

        $data = $query->skip($start)->take($length)->get()->map(function (Fatwa $f) {
            $statusFatwa = $f->status_fatwa ?? 'aktif';

            return [
                'id' => $f->id,
                'judul' => $f->judul,
                'kategori_fatwa_id' => $f->kategori_fatwa_id,
                'kategori_nama' => $f->kategori?->nama,
                'status_fatwa' => $statusFatwa,
                'status_fatwa_label' => Fatwa::STATUSES[$statusFatwa] ?? ucfirst($statusFatwa),
                'keterangan' => $f->keterangan,
                'filepdf' => $f->filepdf ? basename($f->filepdf) : null,
                'file_url' => $f->filepdf ? asset('uploads/fatwa/'.basename($f->filepdf)) : null,
                'file_name' => $f->filepdf ? basename($f->filepdf) : null,
                'publikasi' => (int) $f->publikasi,
                'views' => (int) ($f->views ?? 0),
                'created_at' => $f->created_at,
                'updated_at' => $f->updated_at,
            ];
        });

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ]);
    }
}
