<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fingerprint extends Model
{
    use HasFactory;

    protected $fillable = [
        'karyawan_id',
        'machine_id',
        'finger_id',
        'template',
        'size',
    ];

    protected $casts = [
        'finger_id' => 'integer',
        'size' => 'integer',
    ];

    /**
     * Template disimpan sebagai base64, tapi hanya ditampilkan seperlunya.
     */
    protected $hidden = ['template'];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class);
    }

    public function machine()
    {
        return $this->belongsTo(MachineStatus::class, 'machine_id');
    }

    /**
     * Nama jari berdasarkan penomoran mesin (0..9).
     */
    public function namaJari(): string
    {
        return [
            0 => 'Jari Kiri Telunjuk',
            1 => 'Jari Kiri Jari Tengah',
            2 => 'Jari Kiri Jari Manis',
            3 => 'Jari Kiri Jari Telunjuk',
            4 => 'Jari Kiri Jari Kelingking',
            5 => 'Jari Kiri Jari Bisa',
            6 => 'Jari Kanan Jari Bisa',
            7 => 'Jari Kanan Jari Kelingking',
            8 => 'Jari Kanan Jari Telunjuk',
            9 => 'Jari Kanan Jari Manis',
        ][$this->finger_id] ?? 'Jari '.$this->finger_id;
    }
}
