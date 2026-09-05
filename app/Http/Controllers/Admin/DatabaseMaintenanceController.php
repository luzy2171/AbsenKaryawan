<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Lembur;
use App\Helpers\AuditLogger;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DatabaseMaintenanceController extends Controller
{
    public function index(Request $request)
    {
        $tahunDipilih = $request->input('tahun', date('Y'));
        
        // Dapatkan daftar tahun yang tersedia di database absensi
        $tahunTersedia = Attendance::selectRaw('YEAR(tanggal) as tahun')
                                  ->groupBy('tahun')
                                  ->orderBy('tahun', 'desc')
                                  ->pluck('tahun')
                                  ->toArray();
                                  
        // Pastikan tahun ini dan tahun lalu selalu ada di dropdown
        $tahunTersedia = array_unique(array_merge([date('Y'), date('Y') - 1], $tahunTersedia));
        rsort($tahunTersedia);

        $rekapBulanan = [];
        
        // Looping 12 bulan untuk tahun yang dipilih
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $tanggalMulai = Carbon::create($tahunDipilih, $bulan, 1)->startOfMonth();
            $tanggalSelesai = Carbon::create($tahunDipilih, $bulan, 1)->endOfMonth();
            
            $jumlahAbsensi = Attendance::whereBetween('tanggal', [$tanggalMulai->toDateString(), $tanggalSelesai->toDateString()])->count();
            $jumlahLembur = Lembur::whereBetween('tanggal', [$tanggalMulai->toDateString(), $tanggalSelesai->toDateString()])->count();
            
            if ($jumlahAbsensi > 0 || $jumlahLembur > 0 || $tanggalMulai->isPast()) {
                $rekapBulanan[] = [
                    'bulan_angka' => $bulan,
                    'bulan_nama' => $tanggalMulai->translatedFormat('F'),
                    'tahun' => $tahunDipilih,
                    'jumlah_absensi' => $jumlahAbsensi,
                    'jumlah_lembur' => $jumlahLembur,
                    'periode_start' => $tanggalMulai->toDateString(),
                    'periode_end' => $tanggalSelesai->toDateString(),
                ];
            }
        }

        // Statistik keseluruhan DB
        $totalAbsensiDB = Attendance::count();
        $totalLemburDB = Lembur::count();
        $dbSizeQuery = DB::select("SELECT 
            round(((data_length + index_length) / 1024 / 1024), 2) as size_mb
            FROM information_schema.TABLES 
            WHERE table_schema = ? AND table_name = 'attendances'", [env('DB_DATABASE')]);
            
        $dbSizeMB = $dbSizeQuery[0]->size_mb ?? 0;

        return view('admin.maintenance.index', compact('rekapBulanan', 'tahunDipilih', 'tahunTersedia', 'totalAbsensiDB', 'totalLemburDB', 'dbSizeMB'));
    }

    public function purgeData(Request $request)
    {
        $request->validate([
            'bulan' => 'required|numeric|min:1|max:12',
            'tahun' => 'required|numeric|min:2000',
            'confirm_text' => 'required|in:HAPUS PERMANEN'
        ]);

        $tanggalMulai = Carbon::create($request->tahun, $request->bulan, 1)->startOfMonth()->toDateString();
        $tanggalSelesai = Carbon::create($request->tahun, $request->bulan, 1)->endOfMonth()->toDateString();
        $namaBulan = Carbon::create($request->tahun, $request->bulan, 1)->translatedFormat('F Y');

        // Lakukan penghapusan
        DB::beginTransaction();
        try {
            $deletedLembur = Lembur::whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai])->delete();
            $deletedAbsen = Attendance::whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai])->delete();
            
            DB::commit();

            AuditLogger::logCustom('Database Maintenance', "Menghapus manual $deletedAbsen data absensi dan $deletedLembur data lembur periode $namaBulan.");

            return back()->with('status', "Berhasil menghapus permanen $deletedAbsen catatan absen dan $deletedLembur catatan lembur untuk periode $namaBulan.");
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Terjadi kesalahan saat menghapus data: ' . $e->getMessage());
        }
    }
}