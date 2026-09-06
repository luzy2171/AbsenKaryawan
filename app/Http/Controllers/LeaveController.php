<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\Karyawan;
use App\Models\Attendance;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $query = Leave::with(['karyawan', 'approver', 'leaveApprovals.user'])->orderBy('created_at', 'desc');

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
        $requiredApprovals = \Illuminate\Support\Facades\DB::table('settings')->where('key', 'required_approvals')->value('value') ?? 1;

        return view('admin.leaves.index', compact('leaves', 'karyawans', 'requiredApprovals'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->canEdit()) {
            abort(403, 'Akses ditolak. Role Anda tidak dapat menambah pengajuan.');
        }

        $request->validate([
            'karyawan_id' => 'required|exists:karyawans,id',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'jenis' => 'required|in:Cuti,Sakit,Izin',
            'keterangan' => 'nullable|string',
            'dokumen' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048'
        ]);

        $karyawan = Karyawan::findOrFail($request->karyawan_id);
        $lamaHari = Carbon::parse($request->tanggal_mulai)->diffInDays(Carbon::parse($request->tanggal_selesai)) + 1;

        if ($request->jenis === 'Cuti') {
            $sisaCuti = $karyawan->sisaCuti();
            if ($lamaHari > $sisaCuti) {
                return redirect()->back()->with('error', "Gagal! Sisa cuti tahunan {$karyawan->nama} hanya tersisa $sisaCuti hari, sedangkan pengajuan ini meminta $lamaHari hari.")->withInput();
            }
        }

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
            'status' => 'Menunggu',
            'approved_by' => Auth::id()
        ]);

        if ($leave->status === 'Disetujui') {
            $this->syncLeaveToAttendance($leave);
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'create',
            'module' => 'izin',
            'description' => "Menambahkan pengajuan {$leave->jenis} untuk karyawan ID {$leave->karyawan_id} dari tgl {$leave->tanggal_mulai->format('d/m/Y')} s/d {$leave->tanggal_selesai->format('d/m/Y')}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'status' => 'success',
        ]);

        $pesanSukses = 'Pengajuan ' . $request->jenis . ' berhasil ditambahkan.';
        if ($request->jenis === 'Cuti') {
            $pesanSukses .= " Sisa cuti tahunan {$karyawan->nama} sekarang adalah " . $karyawan->sisaCuti() . " hari.";
        }

        return redirect()->back()->with('status', $pesanSukses);
    }

    public function destroy($id)
    {
        $leave = Leave::findOrFail($id);
        $jenis = $leave->jenis;
        $karyawan_id = $leave->karyawan_id;

        if (!auth()->user()->isTrueApprover()) {
            abort(403, 'Akses ditolak. Hanya Approver dan Superadmin yang dapat menghapus pengajuan ini.');
        }

        Attendance::where('karyawan_id', $leave->karyawan_id)
            ->whereBetween('tanggal', [$leave->tanggal_mulai->toDateString(), $leave->tanggal_selesai->toDateString()])
            ->where('status', $leave->jenis)
            ->delete();

        if ($leave->dokumen) {
            Storage::delete('public/' . $leave->dokumen);
        }

        $leave->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'delete',
            'module' => 'izin',
            'description' => "Menghapus data $jenis karyawan ID $karyawan_id",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'status' => 'success',
        ]);

        return redirect()->back()->with('status', 'Data ' . $jenis . ' berhasil dihapus. Laporan absensi telah diperbarui.');
    }

    public function approve($id)
    {
        if (!auth()->user()->isTrueApprover()) {
            abort(403, 'Akses ditolak. Hanya Approver dan Superadmin yang dapat menyetujui pengajuan.');
        }

        $leave = Leave::findOrFail($id);

        if ($leave->status === 'Disetujui') {
            return redirect()->back()->with('error', 'Pengajuan ini sudah disetujui sepenuhnya.');
        }

        $alreadyApproved = \Illuminate\Support\Facades\DB::table('leave_approvals')
            ->where('leave_id', $leave->id)
            ->where('user_id', Auth::id())
            ->exists();

        if ($alreadyApproved) {
            return redirect()->back()->with('error', 'Anda sudah menyetujui pengajuan ini sebelumnya.');
        }

        \Illuminate\Support\Facades\DB::table('leave_approvals')->insert([
            'leave_id' => $leave->id,
            'user_id' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $currentApprovals = \Illuminate\Support\Facades\DB::table('leave_approvals')->where('leave_id', $leave->id)->count();
        $requiredApprovals = \Illuminate\Support\Facades\DB::table('settings')->where('key', 'required_approvals')->value('value') ?? 1;

        if ($currentApprovals >= $requiredApprovals) {
            $leave->status = 'Disetujui';
            $leave->approved_by = Auth::id();
            $leave->save();

            $this->syncLeaveToAttendance($leave);
            $pesan = 'Pengajuan ' . $leave->jenis . ' berhasil disetujui sepenuhnya.';
        } else {
            $pesan = 'Berhasil menyetujui. Masih menunggu ' . ($requiredApprovals - $currentApprovals) . ' persetujuan lagi.';
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'approve',
            'module' => 'izin',
            'description' => "Menyetujui pengajuan {$leave->jenis} untuk karyawan ID {$leave->karyawan_id}. (Approval ke-$currentApprovals dari $requiredApprovals)",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'status' => 'success',
        ]);

        return redirect()->back()->with('status', $pesan);
    }

    private function syncLeaveToAttendance(Leave $leave)
    {
        $start = Carbon::parse($leave->tanggal_mulai);
        $end = Carbon::parse($leave->tanggal_selesai);

        $dates = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if (!$date->isSunday()) {
                $dates[] = $date->toDateString();
            }
        }

        foreach ($dates as $date) {
            $attendance = Attendance::where('karyawan_id', $leave->karyawan_id)
                ->where('tanggal', $date)
                ->first();

            if ($attendance) {
                $attendance->update([
                    'status' => $leave->jenis,
                    'jam_masuk' => null,
                    'jam_pulang' => null,
                    'verifikasi' => 'Sistem (Izin)'
                ]);
            } else {
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
