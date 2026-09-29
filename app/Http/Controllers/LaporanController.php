<?php

namespace App\Http\Controllers;

use App\Helpers\AuditLogger;
use App\Models\Karyawan;
use App\Services\LaporanKehadiranService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LaporanController extends Controller
{
    public function __construct(private LaporanKehadiranService $layanan)
    {
    }

    /**
     * Normalisasi parameter karyawan_id dari form.
     * Menerima checkbox (karyawan_id[]) maupun string comma-separated.
     */
    private function parseKaryawanIds($raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }

        $values = is_array($raw) ? $raw : explode(',', (string) $raw);

        return array_values(array_unique(array_filter(array_map('intval', $values))));
    }

    /**
     * Halaman Laporan Kehadiran & Jam Kerja.
     */
    public function index(Request $request)
    {
        $periode = $this->tentukanPeriode($request);

        $karyawanIds = $this->parseKaryawanIds($request->input('karyawan_id'));

        $rekap = $this->layanan->rekap($periode['mulai'], $periode['selesai'], $karyawanIds);
        $rekap['ringkasan'] = $this->hitungRingkasan($rekap['baris']);

        $karyawans = Karyawan::orderBy('nama', 'asc')->get();

        return view('laporan.index', array_merge($rekap, $periode, [
            'karyawans' => $karyawans,
            'karyawanIds' => $karyawanIds,
        ]));
    }

    /**
     * Cetak / simpan PDF dari halaman laporan.
     */
    public function cetak(Request $request)
    {
        $request->validate([
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'karyawan_id' => 'nullable',
        ]);

        $mulai = $request->tanggal_mulai;
        $selesai = $request->tanggal_selesai;
        $karyawanIds = $this->parseKaryawanIds($request->input('karyawan_id'));

        $rekap = $this->layanan->rekap($mulai, $selesai, $karyawanIds);
        $rekap['ringkasan'] = $this->hitungRingkasan($rekap['baris']);

        AuditLogger::absensiExported('PDF Rekap Kehadiran', count($rekap['baris']));

        $periode = $this->tentukanPeriode($request);

        return view('laporan.cetak', array_merge($rekap, $periode, [
            'karyawanIds' => $karyawanIds,
        ]));
    }

    /**
     * Export rekap ke format Excel.
     */
    public function exportExcel(Request $request)
    {
        $request->validate([
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'karyawan_id' => 'nullable',
        ]);

        $mulai = $request->tanggal_mulai;
        $selesai = $request->tanggal_selesai;
        $karyawanIds = $this->parseKaryawanIds($request->input('karyawan_id'));

        $rekap = $this->layanan->rekap($mulai, $selesai, $karyawanIds);
        $rekap['ringkasan'] = $this->hitungRingkasan($rekap['baris']);

        AuditLogger::absensiExported('Excel Rekap Kehadiran', count($rekap['baris']));

        $periode = $this->tentukanPeriode($request);
        $namaPeriode = Carbon::parse($mulai)->format('F Y');

        $filename = 'Laporan_Kehadiran_Jam_Kerja_' . $namaPeriode . '.xls';

        $html = $this->susunExcel($rekap, $periode, $namaPeriode);

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Rincian kehadiran satu karyawan per hari.
     */
    public function detail(Request $request, $id)
    {
        $periode = $this->tentukanPeriode($request);

        $detail = $this->layanan->detail((int) $id, $periode['mulai'], $periode['selesai']);

        abort_if($detail['karyawan'] === null, 404, 'Karyawan tidak ditemukan.');

        $detail['ringkasan'] = $this->hitungRingkasanDetail($detail['baris']);

        return view('laporan.detail', array_merge($detail, $periode, [
            'id' => (int) $id,
        ]));
    }

    /**
     * Cetak rincian satu karyawan.
     */
    public function detailCetak(Request $request, $id)
    {
        $request->validate([
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        ]);

        $periode = $this->tentukanPeriode($request);

        $detail = $this->layanan->detail((int) $id, $periode['mulai'], $periode['selesai']);

        abort_if($detail['karyawan'] === null, 404, 'Karyawan tidak ditemukan.');

        $detail['ringkasan'] = $this->hitungRingkasanDetail($detail['baris']);

        AuditLogger::absensiExported('PDF Rincian Karyawan', count($detail['baris']));

        return view('laporan.detail_cetak', array_merge($detail, $periode, [
            'id' => (int) $id,
        ]));
    }

    /**
     * Export rincian satu karyawan ke Excel.
     */
    public function detailExcel(Request $request, $id)
    {
        $request->validate([
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        ]);

        $periode = $this->tentukanPeriode($request);

        $detail = $this->layanan->detail((int) $id, $periode['mulai'], $periode['selesai']);

        abort_if($detail['karyawan'] === null, 404, 'Karyawan tidak ditemukan.');

        $detail['ringkasan'] = $this->hitungRingkasanDetail($detail['baris']);

        AuditLogger::absensiExported('Excel Rincian Karyawan', count($detail['baris']));

        $filename = 'Rincian_Karyawan_' . $detail['karyawan']->id_karyawan . '_' . $periode['periode_label'] . '.xls';

        return response($this->susunExcelDetail($detail, $periode), 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Ringkasan untuk rincian harian.
     */
    private function hitungRingkasanDetail(array $baris): array
    {
        $hitung = function ($status) use ($baris) {
            return count(array_filter($baris, fn($b) => $b['status'] === $status));
        };

        $totalMenit = 0;
        $totalHariKerja = 0;
        $totalLemburJam = 0.0;

        foreach ($baris as $b) {
            if (in_array($b['status'], ['Hadir', 'Terlambat'], true)) {
                $totalHariKerja++;
                $totalMenit += (int) round($b['durasi_jam'] * 60);
            }
            $totalLemburJam += $b['lembur_jam'];
        }

        return [
            'total_hari_kerja' => $totalHariKerja,
            'total_jam_kerja' => round($totalMenit / 60, 1),
            'rata_rata_jam' => $totalHariKerja > 0 ? round(($totalMenit / 60) / $totalHariKerja, 1) : 0.0,
            'total_lembur_jam' => round($totalLemburJam, 1),
            'hadir' => $hitung('Hadir'),
            'terlambat' => $hitung('Terlambat'),
            'izin' => $hitung('Izin'),
            'sakit' => $hitung('Sakit'),
            'cuti' => $hitung('Cuti'),
            'alpha' => $hitung('Alpha'),
        ];
    }

    /**
     * Susun tabel HTML rincian yang kompatibel dengan Excel.
     */
    private function susunExcelDetail(array $detail, array $periode): string
    {
        $karyawan = $detail['karyawan'];
        $baris = $detail['baris'];
        $ringkasan = $detail['ringkasan'];
        $barisTerakhir = count($baris) + 1;

        $warnaBaris = function ($kategori) {
            return match ($kategori) {
                'success' => '#e2efda',
                'warning' => '#fff2cc',
                'secondary' => '#f2f2f2',
                default => '#fce4e4',
            };
        };

        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        $html .= '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
        $html .= '<style>'
            . 'body{font-family:Calibri,Arial,sans-serif;font-size:10pt;}'
            . '.judul{font-size:14pt;font-weight:bold;color:#1f4e79;}'
            . '.sub{font-size:10pt;color:#404040;}'
            . 'table{border-collapse:collapse;}'
            . 'th{background:#1f4e79;color:#fff;font-weight:bold;border:0.75pt solid #1f4e79;padding:5px;}'
            . 'td{border:0.75pt solid #8ea9db;padding:4px;}'
            . '.total{background:#d9e1f2;font-weight:bold;border:0.75pt solid #1f4e79;}'
            . '</style></head><body>';

        $html .= '<table><tr><td colspan="9" class="judul">RINCIAN KEHADIRAN &amp; JAM KERJA KARYAWAN</td></tr>';
        $html .= '<tr><td colspan="9" class="sub">PT. Kawan Solution</td></tr>';
        $html .= '<tr><td colspan="9" class="sub">ID Karyawan: ' . htmlspecialchars((string) $karyawan->id_karyawan) . ' &nbsp;|&nbsp; Nama: ' . htmlspecialchars($karyawan->nama) . ' &nbsp;|&nbsp; Jabatan: ' . htmlspecialchars($this->jabatanLengkap($karyawan)) . '</td></tr>';
        $html .= '<tr><td colspan="9" class="sub">Periode: ' . htmlspecialchars($periode['periode_label']) . ' (' . date('d/m/Y', strtotime($periode['mulai'])) . ' - ' . date('d/m/Y', strtotime($periode['selesai'])) . ')</td></tr>';
        $html .= '<tr><td colspan="9"></td></tr>';

        $html .= '<tr>'
            . '<th width="30">No</th>'
            . '<th width="90">Tanggal</th>'
            . '<th width="70">Hari</th>'
            . '<th width="80">Jam Masuk</th>'
            . '<th width="80">Jam Pulang</th>'
            . '<th width="80">Durasi (Jam)</th>'
            . '<th width="70">Lembur (Jam)</th>'
            . '<th width="100">Status</th>'
            . '<th width="220">Keterangan</th>'
            . '</tr>';

        foreach ($baris as $index => $b) {
            $html .= '<tr>'
                . '<td align="center">' . ($index + 1) . '</td>'
                . '<td align="center">' . htmlspecialchars($b['tanggal_label']) . '</td>'
                . '<td align="center">' . htmlspecialchars($b['nama_hari']) . '</td>'
                . '<td align="center">' . ($b['jam_masuk'] ? substr($b['jam_masuk'], 0, 5) : '-') . '</td>'
                . '<td align="center">' . ($b['jam_pulang'] ? substr($b['jam_pulang'], 0, 5) : '-') . '</td>'
                . '<td align="center" x:num="' . $b['durasi_jam'] . '">' . number_format($b['durasi_jam'], 1, ',', '') . '</td>'
                . '<td align="center" x:num="' . $b['lembur_jam'] . '">' . number_format($b['lembur_jam'], 1, ',', '') . '</td>'
                . '<td align="center" style="background:' . $warnaBaris($b['kategori']) . '">' . htmlspecialchars($b['status']) . '</td>'
                . '<td>' . htmlspecialchars($b['keterangan']) . '</td>'
                . '</tr>';
        }

        if ($baris) {
            $html .= '<tr class="total">'
                . '<td align="center">TOTAL</td>'
                . '<td align="center">' . count($baris) . ' hari</td>'
                . '<td align="center"></td>'
                . '<td align="center"></td>'
                . '<td align="center"></td>'
                . '<td align="center" x:num="=SUM(F2:F' . $barisTerakhir . ')">' . number_format($ringkasan['total_jam_kerja'], 1, ',', '') . '</td>'
                . '<td align="center" x:num="=SUM(G2:G' . $barisTerakhir . ')">' . number_format($ringkasan['total_lembur_jam'], 1, ',', '') . '</td>'
                . '<td align="center">Hadir ' . $ringkasan['hadir'] . ' / Telat ' . $ringkasan['terlambat'] . '</td>'
                . '<td align="center">Rata-rata ' . number_format($ringkasan['rata_rata_jam'], 1, ',', '') . ' jam/hari</td>'
                . '</tr>';
        }

        $html .= '</table></body></html>';

        return $html;
    }

    /**
     * Jabatan / divisi gabungan untuk tampilan.
     */
    private function jabatanLengkap($karyawan): string
    {
        $jabatan = trim((string) $karyawan->jabatan);
        $departemen = trim((string) $karyawan->departemen);

        $jabatan = ($jabatan === '' || $jabatan === '-') ? 'Staf' : $jabatan;
        $departemen = ($departemen === '' || $departemen === '-') ? 'Umum' : $departemen;

        return $jabatan . ' / ' . $departemen;
    }

    /**
     * Tentukan rentang periode dari request; default = bulan berjalan.
     */
    private function tentukanPeriode(Request $request): array
    {
        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $mulai = Carbon::parse($request->tanggal_mulai)->startOfDay();
            $selesai = Carbon::parse($request->tanggal_selesai)->endOfDay();
        } elseif ($request->filled('bulan')) {
            $bulan = Carbon::create(
                (int) $request->input('tahun', date('Y')),
                (int) $request->bulan,
                1
            );
            $mulai = $bulan->copy()->startOfMonth();
            $selesai = $bulan->copy()->endOfMonth();
        } else {
            $mulai = Carbon::now()->startOfMonth();
            $selesai = Carbon::now()->endOfMonth();
        }

        return [
            'mulai' => $mulai->toDateString(),
            'selesai' => $selesai->toDateString(),
            'periode_label' => $mulai->format('F Y'),
        ];
    }

    /**
     * Baris ringkasan TOTAL / RATA-RATA (setara formula SUM & AVERAGE di Excel).
     */
    private function hitungRingkasan(array $baris): array
    {
        $totalHari = 0;
        $totalJam = 0.0;
        $jumlahRataRata = 0;

        foreach ($baris as $b) {
            $totalHari += $b['total_hari_kerja'];
            $totalJam += $b['total_jam_kerja'];
            if ($b['total_hari_kerja'] > 0) {
                $jumlahRataRata++;
            }
        }

        return [
            'total_hari_kerja' => $totalHari,
            'total_jam_kerja' => round($totalJam, 1),
            'rata_rata_jam' => $jumlahRataRata > 0 ? round($totalJam / $jumlahRataRata, 1) : 0.0,
            'jumlah_terlambat' => array_sum(array_column($baris, 'jumlah_terlambat')),
            'total_lembur_jam' => round(array_sum(array_column($baris, 'total_lembur_jam')), 1),
            'izin' => array_sum(array_column($baris, 'izin')),
            'sakit' => array_sum(array_column($baris, 'sakit')),
        ];
    }

    /**
     * Susun tabel HTML yang kompatibel dengan Excel.
     */
    private function susunExcel(array $rekap, array $periode, string $namaPeriode): string
    {
        $baris = $rekap['baris'];
        $ringkasan = $rekap['ringkasan'];
        $jumlahBaris = count($baris);
        $barisTerakhir = $jumlahBaris + 1;

        $warnaBaris = function ($kategori) {
            return match ($kategori) {
                'success' => '#e2efda',
                'warning' => '#fff2cc',
                default => '#fce4e4',
            };
        };

        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        $html .= '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
        $html .= '<style>'
            . 'body{font-family:Calibri,Arial,sans-serif;font-size:10pt;}'
            . '.judul{font-size:14pt;font-weight:bold;color:#1f4e79;}'
            . '.sub{font-size:10pt;color:#404040;}'
            . 'table{border-collapse:collapse;}'
            . 'th{background:#1f4e79;color:#fff;font-weight:bold;border:0.75pt solid #1f4e79;padding:5px;}'
            . 'td{border:0.75pt solid #8ea9db;padding:4px;}'
            . 'total{background:#d9e1f2;font-weight:bold;border:0.75pt solid #1f4e79;}'
            . '</style></head><body>';

        $html .= '<table><tr><td colspan="9" class="judul">LAPORAN KEHADIRAN &amp; JAM KERJA KARYAWAN</td></tr>';
        $html .= '<tr><td colspan="9" class="sub">PT. Kawan Solution</td></tr>';
        $html .= '<tr><td colspan="9" class="sub">Periode: ' . htmlspecialchars($namaPeriode) . ' (' . date('d/m/Y', strtotime($periode['mulai'])) . ' - ' . date('d/m/Y', strtotime($periode['selesai'])) . ')</td></tr>';
        $html .= '<tr><td colspan="9"></td></tr>';

        $html .= '<tr>'
            . '<th width="30">No</th>'
            . '<th width="90">ID Karyawan</th>'
            . '<th width="180">Nama Karyawan</th>'
            . '<th width="150">Jabatan / Divisi</th>'
            . '<th width="80">Total Hari Kerja (Hari)</th>'
            . '<th width="80">Total Jam Kerja (Jam)</th>'
            . '<th width="80">Rata-rata Jam/Hari</th>'
            . '<th width="150">Riport Masuk</th>'
            . '<th width="220">Catatan Operasional</th>'
            . '</tr>';

        foreach ($baris as $index => $b) {
            $warna = $warnaBaris($b['kategori']);
            $html .= '<tr>'
                . '<td align="center">' . ($index + 1) . '</td>'
                . '<td align="center" x:num="' . htmlspecialchars((string) $b['id_karyawan']) . '">' . htmlspecialchars((string) $b['id_karyawan']) . '</td>'
                . '<td>' . htmlspecialchars($b['nama']) . '</td>'
                . '<td>' . htmlspecialchars($b['jabatan']) . '</td>'
                . '<td align="center" x:num="' . $b['total_hari_kerja'] . '">' . $b['total_hari_kerja'] . '</td>'
                . '<td align="center" x:num="' . $b['total_jam_kerja'] . '">' . number_format($b['total_jam_kerja'], 1, ',', '') . '</td>'
                . '<td align="center" x:num="' . $b['rata_rata_jam'] . '">' . number_format($b['rata_rata_jam'], 1, ',', '') . '</td>'
                . '<td align="center" style="background:' . $warna . '">' . htmlspecialchars($b['riport']) . '</td>'
                . '<td>' . htmlspecialchars($b['catatan']) . '</td>'
                . '</tr>';
        }

        if ($jumlahBaris > 0) {
            $html .= '<tr class="total">'
                . '<td align="center">TOTAL</td>'
                . '<td align="center"></td>'
                . '<td align="center">' . $jumlahBaris . ' karyawan</td>'
                . '<td align="center"></td>'
                . '<td align="center" x:num="=SUM(E2:E' . $barisTerakhir . ')">=' . $ringkasan['total_hari_kerja'] . '</td>'
                . '<td align="center" x:num="=SUM(F2:F' . $barisTerakhir . ')">' . number_format($ringkasan['total_jam_kerja'], 1, ',', '') . '</td>'
                . '<td align="center" x:num="=AVERAGE(G2:G' . $barisTerakhir . ')">' . number_format($ringkasan['rata_rata_jam'], 1, ',', '') . '</td>'
                . '<td align="center">-</td>'
                . '<td align="center">-</td>'
                . '</tr>';
        }

        $html .= '</table></body></html>';

        return $html;
    }
}
