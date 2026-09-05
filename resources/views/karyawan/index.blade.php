<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Karyawan - Absensi-BBM</title>
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
                @if(auth()->user()->isAdmin())
                <li class="nav-item mt-3">
                    <small class="text-muted px-3 fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">PENGATURAN</small>
                </li>
                                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/leaves*') ? 'active' : '' }}" href="{{ route('admin.leaves.index') }}">
                        <i class="bi bi-envelope-paper me-2"></i> Izin & Cuti
                    </a>
                </li>
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
                    <h4 class="fw-bold m-0 mb-1"><i class="bi bi-people text-success me-2"></i>Data Karyawan</h4>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-person-badge text-muted me-2"></i>
                        <small class="text-muted">{{ count($karyawans) }} total karyawan terdaftar</small>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <div class="text-end me-3">
                        <p class="mb-0 fw-semibold small">{{ auth()->user()->name }}</p>
                        <small class="text-muted">{{ auth()->user()->isSuperadmin() ? 'Superadmin' : 'Admin' }}</small>
                    </div>
                    <div class="avatar-circle text-success">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                </div>
            </div>

            @if(session('status'))
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4 fade-in" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>{{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4 fade-in" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card card-custom p-4 bg-white fade-in">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="fw-bold mb-1">Daftar Karyawan</h5>
                        <p class="text-muted small mb-0">Kelola data karyawan dan sinkronisasi dengan mesin absensi</p>
                    </div>

                    <div class="d-flex gap-2">
                        <form action="{{ route('karyawan.sync-mesin') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menarik semua data user aktif dari mesin fisik ke database lokal web?');" class="m-0">
                            @csrf
                            <button type="submit" class="btn btn-outline-success">
                                <i class="bi bi-arrow-clockwise me-1"></i> Sync dari Mesin
                            </button>
                        </form>

                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTambahKaryawan">
                            <i class="bi bi-plus-lg me-1"></i> Tambah Karyawan
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr class="border-bottom">
                                <th class="fw-bold text-muted small">ID / PIN</th>
                                <th class="fw-bold text-muted small">NAMA</th>
                                <th class="fw-bold text-muted small">DEPARTEMEN</th>
                                <th class="fw-bold text-muted small">JABATAN</th>
                                <th class="fw-bold text-muted small">SISA CUTI</th>
                                <th class="fw-bold text-muted small">STATUS</th>
                                <th class="fw-bold text-muted small text-center">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($karyawans as $k)
                            <tr class="border-bottom">
                                <td>
                                    <span class="badge bg-light text-dark px-3 py-2" style="font-family: 'Courier New', monospace;">
                                        {{ $k->id_karyawan }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle text-success me-2" style="width: 35px; height: 35px; font-size: 14px;">
                                            {{ strtoupper(substr($k->nama, 0, 1)) }}
                                        </div>
                                        <span class="fw-bold">{{ $k->nama }}</span>
                                    </div>
                                </td>
                                <td class="text-muted">{{ $k->departemen ?? '-' }}</td>
                                <td class="text-muted">{{ $k->jabatan ?? '-' }}</td>
                                <td>
                                    <span class="badge {{ $k->sisaCuti() <= 3 ? 'bg-danger-subtle text-danger' : 'bg-primary-subtle text-primary' }}">
                                        {{ $k->sisaCuti() }} Hari
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-success-subtle text-success">
                                        <i class="bi bi-circle-fill me-1" style="font-size: 6px;"></i>{{ $k->status }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <form action="{{ route('karyawan.destroy', $k->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus karyawan ini dari sistem dan mesin fisik?');" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <i class="bi bi-people fs-1 d-block mb-3 text-secondary opacity-50"></i>
                                    <p class="text-muted mb-0">Belum ada data karyawan.</p>
                                    <small class="text-muted">Klik tombol <strong>Sync dari Mesin</strong> atau <strong>Tambah Karyawan</strong></small>
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

<div class="modal fade" id="modalTambahKaryawan" tabindex="-1" aria-labelledby="modalTambahKaryawanLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold mb-1" id="modalTambahKaryawanLabel">
                        <i class="bi bi-person-plus text-success me-2"></i>Tambah Karyawan Baru
                    </h5>
                    <small class="text-muted">Data akan tersimpan di web dan mesin absensi</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('karyawan.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-hash text-muted me-1"></i>ID Karyawan / PIN Mesin
                        </label>
                        <input type="text" name="id_karyawan" class="form-control" placeholder="Contoh: 6" required>
                        <div class="form-text">
                            <i class="bi bi-info-circle me-1"></i>Pastikan ID berupa angka unik dan cocok dengan registrasi sidik jari di mesin.
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-person text-muted me-1"></i>Nama Lengkap
                        </label>
                        <input type="text" name="nama" class="form-control" placeholder="Nama Karyawan" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-building text-muted me-1"></i>Departemen
                        </label>
                        <input type="text" name="departemen" class="form-control" placeholder="Contoh: IT, HRD, GA">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-briefcase text-muted me-1"></i>Jabatan
                        </label>
                        <input type="text" name="jabatan" class="form-control" placeholder="Contoh: Software Engineer">
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Batal
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-lg me-1"></i>Simpan ke Web & Mesin
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


