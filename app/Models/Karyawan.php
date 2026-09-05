<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Karyawan extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_karyawan', 'nama', 'departemen', 'jabatan', 'status', 'jatah_cuti_tahunan'
    ];

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'karyawan_id');
    }

    public function lembur()
    {
        return $this->hasMany(Lembur::class, 'karyawan_id');
    }
    
    public function leaves()
    {
        return $this->hasMany(Leave::class, 'karyawan_id');
    }

    public function sisaCuti()
    {
        $cutiTerpakai = $this->leaves()
            ->where('jenis', 'Cuti')
            ->where('status', 'Disetujui')
            ->whereYear('tanggal_mulai', date('Y'))
            ->get()
            ->sum(function ($leave) {
                return $leave->tanggal_mulai->diffInDays($leave->tanggal_selesai) + 1;
            });
            
        return max(0, $this->jatah_cuti_tahunan - $cutiTerpakai);
    }
}
