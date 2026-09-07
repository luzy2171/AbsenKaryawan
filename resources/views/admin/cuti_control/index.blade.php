<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Izin & Cuti - Absensi-BBM</title>
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
        <!-- SIDEBAR -->
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
                
@if(auth()->user()->isApprover())
                <li class="nav-item mt-3">
                    <small class="text-muted px-3 fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">PENGATURAN</small>
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

        <!-- MAIN CONTENT -->
                <div class="col-md-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4 fade-in">
                <div>
                    <h4 class="fw-bold m-0 mb-1"><i class="bi bi-sliders text-primary me-2"></i>Kontrol Jatah Cuti</h4>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-info-circle text-muted me-2"></i>
                        <small class="text-muted">Kelola jatah cuti tahunan masing-masing karyawan.</small>
                    </div>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card border-0 shadow-sm rounded-4 mb-4 fade-in">
                <div class="card-body p-0">
                    <div class="p-4 border-bottom d-flex justify-content-between align-items-center bg-light rounded-top-4">
                        <h6 class="fw-bold mb-0"><i class="bi bi-list-task me-2 text-primary"></i>Daftar Karyawan</h6>
                        <form action="{{ route('admin.cuti.control') }}" method="GET" class="d-flex">
                            <input type="text" name="search" class="form-control form-control-sm me-2" placeholder="Cari Nama/ID..." value="{{ request('search') }}">
                            <button type="submit" class="btn btn-sm btn-outline-primary">Cari</button>
                        </form>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-secondary">
                                <tr>
                                    <th class="ps-4 fw-semibold">ID Karyawan</th>
                                    <th class="fw-semibold">Nama Karyawan</th>
                                    <th class="fw-semibold">Departemen</th>
                                    <th class="fw-semibold text-center">Status Cuti</th>
                                    <th class="fw-semibold text-center">Jatah Cuti (Hari)</th>
                                    <th class="fw-semibold text-end pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($karyawans as $karyawan)
                                <tr>
                                    <td class="ps-4 text-muted">{{ $karyawan->id_karyawan }}</td>
                                    <td class="fw-bold">{{ $karyawan->nama }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark border">{{ $karyawan->departemen ?? 'Tidak Ada' }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if($karyawan->jatah_cuti_tahunan > 0)
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill">
                                                <i class="bi bi-check-circle me-1"></i> Bisa Cuti
                                            </span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill">
                                                <i class="bi bi-x-circle me-1"></i> Belum Bisa Cuti
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center fw-bold text-primary">
                                        {{ $karyawan->jatah_cuti_tahunan ?? 0 }}
                                    </td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-outline-primary rounded-3" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editCutiModal-{{ $karyawan->id }}">
                                            <i class="bi bi-pencil me-1"></i> Update
                                        </button>
                                        
                                        <!-- Modal Edit Cuti -->
                                        <div class="modal fade" id="editCutiModal-{{ $karyawan->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content border-0 shadow-lg rounded-4 text-start">
                                                    <div class="modal-header border-bottom-0 pb-0 mt-3 mx-3">
                                                        <h5 class="fw-bold mb-0 text-primary">Update Jatah Cuti</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form action="{{ route('admin.cuti.control.update', $karyawan->id) }}" method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="modal-body p-4">
                                                            <div class="mb-3">
                                                                <label class="form-label text-muted">Nama Karyawan</label>
                                                                <input type="text" class="form-control bg-light" value="{{ $karyawan->nama }}" readonly>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Jatah Cuti Tahunan (Hari)</label>
                                                                <div class="input-group">
                                                                    <input type="number" name="jatah_cuti_tahunan" class="form-control" value="{{ $karyawan->jatah_cuti_tahunan ?? 0 }}" min="0" required>
                                                                    <button type="button" class="btn btn-outline-secondary" onclick="document.querySelector('#editCutiModal-{{ $karyawan->id }} input[name=jatah_cuti_tahunan]').value = 12">
                                                                        Set 12 Hari
                                                                    </button>
                                                                </div>
                                                                <small class="text-muted d-block mt-1">Karyawan mendapat indikator biru jika jatah cuti > 0.</small>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer border-top-0 px-4 pb-4 pt-0">
                                                            <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" class="btn btn-primary px-4">Simpan Perubahan</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-2 d-block mb-2 text-black-50"></i>
                                        Tidak ada data karyawan ditemukan.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($karyawans->hasPages())
                <div class="card-footer bg-white border-0 p-3 rounded-bottom-4">
                    {{ $karyawans->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
