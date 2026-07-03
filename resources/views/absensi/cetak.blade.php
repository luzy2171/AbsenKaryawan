<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan_Absensi_{{ $mulai }}_sd_{{ $selesai }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333; background-color: #fff; font-size: 13px; }
        .table th { background-color: #f8f9fa !important; color: #000 !important; font-weight: 600; text-align: center; border: 1px solid #dee2e6 !important; }
        .table td { border: 1px solid #dee2e6 !important; padding: 8px 10px; }
        .badge-hadir { background-color: #198754 !important; color: #fff; font-size: 11px; padding: 4px 8px; border-radius: 4px; font-weight: 600; display: inline-block; }
        .badge-terlambat { background-color: #ffc107 !important; color: #000; font-size: 11px; padding: 4px 8px; border-radius: 4px; font-weight: 600; display: inline-block; }
        @media print {
            .no-print { display: none !important; }
            body { font-size: 12px; background-color: #fff; }
            .table th { background-color: #f8f9fa !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body onload="window.print()">

<div class="container mt-4">
    <!-- Header Dokumen Laporan -->
    <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
        <div>
            <h3 class="fw-bold text-success mb-0">PT.BEJO BERKAH MAKMUR </h3>
            <p class="text-muted small mb-0">Sistem Informasi Manajemen Absensi</p>
        </div>
        <div class="text-end">
            <h5 class="fw-bold mb-0">LAPORAN DETAIL ABSENSI KARYAWAN</h5>
            <small class="text-secondary">Periode: <strong>{{ date('d/m/Y', strtotime($mulai)) }}</strong> s.d <strong>{{ date('d/m/Y', strtotime($selesai)) }}</strong></small>
        </div>
    </div>

    <!-- Tombol Khusus Layar -->
    <div class="no-print mb-3 text-end">
        <button onclick="window.print()" class="btn btn-sm btn-dark">Cetak Ulang</button>
        <button onclick="window.close()" class="btn btn-sm btn-secondary">Tutup Halaman</button>
    </div>

    <!-- Tabel Data Rekap Laporan Bersih Sesuai image_eaaa6e.png -->
    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="12%">PIN / ID</th>
                    <th>Nama Karyawan</th>
                    <th width="15%">Tanggal</th>
                    <th width="15%">Jam Masuk</th>
                    <th width="15%">Jam Pulang</th>
                    <th width="13%">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cleanedAttendances as $index => $item)
                <tr>
                    <td class="text-center text-muted">{{ $index + 1 }}</td>
                    <td class="text-center"><code class="text-danger fw-semibold">{{ $item['id_karyawan'] }}</code></td>
                    <td class="fw-bold text-dark">{{ $item['nama'] }}</td>
                    <td class="text-center">{{ date('d/m/Y', strtotime($item['tanggal'])) }}</td>
                    <td class="text-center">{{ $item['jam_masuk'] ? $item['jam_masuk'] . ' WIB' : '-' }}</td>
                    <td class="text-center">{{ $item['jam_pulang'] ? $item['jam_pulang'] . ' WIB' : '-' }}</td>
                    <td class="text-center">
                        @if($item['status'] == 'Hadir')
                            <span class="badge-hadir">Hadir</span>
                        @else
                            <span class="badge-terlambat">{{ $item['status'] }}</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">Tidak ditemukan rekaman log kehadiran pada rentang tanggal tersebut.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
