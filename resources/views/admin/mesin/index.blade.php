<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontrol Pusat - Absensi-BBM</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <style>
        .table-responsive { max-height: 400px; overflow-y: auto; }
        .table thead th { position: sticky; top: 0; background: #f8f9fa; z-index: 1; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 sidebar p-3 d-none d-md-block">
            <div class="d-flex align-items-center mb-4 px-2 py-3">
                <div class="stat-icon bg-success text-white me-2" style="width: 40px; height: 40px;"><i class="bi bi-fingerprint"></i></div>
                <div><h5 class="fw-bold m-0 text-success" style="font-size: 18px;">Absensi-BBM</h5><small class="text-muted" style="font-size: 10px;">Attendance System</small></div>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link" href="{{ url('/dashboard') }}"><i class="bi bi-grid me-2"></i> Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/karyawan') }}"><i class="bi bi-people me-2"></i> Karyawan</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/absensi') }}"><i class="bi bi-calendar-check me-2"></i> Absensi</a></li>
                @if(auth()->user()->isApprover())
                    <li class="nav-item mt-3"><small class="text-muted px-3 fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">PENGATURAN</small></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('admin.leaves.index') }}"><i class="bi bi-envelope-paper me-2"></i> Izin & Cuti</a></li>
                    @if(auth()->user()->isTrueApprover())
                    <li class="nav-item"><a class="nav-link" href="{{ route('admin.cuti.control') }}"><i class="bi bi-sliders me-2"></i> Kontrol Cuti</a></li>
                    @endif
                    @if(auth()->user()->isSuperadmin())
                    <li class="nav-item"><a class="nav-link" href="{{ url('/admin/settings') }}"><i class="bi bi-clock-history me-2"></i> Set Jam Kerja</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ url('/pengaturan') }}"><i class="bi bi-gear me-2"></i> Kontrol Mesin</a></li>
                    <!-- DYNAMIC MESIN SIDEBAR MENU -->
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/mesin-absensi*') ? 'active' : '' }}"
                            href="{{ route('admin.mesin.index') }}">
                            <i class="bi bi-diagram-3 me-2"></i> Kontrol Pusat All Vendor
                        </a>
                    </li>
                    <!-- END DYNAMIC MESIN -->
                    <li class="nav-item"><a class="nav-link" href="{{ route('admin.maintenance.index') }}"><i class="bi bi-database-fill-gear me-2"></i> Maintenance DB</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ url('/admin/users') }}"><i class="bi bi-person-gear me-2"></i> Manajemen User</a></li>
                    @endif
                    @if(auth()->user()->isTrueApprover())
                    <li class="nav-item"><a class="nav-link" href="{{ url('/admin/audit-logs') }}"><i class="bi bi-journal-text me-2"></i> Audit Logs</a></li>
                    @endif
                @endif
                <li class="nav-item mt-auto pt-3 border-top">
                    <form action="{{ route('logout') }}" method="POST">@csrf<button type="submit" class="nav-link text-danger w-100 text-start border-0 bg-transparent"><i class="bi bi-box-arrow-left me-2"></i> Keluar</button></form>
                </li>
            </ul>
        </div>

        <div class="col-md-10 p-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4 fade-in">
                <div class="d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-4 me-3">
                        <i class="bi bi-diagram-3 fs-3"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold m-0 mb-1">Kontrol Pusat All Vendor</h4>
                        <small class="text-muted">Manajemen sinkronisasi dan data karyawan secara terpusat untuk semua mesin fisik</small>
                    </div>
                </div>
            </div>

            @if(session('status'))
                <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm">
                    <i class="bi bi-check-circle-fill me-2"></i>{{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="row g-4 mb-4 fade-in">
                <!-- Aksi Global / Central Control -->
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">
                            <!-- Kirim Data -->
                            <h6 class="fw-bold mb-3"><i class="bi bi-upload text-primary me-2"></i>Kirim Data ke Mesin</h6>
                            <p class="text-muted small">Kirim akun karyawan lokal ke mesin absensi fisik.</p>
                            <form action="{{ route('admin.mesin.kirim') }}" method="POST">
                                @csrf
                                <div class="mb-2">
                                    <select class="form-select bg-light border-0" name="karyawan_id" required>
                                        <option value="">-- Pilih Karyawan --</option>
                                        @foreach($karyawans as $k)
                                            <option value="{{ $k->id }}">{{ $k->id_karyawan }} - {{ $k->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <select class="form-select bg-light border-0" name="mesin_tujuan" required>
                                        <option value="">-- Mesin Tujuan --</option>
                                        <option value="all">Semua Mesin (HIK & Solution)</option>
                                        <option value="hikvision">Hanya Hikvision</option>
                                        <option value="solution">Hanya Solution</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 fw-bold rounded-3">
                                    Kirim
                                </button>
                            </form>
                            
                            <hr class="my-4">
                            
                            <!-- Tarik Data -->
                            <h6 class="fw-bold mb-3"><i class="bi bi-download text-success me-2"></i>Tarik Data dari Mesin</h6>
                            <p class="text-muted small">Tarik data pendaftaran dari mesin untuk didaftarkan otomatis ke database lokal (sinkronisasi).</p>
                            <form action="{{ route('admin.mesin.tarik') }}" method="POST" onsubmit="return confirm('Mulai tarik data dan daftarkan ke database lokal?')">
                                @csrf
                                <div class="mb-3">
                                    <select class="form-select bg-light border-0" name="mesin_tujuan" required>
                                        <option value="">-- Mesin Sumber --</option>
                                        <option value="all">Semua Mesin (HIK & Solution)</option>
                                        <option value="hikvision">Hanya Hikvision</option>
                                        <option value="solution">Hanya Solution</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-outline-success w-100 fw-bold rounded-3">
                                    Tarik Data
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Statistik Data Mesin Side by side -->
                <div class="col-md-8">
                    <div class="row g-4 h-100">
                        <!-- Tabel HIK -->
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm rounded-4 h-100">
                                <div class="card-header bg-white border-bottom-0 pt-4 pb-2 px-4 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-person-bounding-box text-dark fs-5 me-2"></i>
                                        <h6 class="fw-bold m-0 me-2">Mesin Hikvision</h6>
                                    </div>
                                    <span class="badge bg-dark-subtle text-dark rounded-pill">{{ $totalHik }} user</span>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="table-light text-secondary">
                                                <tr>
                                                    <th class="ps-4 fw-semibold small" style="width:25%">PIN</th>
                                                    <th class="fw-semibold small" style="width:50%">Nama</th>
                                                    <th class="fw-semibold small text-center pe-4" style="width:25%">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($usersHik as $u)
                                                    @php $isLocal = $karyawans->where('id_karyawan', (string)$u['pin'])->first(); @endphp
                                                    <tr>
                                                        <td class="ps-4 fw-semibold {{ $isLocal ? 'text-dark' : 'text-danger' }}">{{ $u['pin'] }}</td>
                                                        <td class="small text-truncate" style="max-width: 100px;">
                                                            {{ $u['name'] ?: '-' }}
                                                            @if(!$isLocal)<br><small class="text-danger" style="font-size:10px">Belum di-DB</small>@endif
                                                        </td>
                                                        <td class="text-center pe-4">
                                                            <form action="{{ route('admin.mesin.hapus', ['mesin' => 'hikvision', 'pin' => $u['pin']]) }}" method="POST" onsubmit="return confirm('Hapus permanen PIN {{ $u['pin'] }} dari Hikvision?');">
                                                                @csrf @method('DELETE')
                                                                <button type="submit" class="btn btn-sm btn-outline-danger border-0"><i class="bi bi-trash"></i></button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr><td colspan="3" class="text-center py-4 text-muted small">Kosong</td></tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tabel Solution -->
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm rounded-4 h-100">
                                <div class="card-header bg-white border-bottom-0 pt-4 pb-2 px-4 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-fingerprint text-primary fs-5 me-2"></i>
                                        <h6 class="fw-bold m-0 me-2 text-primary">Mesin Solution</h6>
                                    </div>
                                    <span class="badge bg-primary-subtle text-primary rounded-pill">{{ $totalSol }} user</span>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="table-light text-secondary">
                                                <tr>
                                                    <th class="ps-4 fw-semibold small" style="width:25%">PIN</th>
                                                    <th class="fw-semibold small" style="width:50%">Nama</th>
                                                    <th class="fw-semibold small text-center pe-4" style="width:25%">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($usersSol as $u)
                                                    @php $isLocal = $karyawans->where('id_karyawan', (string)$u['pin'])->first(); @endphp
                                                    <tr>
                                                        <td class="ps-4 fw-semibold {{ $isLocal ? 'text-primary' : 'text-danger' }}">{{ $u['pin'] }}</td>
                                                        <td class="small text-truncate" style="max-width: 100px;">
                                                            {{ $u['name'] ?: '-' }}
                                                            @if(!$isLocal)<br><small class="text-danger" style="font-size:10px">Belum di-DB</small>@endif
                                                        </td>
                                                        <td class="text-center pe-4">
                                                            <form action="{{ route('admin.mesin.hapus', ['mesin' => 'solution', 'pin' => $u['pin']]) }}" method="POST" onsubmit="return confirm('Hapus permanen PIN {{ $u['pin'] }} dari Solution?');">
                                                                @csrf @method('DELETE')
                                                                <button type="submit" class="btn btn-sm btn-outline-danger border-0"><i class="bi bi-trash"></i></button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr><td colspan="3" class="text-center py-4 text-muted small">Kosong</td></tr>
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
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
