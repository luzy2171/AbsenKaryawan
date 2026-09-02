<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Helpers\AuditLogger;

class UserController extends Controller
{
    /**
     * Menampilkan daftar semua user / admin.
     */
    public function index()
    {
        $users = User::orderBy('role', 'asc')->orderBy('name', 'asc')->get();
        return view('admin.users.index', compact('users'));
    }

    /**
     * Menyimpan user baru ke database.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email'    => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'role'     => 'required|in:admin,superadmin',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'username' => $request->username,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
        ]);

        // Log audit
        AuditLogger::userCreated($user);

        return redirect()->route('admin.users.index')->with('status', 'User berhasil ditambahkan.');
    }

    /**
     * Memperbarui data user yang dipilih.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name'     => 'required|string|max:255',
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role'     => 'required|in:admin,superadmin',
            'password' => 'nullable|string|min:6',
        ]);

        // Store old values for audit
        $oldData = [
            'username' => $user->username,
            'name' => $user->name,
            'role' => $user->role,
        ];

        $user->name     = $request->name;
        $user->username = $request->username;
        $user->email    = $request->email;
        $user->role     = $request->role;

        // Password hanya diubah jika diisi
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        // Log audit
        AuditLogger::userUpdated($user, $oldData);

        return redirect()->route('admin.users.index')->with('status', 'User berhasil diperbarui.');
    }

    /**
     * Menghapus user dari sistem.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Cegah menghapus diri sendiri (superadmin yang sedang login)
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun yang sedang digunakan.');
        }

        // Cegah menghapus superadmin terakhir
        $totalSuperadmin = User::where('role', 'superadmin')->count();
        if ($user->role === 'superadmin' && $totalSuperadmin <= 1) {
            return back()->with('error', 'Tidak dapat menghapus Superadmin terakhir di sistem.');
        }

        // Log audit before delete
        AuditLogger::userDeleted($user);

        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'User berhasil dihapus.');
    }
}
