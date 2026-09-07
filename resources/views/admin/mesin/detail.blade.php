<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $mesinName }} - Absensi-BBM</title>
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
                    @if(auth()->user()->isApprover())
                        <li class="nav-item mt-3">
                            <small class="text-muted px-3 fw-semibold"
                                style="font-size: 11px; letter-spacing: 0.5px;">PENGATURAN</small>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('admin.leaves.index') }}">
                                <i class="bi bi-envelope-paper me-2"></i> Izin & Cuti
                            </a>
                        </li>
                        @if(auth()->user()->isTrueApprover())
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('admin.cuti.control') }}">
                                <i class="bi bi-sliders me-2"></i> Kontrol Cuti
                            </a>
                        </li>
                        @endif
                        @if(auth()->user()->isSuperadmin())
                        <li class="nav-item">
                            <a class="nav-link" href="{{ url('/admin/settings') }}">
                                <i class="bi bi-clock-history me-2"></i> Set Jam Kerja
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ url('/pengaturan') }}">
                                <i class="bi bi-gear me-2"></i> Kontrol Mesin
                            </a>
                        </li>
                        <!-- DYNAMIC MESIN SIDEBAR MENU -->
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('admin/mesin-absensi/hikvision') ? 'active' : '' }}"
                                href="{{ route('admin.mesin.hikvision') }}">
                                <i class="bi bi-person-bounding-box me-2"></i> Mesin Hikvision
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('admin/mesin-absensi/solution') ? 'active' : '' }}"
                                href="{{ route('admin.mesin.solution') }}">
                                <i class="bi bi-fingerprint me-2"></i> Mesin Solution
                            </a>
                        </li>
                        <!-- END DYNAMIC MESIN -->
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('admin.maintenance.index') }}">
                                <i class="bi bi-database-fill-gear me-2"></i> Maintenance DB
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ url('/admin/users') }}">
                                <i class="bi bi-person-gear me-2"></i> Manajemen User
                            </a>
                        </li>
                        @endif
                        @if(auth()->user()->isTrueApprover())
                        <li class="nav-item">
                            <a class="nav-link" href="{{ url('/admin/audit-logs') }}">
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
                            <i class="{{ $mesinType == 'hikvision' ? 'bi bi-person-bounding-box' : 'bi bi-fingerprint' }} fs-3"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold m-0 mb-1">{{ $mesinName }}</h4>
                            <small class="text-muted">Kelola sinkronisasi user dan data untuk {{ $mesinName }}</small>
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
                    <!-- Form Kirim Data Karyawan -->
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm rounded-4 h-100">
                            <div class="card-body p-4">
                                <h6 class="fw-bold mb-4">Kirim User ke {{ $mesinName }}</h6>
                                <p class="text-muted small">Pilih karyawan dari database lokal untuk dikirim ke mesin fisik.</p>
                                <form action="{{ route('admin.mesin.kirim', $mesinType) }}" method="POST">
                                    @csrf
                                    <div class="mb-3">
                                        <label class="form-label text-muted small fw-semibold">Karyawan (Lokal DB)</label>
                                        <select class="form-select bg-light border-0" name="karyawan_id" required>
                                            <option value="">Pilih karyawan</option>
                                            @foreach($karyawans as $k)
                                                <option value="{{ $k->id }}">{{ $k->id_karyawan }} - {{ $k->nama }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100 fw-bold rounded-3">
                                        <i class="bi bi-send me-2"></i> Kirim ke Mesin
                                    </button>
                                </form>
                                <hr class="my-4">
                                <h6 class="fw-bold mb-3">Tarik Semua User</h6>
                                <p class="text-muted small">Tarik data pengguna dari mesin fisik dan daftarkan ke database lokal jika belum ada.</p>
                                <form action="{{ route('admin.mesin.tarik', $mesinType) }}" method="POST" onsubmit="return confirm('Yakin ingin menarik data user dari mesin ke database lokal?')">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-success w-100 fw-bold rounded-3">
                                        <i class="bi bi-cloud-download me-2"></i> Tarik dari Mesin
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Statistik / Data Users di Mesin -->
                    <div class="col-md-8">
                        <div class="card border-0 shadow-sm rounded-4 h-100">
                            <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <h6 class="fw-bold m-0 me-2">Data User Terdaftar di {{ $mesinName }}</h6>
                                    <span class="badge bg-primary-subtle text-primary rounded-pill">{{ $totalMesin }} user</span>
                                </div>
                            </div>
                            <div class="card-body p-0 mt-3">
                                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light text-secondary sticky-top">
                                            <tr>
                                                <th class="ps-4 fw-semibold small">PIN / ID</th>
                                                <th class="fw-semibold small">Nama di Mesin</th>
                                                <th class="fw-semibold small text-center">Status DB Lokal</th>
                                                <th class="fw-semibold small text-center pe-4">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($usersMesin as $u)
                                                @php
                                                    $isLocal = $karyawans->where('id_karyawan', (string)$u['pin'])->first();
                                                @endphp
                                                <tr>
                                                    <td class="ps-4 fw-semibold text-danger">{{ $u['pin'] }}</td>
                                                    <td class="small">{{ $u['name'] ?: '-' }}</td>
                                                    <td class="text-center">
                                                        @if($isLocal)
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle me-1"></i>Tersinkronisasi</span>
                                                        @else
                                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="bi bi-exclamation-triangle me-1"></i>Belum Terdaftar</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center pe-4">
                                                        <form action="{{ route('admin.mesin.hapus', ['mesin' => $mesinType, 'pin' => $u['pin']]) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus PIN {{ $u['pin'] }} secara permanen dari {{ $mesinName }}?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus dari mesin">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-center py-4 text-muted">
                                                        <i class="bi bi-inbox fs-2 d-block mb-2 text-black-50"></i>
                                                        Tidak ada data user terdeteksi di mesin ini.
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
