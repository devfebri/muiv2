<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OperatorPermissionController extends Controller
{
    /**
     * Tampilkan daftar operator beserta status pembagian hak akses/tugas.
     */
    public function index(Request $request): View|JsonResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            $query = User::query()->where('role', 'operator');
            $search = trim((string) $request->input('search.value', $request->input('search', '')));
            $total = (clone $query)->count();

            if (mb_strlen($search) >= 3) {
                $query->where(function ($builder) use ($search) {
                    $builder->where('name', 'like', "%{$search}%")
                        ->orWhere('name_gelar', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('nohp', 'like', "%{$search}%");
                });
            }

            $filtered = (clone $query)->count();

            $orderColumns = ['id', 'name', 'username', 'email', 'created_at'];
            $orderColumnIndex = $request->integer('order.0.column', 1);
            $orderColumn = $orderColumns[$orderColumnIndex] ?? 'name';
            $orderDirection = $request->input('order.0.dir') === 'desc' ? 'desc' : 'asc';

            $length = min(max($request->integer('length', 10), 1), 100);
            $start = max($request->integer('start', 0), 0);

            $operators = $query
                ->orderBy($orderColumn, $orderDirection)
                ->orderByDesc('id')
                ->offset($start)
                ->limit($length)
                ->get();

            $operators->transform(function ($op) {
                $op->assigned_permissions = $op->getAssignedPermissions();
                $op->append('foto_url');

                return $op;
            });

            return response()->json([
                'draw' => $request->integer('draw', 1),
                'recordsTotal' => $total,
                'recordsFiltered' => $filtered,
                'data' => $operators,
                'allPermissions' => User::OPERATOR_PERMISSIONS,
                'totalAvailable' => count(User::OPERATOR_PERMISSIONS),
            ]);
        }

        $allPermissions = User::OPERATOR_PERMISSIONS;
        $totalOperators = User::where('role', 'operator')->count();

        return view('admin.operator-permissions.index', compact('allPermissions', 'totalOperators'));
    }

    /**
     * Tampilkan detail atau form edit hak akses operator.
     */
    public function edit(Request $request, User $user): View|JsonResponse|RedirectResponse
    {
        if (! $user->isOperator()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'User ini bukan operator.'], 404);
            }

            return redirect()->route('admin.operator-permissions.index')->with('error', 'Hanya akun dengan role operator yang dapat diatur pembagian tugasnya.');
        }

        $allPermissions = User::OPERATOR_PERMISSIONS;
        $assignedPermissions = $user->getAssignedPermissions();

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'name_gelar' => $user->name_gelar,
                    'username' => $user->username,
                    'email' => $user->email,
                    'nohp' => $user->nohp,
                    'assigned_permissions' => $assignedPermissions,
                ],
                'all_permissions' => $allPermissions,
            ]);
        }

        return view('admin.operator-permissions.edit', compact('user', 'allPermissions', 'assignedPermissions'));
    }

    /**
     * Simpan pembaruan hak akses / tugas operator.
     */
    public function update(Request $request, User $user): JsonResponse|RedirectResponse
    {
        if (! $user->isOperator()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'User ini bukan operator.'], 422);
            }

            return redirect()->route('admin.operator-permissions.index')->with('error', 'Akun ini bukan operator.');
        }

        $validKeys = array_keys(User::OPERATOR_PERMISSIONS);

        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in($validKeys)],
        ]);

        $newPermissions = $validated['permissions'] ?? [];

        $user->update([
            'menu_permissions' => array_values(array_unique($newPermissions)),
        ]);

        $message = "Hak akses dan pembagian tugas operator {$user->name} berhasil disimpan.";

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'permissions' => $user->fresh()->getAssignedPermissions(),
                ],
            ]);
        }

        return redirect()->route('admin.operator-permissions.index')->with('success', $message);
    }

    /**
     * Berikan semua hak akses/tugas kepada operator secara cepat.
     */
    public function grantAll(Request $request, User $user): JsonResponse|RedirectResponse
    {
        if (! $user->isOperator()) {
            abort(422, 'Bukan operator');
        }

        $allKeys = array_keys(User::OPERATOR_PERMISSIONS);
        $user->update(['menu_permissions' => $allKeys]);

        $message = "Semua hak akses berhasil diberikan kepada operator {$user->name}.";

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'permissions' => $allKeys,
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Cabut semua hak akses operator secara cepat.
     */
    public function revokeAll(Request $request, User $user): JsonResponse|RedirectResponse
    {
        if (! $user->isOperator()) {
            abort(422, 'Bukan operator');
        }

        $user->update(['menu_permissions' => []]);

        $message = "Semua hak akses operasional untuk {$user->name} telah dicabut.";

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'permissions' => [],
            ]);
        }

        return redirect()->back()->with('success', $message);
    }
}
