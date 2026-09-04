<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Daftar semua user, bisa difilter berdasarkan role dan kata kunci.
     */
    public function index(Request $request): View
    {
        $role = $request->query('role');
        if (! Role::where('nama_role', $role)->exists()) {
            $role = null;
        }

        $cari = trim((string) $request->query('cari', ''));
        $cari = $cari !== '' ? $cari : null;

        $query = User::with('role')->orderBy('nama');

        if ($role) {
            $query->whereHas('role', fn ($q) => $q->where('nama_role', $role));
        }

        if ($cari) {
            $query->where(function ($q) use ($cari) {
                $q->where('nama', 'like', "%{$cari}%")
                    ->orWhere('email', 'like', "%{$cari}%");
            });
        }

        $users = $query->paginate(15)->withQueryString();

        $roles = Role::orderBy('id')->get();

        return view('users.index', compact('users', 'roles', 'role', 'cari'));
    }

    public function create(): View
    {
        $roles = Role::orderBy('id')->where('nama_role', '!=', 'admin')->get();

        return view('users.form', compact('roles'));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Hanya boleh satu Admin — jaga-jaga walau validasi sudah memblokir.
        abort_if($this->roleIsAdmin($data['role_id']), 403, 'Hanya boleh ada satu Admin.');

        User::create([
            'nama' => $data['nama'],
            'email' => $data['email'],
            'role_id' => $data['role_id'],
            'password' => Hash::make($data['password']),
        ]);

        return redirect()->route('pengguna.index')->with('status', 'User baru berhasil ditambahkan.');
    }

    public function edit(User $user): View
    {
        // User Admin tidak bisa diedit (diblokir di update()), jadi dropdown
        // role tidak perlu menyertakan opsi Admin.
        $roles = Role::orderBy('id')->where('nama_role', '!=', 'admin')->get();

        return view('users.form', compact('user', 'roles'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        // Admin tidak boleh mengubah role / menonaktifkan dirinya sendiri.
        abort_if($user->id === $request->user()->id, 403, 'Anda tidak bisa mengubah akun Admin sendiri.');

        $data = $request->validated();

        // Hanya boleh satu Admin — jaga-jaga walau validasi sudah memblokir.
        abort_if($this->roleIsAdmin($data['role_id']), 403, 'Hanya boleh ada satu Admin.');

        $user->update([
            'nama' => $data['nama'],
            'email' => $data['email'],
            'role_id' => $data['role_id'],
        ]);

        if (! empty($data['password'])) {
            $user->update(['password' => Hash::make($data['password'])]);
        }

        return redirect()->route('pengguna.index')->with('status', 'Data user berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        // Admin tidak boleh menghapus dirinya sendiri.
        abort_if($user->id === $request->user()->id, 403, 'Anda tidak bisa menghapus akun Admin sendiri.');

        $user->delete();

        return redirect()->route('pengguna.index')->with('status', 'User berhasil dihapus.');
    }

    private function roleIsAdmin(int $roleId): bool
    {
        return Role::where('id', $roleId)->value('nama_role') === 'admin';
    }
}
