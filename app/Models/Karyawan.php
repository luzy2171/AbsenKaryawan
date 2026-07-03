<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Attendance; // <-- Tambahkan baris import ini jika belum ada

class Karyawan extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_karyawan', 'nama', 'departemen', 'jabatan', 'status'
    ];

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'karyawan_id');
    }
}
