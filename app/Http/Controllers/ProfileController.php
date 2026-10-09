<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Tampilkan formulir edit profil akun pengguna yang sedang login.
     */
    public function edit(): View
    {
        $user = auth()->user();

        return view('profile.edit', compact('user'));
    }

    /**
     * Simpan pembaruan profil akun pengguna.
     */
    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_gelar' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'nohp' => 'nullable|string|max:30',
            'jk' => 'nullable|in:L,P',
            'alamat' => 'nullable|string',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'hapus_foto' => 'nullable|boolean',
            'password_current' => 'nullable|required_with:password|string',
            'password' => 'nullable|string|min:8|confirmed',
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan oleh akun lain.',
            'foto.image' => 'File foto harus berupa gambar.',
            'foto.mimes' => 'Format foto harus JPEG, PNG, JPG, atau WEBP.',
            'foto.max' => 'Ukuran foto maksimal 2 MB.',
            'password_current.required_with' => 'Password saat ini wajib diisi jika ingin mengganti password.',
            'password.min' => 'Password baru minimal terdiri dari 8 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        // Cek verifikasi password saat ini jika user mengganti password
        if (! empty($validated['password'])) {
            if (! Hash::check($validated['password_current'] ?? '', $user->password)) {
                return back()->withErrors(['password_current' => 'Password saat ini salah.'])->withInput();
            }
            $user->password = $validated['password'];
        }

        // Handle upload foto profil
        if ($request->hasFile('foto')) {
            // Hapus foto lama jika ada
            if ($user->foto && File::exists(public_path('uploads/profil/'.$user->foto))) {
                File::delete(public_path('uploads/profil/'.$user->foto));
            }

            $file = $request->file('foto');
            $filename = 'profil_'.$user->id.'_'.time().'_'.Str::random(6).'.'.strtolower($file->getClientOriginalExtension());
            File::ensureDirectoryExists(public_path('uploads/profil'));
            $file->move(public_path('uploads/profil'), $filename);
            $user->foto = $filename;
        } elseif ($request->boolean('hapus_foto') && $user->foto) {
            if (File::exists(public_path('uploads/profil/'.$user->foto))) {
                File::delete(public_path('uploads/profil/'.$user->foto));
            }
            $user->foto = null;
        }

        $user->name = $validated['name'];
        $user->name_gelar = $validated['name_gelar'] ?? null;
        $user->email = $validated['email'];
        $user->nohp = $validated['nohp'] ?? null;
        $user->jk = $validated['jk'] ?? null;
        $user->alamat = $validated['alamat'] ?? null;
        $user->save();

        return redirect()->route('profile.edit')
            ->with('success', 'Profil akun Anda berhasil diperbarui!');
    }
}
