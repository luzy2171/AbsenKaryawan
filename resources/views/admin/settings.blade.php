<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Waktu Kerja - Absensi-BBM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #2e7d32;
            --primary-light: #4caf50;
            --primary-dark: #1b5e20;
            --success-color: #2e7d32;
            --warning-color: #f57c00;
            --danger-color: #d32f2f;
            --info-color: #0288d1;
            --gray-50: #fafafa;
            --gray-100: #f5f5f5;
            --gray-200: #eeeeee;
            --gray-300: #e0e0e0;
            --gray-600: #757575;
            --gray-800: #424242;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        body { 
            background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
            font-family: 'Inter', 'Segoe UI', sans-serif; 
            min-height: 100vh;
        }
        
        .sidebar { 
            height: 100vh; 
            background: linear-gradient(180deg, #ffffff 0%, #fafafa 100%);
            border-right: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
        }
        
        .nav-link { 
            color: var(--gray-800); 
            border-radius: 10px; 
            margin-bottom: 6px;
            padding: 12px 16px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .nav-link:hover {
            background-color: var(--gray-100);
            transform: translateX(4px);
        }
        
        .nav-link.active { 
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            color: var(--primary-color); 
            font-weight: 600;
            box-shadow: var(--shadow-sm);
        }
        
        .card-custom { 
            border: none; 
            border-radius: 16px; 
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            background: white;
        }
        
        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .btn {
            border-radius: 10px;
            padding: 8px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid var(--gray-300);
            padding: 12px 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.1);
        }
        
        .avatar-circle {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            font-size: 18px;
        }
        
        h3, h4, h5 {
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        
        .setting-item {
            background: var(--gray-50);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .setting-item:hover {
            border-color: var(--primary-color);
            background: white;
        }
        
        .input-group-text {
            border-radius: 10px 0 0 10px;
            border: 1px solid var(--gray-300);
            background: var(--gray-50);
        }
        
        .input-group .form-control:first-child,
        .input-group .form-select:first-child {
            border-radius: 0 10px 10px 0;
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 sidebar p-3 d-none d-md-block">
            <div class="d-flex align-items-center mb-4 px-2 py-3">
                <div class="stat-icon bg-success text-white me-2">
                    <i class="bi bi-fingerprint"></i>
                </div>
                <div>
                    <h5 class="fw-bold m-0 text-success" style="font-size: 18px;">Absensi-BBM</h5>
                    <small class="text-muted" style="font-size: 10px;">Attendance System</small>
                </div>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/dashboard') }}">
                        <i class="bi bi-grid me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/karyawan') }}">
                        <i class="bi bi-people me-2"></i> Karyawan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/absensi') }}">
                        <i class="bi bi-calendar-check me-2"></i> Absensi
                    </a>
                </li>
                @if(auth()->user()->isSuperadmin())
                <li class="nav-item mt-3">
                    <small class="text-muted px-3 fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">PENGATURAN</small>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="{{ url('/admin/settings') }}">
                        <i class="bi bi-clock-history me-2"></i> Set Jam Kerja
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/pengaturan') }}">
                        <i class="bi bi-gear me-2"></i> Kontrol Mesin
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/admin/users') }}">
                        <i class="bi bi-person-gear me-2"></i> Manajemen User
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/admin/audit-logs') }}">
                        <i class="bi bi-journal-text me-2"></i> Audit Logs
                    </a>
                </li>
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
                    <h4 class="fw-bold m-0 mb-1"><i class="bi bi-sliders text-success me-2"></i>Pengaturan Sistem</h4>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-gear text-muted me-2"></i>
                        <small class="text-muted">Konfigurasi jam kerja, toleransi, dan interval auto-pull</small>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <div class="text-end me-3">
                        <p class="mb-0 fw-semibold small">{{ auth()->user()->name }}</p>
                        <small class="text-muted">Superadmin</small>
                    </div>
                    <div class="avatar-circle text-success">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4 fade-in" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i><strong>Berhasil!</strong> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="row fade-in">
                <div class="col-md-8">
                    <div class="card-custom p-4 bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h5 class="fw-bold mb-1"><i class="bi bi-clock-history me-2 text-success"></i>Pengaturan Parameter</h5>
                                <p class="text-muted small mb-0">Atur konfigurasi sistem absensi</p>
                            </div>
                        </div>

                        <form action="{{ route('settings.update') }}" method="POST">
                            @csrf

                            <div class="setting-item">
                                <label class="form-label fw-bold mb-3">
                                    <i class="bi bi-box-arrow-in-right text-success me-2"></i>Jam Masuk Kerja
                                </label>
                                <input type="time" name="jam_masuk"
                                       value="{{ $settings['jam_masuk'] ?? '08:00' }}"
                                       class="form-control" required>
                                <small class="text-muted mt-2 d-block">
                                    <i class="bi bi-info-circle me-1"></i>Waktu standar karyawan harus mulai bekerja
                                </small>
                            </div>

                            <div class="setting-item">
                                <label class="form-label fw-bold mb-3">
                                    <i class="bi bi-box-arrow-right text-danger me-2"></i>Jam Pulang Kerja
                                </label>
                                <input type="time" name="jam_pulang"
                                       value="{{ $settings['jam_pulang'] ?? '17:00' }}"
                                       class="form-control" required>
                                <small class="text-muted mt-2 d-block">
                                    <i class="bi bi-info-circle me-1"></i>Waktu standar karyawan selesai bekerja
                                </small>
                            </div>

                            <div class="setting-item">
                                <label class="form-label fw-bold mb-3">
                                    <i class="bi bi-hourglass-split text-warning me-2"></i>Toleransi Keterlambatan
                                </label>
                                <div class="input-group">
                                    <input type="number" name="toleransi_terlambat"
                                           value="{{ $settings['toleransi_terlambat'] ?? '15' }}"
                                           class="form-control text-center fs-4 fw-bold" min="0" required>
                                    <span class="input-group-text text-muted fw-semibold">Menit</span>
                                </div>
                                <small class="text-muted mt-2 d-block">
                                    <i class="bi bi-info-circle me-1"></i>Batas waktu toleransi sebelum dihitung terlambat
                                </small>
                            </div>

                            <div class="setting-item">
                                <label class="form-label fw-bold mb-3">
                                    <i class="bi bi-arrow-repeat text-info me-2"></i>Interval Auto-Pull
                                </label>
                                <select name="auto_pull_interval" class="form-select">
                                    <option value="1" {{ ($settings['auto_pull_interval'] ?? '24') == '1' ? 'selected' : '' }}>Setiap 1 Jam</option>
                                    <option value="2" {{ ($settings['auto_pull_interval'] ?? '24') == '2' ? 'selected' : '' }}>Setiap 2 Jam</option>
                                    <option value="4" {{ ($settings['auto_pull_interval'] ?? '24') == '4' ? 'selected' : '' }}>Setiap 4 Jam</option>
                                    <option value="6" {{ ($settings['auto_pull_interval'] ?? '24') == '6' ? 'selected' : '' }}>Setiap 6 Jam</option>
                                    <option value="8" {{ ($settings['auto_pull_interval'] ?? '24') == '8' ? 'selected' : '' }}>Setiap 8 Jam</option>
                                    <option value="12" {{ ($settings['auto_pull_interval'] ?? '24') == '12' ? 'selected' : '' }}>Setiap 12 Jam</option>
                                    <option value="24" {{ ($settings['auto_pull_interval'] ?? '24') == '24' ? 'selected' : '' }}>Sekali Sehari (24 Jam)</option>
                                </select>
                                <small class="text-muted mt-2 d-block">
                                    <i class="bi bi-info-circle me-1"></i>Jarak waktu sistem otomatis tarik data dari mesin
                                </small>
                            </div>

                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-success btn-lg py-3">
                                    <i class="bi bi-check-circle me-2"></i> Simpan Konfigurasi
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card-custom p-4 bg-white mb-3">
                        <h6 class="fw-bold mb-3"><i class="bi bi-info-circle text-primary me-2"></i>Informasi</h6>
                        <div class="small text-muted">
                            <p class="mb-2"><i class="bi bi-check2 text-success me-2"></i>Perubahan akan diterapkan langsung</p>
                            <p class="mb-2"><i class="bi bi-check2 text-success me-2"></i>Sistem akan menghitung status berdasarkan setting ini</p>
                            <p class="mb-0"><i class="bi bi-check2 text-success me-2"></i>Auto-pull berjalan jika switch ON</p>
                        </div>
                    </div>

                    <div class="card-custom p-4 bg-white">
                        <h6 class="fw-bold mb-3"><i class="bi bi-calculator text-success me-2"></i>Perhitungan</h6>
                        <div class="small">
                            <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                                <span class="text-muted">Jam Masuk:</span>
                                <span class="fw-bold">{{ $settings['jam_masuk'] ?? '08:00' }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                                <span class="text-muted">Toleransi:</span>
                                <span class="fw-bold text-warning">+{{ $settings['toleransi_terlambat'] ?? '15' }} menit</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                                <span class="text-muted">Batas Terlambat:</span>
                                <span class="fw-bold text-danger">
                                    {{ \Carbon\Carbon::parse($settings['jam_masuk'] ?? '08:00')->addMinutes($settings['toleransi_terlambat'] ?? 15)->format('H:i') }}
                                </span>
                            </div>
                            <div class="alert alert-info border-0 mt-3 mb-0 small">
                                <i class="bi bi-lightbulb me-1"></i>Absen setelah batas = <strong>Terlambat</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
