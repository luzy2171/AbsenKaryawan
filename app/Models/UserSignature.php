<?php

namespace App\Models;

use App\Helpers\PdfImage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class UserSignature extends Model
{
    protected $table = 'user_signatures';

    protected $fillable = [
        'user_id',
        'image_path',
        'nama',
        'jabatan',
    ];

    protected $hidden = ['image_path'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * URL gambar tanda tangan, atau string kosong bila file hilang.
     */
    public function imageUrl(): string
    {
        if (! $this->image_path || ! Storage::disk('public')->exists($this->image_path)) {
            return '';
        }

        return asset('storage/'.$this->image_path);
    }

    /**
     * Data URI gambar tanda tangan untuk template PDF.
     *
     * dompdf tidak bisa memuat URL http://, jadi gambar harus disisipkan
     * langsung sebagai base64.
     */
    public function imageDataUri(): string
    {
        return PdfImage::fromPublic($this->image_path);
    }

    /**
     * Nama yang dicetak di surat, atau string kosong.
     */
    public function namaTtd(): string
    {
        return trim((string) ($this->nama ?: ''));
    }

    public function hapusFile(): void
    {
        // image_path bisa null pada model yang baru dibuat (belum ada file).
        if (! $this->image_path) {
            return;
        }

        if (Storage::disk('public')->exists($this->image_path)) {
            Storage::disk('public')->delete($this->image_path);
        }
    }
}
