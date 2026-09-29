<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Karyawan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Menyusun rekap Laporan Kehadiran & Jam Kerja per karyawan.
 *
 * Satu baris hasil = satu karyawan, berisi total hari kerja, total jam kerja,
 * rata-rata jam per hari, riport status kehadiran, dan catatan operasional.
 */
class LaporanKehadiranService
{
    /**
     * Ambil standar jam kerja dari tabel settings.
     */
    public function getStandarJamKerja(): array
    {
        $settings = DB::table('settings')
            ->whereIn('key', ['jam_masuk', 'jam_pulang', 'toleransi_terlambat', 'jam_lembur_mulai'])
            ->pluck('value', 'key');

        $jamMasuk = $settings['jam_masuk'] ?? '08:00';
        $jamPulang = $settings['jam_pulang'] ?? '17:00';

        return [
            'jam_masuk' => substr($jamMasuk, 0, 5),
            'jam_pulang' => substr($jamPulang, 0, 5),
            'toleransi_terlambat' => (int) ($settings['toleransi_terlambat'] ?? 0),
            'jam_lembur_mulai' => substr($settings['jam_lembur_mulai'] ?? $jamPulang, 0, 5),
            'durasi_standar_jam' => $this->selisihMenit(
                Carbon::createFromFormat('H:i', substr($jamMasuk, 0, 5)),
                Carbon::createFromFormat('H:i', substr($jamPulang, 0, 5))
            ) / 60,
        ];
    }

    /**
     * Susun rekap kehadiran.
     *
     * @param  string  $mulai       Tanggal awal (Y-m-d)
     * @param  string  $selesai     Tanggal akhir (Y-m-d)
     * @param  array   $karyawanIds Batas ID karyawan; kosong = semua
     */
    public function rekap(string $mulai, string $selesai, array $karyawanIds = []): array
    {
        $mulai = Carbon::parse($mulai)->startOfDay();
        $selesai = Carbon::parse($selesai)->endOfDay();

        $standar = $this->getStandarJamKerja();

        $query = Karyawan::query()->orderByRaw('CAST(id_karyawan AS UNSIGNED) asc');
        if (!empty($karyawanIds)) {
            $query->whereIn('id', $karyawanIds);
        }
        $karyawans = $query->get();

        $semuaAbsensi = Attendance::with('lembur')
            ->whereBetween('tanggal', [$mulai->toDateString(), $selesai->toDateString()])
            ->get();

        $absensi = $semuaAbsensi->groupBy('karyawan_id');

        $jumlahHariEfektif = $this->hitungJumlahHariEfektif($mulai, $selesai, $semuaAbsensi);
        $baris = [];

        foreach ($karyawans as $karyawan) {
            $baris[] = $this->hitungPerKaryawan(
                $karyawan,
                $absensi->get($karyawan->id, collect()),
                $standar,
                $jumlahHariEfektif
            );
        }

        return [
            'baris' => $baris,
            'standar' => $standar,
            'jumlah_hari_efektif' => $jumlahHariEfektif,
            'total_karyawan' => count($baris),
        ];
    }

    /**
     * Susun rincian kehadiran satu karyawan per hari.
     *
     * Menghasilkan daftar seluruh hari kerja dalam periode. Hari kerja yang
     * tidak memiliki catatan absensi ditandai sebagai "Tanpa Absensi" (Alpha),
     * sehingga selisih antara hari efektif dan hari kerja dapat terlihat.
     *
     * @param  int    $karyawanId
     * @param  string $mulai
     * @param  string $selesai
     */
    public function detail(int $karyawanId, string $mulai, string $selesai): array
    {
        $mulai = Carbon::parse($mulai)->startOfDay();
        $selesai = Carbon::parse($selesai)->endOfDay();

        $karyawan = Karyawan::find($karyawanId);

        if (!$karyawan) {
            return [
                'karyawan' => null,
                'baris' => [],
                'ringkasan' => [],
                'standar' => [],
                'jumlah_hari_efektif' => 0,
                'total_karyawan' => 0,
            ];
        }

        $standar = $this->getStandarJamKerja();

        $absensi = Attendance::with('lembur')
            ->where('karyawan_id', $karyawanId)
            ->whereBetween('tanggal', [$mulai->toDateString(), $selesai->toDateString()])
            ->get()
            ->keyBy(fn($a) => Carbon::parse($a->tanggal)->toDateString());

        // Basis hari kerja efektif mengikuti seluruh karyawan agar konsisten
        // dengan rekap, bukan hanya data karyawan ini.
        $absensiPeriode = Attendance::whereBetween('tanggal', [$mulai->toDateString(), $selesai->toDateString()])
            ->get(['tanggal', 'status']);

        $jumlahHariEfektif = $this->hitungJumlahHariEfektif($mulai, $selesai, $absensiPeriode);

        $baris = [];
        $tanggal = $mulai->copy();

        while ($tanggal->lessThanOrEqualTo($selesai)) {
            // Minggu tidak menjadi hari kerja
            if ($tanggal->dayOfWeek !== Carbon::SUNDAY) {
                $baris[] = $this->susunBarisHarian(
                    $tanggal->copy(),
                    $absensi->get($tanggal->toDateString()),
                    $standar
                );
            }
            $tanggal->addDay();
        }

        return [
            'karyawan' => $karyawan,
            'baris' => $baris,
            'standar' => $standar,
            'jumlah_hari_efektif' => $jumlahHariEfektif,
            'total_karyawan' => 1,
        ];
    }

    /**
     * Susun satu baris rincian harian.
     */
    private function susunBarisHarian(Carbon $tanggal, $att, array $standar): array
    {
        $baris = [
            'tanggal' => $tanggal->toDateString(),
            'tanggal_label' => $tanggal->format('d/m/Y'),
            'nama_hari' => $this->namaHari($tanggal),
            'jam_masuk' => null,
            'jam_pulang' => null,
            'durasi_jam' => 0.0,
            'lembur_jam' => 0.0,
            'menit_terlambat' => 0,
            'status' => 'Alpha',
            'kategori' => 'danger',
            'keterangan' => 'Tanpa absensi pada hari kerja ini',
        ];

        // Hari Minggu: di luar zona penilaian kehadiran
        if ($tanggal->dayOfWeek === Carbon::SUNDAY) {
            $baris['status'] = 'Libur';
            $baris['kategori'] = 'secondary';
            $baris['keterangan'] = 'Hari Minggu';
            return $baris;
        }

        if (!$att) {
            return $baris;
        }

        $baris['status'] = $att->status ?: 'Alpha';
        $baris['jam_masuk'] = $att->jam_masuk ? $this->jam($att->jam_masuk) : null;
        $baris['jam_pulang'] = $att->jam_pulang ? $this->jam($att->jam_pulang) : null;

        if (in_array($baris['status'], ['Hadir', 'Terlambat'], true)) {
            $durasi = $this->selisihMenit(
                $baris['jam_masuk'] ? Carbon::createFromFormat('H:i:s', $baris['jam_masuk']) : null,
                $baris['jam_pulang'] ? Carbon::createFromFormat('H:i:s', $baris['jam_pulang']) : null
            ) / 60;
            $baris['durasi_jam'] = round($durasi, 1);
            $baris['kategori'] = 'success';
            $baris['keterangan'] = 'Kehadiran tepat waktu';

            if ($baris['status'] === 'Terlambat') {
                $telat = $this->menitKeterlambatan($baris['jam_masuk'], $standar);
                $baris['menit_terlambat'] = $telat;
                $baris['kategori'] = 'warning';
                $baris['keterangan'] = $telat > 0
                    ? 'Terlambat ' . $this->formatMenit($telat)
                    : 'Terlambat di luar jam toleransi';
            }

            if (!$baris['jam_pulang']) {
                $baris['kategori'] = 'warning';
                $baris['keterangan'] .= ' (belum ada scan pulang)';
            }

            if ($att->lembur) {
                $baris['lembur_jam'] = round((int) $att->lembur->lama_lembur / 60, 1);
                $baris['kategori'] = 'warning';
                $baris['keterangan'] .= '; lembur ' . $this->formatMenit((int) $att->lembur->lama_lembur);
            }
        } else {
            $baris['kategori'] = in_array($baris['status'], ['Cuti', 'Izin', 'Sakit'], true) ? 'warning' : 'danger';
            $baris['keterangan'] = match ($baris['status']) {
                'Cuti' => 'Cuti (tidak menambah jam kerja)',
                'Izin' => 'Izin (tidak menambah jam kerja)',
                'Sakit' => 'Sakit (tidak menambah jam kerja)',
                default => 'Tanpa keterangan',
            };
        }

        return $baris;
    }

    /**
     * Nama hari dalam bahasa Indonesia.
     */
    private function namaHari(Carbon $tanggal): string
    {
        return [
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
        ][$tanggal->dayOfWeek];
    }

    /**
     * Format durasi menit menjadi jam/menit yang mudah dibaca.
     */
    private function formatMenit(int $menit): string
    {
        if ($menit < 60) {
            return $menit . ' menit';
        }

        $jam = intdiv($menit, 60);
        $sisa = $menit % 60;

        return $sisa > 0 ? $jam . ' jam ' . $sisa . ' menit' : $jam . ' jam';
    }

    /**
     * Hitung rekap satu karyawan.
     */
    private function hitungPerKaryawan(Karyawan $karyawan, $absensi, array $standar, int $jumlahHariEfektif): array
    {
        $totalHariKerja = 0;
        $totalMenit = 0;
        $totalLemburMenit = 0;
        $jumlahTerlambat = 0;
        $totalMenitTerlambat = 0;
        $izin = 0;
        $sakit = 0;
        $cuti = 0;
        $alpha = 0;

        foreach ($absensi as $att) {
            $status = $att->status;

            if (in_array($status, ['Izin', 'Sakit', 'Cuti', 'Alpha'], true)) {
                match ($status) {
                    'Izin' => $izin++,
                    'Sakit' => $sakit++,
                    'Cuti' => $cuti++,
                    default => $alpha++,
                };
                continue;
            }

            $totalHariKerja++;

            $menit = $this->selisihMenit(
                $att->jam_masuk ? Carbon::createFromFormat('H:i:s', $this->jam($att->jam_masuk)) : null,
                $att->jam_pulang ? Carbon::createFromFormat('H:i:s', $this->jam($att->jam_pulang)) : null
            );
            $totalMenit += $menit;

            if ($att->lembur) {
                $totalLemburMenit += (int) $att->lembur->lama_lembur;
            }

            if ($status === 'Terlambat') {
                $jumlahTerlambat++;
                $telat = $this->menitKeterlambatan($att->jam_masuk, $standar);
                if ($telat > 0) {
                    $totalMenitTerlambat += $telat;
                }
            }
        }

        $rataRataJam = $totalHariKerja > 0 ? round(($totalMenit / 60) / $totalHariKerja, 1) : 0.0;
        $persentaseHadir = $jumlahHariEfektif > 0
            ? round(($totalHariKerja / $jumlahHariEfektif) * 100, 1)
            : 0.0;

        $riport = $this->susunRiport([
            'total_hari_kerja' => $totalHariKerja,
            'jumlah_terlambat' => $jumlahTerlambat,
            'total_lembur_menit' => $totalLemburMenit,
            'izin' => $izin,
            'sakit' => $sakit,
            'cuti' => $cuti,
            'alpha' => $alpha,
        ]);

        return [
            'karyawan' => $karyawan,
            'id_karyawan' => $karyawan->id_karyawan,
            'nama' => $karyawan->nama,
            'jabatan' => $this->jabatanDivisi($karyawan),
            'total_hari_kerja' => $totalHariKerja,
            'total_jam_kerja' => round($totalMenit / 60, 1),
            'rata_rata_jam' => $rataRataJam,
            'persentase_hadir' => $persentaseHadir,
            'jumlah_terlambat' => $jumlahTerlambat,
            'total_menit_terlambat' => $totalMenitTerlambat,
            'total_lembur_jam' => round($totalLemburMenit / 60, 1),
            'izin' => $izin,
            'sakit' => $sakit,
            'cuti' => $cuti,
            'alpha' => $alpha,
            'riport' => $riport['teks'],
            'kategori' => $riport['kategori'],
            'catatan' => $this->susunCatatan($karyawan, $totalHariKerja, $jumlahTerlambat, $totalMenitTerlambat, $totalLemburMenit, $izin, $sakit, $cuti, $alpha, $persentaseHadir),
        ];
    }

    /**
     * Susun teks riport masuk / status kehadiran.
     */
    private function susunRiport(array $d): array
    {
        if ($d['total_hari_kerja'] === 0) {
            return ['teks' => 'Belum Ada Data', 'kategori' => 'danger'];
        }

        $bagian = [];
        $kategori = 'success';

        if ($d['izin'] > 0) {
            $bagian[] = 'Izin ' . $d['izin'] . ' Hari';
            $kategori = 'warning';
        }
        if ($d['sakit'] > 0) {
            $bagian[] = 'Sakit ' . $d['sakit'] . ' Hari';
            $kategori = 'warning';
        }
        if ($d['cuti'] > 0) {
            $bagian[] = 'Cuti ' . $d['cuti'] . ' Hari';
            $kategori = 'warning';
        }
        if ($d['jumlah_terlambat'] > 0) {
            $bagian[] = 'Terlambat ' . $d['jumlah_terlambat'] . 'x';
            $kategori = 'warning';
        }
        if ($d['total_lembur_menit'] > 0) {
            $jamLembur = round($d['total_lembur_menit'] / 60, 1);
            $satuan = floor($jamLembur) == $jamLembur ? (int) $jamLembur : $jamLembur;
            $bagian[] = 'Lembur ' . $satuan . ' Jam';
            $kategori = 'warning';
        }
        if ($d['alpha'] > 0) {
            $bagian[] = 'Alpha ' . $d['alpha'] . ' Hari';
            $kategori = 'danger';
        }

        if (empty($bagian)) {
            return ['teks' => 'Tepat Waktu (100%)', 'kategori' => 'success'];
        }

        return ['teks' => implode(' | ', $bagian), 'kategori' => $kategori];
    }

    /**
     * Susun catatan operasional.
     */
    private function susunCatatan(Karyawan $karyawan, int $totalHariKerja, int $jumlahTerlambat, int $totalMenitTerlambat, int $totalLemburMenit, int $izin, int $sakit, int $cuti, int $alpha, float $persentaseHadir): string
    {
        $catatan = [];

        if ($totalHariKerja === 0) {
            return 'Tidak ada log kehadiran pada periode ini';
        }

        if ($persentaseHadir >= 99.5) {
            $catatan[] = 'Kehadiran sempurna';
        } elseif ($persentaseHadir >= 90) {
            $catatan[] = 'Kehadiran baik';
        } else {
            $catatan[] = 'Kehadiran perlu perhatian';
        }

        if ($jumlahTerlambat > 0) {
            if ($totalMenitTerlambat > 0) {
                $satuan = floor($totalMenitTerlambat / 60) == round($totalMenitTerlambat / 60, 1)
                    ? round($totalMenitTerlambat / 60, 1) . ' jam'
                    : $totalMenitTerlambat . ' menit';
                $catatan[] = 'Total keterlambatan ' . $satuan;
            } else {
                $catatan[] = 'Terlambat di luar jam toleransi';
            }
        }

        if ($totalLemburMenit > 0) {
            $catatan[] = 'Lembur tercatat ' . $totalLemburMenit . ' menit';
        }
        if ($izin > 0) {
            $catatan[] = 'Izin perlu kejelasan keperluan';
        }
        if ($sakit > 0) {
            $catatan[] = 'Sakit perlu surat keterangan';
        }
        if ($cuti > 0) {
            $catatan[] = 'Cuti sesuai jatah (' . $karyawan->jatah_cuti_tahunan . ' hari/tahun)';
        }
        if ($alpha > 0) {
            $catatan[] = 'Terdeteksi ' . $alpha . ' hari tanpa keterangan';
        }

        return implode('; ', $catatan);
    }

    /**
     * Jumlah hari kerja efektif dalam periode.
     *
     * Mengacu pada hari kerja yang benar-benar terdeteksi dari data kehadiran
     * (hari kerja aktif perusahaan), bukan seluruh hari kalender, sehingga
     * hari libur/cuti perusahaan tidak dihitung sebagai absence.
     * Fallback ke hitungan Senin-Sabtu bila periode tidak memiliki data sama sekali.
     */
    private function hitungJumlahHariEfektif(Carbon $mulai, Carbon $selesai, $absensi): int
    {
        $tanggalKerja = $absensi
            ->filter(fn($a) => in_array($a->status, ['Hadir', 'Terlambat'], true))
            ->map(fn($a) => Carbon::parse($a->tanggal))
            ->filter(fn($d) => $d->dayOfWeek !== Carbon::SUNDAY)
            ->map(fn($d) => $d->toDateString())
            ->unique();

        if ($tanggalKerja->isNotEmpty()) {
            return $tanggalKerja->count();
        }

        $jumlah = 0;
        $tanggal = $mulai->copy();

        while ($tanggal->lessThanOrEqualTo($selesai)) {
            if ($tanggal->dayOfWeek !== Carbon::SUNDAY) {
                $jumlah++;
            }
            $tanggal->addDay();
        }

        return max($jumlah, 1);
    }

    /**
     * Jabatan / divisi gabungan, dengan fallback yang rapi.
     */
    private function jabatanDivisi(Karyawan $karyawan): string
    {
        $jabatan = trim((string) $karyawan->jabatan);
        $departemen = trim((string) $karyawan->departemen);

        $jabatan = ($jabatan === '' || $jabatan === '-') ? 'Staf' : $jabatan;
        $departemen = ($departemen === '' || $departemen === '-') ? 'Umum' : $departemen;

        return $jabatan . ' / ' . $departemen;
    }

    /**
     * Menit keterlambatan terhadap jam masuk standar (di luar toleransi).
     */
    private function menitKeterlambatan($jamMasuk, array $standar): int
    {
        if (empty($jamMasuk)) {
            return 0;
        }

        $batas = Carbon::createFromFormat('H:i', $standar['jam_masuk'])
            ->addMinutes($standar['toleransi_terlambat']);

        $selisih = $batas->diffInMinutes(
            Carbon::createFromFormat('H:i:s', $this->jam($jamMasuk)),
            false
        );

        return $selisih > 0 ? $selisih : 0;
    }

    /**
     * Selisih menit antara dua waktu; null bila data tidak lengkap.
     */
    private function selisihMenit($mulai, $selesai): int
    {
        if (empty($mulai) || empty($selesai)) {
            return 0;
        }

        $selisih = $mulai->diffInMinutes($selesai, false);

        return $selisih > 0 ? $selisih : 0;
    }

    /**
     * Normalisasi value kolom time dari MySQL menjadi string H:i:s.
     */
    private function jam($value): string
    {
        if (empty($value)) {
            return '00:00:00';
        }

        $value = (string) $value;

        if (strlen($value) === 5) {
            return $value . ':00';
        }

        return substr($value, 0, 8);
    }
}
