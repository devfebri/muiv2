<?php

namespace App\Http\Controllers;

use App\Models\Surat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SuratController extends Controller
{
    /** Tampilkan view atau JSON DataTables. */
    public function index(Request $request): mixed
    {
        if ($request->ajax() || $request->has('draw')) {
            return $this->datatableResponse($request);
        }

        return view('surat.index');
    }

    /** Simpan surat baru (dengan upload file). */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nomor_surat' => 'nullable|string|max:100',
            'perihal' => 'required|string|max:500',
            'tanggal_surat' => 'required|date',
            'file_surat' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:5120',
        ]);

        $validated['user_id'] = auth()->id();
        if ($request->hasFile('file_surat')) {
            $file = $request->file('file_surat');
            $filename = time().'_'.Str::random(16).'.'.strtolower($file->getClientOriginalExtension());
            File::ensureDirectoryExists(public_path('uploads/surat'));
            $file->move(public_path('uploads/surat'), $filename);
            $validated['file_surat'] = $filename;
        }

        $surat = Surat::create($validated);

        return response()->json([
            'message' => 'Surat berhasil diunggah.',
            'data' => $surat->load('pengunggah'),
        ], 201);
    }

    /** Perbarui data surat (file opsional). */
    public function update(Request $request, Surat $surat): JsonResponse
    {
        $validated = $request->validate([
            'nomor_surat' => 'nullable|string|max:100',
            'perihal' => 'required|string|max:500',
            'tanggal_surat' => 'required|date',
            'file_surat' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:5120',
        ]);

        if ($request->hasFile('file_surat')) {
            // Hapus file lama
            if ($surat->file_surat) {
                $oldFilename = basename($surat->file_surat);
                $oldPath = public_path('uploads/surat/'.$oldFilename);
                if (File::exists($oldPath)) {
                    File::delete($oldPath);
                }
                if (Storage::disk('public')->exists('surat/'.$oldFilename)) {
                    Storage::disk('public')->delete('surat/'.$oldFilename);
                }
            }

            $file = $request->file('file_surat');
            $filename = time().'_'.Str::random(16).'.'.strtolower($file->getClientOriginalExtension());
            File::ensureDirectoryExists(public_path('uploads/surat'));
            $file->move(public_path('uploads/surat'), $filename);
            $validated['file_surat'] = $filename;
        } else {
            unset($validated['file_surat']);
        }

        $surat->update($validated);

        return response()->json([
            'message' => 'Surat berhasil diperbarui.',
            'data' => $surat->fresh('pengunggah'),
        ]);
    }

    /** Hapus surat beserta file fisiknya. */
    public function destroy(Surat $surat): JsonResponse
    {
        if ($surat->file_surat) {
            $oldFilename = basename($surat->file_surat);
            $oldPath = public_path('uploads/surat/'.$oldFilename);
            if (File::exists($oldPath)) {
                File::delete($oldPath);
            }
            if (Storage::disk('public')->exists('surat/'.$oldFilename)) {
                Storage::disk('public')->delete('surat/'.$oldFilename);
            }
        }

        $surat->delete();

        return response()->json(['message' => 'Surat berhasil dihapus.']);
    }

    /**
     * Halaman publik arsip surat MUI Batanghari.
     */
    public function publicList(Request $request): View
    {
        $search = trim($request->input('q', ''));
        $tahunAktif = $request->input('tahun');

        $query = Surat::with('pengunggah')->latest('tanggal_surat');

        if ($tahunAktif !== null && $tahunAktif !== '') {
            $query->whereYear('tanggal_surat', $tahunAktif);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_surat', 'like', "%{$search}%")
                    ->orWhere('perihal', 'like', "%{$search}%");
            });
        }

        $surats = $query->paginate(10)->withQueryString();

        $tahunList = Surat::whereNotNull('tanggal_surat')
            ->orderByDesc('tanggal_surat')
            ->get()
            ->map(fn (Surat $s) => $s->tanggal_surat?->format('Y'))
            ->filter()
            ->unique()
            ->values();

        $totalSemua = Surat::count();
        $totalTahunIni = Surat::whereYear('tanggal_surat', date('Y'))->count();

        return view('pages.surat-list', compact(
            'surats',
            'tahunList',
            'tahunAktif',
            'search',
            'totalSemua',
            'totalTahunIni'
        ));
    }

    /* ── Private ─────────────────────────────── */

    private function datatableResponse(Request $request): JsonResponse
    {
        $query = Surat::with('pengunggah');

        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_surat', 'like', "%{$search}%")
                    ->orWhere('perihal', 'like', "%{$search}%");
            });
        }

        $total = Surat::count();
        $filtered = $query->count();

        $orderCol = (int) $request->input('order.0.column', 3);
        $orderDir = $request->input('order.0.dir', 'desc');
        $cols = ['id', 'nomor_surat', 'perihal', 'tanggal_surat', 'created_at'];
        $col = $cols[$orderCol] ?? 'tanggal_surat';

        $query->orderBy($col, $orderDir);

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);

        $data = $query->skip($start)->take($length)->get()->map(function (Surat $s) {
            return [
                'id' => $s->id,
                'nomor_surat' => $s->nomor_surat,
                'perihal' => $s->perihal,
                'tanggal_surat' => $s->tanggal_surat->format('Y-m-d'),
                'file_surat' => $s->file_surat ? basename($s->file_surat) : null,
                'file_url' => $s->file_surat ? asset('uploads/surat/'.basename($s->file_surat)) : null,
                'file_ext' => $s->file_surat ? pathinfo($s->file_surat, PATHINFO_EXTENSION) : null,
                'pengunggah' => $s->pengunggah ? ['name' => $s->pengunggah->name] : null,
                'created_at' => $s->created_at,
            ];
        });

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
            'stats' => [
                'total' => $total,
                'bulan_ini' => Surat::whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->count(),
                'saya' => Surat::where('user_id', auth()->id())->count(),
            ],
        ]);
    }
}
