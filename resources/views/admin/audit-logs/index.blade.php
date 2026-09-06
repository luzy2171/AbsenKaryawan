<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - Absensi-BBM</title>
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
                @if(auth()->user()->isApprover())
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
                    <h4 class="fw-bold m-0 mb-1"><i class="bi bi-journal-text text-success me-2"></i>Audit Logs</h4>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-clock-history text-muted me-2"></i>
                        <small class="text-muted">Riwayat aktivitas sistem dan tracking user</small>
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

            <!-- Filter Section -->
            <div class="filter-card mb-4 fade-in">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">
                        <i class="bi bi-funnel text-primary me-2"></i>Filter Aktivitas
                    </h6>
                    <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-light btn-sm">
                        <i class="bi bi-arrow-clockwise me-1"></i>Reset
                    </a>
                </div>
                <form action="{{ route('admin.audit-logs.index') }}" method="GET" class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small">
                            <i class="bi bi-cube text-muted me-1"></i>Module
                        </label>
                        <select name="module" class="form-select form-select-sm">
                            <option value="all">Semua Module</option>
                            @foreach($modules as $module)
                            <option value="{{ $module }}" {{ request('module') == $module ? 'selected' : '' }}>
                                {{ ucfirst($module) }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small">
                            <i class="bi bi-play-circle text-muted me-1"></i>Action
                        </label>
                        <select name="action" class="form-select form-select-sm">
                            <option value="all">Semua Action</option>
                            @foreach($actions as $action)
                            <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>
                                {{ ucfirst($action) }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small">
                            <i class="bi bi-person text-muted me-1"></i>User
                        </label>
                        <select name="user_id" class="form-select form-select-sm">
                            <option value="all">Semua User</option>
                            @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small">
                            <i class="bi bi-calendar text-muted me-1"></i>Dari Tanggal
                        </label>
                        <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small">
                            <i class="bi bi-calendar-check text-muted me-1"></i>Sampai Tanggal
                        </label>
                        <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-search me-1"></i>Filter
                        </button>
                    </div>
                </form>
            </div>

            <!-- Export & Stats -->
            <div class="d-flex justify-content-between align-items-center mb-4 fade-in">
                <div class="d-flex align-items-center gap-3">
                    <a href="{{ route('admin.audit-logs.export', request()->all()) }}" class="btn btn-outline-success">
                        <i class="bi bi-download me-1"></i>Export Excel
                    </a>
                    <span class="badge bg-light text-dark">
                        <i class="bi bi-file-earspdf me-1"></i>{{ $logs->total() }} log ditemukan
                    </span>
                </div>
            </div>

            <!-- Audit Logs Table -->
            <div class="card-custom p-4 bg-white fade-in">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr class="border-bottom">
                                <th class="fw-bold text-muted small">
                                    <i class="bi bi-clock me-1"></i>Waktu
                                </th>
                                <th class="fw-bold text-muted small">
                                    <i class="bi bi-person me-1"></i>User
                                </th>
                                <th class="fw-bold text-muted small">
                                    <i class="bi bi-cube me-1"></i>Module
                                </th>
                                <th class="fw-bold text-muted small">
                                    <i class="bi bi-play-circle me-1"></i>Action
                                </th>
                                <th class="fw-bold text-muted small">
                                    <i class="bi bi-card-text me-1"></i>Deskripsi
                                </th>
                                <th class="fw-bold text-muted small">
                                    <i class="bi bi-globe me-1"></i>IP Address
                                </th>
                                <th class="fw-bold text-muted small text-center">
                                    <i class="bi bi-check-circle me-1"></i>Status
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                            <tr class="border-bottom">
                                <td class="small text-muted">
                                    <i class="bi bi-calendar3 me-1"></i>
                                    {{ $log->created_at->format('d/m/Y H:i:s') }}
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle text-success me-2" style="width: 28px; height: 28px; font-size: 11px;">
                                            {{ strtoupper(substr($log->user->name ?? 'Sys', 0, 1)) }}
                                        </div>
                                        <span class="fw-semibold small">
                                            {{ $log->user ? $log->user->name : 'System' }}
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary module-badge">
                                        {{ strtoupper($log->module) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-info-subtle text-info module-badge">
                                        {{ strtoupper($log->action) }}
                                    </span>
                                </td>
                                <td class="small text-muted description-text" title="{{ $log->description }}">
                                    {{ $log->description }}
                                </td>
                                <td class="small text-muted">
                                    <code>{{ $log->ip_address }}</code>
                                </td>
                                <td class="text-center">
                                    @if($log->status === 'success')
                                    <span class="badge bg-success-subtle text-success">
                                        <i class="bi bi-check-lg me-1"></i>Success
                                    </span>
                                    @else
                                    <span class="badge bg-danger-subtle text-danger">
                                        <i class="bi bi-x-lg me-1"></i>Failed
                                    </span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="bi bi-inbox fs-1 d-block mb-3 text-secondary opacity-50"></i>
                                    <p class="text-muted mb-1">Tidak ada log yang ditemukan</p>
                                    <small class="text-muted">Coba ubah filter atau reset pencarian</small>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($logs->hasPages())
                <div class="p-3 border-top mt-3">
                    {{ $logs->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


