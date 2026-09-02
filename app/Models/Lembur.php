<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lembur extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'karyawan_id',
        'tanggal',
        'jam_lembur_mulai',
        'jam_lembur_selesai',
        'lama_lembur',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'lama_lembur' => 'integer',
    ];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id');
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class, 'attendance_id');
    }
}