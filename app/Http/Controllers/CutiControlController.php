<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use Illuminate\Http\Request;

class CutiControlController extends Controller
{
    public function index(Request $request)
    {
        $query = Karyawan::query();

        if ($request->filled('search')) {
            $query->where('nama', 'like', '%' . $request->search . '%')
                  ->orWhere('id_karyawan', 'like', '%' . $request->search . '%');
        }

        $karyawans = $query->orderBy('nama', 'asc')->paginate(10);

        return view('admin.cuti_control.index', compact('karyawans'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'jatah_cuti_tahunan' => 'required|numeric|min:0'
        ]);

        $karyawan = Karyawan::findOrFail($id);
        $karyawan->jatah_cuti_tahunan = $request->jatah_cuti_tahunan;
        $karyawan->save();

        return redirect()->route('admin.cuti.control')->with('success', 'Jatah cuti tahunan berhasil diperbarui.');
    }
}
