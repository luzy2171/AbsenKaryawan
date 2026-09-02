<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function lembur()
    {
        return $this->hasMany(Lembur::class, 'karyawan_id');
    }
}
