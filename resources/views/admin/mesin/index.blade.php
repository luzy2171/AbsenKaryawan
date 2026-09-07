<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Absensi-BBM</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
</head>

<body>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 sidebar p-3 d-none d-md-block">
                <div class="d-flex align-items-center mb-4 px-2 py-3">
                    <div class="stat-icon bg-success text-white me-2" style="width: 40px; height: 40px;">
                        <i class="bi bi-fingerprint"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold m-0 text-success" style="font-size: 18px;">Absensi-BBM</h5>
                        <small class="text-muted" style="font-size: 10px;">Attendance System</small>
                    </div>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}"
                            href="{{ url('/dashboard') }}">
                            <i class="bi bi-grid me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('karyawan*') ? 'active' : '' }}"
                            href="{{ url('/karyawan') }}">
                            <i class="bi bi-people me-2"></i> Karyawan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('absensi*') ? 'active' : '' }}"
                            href="{{ url('/absensi') }}">
                            <i class="bi bi-calendar-check me-2"></i> Absensi
                        </a>
                    </li>
                    @if(auth()->user()->isApprover())
                        <li class="nav-item mt-3">
                            <small class="text-muted px-3 fw-semibold"
                                style="font-size: 11px; letter-spacing: 0.5px;">PENGATURAN</small>
                        </li>
                                        <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/leaves*') ? 'active' : '' }}" href="{{ route('admin.leaves.index') }}">
                        <i class="bi bi-envelope-paper me-2"></i> Izin & Cuti
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
                            <a class="nav-link {{ request()->is('admin/settings*') ? 'active' : '' }}"
                                href="{{ url('/admin/settings') }}">
                                <i class="bi bi-clock-history me-2"></i> Set Jam Kerja
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('pengaturan*') ? 'active' : '' }}"
                                href="{{ url('/pengaturan') }}">
                                <i class="bi bi-gear me-2"></i> Kontrol Mesin
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('admin/mesin-absensi*') ? 'active' : '' }}"
                                href="{{ route('admin.mesin.index') }}">
                                <i class="bi bi-hdd-network me-2"></i> Mesin Absensi
                            </a>
                        </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/maintenance*') ? 'active' : '' }}" href="{{ route('admin.maintenance.index') }}">
                        <i class="bi bi-database-fill-gear me-2"></i> Maintenance DB
                    </a>
                </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('admin/users*') ? 'active' : '' }}"
                                href="{{ url('/admin/users') }}">
                                <i class="bi bi-person-gear me-2"></i> Manajemen User
                            </a>
                        </li>
                        @endif
@if(auth()->user()->isTrueApprover())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('admin/audit-logs*') ? 'active' : '' }}"
                                href="{{ url('/admin/audit-logs') }}">
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
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4 fade-in">
                <div class="d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-4 me-3">
                        <i class="bi bi-hdd-network fs-3"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold m-0 mb-1">Mesin Absensi</h4>
                        <small class="text-muted">Kelola & sinkronisasi data karyawan ke mesin Solution & HIK</small>
                    </div>
                </div>
                <div>
                    <button class="btn btn-outline-primary fw-semibold rounded-3 px-4">
                        <i class="bi bi-arrow-left-right me-2"></i>Sinkron Dua Arah
                    </button>
                </div>
            </div>

            <div class="row g-4 mb-4 fade-in">
                <!-- Form Tambah Karyawan ke Mesin -->
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-4">Daftarkan Karyawan ke Mesin</h6>
                            <form>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-semibold">Mesin Tujuan</label>
                                    <select class="form-select bg-light border-0">
                                        <option value="solution">Solution</option>
                                        <option value="hik">HIK</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-semibold">Karyawan</label>
                                    <select class="form-select bg-light border-0">
                                        <option value="">Pilih karyawan</option>
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label text-muted small fw-semibold">Finger ID</label>
                                    <input type="number" class="form-control bg-light border-0" value="0">
                                </div>
                                <button type="button" class="btn btn-primary w-100 fw-bold rounded-3">
                                    <i class="bi bi-plus-lg me-2"></i> Tambah
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Statistik -->
                <div class="col-md-8">
                    <div class="row g-3 h-100">
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm rounded-4 bg-primary text-white h-100">
                                <div class="card-body p-4 d-flex flex-column justify-content-center">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h6 class="text-white-50 mb-1">Mesin Solution</h6>
                                            <h3 class="fw-bold mb-0">1</h3>
                                        </div>
                                        <div class="bg-white bg-opacity-25 p-3 rounded-4">
                                            <i class="bi bi-fingerprint fs-3"></i>
                                        </div>
                                    </div>
                                    <span class="small text-white-50">karyawan terdaftar</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm rounded-4 bg-dark text-white h-100">
                                <div class="card-body p-4 d-flex flex-column justify-content-center">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h6 class="text-white-50 mb-1">Mesin HIK</h6>
                                            <h3 class="fw-bold mb-0">2</h3>
                                        </div>
                                        <div class="bg-white bg-opacity-25 p-3 rounded-4">
                                            <i class="bi bi-person-bounding-box fs-3"></i>
                                        </div>
                                    </div>
                                    <span class="small text-white-50">karyawan terdaftar</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel Data Mesin -->
            <div class="row g-4 fade-in">
                <!-- Tabel Solution -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <h6 class="fw-bold m-0 me-2">Solution</h6>
                                <span class="badge bg-primary-subtle text-primary rounded-pill">1 karyawan</span>
                            </div>
                            <button class="btn btn-sm btn-outline-success fw-semibold rounded-3 px-3">
                                <i class="bi bi-send me-1"></i> Kirim ke HIK
                            </button>
                        </div>
                        <div class="card-body p-0 mt-3">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" style="min-width: 500px;">
                                    <thead class="table-light text-secondary">
                                        <tr>
                                            <th class="ps-4 fw-semibold small">ID</th>
                                            <th class="fw-semibold small">Nama</th>
                                            <th class="fw-semibold small">Dept</th>
                                            <th class="fw-semibold small">Jabatan</th>
                                            <th class="fw-semibold small text-center">Finger</th>
                                            <th class="fw-semibold small text-center pe-4">Sync</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="ps-4 text-muted small">004</td>
                                            <td class="fw-semibold small">Dewi Lestari</td>
                                            <td class="small"><span class="badge bg-light text-dark border">Marketing</span></td>
                                            <td class="small text-muted">Marketing Staff</td>
                                            <td class="text-center small">0</td>
                                            <td class="text-center pe-4">
                                                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabel HIK -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <h6 class="fw-bold m-0 me-2">HIK</h6>
                                <span class="badge bg-dark-subtle text-dark rounded-pill">2 karyawan</span>
                            </div>
                            <button class="btn btn-sm btn-outline-primary fw-semibold rounded-3 px-3">
                                <i class="bi bi-send me-1"></i> Kirim ke Solution
                            </button>
                        </div>
                        <div class="card-body p-0 mt-3">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" style="min-width: 500px;">
                                    <thead class="table-light text-secondary">
                                        <tr>
                                            <th class="ps-4 fw-semibold small">ID</th>
                                            <th class="fw-semibold small">Nama</th>
                                            <th class="fw-semibold small">Dept</th>
                                            <th class="fw-semibold small">Jabatan</th>
                                            <th class="fw-semibold small text-center">Finger</th>
                                            <th class="fw-semibold small text-center pe-4">Sync</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="ps-4 text-muted small">004</td>
                                            <td class="fw-semibold small">Dewi Lestari</td>
                                            <td class="small"><span class="badge bg-light text-dark border">Marketing</span></td>
                                            <td class="small text-muted">Marketing Staff</td>
                                            <td class="text-center small">0</td>
                                            <td class="text-center pe-4">
                                                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="ps-4 text-muted small">001</td>
                                            <td class="fw-semibold small">Budi</td>
                                            <td class="small"><span class="badge bg-light text-dark border">IT</span></td>
                                            <td class="small text-muted">Programmer</td>
                                            <td class="text-center small">0</td>
                                            <td class="text-center pe-4">
                                                <i class="bi bi-exclamation-circle-fill text-warning fs-5" title="Belum tersinkronisasi"></i>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>

<!-- Modal Edit Profil -->
<div class="modal fade" id="profileModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-circle me-2"></i>Edit Profil & Password</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('profile.update') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Lengkap</label>
                        <input type="text" name="name" class="form-control" value="{{ auth()->user()->name }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Username</label>
                        <input type="text" name="username" class="form-control" value="{{ auth()->user()->username }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="email" class="form-control" value="{{ auth()->user()->email }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Password Baru <small class="text-muted fw-normal">(Opsional)</small></label>
                        <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin mengubah password">
                        <small class="text-muted">Minimal 6 karakter jika ingin diubah.</small>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

