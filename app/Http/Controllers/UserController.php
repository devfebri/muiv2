<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        if ($request->expectsJson()) {
            $query = User::query();
            $search = trim($request->input('search.value', ''));
            $total = (clone $query)->count();

            if (mb_strlen($search) >= 3) {
                $query->where(function ($builder) use ($search) {
                    $builder->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('role', 'like', "%{$search}%");
                });
            }

            $filtered = (clone $query)->count();
            $orderColumns = ['id', 'name', 'username', 'email', 'role', 'created_at'];
            $orderColumn = $orderColumns[$request->integer('order.0.column', 5)] ?? 'created_at';
            $orderDirection = $request->input('order.0.dir') === 'asc' ? 'asc' : 'desc';
            $length = min(max($request->integer('length', 10), 1), 100);

            $users = $query
                ->orderBy($orderColumn, $orderDirection)
                ->orderByDesc('id')
                ->offset(max($request->integer('start', 0), 0))
                ->limit($length)
                ->get($this->responseFields());

            return response()->json([
                'draw' => $request->integer('draw'),
                'recordsTotal' => $total,
                'recordsFiltered' => $filtered,
                'data' => $users,
            ]);
        }

        return view('users.index');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());

        if (($validated['role'] ?? '') === 'operator' && ! isset($validated['menu_permissions'])) {
            $validated['menu_permissions'] = User::DEFAULT_OPERATOR_PERMISSIONS;
        }

        $user = User::create($validated);

        return response()->json([
            'message' => 'Pengguna berhasil ditambahkan.',
            'data' => $user->only($this->responseFields()),
        ], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate($this->rules($user));

        // Password kosong saat mengubah data berarti password lama dipertahankan.
        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Pengguna berhasil diperbarui.',
            'data' => $user->fresh()->only($this->responseFields()),
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        if ($user->is(auth()->user())) {
            return response()->json([
                'message' => 'Akun yang sedang digunakan tidak dapat dihapus.',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'message' => 'Pengguna berhasil dihapus.',
        ]);
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    private function rules(?User $user = null): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'name_gelar' => ['nullable', 'string', 'max:100'],
            'jk' => ['nullable', 'string', 'max:15'],
            'alamat' => ['nullable', 'string'],
            'nohp' => ['nullable', 'string', 'max:15'],
            'username' => [
                'required',
                'string',
                'max:20',
                Rule::unique('users', 'username')->ignore($user),
            ],
            'role' => ['required', Rule::in(['admin', 'operator'])],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user),
            ],
            'menu_permissions' => ['nullable', 'array'],
            'menu_permissions.*' => ['string', Rule::in(array_keys(User::OPERATOR_PERMISSIONS))],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function responseFields(): array
    {
        return [
            'id',
            'name',
            'name_gelar',
            'jk',
            'alamat',
            'nohp',
            'username',
            'role',
            'menu_permissions',
            'email',
            'created_at',
        ];
    }
}
