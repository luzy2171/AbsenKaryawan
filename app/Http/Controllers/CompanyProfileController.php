<?php

namespace App\Http\Controllers;

use App\Helpers\AuditLogger;
use App\Helpers\CompanyProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CompanyProfileController extends Controller
{
    /**
     * Halaman pengaturan profil perusahaan.
     */
    public function index()
    {
        $profil = CompanyProfile::all();

        return view('admin.company-profile', compact('profil'));
    }

    /**
     * Pratinjau kop surat dalam ukuran A4 asli.
     */
    public function previewKop()
    {
        return view('admin.kop-preview');
    }

    /**
     * Simpan nama PT + data kop.
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:191',
            'subtitle' => 'nullable|string|max:191',
            'alamat' => 'nullable|string|max:500',
            'telepon' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:191',
            'website' => 'nullable|string|max:191',
            'nama_ttd' => 'nullable|string|max:191',
            'jabatan_ttd' => 'nullable|string|max:191',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp,svg|max:2048',
        ]);

        $lama = CompanyProfile::all();

        CompanyProfile::put([
            'nama' => $data['nama'],
            'subtitle' => $data['subtitle'] ?? '',
            'alamat' => $data['alamat'] ?? '',
            'telepon' => $data['telepon'] ?? '',
            'email' => $data['email'] ?? '',
            'website' => $data['website'] ?? '',
            'nama_ttd' => $data['nama_ttd'] ?? '',
            'jabatan_ttd' => $data['jabatan_ttd'] ?? '',
        ]);

        if ($request->hasFile('logo')) {
            // Kalau file gagal tersimpan (permission, disk penuh, dll) jangan
            // simpan path-nya: setting yang menunjuk file hilang bikin logo
            // hilang tanpa ada pesan error sama sekali.
            $disimpan = $this->simpanLogo($request->file('logo'), $lama['logo'] ?? '');

            if ($disimpan === null) {
                CompanyProfile::put(['logo' => $lama['logo'] ?? '']);

                return redirect()->route('company-profile.index')->with(
                    'error',
                    'Profil tersimpan, tetapi file logo gagal ditulis ke storage. '
                    .'Periksa izin folder storage/app/public lalu upload ulang logo.'
                );
            }
        }

        AuditLogger::logCustom(
            'update',
            'Memperbarui profil perusahaan (nama PT & kop laporan)',
            'perusahaan',
            'success',
            [
                'nama' => $data['nama'],
                'alamat' => $data['alamat'] ?? '',
                'telepon' => $data['telepon'] ?? '',
                'email' => $data['email'] ?? '',
                'website' => $data['website'] ?? '',
                'logo' => $request->hasFile('logo') ? 'diperbarui' : ($lama['logo'] ?? 'tidak ada'),
            ],
            [
                'nama' => $lama['nama'] ?? '',
                'alamat' => $lama['alamat'] ?? '',
                'telepon' => $lama['telepon'] ?? '',
                'email' => $lama['email'] ?? '',
                'website' => $lama['website'] ?? '',
                'logo' => $lama['logo'] ?? 'tidak ada',
            ]
        );

        return redirect()->route('company-profile.index')
            ->with('success', 'Profil perusahaan berhasil disimpan. Kop laporan dan sidebar ikut diperbarui.');
    }

    /**
     * Hapus logo perusahaan dan kembali ke logo bawaan.
     */
    public function destroyLogo()
    {
        $lama = CompanyProfile::all();

        if (! empty($lama['logo'])) {
            Storage::disk('public')->delete($lama['logo']);
        }

        CompanyProfile::put(['logo' => '']);

        AuditLogger::logCustom(
            'delete',
            'Menghapus logo perusahaan dan kembali ke logo bawaan',
            'perusahaan',
            'success',
            ['logo' => 'tidak ada'],
            ['logo' => $lama['logo'] ?? 'tidak ada']
        );

        return redirect()->route('company-profile.index')
            ->with('success', 'Logo perusahaan telah dihapus.');
    }

    /**
     * Simpan file logo baru dan hapus logo lama.
     *
     * @return string|null Path tersimpan, atau null bila gagal ditulis.
     */
    private function simpanLogo($file, string $logoLama): ?string
    {
        Storage::disk('public')->makeDirectory(CompanyProfile::LOGO_DIR);

        $nama = 'logo-'.time().'-'.Str::random(6).'.'.strtolower($file->getClientOriginalExtension());
        $path = CompanyProfile::LOGO_DIR.'/'.$nama;

        $ok = false;

        try {
            $ok = (bool) $file->storeAs(CompanyProfile::LOGO_DIR, $nama, 'public');
        } catch (\Throwable $e) {
            report($e);
            $ok = false;
        }

        // storeAs() bisa return true padahal file tidak ada (mis. permission
        // folder), jadi pastikan filenya benar-benar muncul di disk.
        if (! $ok || ! Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);

            return null;
        }

        if ($logoLama !== '') {
            Storage::disk('public')->delete($logoLama);
        }

        CompanyProfile::put(['logo' => $path]);

        return $path;
    }
}
