<?php

namespace App\Http\Controllers;

use App\Helpers\CompanyProfile;
use App\Helpers\NomorSurat;
use App\Helpers\PdfImage;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Karyawan;
use App\Models\Leave;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $query = Leave::with(['karyawan', 'approver', 'leaveApprovals.user'])->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('karyawan', function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%");
            });
        }

        if ($request->filled('bulan') && $request->filled('tahun')) {
            $start = Carbon::create($request->tahun, $request->bulan, 1)->startOfMonth();
            $end = Carbon::create($request->tahun, $request->bulan, 1)->endOfMonth();

            $query->where(function ($q) use ($start, $end) {
                $q->whereBetween('tanggal_mulai', [$start, $end])
                    ->orWhereBetween('tanggal_selesai', [$start, $end]);
            });
        }

        $leaves = $query->paginate(15);
        $karyawans = Karyawan::where('status', 'Aktif')->orderBy('nama')->get();
        $requiredApprovals = DB::table('settings')->where('key', 'required_approvals')->value('value') ?? 1;

        return view('admin.leaves.index', compact('leaves', 'karyawans', 'requiredApprovals'));
    }

    public function store(Request $request)
    {
        if (! auth()->user()->canEdit()) {
            abort(403, 'Akses ditolak. Role Anda tidak dapat menambah pengajuan.');
        }

        $request->validate([
            'karyawan_id' => 'required|exists:karyawans,id',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'jenis' => 'required|in:Cuti,Sakit,Izin',
            'keterangan' => 'nullable|string',
            'dokumen' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
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
            $filename = time().'_'.$file->getClientOriginalName();
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
            'approved_by' => Auth::id(),
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

        $pesanSukses = 'Pengajuan '.$request->jenis.' berhasil ditambahkan.';
        if ($request->jenis === 'Cuti') {
            $pesanSukses .= " Sisa cuti tahunan {$karyawan->nama} sekarang adalah ".$karyawan->sisaCuti().' hari.';
        }

        return redirect()->back()->with('status', $pesanSukses);
    }

    public function destroy($id)
    {
        $leave = Leave::findOrFail($id);

        if ($leave->status === 'Disetujui' && ! auth()->user()->isTrueApprover()) {
            return back()->with('error', 'Gagal menghapus! Pengajuan Cuti/Izin/Sakit yang sudah disetujui hanya dapat dihapus oleh Approver/Superadmin.');
        }

        $jenis = $leave->jenis;
        $karyawan_id = $leave->karyawan_id;

        if (! auth()->user()->isApprover()) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk menghapus pengajuan ini.');
        }

        Attendance::where('karyawan_id', $leave->karyawan_id)
            ->whereBetween('tanggal', [$leave->tanggal_mulai->toDateString(), $leave->tanggal_selesai->toDateString()])
            ->where('status', $leave->jenis)
            ->delete();

        if ($leave->dokumen) {
            Storage::delete('public/'.$leave->dokumen);
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

        return redirect()->back()->with('status', 'Data '.$jenis.' berhasil dihapus. Laporan absensi telah diperbarui.');
    }

    public function approve($id)
    {
        if (! auth()->user()->isTrueApprover()) {
            abort(403, 'Akses ditolak. Hanya Approver dan Superadmin yang dapat menyetujui pengajuan.');
        }

        $leave = Leave::findOrFail($id);

        if ($leave->status === 'Disetujui') {
            return redirect()->back()->with('error', 'Pengajuan ini sudah disetujui sepenuhnya.');
        }

        $alreadyApproved = DB::table('leave_approvals')
            ->where('leave_id', $leave->id)
            ->where('user_id', Auth::id())
            ->exists();

        if ($alreadyApproved) {
            return redirect()->back()->with('error', 'Anda sudah menyetujui pengajuan ini sebelumnya.');
        }

        DB::table('leave_approvals')->insert([
            'leave_id' => $leave->id,
            'user_id' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $currentApprovals = DB::table('leave_approvals')->where('leave_id', $leave->id)->count();
        $requiredApprovals = DB::table('settings')->where('key', 'required_approvals')->value('value') ?? 1;

        if ($currentApprovals >= $requiredApprovals) {
            $leave->status = 'Disetujui';
            $leave->approved_by = Auth::id();
            $leave->save();

            // Nomor surat resmi saat keputusan sudah keluar.
            NomorSurat::pasifkan($leave);

            $this->syncLeaveToAttendance($leave);
            $pesan = 'Pengajuan '.$leave->jenis.' berhasil disetujui sepenuhnya.';
        } else {
            $pesan = 'Berhasil menyetujui. Masih menunggu '.($requiredApprovals - $currentApprovals).' persetujuan lagi.';
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

    /**
     * Tolak pengajuan izin/sakit/cuti.
     */
    public function reject(Request $request, $id)
    {
        if (! auth()->user()->isTrueApprover()) {
            abort(403, 'Akses ditolak. Hanya Approver dan Superadmin yang dapat menolak pengajuan.');
        }

        $request->validate([
            'alasan_tolak' => 'required|string|max:500',
        ]);

        $leave = Leave::with('karyawan')->findOrFail($id);

        if ($leave->status === 'Disetujui') {
            return redirect()->back()->with('error', 'Pengajuan ini sudah disetujui sehingga tidak bisa ditolak.');
        }

        if ($leave->status === 'Ditolak') {
            return redirect()->back()->with('error', 'Pengajuan ini sudah ditolak sebelumnya.');
        }

        // Nomor surat resmi saat keputusan sudah keluar, termasuk surat penolakan.
        NomorSurat::pasifkan($leave);

        $leave->update([
            'status' => 'Ditolak',
            'alasan_tolak' => $request->alasan_tolak,
            'ditolak_at' => now(),
            'ditolak_by' => auth()->id(),
        ]);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'reject',
            'module' => 'izin',
            'description' => "Menolak pengajuan {$leave->jenis} untuk {$leave->karyawan->nama}. Alasan: {$request->alasan_tolak}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'status' => 'success',
        ]);

        return redirect()->back()->with('status', "Pengajuan {$leave->jenis} {$leave->karyawan->nama} berhasil ditolak.");
    }

    /**
     * Unduh PDF persetujuan / penolakan izin-sakit-cuti.
     *
     * Surat terbit untuk pengajuan yang sudah diputuskan:
     *   - Disetujui : menampilkan semua approver yang menyetujui + tanda tangannya.
     *   - Ditolak   : menampilkan penolak, alasan penolakan, dan tanda tangannya.
     *   - Menunggu  : belum ada keputusan sehingga tidak ada surat.
     */
    public function pdf($id)
    {
        $leave = Leave::with([
            'karyawan',
            'approver.signature',
            'rejecter.signature',
            'leaveApprovals.user.signature',
        ])->findOrFail($id);

        if ($leave->status === 'Menunggu') {
            return redirect()->back()->with('error', 'Surat belum bisa dibuat. Pengajuan ini masih menunggu persetujuan.');
        }

        $disetujui = $leave->status === 'Disetujui';

        $penyetuju = $disetujui
            ? $this->penyetujuPersetujuan($leave)
            : $this->penyetujuPenolakan($leave);

        $html = view('admin.leaves.pdf', [
            'leave' => $leave,
            'nomorSurat' => $leave->nomorSurat(),
            'penyetuju' => $penyetuju,
            'disetujui' => $disetujui,
            'lampiranGambar' => PdfImage::fromPublic($leave->dokumen),
            'perusahaan' => CompanyProfile::all(),
        ])->render();

        $namaKaryawan = preg_replace('/[^A-Za-z0-9]+/', '-', $leave->karyawan->nama ?? 'karyawan');
        $berkas = sprintf(
            'Surat-%s-%s-%s.pdf',
            $disetujui ? 'Persetujuan' : 'Penolakan',
            $leave->jenis,
            trim((string) $namaKaryawan, '-')
        );

        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait');

        return $pdf->download($berkas);
    }

    /**
     * Daftar penanda tangan untuk surat persetujuan.
     *
     * Semua approver yang menyetujui dicetak karena surat ini sah untuk semua
     * approver. Bila data approval tidak ada (mis. diapprove manual) fallback
     * ke user pada leaves.approved_by.
     *
     * @return array<int, array{nama: string, jabatan: string, signature: string}>
     */
    private function penyetujuPersetujuan(Leave $leave): array
    {
        $users = $leave->leaveApprovals->pluck('user')->filter();

        if ($users->isEmpty() && $leave->approver) {
            $users = collect([$leave->approver]);
        }

        if ($users->isEmpty()) {
            return [];
        }

        return $users->map(fn ($user) => $this->barisPenandaTangan($user))->values()->all();
    }

    /**
     * Daftar penanda tangan untuk surat penolakan.
     *
     * @return array<int, array{nama: string, jabatan: string, signature: string}>
     */
    private function penyetujuPenolakan(Leave $leave): array
    {
        if (! $leave->rejecter) {
            return [];
        }

        return [$this->barisPenandaTangan($leave->rejecter)];
    }

    /**
     * Satu baris tanda tangan: nama, jabatan, dan gambar (data URI) bila ada.
     *
     * @return array{nama: string, jabatan: string, signature: string}
     */
    private function barisPenandaTangan(?User $user): array
    {
        $ttd = $user?->signature;

        return [
            'nama' => $ttd?->namaTtd() ?: ($user->name ?? '-'),
            'jabatan' => $ttd?->jabatan ?: $this->jabatanDefault($user),
            'signature' => $ttd?->imageDataUri() ?: '',
        ];
    }

    private function jabatanDefault(?User $user): string
    {
        return match ($user?->role) {
            'superadmin' => 'Superadmin',
            'admin' => 'Administrator',
            'approval' => 'Approver',
            default => 'Pemberi Persetujuan',
        };
    }

    private function syncLeaveToAttendance(Leave $leave)
    {
        $statusMap = [
            'Cuti' => 'Cuti',
            'Sakit' => 'Sakit',
            'Izin' => 'Izin',
        ];

        $attendanceStatus = $statusMap[$leave->jenis] ?? 'Izin';

        $start = Carbon::parse($leave->tanggal_mulai);
        $end = Carbon::parse($leave->tanggal_selesai);

        $dates = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if (! $date->isSunday()) {
                $dates[] = $date->toDateString();
            }
        }

        foreach ($dates as $date) {
            $attendance = Attendance::where('karyawan_id', $leave->karyawan_id)
                ->where('tanggal', $date)
                ->first();

            if ($attendance) {
                $attendance->update([
                    'status' => $attendanceStatus,
                    'jam_masuk' => null,
                    'jam_pulang' => null,
                    'verifikasi' => 'Sistem (Izin)',
                ]);
            } else {
                Attendance::create([
                    'karyawan_id' => $leave->karyawan_id,
                    'tanggal' => $date,
                    'jam_masuk' => null,
                    'jam_pulang' => null,
                    'status' => $attendanceStatus,
                    'verifikasi' => 'Sistem (Izin)',
                ]);
            }
        }
    }
}
