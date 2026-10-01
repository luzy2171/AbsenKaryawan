<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Audit Log #{{ $log->id }} - Absensi-BBM</title>
    @include('partials.favicon')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ \App\Helpers\Asset::url('css/custom.css') }}">
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 sidebar p-3">
            @include('partials.brand')
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
                @if(auth()->user()->isTrueApprover())
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/tanda-tangan*') ? 'active' : '' }}" href="{{ route('signature.edit') }}">
                        <i class="bi bi-pen me-2"></i> Tanda Tangan
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
                    <a class="nav-link {{ request()->is('admin/perusahaan') ? 'active' : '' }}" href="{{ route('company-profile.index') }}">
                        <i class="bi bi-building me-2"></i> Profil Perusahaan
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
                    <h4 class="fw-bold m-0 mb-1"><i class="bi bi-journal-text text-success me-2"></i>Detail Audit Log</h4>
                    <small class="text-muted">#{{ $log->id }} &mdash; {{ $log->created_at->format('d/m/Y H:i:s') }}</small>
                </div>
                <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-light border">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Audit Logs
                </a>
            </div>

            <div class="card-custom p-4 bg-white mb-4 fade-in">
                <div class="row g-3">
                    <div class="col-md-4">
                        <small class="text-muted d-block">Aksi</small>
                        <span class="badge bg-primary-subtle text-primary fs-6">{{ strtoupper($log->action) }}</span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Modul</small>
                        <span class="badge bg-secondary-subtle text-secondary fs-6">{{ strtoupper($log->module) }}</span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Status</small>
                        <span class="badge {{ $log->status === 'success' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} fs-6">
                            {{ ucfirst($log->status) }}
                        </span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Pengguna</small>
                        <strong>{{ $log->user ? $log->user->name : 'System' }}</strong>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Waktu</small>
                        <strong>{{ $log->created_at->format('d/m/Y H:i:s') }}</strong>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">IP Address</small>
                        <strong>{{ $log->ip_address ?: '-' }}</strong>
                    </div>
                    <div class="col-12">
                        <small class="text-muted d-block">Deskripsi</small>
                        <p class="mb-0">{{ $log->description ?: '-' }}</p>
                    </div>
                    <div class="col-12">
                        <small class="text-muted d-block">User Agent</small>
                        <p class="mb-0 small text-muted text-break">{{ $log->user_agent ?: '-' }}</p>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card-custom p-4 bg-white h-100">
                        <h6 class="fw-bold text-danger mb-3"><i class="bi bi-arrow-left-circle me-2"></i>Nilai Lama (sebelum)</h6>
                        @if(!empty($log->old_values))
                            <table class="table table-sm table-bordered mb-0">
                                <thead><tr><th>Field</th><th>Nilai</th></tr></thead>
                                <tbody>
                                    @foreach($log->old_values as $field => $value)
                                        <tr>
                                            <td class="fw-semibold">{{ $field }}</td>
                                            <td>{{ is_scalar($value) || $value === null ? ($value ?? '-') : json_encode($value) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="text-muted small mb-0">Tidak ada data perubahan lama.</p>
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card-custom p-4 bg-white h-100">
                        <h6 class="fw-bold text-success mb-3"><i class="bi bi-arrow-right-circle me-2"></i>Nilai Baru (sesudah)</h6>
                        @if(!empty($log->new_values))
                            <table class="table table-sm table-bordered mb-0">
                                <thead><tr><th>Field</th><th>Nilai</th></tr></thead>
                                <tbody>
                                    @foreach($log->new_values as $field => $value)
                                        <tr>
                                            <td class="fw-semibold">{{ $field }}</td>
                                            <td>{{ is_scalar($value) || $value === null ? ($value ?? '-') : json_encode($value) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="text-muted small mb-0">Tidak ada data perubahan baru.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
