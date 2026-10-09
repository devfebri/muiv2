<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class KategoriController extends Controller
{
    /** Tampilkan view atau JSON DataTables. */
    public function index(Request $request): mixed
    {
        if ($request->ajax()) {
            return $this->datatableResponse($request);
        }

        return view('kategori.index');
    }

    /** Simpan kategori baru. */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:100|unique:kategoris,nama',
            'warna' => 'required|string|max:30',
            'deskripsi' => 'nullable|string|max:500',
            'aktif' => 'boolean',
            'urutan' => 'nullable|integer|min:0',
        ]);

        $validated['slug'] = $this->slugFor($validated['nama']);
        $validated['aktif'] = $request->boolean('aktif', true);
        $validated['urutan'] = $validated['urutan'] ?? 0;

        $kategori = Kategori::create($validated);

        return response()->json([
            'message' => 'Kategori berhasil ditambahkan.',
            'data' => $kategori,
        ], 201);
    }

    /** Perbarui kategori. */
    public function update(Request $request, Kategori $kategori): JsonResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:100|unique:kategoris,nama,'.$kategori->id,
            'warna' => 'required|string|max:30',
            'deskripsi' => 'nullable|string|max:500',
            'aktif' => 'boolean',
            'urutan' => 'nullable|integer|min:0',
        ]);

        $validated['slug'] = $this->slugFor($validated['nama'], $kategori);
        $validated['aktif'] = $request->boolean('aktif', true);
        $validated['urutan'] = $validated['urutan'] ?? 0;

        $kategori->update($validated);

        return response()->json([
            'message' => 'Kategori berhasil diperbarui.',
            'data' => $kategori->fresh(),
        ]);
    }

    /** Hapus kategori. */
    public function destroy(Kategori $kategori): JsonResponse
    {
        $kategori->delete();

        return response()->json([
            'message' => 'Kategori berhasil dihapus.',
        ]);
    }

    /* ── Private ─────────────────────────────────────── */

    /**
     * Bentuk slug dari nama dan pastikan belum dipakai kategori lain (kolom slug unik),
     * agar nama berbeda yang menghasilkan slug sama (mis. "Halal!" dan "Halal") tidak memicu galat SQL.
     *
     * @throws ValidationException
     */
    private function slugFor(string $nama, ?Kategori $except = null): string
    {
        $slug = Str::slug($nama);

        $taken = Kategori::where('slug', $slug)
            ->when($except, fn ($query) => $query->whereKeyNot($except->getKey()))
            ->exists();

        if ($slug === '' || $taken) {
            throw ValidationException::withMessages([
                'nama' => $slug === ''
                    ? 'Nama kategori harus mengandung huruf atau angka.'
                    : 'Nama kategori terlalu mirip dengan kategori yang sudah ada.',
            ]);
        }

        return $slug;
    }

    private function datatableResponse(Request $request): JsonResponse
    {
        $query = Kategori::query();

        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        $filterAktif = $request->input('filter_aktif');
        if ($filterAktif !== null && $filterAktif !== '') {
            $query->where('aktif', (bool) $filterAktif);
        }

        $total = Kategori::count();
        $filtered = $query->count();

        $orderCol = (int) $request->input('order.0.column', 5);
        $orderDir = $request->input('order.0.dir', 'asc');
        $cols = ['id', 'nama', 'slug', 'aktif', 'urutan', 'created_at'];
        $col = $cols[$orderCol] ?? 'urutan';

        $query->orderBy($col, $orderDir);

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $data = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
            'stats' => [
                'total' => $total,
                'aktif' => Kategori::where('aktif', true)->count(),
                'nonaktif' => Kategori::where('aktif', false)->count(),
            ],
        ]);
    }
}
