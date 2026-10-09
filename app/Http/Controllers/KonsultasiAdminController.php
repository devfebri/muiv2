<?php

namespace App\Http\Controllers;

use App\Models\Konsultasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KonsultasiAdminController extends Controller
{
    /**
     * Tampilkan halaman daftar konsultasi atau response JSON DataTables.
     */
    public function index(Request $request): mixed
    {
        if ($request->ajax() || $request->has('draw')) {
            return $this->datatableResponse($request);
        }

        $total = Konsultasi::count();
        $pending = Konsultasi::where('status', 'pending')->count();
        $dijawab = Konsultasi::where('status', 'dijawab')->count();
        $ditolak = Konsultasi::where('status', 'ditolak')->count();
        $kategoriList = KonsultasiController::KATEGORI;

        return view('konsultasi.index', compact('total', 'pending', 'dijawab', 'ditolak', 'kategoriList'));
    }

    /**
     * Tampilkan detail konsultasi (JSON untuk AJAX / DataTables, atau redirect ke halaman index dengan modal terbuka).
     */
    public function show(Request $request, Konsultasi $konsultasi): mixed
    {
        if ($request->wantsJson() || $request->ajax()) {
            $konsultasi->load('penjawab:id,name,role');

            return response()->json([
                'id' => $konsultasi->id,
                'nama' => $konsultasi->nama,
                'email' => $konsultasi->email,
                'usia' => $konsultasi->usia,
                'jenis_kelamin' => $konsultasi->jenis_kelamin,
                'kab_kota' => $konsultasi->kab_kota,
                'kategori' => $konsultasi->kategori,
                'pertanyaan' => $konsultasi->pertanyaan,
                'status' => $konsultasi->status,
                'jawaban' => $konsultasi->jawaban,
                'penjawab_nama' => $konsultasi->penjawab?->name,
                'penjawab_role' => $konsultasi->penjawab?->role,
                'answered_at' => $konsultasi->answered_at?->translatedFormat('d F Y H:i'),
                'created_at' => $konsultasi->created_at?->translatedFormat('d F Y H:i'),
            ]);
        }

        $targetRoute = auth()->user() && auth()->user()->isOperator()
            ? 'operator.konsultasi.index'
            : 'admin.konsultasi.index';

        return redirect()->route($targetRoute, ['detail_id' => $konsultasi->id]);
    }

    /**
     * Jawab / balas konsultasi (Khusus Operator).
     */
    public function jawab(Request $request, Konsultasi $konsultasi): JsonResponse
    {
        if (! auth()->user()->isOperator()) {
            return response()->json([
                'message' => 'Akses ditolak: Hanya Operator yang diizinkan membalas konsultasi.',
            ], 403);
        }

        $validated = $request->validate([
            'jawaban' => 'required|string|min:5',
            'status' => 'required|in:dijawab,ditolak',
        ], [
            'jawaban.required' => 'Jawaban / tanggapan wajib diisi.',
            'jawaban.min' => 'Jawaban minimal 5 karakter.',
            'status.required' => 'Status konsultasi wajib dipilih.',
            'status.in' => 'Status harus berupa dijawab atau ditolak.',
        ]);

        $konsultasi->update([
            'jawaban' => $validated['jawaban'],
            'status' => $validated['status'],
            'penjawab_id' => auth()->id(),
            'answered_at' => now(),
        ]);

        return response()->json([
            'message' => 'Jawaban konsultasi berhasil disimpan.',
            'data' => $konsultasi->fresh()->load('penjawab:id,name,role'),
        ]);
    }

    /**
     * Hapus data konsultasi (Khusus Operator).
     */
    public function destroy(Konsultasi $konsultasi): JsonResponse
    {
        if (! auth()->user()->isOperator()) {
            return response()->json([
                'message' => 'Akses ditolak: Hanya Operator yang diizinkan menghapus data konsultasi.',
            ], 403);
        }

        $konsultasi->delete();

        return response()->json([
            'message' => 'Data konsultasi berhasil dihapus.',
        ]);
    }

    /**
     * Response data untuk server-side DataTables.
     */
    private function datatableResponse(Request $request): JsonResponse
    {
        $query = Konsultasi::query()->with('penjawab:id,name,role');

        // Pencarian global
        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('kab_kota', 'like', "%{$search}%")
                    ->orWhere('kategori', 'like', "%{$search}%")
                    ->orWhere('pertanyaan', 'like', "%{$search}%");
            });
        }

        // Filter status
        if ($status = $request->input('filter_status')) {
            $query->where('status', $status);
        }

        // Filter kategori
        if ($kategori = $request->input('filter_kategori')) {
            $query->where('kategori', $kategori);
        }

        $total = Konsultasi::count();
        $filtered = $query->count();

        // Pengurutan
        $orderCol = (int) $request->input('order.0.column', 1);
        $orderDir = $request->input('order.0.dir', 'desc');
        $cols = ['id', 'created_at', 'nama', 'kategori', 'pertanyaan', 'status'];
        $col = $cols[$orderCol] ?? 'created_at';

        $query->orderBy($col, $orderDir);

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);

        $data = $query->skip($start)->take($length)->get()->map(function (Konsultasi $k) {
            return [
                'id' => $k->id,
                'nama' => $k->nama,
                'email' => $k->email,
                'usia' => $k->usia,
                'jenis_kelamin' => $k->jenis_kelamin,
                'kab_kota' => $k->kab_kota,
                'kategori' => $k->kategori,
                'pertanyaan' => $k->pertanyaan,
                'status' => $k->status,
                'jawaban' => $k->jawaban,
                'penjawab' => $k->penjawab?->name,
                'answered_at' => $k->answered_at?->format('d/m/Y H:i'),
                'created_at' => $k->created_at?->format('d/m/Y H:i'),
            ];
        });

        $perStatus = Konsultasi::query()
            ->selectRaw('status, COUNT(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
            'stats' => [
                'total' => $total,
                'pending' => (int) ($perStatus['pending'] ?? 0),
                'dijawab' => (int) ($perStatus['dijawab'] ?? 0),
                'ditolak' => (int) ($perStatus['ditolak'] ?? 0),
            ],
        ]);
    }
}
