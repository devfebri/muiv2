<?php

namespace App\Http\Controllers;

use App\Models\Fatwa;
use App\Models\KategoriFatwa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class KategoriFatwaController extends Controller
{
    /**
     * Tampilkan view manajemen kategori fatwa atau JSON DataTables.
     */
    public function index(Request $request): mixed
    {
        if ($request->ajax() || $request->has('draw')) {
            return $this->datatableResponse($request);
        }

        $total = KategoriFatwa::count();
        $aktif = KategoriFatwa::where('aktif', true)->count();
        $nonaktif = KategoriFatwa::where('aktif', false)->count();
        $totalFatwa = Fatwa::whereNotNull('kategori_fatwa_id')->count();

        return view('fatwa.kategori', compact('total', 'aktif', 'nonaktif', 'totalFatwa'));
    }

    /**
     * Simpan kategori fatwa baru.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:150|unique:kategori_fatwas,nama',
            'deskripsi' => 'nullable|string|max:500',
            'aktif' => 'required|boolean',
        ], [
            'nama.required' => 'Nama kategori fatwa wajib diisi.',
            'nama.unique' => 'Nama kategori fatwa sudah terdaftar.',
            'nama.max' => 'Nama kategori fatwa maksimal 150 karakter.',
            'deskripsi.max' => 'Deskripsi maksimal 500 karakter.',
        ]);

        $validated['slug'] = $this->slugFor($validated['nama']);

        $kategori = KategoriFatwa::create($validated);

        return response()->json([
            'message' => 'Kategori fatwa berhasil ditambahkan.',
            'data' => $kategori,
        ], 201);
    }

    /**
     * Perbarui data kategori fatwa.
     */
    public function update(Request $request, KategoriFatwa $kategoriFatwa): JsonResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:150|unique:kategori_fatwas,nama,'.$kategoriFatwa->id,
            'deskripsi' => 'nullable|string|max:500',
            'aktif' => 'required|boolean',
        ], [
            'nama.required' => 'Nama kategori fatwa wajib diisi.',
            'nama.unique' => 'Nama kategori fatwa sudah digunakan.',
            'nama.max' => 'Nama kategori fatwa maksimal 150 karakter.',
            'deskripsi.max' => 'Deskripsi maksimal 500 karakter.',
        ]);

        $validated['slug'] = $this->slugFor($validated['nama'], $kategoriFatwa);

        $kategoriFatwa->update($validated);

        return response()->json([
            'message' => 'Kategori fatwa berhasil diperbarui.',
            'data' => $kategoriFatwa->fresh(),
        ]);
    }

    /**
     * Toggle status aktif kategori fatwa.
     */
    public function toggleStatus(KategoriFatwa $kategoriFatwa): JsonResponse
    {
        $kategoriFatwa->update(['aktif' => ! $kategoriFatwa->aktif]);

        return response()->json([
            'message' => 'Status kategori berhasil diperbarui.',
            'aktif' => $kategoriFatwa->aktif,
        ]);
    }

    /**
     * Hapus kategori fatwa.
     */
    public function destroy(KategoriFatwa $kategoriFatwa): JsonResponse
    {
        $kategoriFatwa->delete();

        return response()->json([
            'message' => 'Kategori fatwa berhasil dihapus.',
        ]);
    }

    /**
     * Bentuk slug dari nama dan pastikan belum dipakai kategori lain (kolom slug unik di database),
     * agar nama yang berbeda tetapi menghasilkan slug sama tidak memicu galat SQL 500.
     *
     * @throws ValidationException
     */
    private function slugFor(string $nama, ?KategoriFatwa $except = null): string
    {
        $slug = Str::slug($nama);

        $taken = KategoriFatwa::where('slug', $slug)
            ->when($except, fn ($query) => $query->whereKeyNot($except->getKey()))
            ->exists();

        if ($slug === '' || $taken) {
            throw ValidationException::withMessages([
                'nama' => $slug === ''
                    ? 'Nama kategori fatwa harus mengandung huruf atau angka.'
                    : 'Nama kategori fatwa terlalu mirip dengan kategori yang sudah ada.',
            ]);
        }

        return $slug;
    }

    /* ── Private Datatable Response ───────────────── */

    private function datatableResponse(Request $request): JsonResponse
    {
        $query = KategoriFatwa::query()->withCount('fatwas');

        // Pencarian global
        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        // Filter aktif / nonaktif
        $filterAktif = $request->input('filter_aktif');
        if ($filterAktif !== null && $filterAktif !== '') {
            $query->where('aktif', (int) $filterAktif);
        }

        $total = KategoriFatwa::count();
        $filtered = $query->count();

        // Pengurutan
        $orderCol = (int) $request->input('order.0.column', 1);
        $orderDir = $request->input('order.0.dir', 'asc');
        $cols = ['id', 'nama', 'slug', 'deskripsi', 'fatwas_count', 'aktif', 'created_at'];
        $col = $cols[$orderCol] ?? 'nama';

        $query->orderBy($col, $orderDir);

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);

        $data = $query->skip($start)->take($length)->get()->map(function (KategoriFatwa $k) {
            return [
                'id' => $k->id,
                'nama' => $k->nama,
                'slug' => $k->slug,
                'deskripsi' => $k->deskripsi,
                'fatwas_count' => $k->fatwas_count,
                'aktif' => (int) $k->aktif,
                'created_at' => $k->created_at?->format('d/m/Y H:i'),
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
