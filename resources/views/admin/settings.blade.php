<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Waktu Kerja - Absensi-BBM</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sidebar { height: 100vh; background-color: #fff; border-right: 1px solid #dee2e6; }
        .nav-link { color: #333; border-radius: 8px; margin-bottom: 5px; }
        .nav-link.active { background-color: #e8f5e9; color: #2e7d32; font-weight: bold; }
        .card-custom { border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
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
                    <a class="nav-link" href="{{ url('/dashboard') }}"><i class="bi bi-grid me-2"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/karyawan') }}"><i class="bi bi-people me-2"></i> Karyawan</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/absensi') }}"><i class="bi bi-calendar-check me-2"></i> Absensi</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="{{ url('/admin/settings') }}"><i class="bi bi-gear me-2"></i> Pengaturan</a>
                </li>
            </ul>
        </div>

        <div class="col-md-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold m-0">Pengaturan Parameter Aplikasi</h4>
                    <small class="text-muted">Konfigurasi jam kerja dan batasan keterlambatan karyawan</small>
                </div>
                <div class="d-flex align-items-center">
                    <span class="me-3 fw-semibold">Administrator</span>
                    <i class="bi bi-person-circle fs-3 text-secondary"></i>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show card-custom p-3 mb-4 border-0 bg-success-subtle text-success" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> <strong>Berhasil!</strong> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="box" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="row">
                <div class="col-md-6">
                    <div class="card card-custom p-4 bg-white">
                        <h5 class="fw-bold text-dark mb-4"><i class="bi bi-clock-history me-2 text-success"></i>Atur Jam Operasional</h5>

                        <form action="{{ route('settings.update') }}" method="POST">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label fw-semibold text-secondary small">JAM MASUK KERJA</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-box-arrow-in-right text-success"></i></span>
                                    <input type="time" name="jam_masuk"
                                           value="{{ $settings['jam_masuk'] ?? '08:00' }}"
                                           class="form-control bg-light border-start-0" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold text-secondary small">JAM PULANG KERJA</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-box-arrow-left text-danger"></i></span>
                                    <input type="time" name="jam_pulang"
                                           value="{{ $settings['jam_pulang'] ?? '17:00' }}"
                                           class="form-control bg-light border-start-0" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold text-secondary small">BATAS TOLERANSI TERLAMBAT</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-hourglass-split text-warning"></i></span>
                                    <input type="number" name="toleransi_terlambat"
                                           value="{{ $settings['toleransi_terlambat'] ?? '15' }}"
                                           class="form-control bg-light border-x-0 text-center" min="0" required>
                                    <span class="input-group-text bg-light border-start-0 text-muted small">Menit</span>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-success fw-bold py-2 card-custom shadow-sm">
                                    <i class="bi bi-floppy me-2"></i> Simpan Konfigurasi
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
