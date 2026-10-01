<?php

namespace App\Http\Controllers;

use App\Helpers\CompanyProfile;
use App\Helpers\NomorSurat;
use App\Models\Karyawan;
use App\Models\Leave;
use App\Models\User;
use App\Models\UserSignature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SignatureController extends Controller
{
    /**
     * Halaman unggah tanda tangan approver.
     */
    public function edit()
    {
        $user = auth()->user();

        abort_unless($user->isTrueApprover(), 403, 'Hanya Approver dan Superadmin yang punya tanda tangan.');

        return view('admin.signature', [
            'signature' => $user->signature,
        ]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        abort_unless($user->isTrueApprover(), 403, 'Hanya Approver dan Superadmin yang punya tanda tangan.');

        $request->validate([
            'nama' => 'required|string|max:100',
            'jabatan' => 'nullable|string|max:100',
            'image' => [
                'required',
                'file',
                // webp sengaja tidak diterima: ekstensi GD di server tidak punya
                // imagecreatefromwebp, jadi dompdf gagal merendernya dan kolom
                // tanda tangan keluar kosong.
                'mimes:png,jpg,jpeg',
                'max:1024',
                'dimensions:min_width=120,min_height=60,max_width=2000,max_height=1000',
            ],
        ], [
            'image.required' => 'Gambar tanda tangan wajib diunggah.',
            'image.mimes' => 'Gambar tanda tangan harus berformat PNG, JPG, atau JPEG.',
            'image.max' => 'Ukuran gambar tanda tangan maksimal 1 MB.',
            'image.dimensions' => 'Gambar tanda tangan_resolution minimal 120x60 piksel.',
            'nama.required' => 'Nama penanda tangan wajib diisi.',
        ]);

        $signature = UserSignature::firstOrNew(['user_id' => $user->id]);

        // File baru ditulis dulu. Kalau gagal (mis. folder tidak writable),
        // baris lama tetap utuh dan user diberi tahu.
        $file = $request->file('image');
        $path = $file->store('tanda-tangan', 'public');

        if ($path === false || ! Storage::disk('public')->exists($path)) {
            return redirect()->route('signature.edit')
                ->with('error', 'Gambar tanda tangan gagal disimpan. Periksa izin folder storage/app/public lalu coba lagi.');
        }

        // Ganti file lama bila ada.
        $signature->hapusFile();

        $signature->image_path = $path;
        $signature->nama = $request->nama;
        $signature->jabatan = $request->jabatan;
        $signature->save();

        return redirect()->route('signature.edit')->with('status', 'Tanda tangan berhasil disimpan dan akan otomatis dipakai di Surat Persetujuan dan Surat Penolakan.');
    }

    public function destroy()
    {
        $user = auth()->user();

        abort_unless($user->isTrueApprover(), 403, 'Hanya Approver dan Superadmin yang punya tanda tangan.');

        $signature = UserSignature::where('user_id', $user->id)->first();

        if ($signature) {
            $signature->hapusFile();
            $signature->delete();
        }

        return redirect()->route('signature.edit')->with('status', 'Tanda tangan berhasil dihapus.');
    }

    /**
     * Ubah format nomor surat, mis. {nomor}/{jenis}/{bulan}/{tahun}.
     */
    public function updateFormatNomor(Request $request)
    {
        $user = auth()->user();

        abort_unless($user->isTrueApprover(), 403, 'Hanya Approver dan Superadmin yang dapat mengubah format nomor surat.');

        $request->validate([
            'nomor_surat_format' => 'required|string|max:100',
        ]);

        $format = trim($request->nomor_surat_format);

        // Hanya token yang dikenal yang boleh dipakai.
        preg_match_all('/\{[^}]*\}/', $format, $cocok);
        $takDikenal = array_diff(
            $cocok[0],
            array_keys(NomorSurat::PLACEHOLDERS)
        );

        if ($takDikenal !== []) {
            return back()->with('error', 'Token tidak dikenal: '.implode(', ', $takDikenal).'. Yang boleh dipakai: '.implode(', ', array_keys(NomorSurat::PLACEHOLDERS)));
        }

        if (strpos($format, '{nomor}') === false) {
            return back()->with('error', 'Format wajib memuat {nomor}, jika tidak setiap surat akan bernomor sama.');
        }

        DB::table('settings')->updateOrInsert(
            ['key' => NomorSurat::KEY_FORMAT],
            [
                'value' => $format,
                'description' => 'Format nomor surat izin/sakit/cuti',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return back()->with('status', 'Format nomor surat disimpan. Contoh: '.NomorSurat::susun('Cuti', NomorSurat::berikutnya('Cuti', (int) now()->format('Y')), (int) now()->format('Y')));
    }

    /**
     * Pratinjau surat persetujuan/penolakan dengan data contoh.
     */
    public function preview(Request $request)
    {
        $user = auth()->user();

        abort_unless($user->isTrueApprover(), 403, 'Hanya Approver dan Superadmin yang dapat melihat pratinjau.');

        $request->validate([
            'jenis' => 'nullable|in:Cuti,Sakit,Izin',
            'status' => 'nullable|in:Disetujui,Ditolak',
        ]);

        $jenis = $request->input('jenis', 'Cuti');
        $status = $request->input('status', 'Disetujui');
        $disetujui = $status === 'Disetujui';
        $tahun = (int) now()->format('Y');

        $signature = $user->signature;

        $penyetuju = [[
            'nama' => $signature?->namaTtd() ?: $user->name,
            'jabatan' => $signature?->jabatan ?: $this->jabatanDefault($user),
            'signature' => $signature?->imageDataUri() ?: '',
        ]];

        $html = view('admin.leaves.pdf', [
            'leave' => $this->contohLeave($jenis, $status),
            'nomorSurat' => NomorSurat::susun($jenis, NomorSurat::berikutnya($jenis, $tahun), $tahun),
            'penyetuju' => $penyetuju,
            'disetujui' => $disetujui,
            'lampiranGambar' => '',
            'perusahaan' => CompanyProfile::all(),
        ])->render();

        return response($html)
            ->header('Content-Type', 'text/html; charset=utf-8')
            ->header('X-Robots-Tag', 'noindex');
    }

    /**
     * Pengajuan contoh untuk pratinjau, tidak disimpan ke database.
     */
    private function contohLeave(string $jenis, string $status): Leave
    {
        $leave = new Leave([
            'jenis' => $jenis,
            'tanggal_mulai' => now()->startOfDay(),
            'tanggal_selesai' => now()->startOfDay()->addDays(2),
            'keterangan' => 'Keperluan keluarga yang mendesak,-example keterangan pengajuan.',
            'alasan_tolak' => 'Bentrok dengan cuti tahunan yang sudah disetujui.',
            'status' => $status,
            'created_at' => now(),
        ]);

        $leave->id = 1;
        $leave->setRelation('karyawan', new Karyawan([
            'id_karyawan' => '001',
            'nama' => 'Nama Karyawan',
            'departemen' => 'Departemen',
            'jabatan' => 'Jabatan',
        ]));

        return $leave;
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
}
