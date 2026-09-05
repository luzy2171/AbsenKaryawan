<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\Karyawan;
use App\Models\Attendance;
use App\Helpers\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $query = Leave::with(['karyawan', 'approver'])->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('karyawan', function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('bulan') && $request->filled('tahun')) {
            $start = Carbon::create($request->tahun, $request->bulan, 1)->startOfMonth();
            $end = Carbon::create($request->tahun, $request->bulan, 1)->endOfMonth();
            
            $query->where(function($q) use ($start, $end) {
                $q->whereBetween('tanggal_mulai', [$start, $end])
                  ->orWhereBetween('tanggal_selesai', [$start, $end]);
            });
        }

        $leaves = $query->paginate(15);
        $karyawans = Karyawan::where('status', 'Aktif')->orderBy('nama')->get();

        return view('admin.leaves.index', compact('leaves', 'karyawans'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'karyawan_id' => 'required|exists:karyawans,id',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'jenis' => 'required|in:Cuti,Sakit,Izin',
            'keterangan' => 'nullable|string',
            'dokumen' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048'
        ]);

        $dokumenPath = null;
        if ($request->hasFile('dokumen')) {
            $file = $request->file('dokumen');
            $filename = time() . '_' . $file->getClientOriginalName();
            $dokumenPath = $file->storeAs('public/dokumen_izin', $filename);
            $dokumenPath = str_replace('public/', '', $dokumenPath);
        }

        $leave = Leave::create([
            'karyawan_id' => $request->karyawan_id,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'jenis' => $request->jenis,
            'keterangan' => $request->keterangan,
            'dokumen' => $dokumenPath,
            'status' => 'Disetujui', // Auto approved since Admin/Superadmin inputs it
            'approved_by' => Auth::id()
        ]);

        // FITUR INTI: Sinkronisasi ke Tabel Absensi
        // Jika disetujui, otomatis hapus record "Alpha/Terlambat" yang ada di rentang tanggal itu
        // lalu buatkan record absensi baru dengan status Cuti/Sakit/Izin
        $this->syncLeaveToAttendance($leave);

        AuditLogger::logCustom('Izin/Cuti', "Menambahkan pengajuan {$leave->jenis} untuk karyawan ID {$leave->karyawan_id} dari tgl {$leave->tanggal_mulai->format('d/m/Y')} s/d {$leave->tanggal_selesai->format('d/m/Y')}");

        return redirect()->back()->with('status', 'Pengajuan ' . $request->jenis . ' berhasil ditambahkan dan disinkronkan ke laporan absensi.');
    }

    public function destroy($id)
    {
        $leave = Leave::findOrFail($id);
        $jenis = $leave->jenis;
        $karyawan_id = $leave->karyawan_id;
        
        // Hapus dari tabel absensi
        Attendance::where('karyawan_id', $leave->karyawan_id)
            ->whereBetween('tanggal', [$leave->tanggal_mulai->toDateString(), $leave->tanggal_selesai->toDateString()])
            ->where('status', $leave->jenis)
            ->delete();

        if ($leave->dokumen) {
            Storage::delete('public/' . $leave->dokumen);
        }

        $leave->delete();
        
        AuditLogger::logCustom('Izin/Cuti', "Menghapus data $jenis karyawan ID $karyawan_id");

        return redirect()->back()->with('status', 'Data ' . $jenis . ' berhasil dihapus.');
    }
    
    /**
     * Memasukkan data izin ke tabel absensi agar tampil di laporan PDF/Excel
     */
    private function syncLeaveToAttendance(Leave $leave)
    {
        $start = Carbon::parse($leave->tanggal_mulai);
        $end = Carbon::parse($leave->tanggal_selesai);
        
        $dates = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            // Hanya masukkan ke absen jika bukan hari minggu (Opsional: sesuaikan kebijakan perusahaan)
            if (!$date->isSunday()) {
                $dates[] = $date->toDateString();
            }
        }
        
        foreach ($dates as $date) {
            // Cek apakah ada record absen di tanggal tsb
            $attendance = Attendance::where('karyawan_id', $leave->karyawan_id)
                ->where('tanggal', $date)
                ->first();
                
            if ($attendance) {
                // Jika sudah ada (mungkin Alpha atau Terlambat), update jadi Sakit/Izin/Cuti
                $attendance->update([
                    'status' => $leave->jenis,
                    'jam_masuk' => null,
                    'jam_pulang' => null,
                    'verifikasi' => 'Sistem (Izin)'
                ]);
            } else {
                // Jika belum ada, buat record baru
                Attendance::create([
                    'karyawan_id' => $leave->karyawan_id,
                    'tanggal' => $date,
                    'jam_masuk' => null,
                    'jam_pulang' => null,
                    'status' => $leave->jenis,
                    'verifikasi' => 'Sistem (Izin)'
                ]);
            }
        }
    }
}
