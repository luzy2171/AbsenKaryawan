<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Absensi-BBM</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sidebar { height: 100vh; background-color: #fff; border-right: 1px solid #dee2e6; }
        .nav-link { color: #333; border-radius: 8px; margin-bottom: 5px; }
        .nav-link.active { background-color: #e8f5e9; color: #2e7d32; font-weight: bold; }
        .card-custom { border: none; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.04); }
        .avatar-circle { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 sidebar p-3 d-none d-md-block">
            <div class="d-flex align-items-center mb-4 px-2">
                <i class="bi bi-fingerprint text-success fs-3 me-2"></i>
                <h5 class="fw-bold m-0 text-success">Absensi-BBM</h5>
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
            </ul>
        </div>

        <div class="col-md-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold m-0">Dashboard Ringkasan</h4>
                    <small class="text-muted">{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</small>
                </div>
                <div class="d-flex align-items-center">
                    <span class="me-3 fw-semibold">Administrator</span>
                    <i class="bi bi-person-circle fs-3 text-secondary"></i>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card card-custom p-3 bg-white">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted small mb-1">Total Karyawan</p>
                                <h3 class="fw-bold mb-0">{{ $totalKaryawan }}</h3>
                            </div>
                            <div class="bg-primary-subtle text-primary p-2 rounded-3"><i class="bi bi-people fs-4"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-custom p-3 bg-white">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted small mb-1">Hadir Hari Ini</p>
                                <h3 class="fw-bold mb-0 text-success">{{ $hadirHariIni }}</h3>
                            </div>
                            <div class="bg-success-subtle text-success p-2 rounded-3"><i class="bi bi-check-circle fs-4"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-custom p-3 bg-white">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted small mb-1">Terlambat</p>
                                <h3 class="fw-bold mb-0 text-warning">{{ $terlambat }}</h3>
                            </div>
                            <div class="bg-warning-subtle text-warning p-2 rounded-3"><i class="bi bi-exclamation-circle fs-4"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-custom p-3 bg-white">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted small mb-1">Tidak Hadir (Alpha)</p>
                                <h3 class="fw-bold mb-0 text-danger">{{ $tidakHadir }}</h3>
                            </div>
                            <div class="bg-danger-subtle text-danger p-2 rounded-3"><i class="bi bi-x-circle fs-4"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card card-custom p-4 bg-white h-100">
                        <h5 class="fw-bold mb-3">Grafik Kehadiran</h5>
                        <div class="d-flex justify-content-center align-items-center my-auto" style="min-height: 200px;">
                            <canvas id="donutChart" width="200" height="200"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card card-custom p-4 bg-white h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold m-0">Absensi Terbaru</h5>
                            <a href="{{ url('/absensi') }}" class="btn btn-sm btn-outline-success">Lihat Semua</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-borderless align-middle">
                                <thead>
                                    <tr class="text-muted small border-bottom">
                                        <th>NAMA</th>
                                        <th>JAM MASUK</th>
                                        <th>STATUS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($absensiTerbaru as $absen)
                                    <tr class="border-bottom">
                                        <td>
                                            <div class="d-flex align-items-center py-1">
                                                <div class="avatar-circle bg-light text-success fw-bold me-2 small">
                                                    {{ strtoupper(substr($absen->karyawan->nama, 0, 1)) }}
                                                </div>
                                                <div>
                                                    <div class="fw-bold small">{{ $absen->karyawan->nama }}</div>
                                                    <small class="text-muted extra-small">{{ $absen->karyawan->jabatan ?? 'Staf' }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="small">{{ \Carbon\Carbon::parse($absen->jam_masuk)->format('H:i') }} WIB</td>
                                        <td>
                                            <span class="badge {{ $absen->status == 'Hadir' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }} px-2 py-1.5 small">
                                                {{ $absen->status }}
                                            </span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-5 small">
                                            <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                            Belum ada absensi hari ini
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('donutChart');
    if(ctx) {
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Hadir', 'Terlambat', 'Tidak Hadir'],
                datasets: [{
                    data: [{{ $hadirHariIni }}, {{ $terlambat }}, {{ $tidakHadir }}],
                    backgroundColor: ['#2e7d32', '#f57c00', '#d32f2f'],
                    borderWidth: 0
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });
    }
</script>
</body>
</html>
