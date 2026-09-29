<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan_Kehadiran_{{ $periode_label }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333; background-color: #fff; font-size: 12px; }
        .kop { border-bottom: 3px solid #1f4e79; margin-bottom: 16px; padding-bottom: 10px; }
        .judul-laporan { font-size: 16pt; font-weight: 800; color: #1f4e79; margin: 0; }
        .sub-kop { font-size: 10pt; color: #44546a; }
        table { border-collapse: collapse; width: 100%; }
        th { background: #1f4e79; color: #fff; font-weight: 600; text-align: center; border: 1px solid #1f4e79; padding: 6px 4px; font-size: 10.5pt; }
        td { border: 1px solid #8ea9db; padding: 5px 6px; font-size: 10pt; }
        tbody tr:nth-child(even) td { background-color: #f4f8fd; }
        tfoot td { background: #d9e1f2; font-weight: 700; border: 1px solid #1f4e79; }
        .sel-kanan { text-align: right; }
        .sel-tengah { text-align: center; }
        .kartu-hijau { background: #e2efda; border: 1px solid #a9d08e; color: #1d5c2c; font-weight: 600; font-size: 9pt; padding: 2px 4px; }
        .kartu-kuning { background: #fff2cc; border: 1px solid #ffd966; color: #7f6000; font-weight: 600; font-size: 9pt; padding: 2px 4px; }
        .kartu-erah { background: #fce4e4; border: 1px solid #f4a6a6; color: #9c1c1c; font-weight: 600; font-size: 9pt; padding: 2px 4px; }
        .ttd { margin-top: 40px; text-align: center; font-size: 10pt; }
        @media print {
            .no-print { display: none !important; }
            body { font-size: 10pt; }
            @page { size: A4 landscape; margin: 10mm; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; }
            th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .kartu-hijau, .kartu-kuning, .kartu-erah, tfoot td, tbody tr:nth-child(even) td { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body onload="window.print()">

<div class="container-fluid mt-3 px-4">

    <div class="no-print mb-3 text-end">
        <button onclick="window.print()" class="btn btn-sm btn-dark">Cetak Ulang</button>
        <button onclick="window.close()" class="btn btn-sm btn-secondary">Tutup Halaman</button>
    </div>

    <div class="kop d-flex justify-content-between align-items-start">
        <div>
            <div class="judul-laporan">LAPORAN KEHADIRAN &amp; JAM KERJA KARYAWAN</div>
            <div class="sub-kop fw-semibold">PT. Kawan Solution</div>
            <div class="sub-kop">Sistem Informasi Manajemen Absensi Karyawan</div>
        </div>
        <div class="text-end">
            <div class="sub-kop">Periode: <strong>{{ $periode_label }}</strong></div>
            <div class="sub-kop">{{ \Carbon\Carbon::parse($mulai)->format('d/m/Y') }} s.d {{ \Carbon\Carbon::parse($selesai)->format('d/m/Y') }}</div>
            <div class="sub-kop">
                {{ empty($karyawanIds) ? 'Semua Karyawan' : count($karyawanIds) . ' karyawan terpilih' }}
            </div>
            <div class="sub-kop">Dicetak: {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }} WIB</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 9%;">ID Karyawan</th>
                <th style="width: 18%;">Nama Karyawan</th>
                <th style="width: 14%;">Jabatan / Divisi</th>
                <th style="width: 8%;">Total Hari Kerja (Hari)</th>
                <th style="width: 8%;">Total Jam Kerja (Jam)</th>
                <th style="width: 8%;">Rata-rata Jam/Hari</th>
                <th style="width: 15%;">Riport Masuk / Status Kehadiran</th>
                <th style="width: 16%;">Catatan Operasional</th>
            </tr>
        </thead>
        <tbody>
            @forelse($baris as $index => $b)
                <tr>
                    <td class="sel-tengah text-muted">{{ $index + 1 }}</td>
                    <td class="sel-tengah"><strong>{{ $b['id_karyawan'] }}</strong></td>
                    <td>{{ $b['nama'] }}</td>
                    <td>{{ $b['jabatan'] }}</td>
                    <td class="sel-tengah">{{ $b['total_hari_kerja'] }}</td>
                    <td class="sel-kanan">{{ number_format($b['total_jam_kerja'], 1, ',', '') }}</td>
                    <td class="sel-kanan">{{ number_format($b['rata_rata_jam'], 1, ',', '') }}</td>
                    <td class="sel-tengah">
                        <span class="{{ $b['kategori'] == 'success' ? 'kartu-hijau' : ($b['kategori'] == 'warning' ? 'kartu-kuning' : 'kartu-erah') }} d-inline-block">
                            {{ $b['riport'] }}
                        </span>
                    </td>
                    <td>{{ $b['catatan'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="sel-tengah py-4 text-muted">Tidak ada data kehadiran pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td class="sel-tengah" colspan="4">TOTAL / RATA-RATA ({{ $total_karyawan }} karyawan)</td>
                <td class="sel-tengah">{{ $ringkasan['total_hari_kerja'] }}</td>
                <td class="sel-kanan">{{ number_format($ringkasan['total_jam_kerja'], 1, ',', '') }}</td>
                <td class="sel-kanan">{{ number_format($ringkasan['rata_rata_jam'], 1, ',', '') }}</td>
                <td class="sel-tengah">-</td>
                <td>-</td>
            </tr>
        </tfoot>
    </table>

    <div class="ttd">
        <div>Mengetahui,</div>
        <div style="margin-top: 6px; font-weight: 600;">Finance / HRD</div>
        <div style="height: 70px;"></div>
        <div style="display: inline-block; border-top: 1px solid #333; padding: 0 40px; font-weight: 600;">
            {{ auth()->user()->name }}
        </div>
    </div>
</div>

</body>
</html>
