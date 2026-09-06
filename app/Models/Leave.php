<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    use HasFactory;

    protected $fillable = [
        'karyawan_id',
        'tanggal_mulai',
        'tanggal_selesai',
        'jenis', // Cuti, Sakit, Izin
        'keterangan',
        'dokumen', // Optional attachment
        'status', // Menunggu, Disetujui, Ditolak
        'approved_by'
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function leaveApprovals()
    {
        return $this->hasMany(\App\Models\LeaveApproval::class);
    }
}
