<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rincian_Kehadiran_{{ $karyawan->id_karyawan }}_{{ $periode_label }}</title>
    @include('partials.favicon')

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
        .kartu-abu { background: #f2f2f2; border: 1px solid #d0d0d0; color: #6c757d; font-weight: 600; font-size: 9pt; padding: 2px 4px; }
        .identitas { border: 1px solid #8ea9db; border-collapse: collapse; margin-bottom: 14px; width: auto; }
        .identitas td { border: 1px solid #8ea9db; padding: 5px 10px; font-size: 10pt; }
        .kop-logo { width: 78px; height: 78px; object-fit: contain; object-position: center; }
        .ttd { margin-top: 40px; text-align: center; font-size: 10pt; }
        .ttd-nama { display: inline-block; border-top: 1px solid #333; padding: 0 40px; font-weight: 600; }
        @media print {
            .no-print { display: none !important; }
            body { font-size: 10pt; }
            @page { size: A4 landscape; margin: 10mm; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; }
            th, .kartu-hijau, .kartu-kuning, .kartu-erah, .kartu-abu, tfoot td, tbody tr:nth-child(even) td {
                -webkit-print-color-adjust: exact; print-color-adjust: exact;
            }
        }
    </style>
</head>
<body onload="window.print()">

<div class="container-fluid mt-3 px-4" style="position: relative;">
    @include('partials.watermark')
    <div style="position: relative; z-index: 1;">

    <div class="no-print mb-3 text-end">
        <button onclick="window.print()" class="btn btn-sm btn-dark">Cetak Ulang</button>
        <button onclick="window.close()" class="btn btn-sm btn-secondary">Tutup Halaman</button>
    </div>

    @include('partials.kop', [
        'kopJudul' => 'RINCIAN KEHADIRAN & JAM KERJA KARYAWAN',
        'kopKanan' => [
            'Periode: <strong>' . $periode_label . '</strong>',
            \Carbon\Carbon::parse($mulai)->format('d/m/Y') . ' s.d ' . \Carbon\Carbon::parse($selesai)->format('d/m/Y'),
            'Dicetak: ' . \Carbon\Carbon::now()->format('d/m/Y H:i') . ' WIB',
        ],
    ])

    <table class="identitas">
        <tr>
            <td style="background:#f2f6fb;"><strong>ID Karyawan</strong></td>
            <td>{{ $karyawan->id_karyawan }}</td>
            <td style="background:#f2f6fb;"><strong>Nama</strong></td>
            <td>{{ $karyawan->nama }}</td>
        </tr>
        <tr>
            <td style="background:#f2f6fb;"><strong>Jabatan</strong></td>
            <td>{{ $karyawan->jabatan ?: 'Staf' }}</td>
            <td style="background:#f2f6fb;"><strong>Divisi</strong></td>
            <td>{{ $karyawan->departemen ?: 'Umum' }}</td>
        </tr>
        <tr>
            <td style="background:#f2f6fb;"><strong>Total Hari Kerja</strong></td>
            <td>{{ $ringkasan['total_hari_kerja'] }} hari</td>
            <td style="background:#f2f6fb;"><strong>Total Jam Kerja</strong></td>
            <td>{{ number_format($ringkasan['total_jam_kerja'], 1, ',', '') }} jam</td>
        </tr>
        <tr>
            <td style="background:#f2f6fb;"><strong>Rata-rata / Hari</strong></td>
            <td>{{ number_format($ringkasan['rata_rata_jam'], 1, ',', '') }} jam</td>
            <td style="background:#f2f6fb;"><strong>Total Lembur</strong></td>
            <td>{{ number_format($ringkasan['total_lembur_jam'], 1, ',', '') }} jam</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 9%;">Tanggal</th>
                <th style="width: 8%;">Hari</th>
                <th style="width: 9%;">Jam Masuk</th>
                <th style="width: 9%;">Jam Pulang</th>
                <th style="width: 9%;">Durasi (Jam)</th>
                <th style="width: 8%;">Lembur (Jam)</th>
                <th style="width: 10%;">Status</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($baris as $index => $b)
                <tr>
                    <td class="sel-tengah text-muted">{{ $index + 1 }}</td>
                    <td class="sel-tengah">{{ $b['tanggal_label'] }}</td>
                    <td class="sel-tengah">{{ $b['nama_hari'] }}</td>
                    <td class="sel-tengah">{{ $b['jam_masuk'] ? substr($b['jam_masuk'], 0, 5) : '-' }}</td>
                    <td class="sel-tengah">{{ $b['jam_pulang'] ? substr($b['jam_pulang'], 0, 5) : '-' }}</td>
                    <td class="sel-kanan">{{ number_format($b['durasi_jam'], 1, ',', '') }}</td>
                    <td class="sel-kanan">{{ $b['lembur_jam'] > 0 ? number_format($b['lembur_jam'], 1, ',', '') : '-' }}</td>
                    <td class="sel-tengah">
                        <span class="{{ match($b['kategori']) {
                            'success' => 'kartu-hijau',
                            'warning' => 'kartu-kuning',
                            'secondary' => 'kartu-abu',
                            default => 'kartu-erah',
                        } }} d-inline-block">{{ $b['status'] }}</span>
                    </td>
                    <td>{{ $b['keterangan'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="sel-tengah py-4 text-muted">Tidak ada data kehadiran pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td class="sel-tengah" colspan="5">TOTAL / RATA-RATA ({{ $ringkasan['total_hari_kerja'] }} hari kerja)</td>
                <td class="sel-kanan">{{ number_format($ringkasan['total_jam_kerja'], 1, ',', '') }}</td>
                <td class="sel-kanan">{{ number_format($ringkasan['total_lembur_jam'], 1, ',', '') }}</td>
                <td class="sel-tengah">{{ $ringkasan['terlambat'] }}x telat</td>
                <td>Rata-rata {{ number_format($ringkasan['rata_rata_jam'], 1, ',', '') }} jam/hari</td>
            </tr>
        </tfoot>
    </table>

    <div class="ttd">
        <div>Mengetahui,</div>
        <div style="margin-top: 6px; font-weight: 600;">{{ $companyProfile->get('jabatan_ttd', 'Finance / HRD') }}</div>
        <div style="height: 70px;"></div>
        <div class="ttd-nama">{{ $companyProfile->get('nama_ttd') ?: auth()->user()->name }}</div>
    </div>
</div>

    </div>
</body>
</html>
