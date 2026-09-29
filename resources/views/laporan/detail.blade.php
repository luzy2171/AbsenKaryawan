<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rincian Kehadiran {{ $karyawan->nama }} - Absensi-BBM</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <style>
        .excel-sheet { background: #fff; border: 1px solid #c9d3e0; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        .excel-sheet .table { margin-bottom: 0; font-size: 12.5px; }
        .excel-sheet .table > thead > tr > th {
            background: #1f4e79; color: #fff; font-weight: 600; text-align: center;
            border: 1px solid #1f4e79; padding: 10px 8px; white-space: nowrap;
        }
        .excel-sheet .table > tbody > tr > td { border: 1px solid #b4c6e7; padding: 7px 8px; vertical-align: middle; }
        .excel-sheet .table > tbody > tr:nth-child(even) > td { background-color: #f4f8fd; }
        .excel-sheet .table > tfoot > tr > td { background: #d9e1f2; font-weight: 700; border: 1px solid #1f4e79; padding: 9px 8px; }
        .sel-kanan { text-align: right; }
        .sel-tengah { text-align: center; }
        .sel-hijau { background: #e2efda; border: 1px solid #a9d08e; color: #1d5c2c; font-weight: 600; font-size: 11.5px; padding: 2px 8px; }
        .sel-kuning { background: #fff2cc; border: 1px solid #ffd966; color: #7f6000; font-weight: 600; font-size: 11.5px; padding: 2px 8px; }
        .sel-erah { background: #fce4e4; border: 1px solid #f4a6a6; color: #9c1c1c; font-weight: 600; font-size: 11.5px; padding: 2px 8px; }
        .sel-abu { background: #f2f2f2; border: 1px solid #d0d0d0; color: #6c757d; font-weight: 600; font-size: 11.5px; padding: 2px 8px; }
        .kartu-judul { font-size: 17px; font-weight: 800; color: #1f4e79; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 sidebar p-3 d-none d-md-block">
            <div class="d-flex align-items-center mb-4 px-2 py-3">
                <div class="stat-icon bg-success text-white me-2"><i class="bi bi-fingerprint"></i></div>
                <div>
                    <h5 class="fw-bold m-0 text-success" style="font-size: 18px;">Absensi-BBM</h5>
                    <small class="text-muted" style="font-size: 10px;">Attendance System</small>
                </div>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}" href="{{ url('/dashboard') }}">
                        <i class="bi bi-grid me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('karyawan*') ? 'active' : '' }}" href="{{ url('/karyawan') }}">
                        <i class="bi bi-people me-2"></i> Karyawan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('absensi*') ? 'active' : '' }}" href="{{ url('/absensi') }}">
                        <i class="bi bi-calendar-check me-2"></i> Absensi
                    </a>
                </li>
                @if(auth()->user()->isApprover())
                <li class="nav-item mt-3">
                    <small class="text-muted px-3 fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">LAPORAN</small>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('laporan*') ? 'active' : '' }}" href="{{ url('/laporan/kehadiran') }}">
                        <i class="bi bi-clipboard-data me-2"></i> Laporan Kehadiran
                    </a>
                </li>
                @endif
                @if(auth()->user()->isApprover())
                <li class="nav-item mt-3">
                    <small class="text-muted px-3 fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">PENGATURAN</small>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/leaves*') ? 'active' : '' }}" href="{{ route('admin.leaves.index') }}">
                        <i class="bi bi-envelope-paper me-2"></i> Izin &amp; Cuti
                    </a>
                </li>
                @if(auth()->user()->isTrueApprover())
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/cuti-control*') ? 'active' : '' }}" href="{{ route('admin.cuti.control') }}">
                        <i class="bi bi-sliders me-2"></i> Kontrol Cuti
                    </a>
                </li>
                @endif
                @if(auth()->user()->isSuperadmin())
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/settings*') ? 'active' : '' }}" href="{{ url('/admin/settings') }}">
                        <i class="bi bi-clock-history me-2"></i> Set Jam Kerja
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('pengaturan*') ? 'active' : '' }}" href="{{ url('/pengaturan') }}">
                        <i class="bi bi-gear me-2"></i> Kontrol Mesin
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/maintenance*') ? 'active' : '' }}" href="{{ route('admin.maintenance.index') }}">
                        <i class="bi bi-database-fill-gear me-2"></i> Maintenance DB
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/users*') ? 'active' : '' }}" href="{{ url('/admin/users') }}">
                        <i class="bi bi-person-gear me-2"></i> Manajemen User
                    </a>
                </li>
                @endif
                @if(auth()->user()->isTrueApprover())
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/audit-logs*') ? 'active' : '' }}" href="{{ url('/admin/audit-logs') }}">
                        <i class="bi bi-journal-text me-2"></i> Audit Logs
                    </a>
                </li>
                @endif
                @endif
                <li class="nav-item mt-auto pt-3 border-top">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="nav-link text-danger w-100 text-start border-0 bg-transparent">
                            <i class="bi bi-box-arrow-left me-2"></i> Keluar
                        </button>
                    </form>
                </li>
            </ul>
        </div>

        <div class="col-md-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4 fade-in">
                <div>
                    <h4 class="fw-bold m-0 mb-1">
                        <i class="bi bi-person-lines-fill text-success me-2"></i>Rincian Kehadiran
                    </h4>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-file-earmark-spreadsheet text-muted me-2"></i>
                        <small class="text-muted">{{ count($baris) }} hari kerja tercatat &mdash; {{ $periode_label }}</small>
                    </div>
                </div>
                <a href="{{ route('laporan.kehadiran', ['tanggal_mulai' => $mulai, 'tanggal_selesai' => $selesai]) }}" class="btn btn-light border">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Rekap
                </a>
            </div>

            <!-- Kartu identitas karyawan -->
            <div class="card-custom p-4 bg-white mb-4 fade-in">
                <div class="d-flex align-items-center flex-wrap gap-3">
                    <div class="avatar-circle bg-success text-white" style="width: 60px; height: 60px; font-size: 24px;">
                        {{ strtoupper(substr($karyawan->nama, 0, 1)) }}
                    </div>
                    <div class="me-4">
                        <h5 class="fw-bold mb-1">{{ $karyawan->nama }}</h5>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-light text-dark border" style="font-family:'Courier New',monospace;">
                                {{ $karyawan->id_karyawan }}
                            </span>
                            <span class="badge bg-success-subtle text-success">{{ $karyawan->jabatan ?: 'Staf' }}</span>
                            <span class="badge bg-secondary-subtle text-secondary">{{ $karyawan->departemen ?: 'Umum' }}</span>
                        </div>
                    </div>
                    <div class="ms-auto text-md-end">
                        <small class="text-muted d-block">Periode laporan</small>
                        <strong class="d-block">{{ $periode_label }}</strong>
                        <small class="text-muted">{{ \Carbon\Carbon::parse($mulai)->format('d/m/Y') }} &ndash; {{ \Carbon\Carbon::parse($selesai)->format('d/m/Y') }}</small>
                    </div>
                </div>

                <hr class="my-3">

                <form action="{{ route('laporan.kehadiran.detail', $id) }}" method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small"><i class="bi bi-calendar text-muted me-1"></i>Dari Tanggal</label>
                        <input type="date" name="tanggal_mulai" class="form-control form-control-sm" value="{{ $mulai }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small"><i class="bi bi-calendar-check text-muted me-1"></i>Sampai Tanggal</label>
                        <input type="date" name="tanggal_selesai" class="form-control form-control-sm" value="{{ $selesai }}" required>
                    </div>
                    <div class="col-md-6 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary fw-semibold">
                            <i class="bi bi-search me-1"></i> Tampilkan
                        </button>
                        <button type="button" onclick="submitDetail('{{ route('laporan.kehadiran.detail.cetak', $id) }}', '_blank')" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-printer me-1"></i> Cetak / PDF
                        </button>
                        <button type="button" onclick="submitDetail('{{ route('laporan.kehadiran.detail.excel', $id) }}', '_self')" class="btn btn-sm btn-success">
                            <i class="bi bi-file-earmark-excel me-1"></i> Excel
                        </button>
                    </div>
                </form>
            </div>

            <!-- Kartu ringkasan -->
            <div class="row g-3 mb-4 fade-in">
                <div class="col-md-3">
                    <div class="card-custom p-3 bg-white h-100">
                        <small class="text-muted">Total Hari Kerja</small>
                        <div class="fs-4 fw-bold text-success">{{ $ringkasan['total_hari_kerja'] }} <small class="fs-6 text-muted">hari</small></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card-custom p-3 bg-white h-100">
                        <small class="text-muted">Total Jam Kerja</small>
                        <div class="fs-4 fw-bold text-success">{{ number_format($ringkasan['total_jam_kerja'], 1, ',', '') }} <small class="fs-6 text-muted">jam</small></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card-custom p-3 bg-white h-100">
                        <small class="text-muted">Rata-rata Jam / Hari</small>
                        <div class="fs-4 fw-bold text-success">{{ number_format($ringkasan['rata_rata_jam'], 1, ',', '') }} <small class="fs-6 text-muted">jam</small></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card-custom p-3 bg-white h-100">
                        <small class="text-muted">Total Lembur</small>
                        <div class="fs-4 fw-bold text-info">{{ number_format($ringkasan['total_lembur_jam'], 1, ',', '') }} <small class="fs-6 text-muted">jam</small></div>
                    </div>
                </div>
            </div>

            <!-- Filter status harian -->
            <div class="card-custom p-3 bg-white mb-3 fade-in">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <strong class="small text-muted me-1">Saring status:</strong>
                    @php
                        $chip = function ($label, $value, $warna) {
                            $aktif = (request('status', 'semua') === $value);
                            return '<a href="' . request()->fullUrlWithQuery(['status' => $value]) . '" '
                                . 'class="badge text-decoration-none border ' . ($aktif ? $warna . ' text-white' : 'bg-light text-dark') . '">'
                                . $label . '</a>';
                        };
                    @endphp
                    {!! $chip('Semua (' . count($baris) . ')', 'semua', 'bg-secondary') !!}
                    {!! $chip('Hadir (' . $ringkasan['hadir'] . ')', 'Hadir', 'bg-success') !!}
                    {!! $chip('Terlambat (' . $ringkasan['terlambat'] . ')', 'Terlambat', 'bg-warning') !!}
                    {!! $chip('Izin (' . $ringkasan['izin'] . ')', 'Izin', 'bg-warning') !!}
                    {!! $chip('Sakit (' . $ringkasan['sakit'] . ')', 'Sakit', 'bg-danger') !!}
                    {!! $chip('Cuti (' . $ringkasan['cuti'] . ')', 'Cuti', 'bg-info') !!}
                    {!! $chip('Alpha (' . $ringkasan['alpha'] . ')', 'Alpha', 'bg-danger') !!}
                </div>
            </div>

            <!-- Tabel rincian harian -->
            <div class="excel-sheet rounded fade-in overflow-auto" style="max-height: 60vh;">
                <table class="table table-bordered mb-0 align-middle">
                    <thead class="sticky-top" style="z-index: 2;">
                        <tr>
                            <th style="width:40px;">No</th>
                            <th style="width:95px;">Tanggal</th>
                            <th style="width:80px;">Hari</th>
                            <th style="width:95px;">Jam Masuk</th>
                            <th style="width:95px;">Jam Pulang</th>
                            <th style="width:95px;">Durasi (Jam)</th>
                            <th style="width:85px;">Lembur (Jam)</th>
                            <th style="width:110px;">Status</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $filterStatus = request('status', 'semua');
                            $barisTampil = $filterStatus === 'semua'
                                ? $baris
                                : array_values(array_filter($baris, fn($b) => $b['status'] === $filterStatus));
                            $noUrut = 0;
                        @endphp
                        @forelse($barisTampil as $b)
                            <tr>
                                <td class="sel-tengah text-muted">{{ ++$noUrut }}</td>
                                <td class="sel-tengah">{{ $b['tanggal_label'] }}</td>
                                <td class="sel-tengah text-muted">{{ $b['nama_hari'] }}</td>
                                <td class="sel-tengah fw-semibold">{{ $b['jam_masuk'] ? substr($b['jam_masuk'], 0, 5) : '-' }}</td>
                                <td class="sel-tengah fw-semibold">{{ $b['jam_pulang'] ? substr($b['jam_pulang'], 0, 5) : '-' }}</td>
                                <td class="sel-kanan">{{ number_format($b['durasi_jam'], 1, ',', '') }}</td>
                                <td class="sel-kanan">{{ $b['lembur_jam'] > 0 ? number_format($b['lembur_jam'], 1, ',', '') : '-' }}</td>
                                <td class="sel-tengah">
                                    <span class="{{ match($b['kategori']) {
                                        'success' => 'sel-hijau',
                                        'warning' => 'sel-kuning',
                                        'secondary' => 'sel-abu',
                                        default => 'sel-erah',
                                    } }} d-inline-block">{{ $b['status'] }}</span>
                                </td>
                                <td class="text-muted small">{{ $b['keterangan'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <i class="bi bi-inbox fs-1 d-block mb-3 text-secondary opacity-50"></i>
                                    <p class="text-muted mb-0">Tidak ada data untuk saringan ini.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="sel-tengah" colspan="5">TOTAL / RATA-RATA ({{ $ringkasan['total_hari_kerja'] }} hari kerja)</td>
                            <td class="sel-kanan">{{ number_format($ringkasan['total_jam_kerja'], 1, ',', '') }}</td>
                            <td class="sel-kanan">{{ number_format($ringkasan['total_lembur_jam'], 1, ',', '') }}</td>
                            <td class="sel-tengah">{{ $ringkasan['terlambat'] }}x telat</td>
                            <td class="small text-muted">Rata-rata {{ number_format($ringkasan['rata_rata_jam'], 1, ',', '') }} jam/hari</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function submitDetail(url, target) {
        const form = document.querySelector('form[action*="laporan/kehadiran/karyawan"]');
        if (!form) return;
        form.action = url;
        form.target = target;
        form.submit();
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
