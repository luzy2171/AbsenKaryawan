<?php

namespace App\Models;

use App\Helpers\NomorSurat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Leave extends Model
{
    use HasFactory;

    protected $fillable = [
        'karyawan_id',
        'nomor_surat',
        'tanggal_mulai',
        'tanggal_selesai',
        'jenis', // Cuti, Sakit, Izin
        'keterangan',
        'dokumen', // Optional attachment
        'status', // Menunggu, Disetujui, Ditolak
        'approved_by',
        'alasan_tolak',
        'ditolak_at',
        'ditolak_by',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'ditolak_at' => 'datetime',
    ];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejecter()
    {
        return $this->belongsTo(User::class, 'ditolak_by');
    }

    /**
     * Berapa hari kerja (Senin-Sabtu) dalam pengajuan ini.
     */
    public function jumlahHari(): int
    {
        $mulai = Carbon::parse($this->tanggal_mulai);
        $selesai = Carbon::parse($this->tanggal_selesai);

        $hari = 0;
        for ($d = $mulai->copy(); $d->lte($selesai); $d->addDay()) {
            if (! $d->isSunday()) {
                $hari++;
            }
        }

        return $hari;
    }

    public function leaveApprovals()
    {
        return $this->hasMany(LeaveApproval::class);
    }

    /**
     * User yang terakhir memberi keputusan (menyetujui atau menolak).
     */
    public function pemutus()
    {
        if ($this->status === 'Ditolak') {
            return $this->rejecter;
        }

        if ($this->status === 'Disetujui') {
            return $this->approver;
        }

        return null;
    }

    /**
     * Waktu pengajuan diputuskan.
     *
     * Jangan pakai updated_at: kolom itu ikut berubah setiap kali data lain
     * diubah, sehingga tanggal di surat bisa bergeser.
     */
    public function waktuKeputusan(): ?Carbon
    {
        if ($this->status === 'Ditolak' && $this->ditolak_at) {
            return Carbon::parse($this->ditolak_at);
        }

        if ($this->status === 'Disetujui') {
            // Persetujuan terakhir dicatat di leave_approvals.
            $terakhir = $this->relationLoaded('leaveApprovals')
                ? $this->leaveApprovals->sortBy('created_at')->last()
                : $this->leaveApprovals()->orderByDesc('created_at')->first();

            if ($terakhir && $terakhir->created_at) {
                return Carbon::parse($terakhir->created_at);
            }

            return null;
        }

        return null;
    }

    /**
     * Nama file lampiran (dokumen pendukung) yang diunggah.
     */
    public function namaLampiran(): ?string
    {
        if (! $this->dokumen) {
            return null;
        }

        return basename($this->dokumen);
    }

    /**
     * Nomor surat yang dicetak di kop.
     *
     * Dipakai nomor yang diinput admin/approver kalau ada, kalau tidak memakai
     * format otomatis dari nomor urut pengajuan.
     */
    public function nomorSurat(): string
    {
        return NomorSurat::untuk($this);
    }
}
