<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pratinjau Kop - Absensi-BBM</title>
    @include('partials.favicon')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background: #e9edf1; font-family: 'Segoe UI', Tahoma, sans-serif; }
        .doc {
            background: #fff;
            width: 210mm;
            min-height: 297mm;
            margin: 24px auto;
            padding: 25.4mm;
            box-shadow: 0 4px 18px rgba(0, 0, 0, .15);
            position: relative;
        }
        .doc > .isi-dokumen { position: relative; z-index: 1; }
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            background: #212529;
            color: #fff;
            padding: 10px 18px;
        }
        table { border-collapse: collapse; width: 100%; }
        th { background: #1f4e79; color: #fff; border: 1px solid #1f4e79; padding: 5px; font-size: 9pt; }
        td { border: 1px solid #8ea9db; padding: 5px; font-size: 9pt; }
        .isi-kop { position: relative; min-height: 120px; }
    </style>
</head>
<body>

<div class="toolbar">
    <strong>Pratinjau Kop</strong>
    &nbsp;·&nbsp; A4 210×297mm, margin 25.4mm
    &nbsp;·&nbsp;
    <button onclick="window.print()" class="btn btn-sm btn-light">Cetak / Simpan PDF</button>
</div>

<div class="doc">
    @include('partials.watermark')

    <div class="isi-dokumen">
    @include('partials.kop', [
        'kopJudul' => 'LAPORAN KEHADIRAN & JAM KERJA KARYAWAN',
        'kopKanan' => [
            'Periode: <strong>Agustus 2026</strong>',
            '01/08/2026 s.d 31/08/2026',
            'Semua Karyawan',
            'Dicetak: 30/09/2026 10:24 WIB',
        ],
    ])

    <p style="font-size: 9.5pt; text-align: justify; line-height: 1.6; color: #333;">
        Huruf ini hanya contoh untuk melihat tata letak. Area watermark logo berada di
        belakang konten, sama seperti di dokumen letterhead asli. Baris tabel di bawah
        menunjukkan bahwa isi laporan tetap terbaca di atas watermark.
    </p>

    <table style="margin-top: 14px;">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 12%;">ID</th>
                <th>Nama Karyawan</th>
                <th style="width: 15%;">Jabatan</th>
                <th style="width: 12%;">Total Jam</th>
                <th style="width: 12%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @for ($i = 1; $i <= 6; $i++)
                <tr>
                    <td style="text-align: center;">{{ $i }}</td>
                    <td style="text-align: center;">00{{ $i }}</td>
                    <td>Karyawan Contoh {{ $i }}</td>
                    <td>Staf</td>
                    <td style="text-align: right;">{{ 168 + $i }},0</td>
                    <td style="text-align: center;">Hadir</td>
                </tr>
            @endfor
        </tbody>
    </table>

    <div style="margin-top: 40px; text-align: center; font-size: 10pt;">
        <div>Mengetahui,</div>
        <div style="margin-top: 6px; font-weight: 600;">{{ $companyProfile->get('jabatan_ttd', 'Finance / HRD') }}</div>
        <div style="height: 66px;"></div>
        <div style="display: inline-block; border-top: 1px solid #333; padding: 0 40px; font-weight: 600;">
            {{ $companyProfile->get('nama_ttd') ?: 'Nama Penanda Tangan' }}
        </div>
    </div>
    </div>
</div>

</body>
</html>
