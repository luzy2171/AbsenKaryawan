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
                <!-- System Monitoring & Management -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-header bg-white border-bottom-0 pt-4 pb-2 px-4 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold m-0"><i class="bi bi-activity text-danger me-2"></i>System Monitoring & Devices</h6>
                            <button class="btn btn-sm btn-primary fw-semibold rounded-3" data-bs-toggle="modal" data-bs-target="#addDeviceModal">
                                <i class="bi bi-plus-lg me-1"></i> Tambah Perangkat
                            </button>
                        </div>
                        <div class="card-body px-4 pb-4 pt-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light text-secondary">
                                        <tr>
                                            <th class="small fw-semibold">NAMA PERANGKAT</th>
                                            <th class="small fw-semibold">VENDOR / TIPE</th>
                                            <th class="small fw-semibold">IP ADDRESS</th>
                                            <th class="small fw-semibold text-center">STATUS</th>
                                            <th class="small fw-semibold">LAST PING</th>
                                            <th class="small fw-semibold">RESPONSE</th>
                                            <th class="small fw-semibold text-end pe-3">AKSI</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($machines as $m)
                                        <tr>
                                            <td class="fw-semibold">{{ $m->machine_name }}</td>
                                            <td>
                                                @if($m->machine_type == 'hikvision')
                                                    <span class="badge bg-dark text-white"><i class="bi bi-person-bounding-box me-1"></i>Hikvision</span>
                                                @else
                                                    <span class="badge bg-primary text-white"><i class="bi bi-fingerprint me-1"></i>Solution</span>
                                                @endif
                                            </td>
                                            <td class="font-monospace text-muted">{{ $m->machine_ip }}:{{ $m->port }}</td>
                                            <td class="text-center">
                                                <span class="badge {{ $m->getStatusBadgeClass() }} rounded-pill px-3">{{ $m->getStatusLabel() }}</span>
                                            </td>
                                            <td class="small text-muted">{{ $m->getLastPingHuman() }}</td>
                                            <td class="small text-muted">{{ $m->getFormattedResponseTime() }}</td>
                                            <td class="text-end pe-3">
                                                <button type="button" class="btn btn-sm btn-outline-warning border-0 me-1" title="Edit Perangkat" onclick="editDevice({{ $m->id }}, '{{ $m->machine_name }}', '{{ $m->machine_type }}', '{{ $m->machine_ip }}', '{{ $m->port }}', '{{ $m->username }}')"><i class="bi bi-pencil"></i></button>
                                                <form action="{{ route('admin.mesin.device.ping', $m->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-success border-0 me-1" title="Ping Koneksi"><i class="bi bi-broadcast"></i></button>
                                                </form>
                                                <form action="{{ route('admin.mesin.device.destroy', $m->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus perangkat {{ $m->machine_name }}?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger border-0" title="Hapus Perangkat"><i class="bi bi-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="7" class="text-center py-4 text-muted">Belum ada perangkat yang ditambahkan.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-4 mb-4 fade-in">
                <!-- Action Panels -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3"><i class="bi bi-upload text-primary me-2"></i>Kirim Data ke Mesin</h6>
                            <p class="text-muted small">Kirim akun karyawan lokal ke mesin absensi fisik (Sinkronisasi Web ke Mesin).</p>
                            <form action="{{ route('admin.mesin.kirim') }}" method="POST">
                                @csrf
                                <div class="row g-2 mb-3">
                                    <div class="col-md-6">
                                        <select class="form-select bg-light border-0" name="karyawan_id" required>
                                            <option value="">-- Pilih Karyawan --</option>
                                            @foreach($karyawans as $k)
                                                <option value="{{ $k->id }}">{{ $k->id_karyawan }} - {{ $k->nama }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <select class="form-select bg-light border-0" name="mesin_tujuan" required>
                                            <option value="">-- Mesin Tujuan --</option>
                                            <option value="all">Semua Mesin (HIK & Solution)</option>
                                            <option value="hikvision">Hanya Hikvision</option>
                                            <option value="solution">Hanya Solution</option>
                                        </select>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 fw-bold rounded-3">
                                    <i class="bi bi-send me-1"></i> Kirim
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3"><i class="bi bi-download text-success me-2"></i>Tarik Data dari Mesin</h6>
                            <p class="text-muted small">Tarik data pendaftaran dari mesin untuk didaftarkan otomatis ke database lokal (Sinkronisasi Mesin ke Web).</p>
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
                                    <i class="bi bi-cloud-download me-1"></i> Tarik Data
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Real-Time Event & Controlled -->
            <div class="row g-4 mb-4 fade-in">
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 h-auto">
                        <div class="card-header bg-dark text-white border-bottom-0 pt-3 pb-2 px-4 rounded-top-4 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold m-0"><i class="bi bi-shield-lock me-2"></i>Real-Time Event & Controlled</h6>
                            <span class="badge bg-secondary rounded-pill" style="font-size: 10px;">Hikvision Only</span>
                        </div>
                        <div class="card-body p-4">
                            <p class="text-muted small mb-3">Kontrol akses pintu dari jarak jauh dan pantau log aktivitas pintu secara real-time.</p>
                            
                            <div class="row g-3 mb-4">
                                <div class="col-md-9">
                                    <select class="form-select bg-light border-0" id="live_machine_id">
                                        <option value="">-- Pilih Pintu Hikvision untuk Dipantau --</option>
                                        @foreach($machines->where('machine_type', 'hikvision') as $m)
                                            <option value="{{ $m->id }}">{{ $m->machine_name }} ({{ $m->machine_ip }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <form action="{{ route('admin.mesin.door.open') }}" method="POST" id="doorOpenForm" onsubmit="return submitDoorOpen(event);">
                                        @csrf
                                        <input type="hidden" name="machine_id" id="door_machine_id">
                                        <button type="submit" class="btn btn-warning w-100 fw-bold rounded-3 text-dark shadow-sm" id="btnOpenDoor" disabled>
                                            <i class="bi bi-unlock-fill me-2"></i> BUKA PINTU
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div class="table-responsive bg-light rounded-3 p-1">
                                <table class="table table-borderless table-hover align-middle mb-0" style="font-size: 13px;">
                                    <thead class="text-muted" style="border-bottom: 2px solid #e9ecef;">
                                        <tr>
                                            <th class="fw-semibold pb-2 ps-3">TIME</th>
                                            <th class="fw-semibold pb-2">EVENT TYPES</th>
                                            <th class="fw-semibold pb-2">NAME</th>
                                            <th class="fw-semibold pb-2">EMP. ID</th>
                                            <th class="fw-semibold pb-2">VERIFY</th>
                                        </tr>
                                    </thead>
                                    <tbody id="liveEventTable">
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">
                                                <i class="bi bi-activity fs-3 d-block mb-2 opacity-50"></i>
                                                Pilih mesin di atas untuk mulai memantau <strong>Real-Time Events</strong>.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data User di Mesin (Tab Layout) -->
            <div class="row g-4 mb-4 fade-in">
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white border-bottom-0 pt-4 pb-2 px-4 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold m-0"><i class="bi bi-people text-primary me-2"></i>Data Karyawan di Mesin Fisik</h6>
                        </div>
                        <div class="card-body p-0">
                            <!-- Nav tabs -->
                            <ul class="nav nav-tabs px-4 border-bottom-0" id="mesinTab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active fw-semibold text-dark pb-3 border-0 border-bottom border-3 border-primary" id="hik-tab" data-bs-toggle="tab" data-bs-target="#hik" type="button" role="tab">
                                        <i class="bi bi-person-bounding-box me-1"></i> Hikvision <span class="badge bg-dark ms-1">{{ $totalHik }}</span>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fw-semibold text-muted pb-3 border-0" id="sol-tab" data-bs-toggle="tab" data-bs-target="#sol" type="button" role="tab" onclick="this.classList.add('text-dark', 'border-bottom', 'border-3', 'border-primary'); this.classList.remove('text-muted'); document.getElementById('hik-tab').classList.remove('text-dark', 'border-bottom', 'border-3', 'border-primary'); document.getElementById('hik-tab').classList.add('text-muted');">
                                        <i class="bi bi-fingerprint me-1"></i> Solution <span class="badge bg-primary ms-1">{{ $totalSol }}</span>
                                    </button>
                                </li>
                            </ul>

                            <!-- Tab panes -->
                            <div class="tab-content" id="mesinTabContent">
                                <!-- Tab Hikvision -->
                                <div class="tab-pane fade show active" id="hik" role="tabpanel">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="table-light text-secondary">
                                                <tr>
                                                    <th class="ps-4 fw-semibold small" style="width: 20%;">PIN / ID</th>
                                                    <th class="fw-semibold small" style="width: 40%;">NAMA DI MESIN</th>
                                                    <th class="fw-semibold small text-center" style="width: 20%;">STATUS LOKAL DB</th>
                                                    <th class="fw-semibold small text-center pe-4" style="width: 20%;">AKSI</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($usersHik as $u)
                                                    @php $isLocal = $karyawans->where('id_karyawan', (string)$u['pin'])->first(); @endphp
                                                    <tr>
                                                        <td class="ps-4 fw-bold {{ $isLocal ? 'text-dark' : 'text-danger' }}">{{ $u['pin'] }}</td>
                                                        <td>{{ $u['name'] ?: '-' }}</td>
                                                        <td class="text-center">
                                                            @if($isLocal)
                                                                <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>Sinkron</span>
                                                            @else
                                                                <span class="badge bg-danger-subtle text-danger"><i class="bi bi-exclamation-triangle me-1"></i>Belum di-DB</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center pe-4">
                                                            <div class="dropdown">
                                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                                    Opsi
                                                                </button>
                                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                                                    @if($isLocal)
                                                                        <li>
                                                                            <button type="button" class="dropdown-item" onclick="editKaryawan({{ $isLocal->id }}, '{{ $isLocal->nama }}', '{{ $isLocal->departemen }}', '{{ $isLocal->jabatan }}')">
                                                                                <i class="bi bi-pencil-square text-primary me-2"></i> Edit Data Web
                                                                            </button>
                                                                        </li>
                                                                        <li>
                                                                            <form action="{{ route('admin.mesin.karyawan.db.destroy', $isLocal->id) }}" method="POST" onsubmit="return confirm('Hapus karyawan ini DARI DATABASE WEB saja? (Tetap ada di mesin fisik)');">
                                                                                @csrf @method('DELETE')
                                                                                <button type="submit" class="dropdown-item">
                                                                                    <i class="bi bi-trash text-warning me-2"></i> Hapus dari Web
                                                                                </button>
                                                                            </form>
                                                                        </li>
                                                                        <li><hr class="dropdown-divider"></li>
                                                                    @endif
                                                                    <li>
                                                                        <form action="{{ route('admin.mesin.hapus', ['mesin' => 'hikvision', 'pin' => $u['pin']]) }}" method="POST" onsubmit="return confirm('Hapus permanen PIN {{ $u['pin'] }} DARI MESIN FISIK HIKVISION?');">
                                                                            @csrf @method('DELETE')
                                                                            <button type="submit" class="dropdown-item text-danger">
                                                                                <i class="bi bi-trash-fill text-danger me-2"></i> Hapus dari Mesin
                                                                            </button>
                                                                        </form>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr><td colspan="4" class="text-center py-4 text-muted small">Kosong</td></tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                
                                <!-- Tab Solution -->
                                <div class="tab-pane fade" id="sol" role="tabpanel">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="table-light text-secondary">
                                                <tr>
                                                    <th class="ps-4 fw-semibold small" style="width: 20%;">PIN / ID</th>
                                                    <th class="fw-semibold small" style="width: 40%;">NAMA DI MESIN</th>
                                                    <th class="fw-semibold small text-center" style="width: 20%;">STATUS LOKAL DB</th>
                                                    <th class="fw-semibold small text-center pe-4" style="width: 20%;">AKSI</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($usersSol as $u)
                                                    @php $isLocal = $karyawans->where('id_karyawan', (string)$u['pin'])->first(); @endphp
                                                    <tr>
                                                        <td class="ps-4 fw-bold {{ $isLocal ? 'text-primary' : 'text-danger' }}">{{ $u['pin'] }}</td>
                                                        <td>{{ $u['name'] ?: '-' }}</td>
                                                        <td class="text-center">
                                                            @if($isLocal)
                                                                <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>Sinkron</span>
                                                            @else
                                                                <span class="badge bg-danger-subtle text-danger"><i class="bi bi-exclamation-triangle me-1"></i>Belum di-DB</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center pe-4">
                                                            <div class="dropdown">
                                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                                    Opsi
                                                                </button>
                                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                                                    @if($isLocal)
                                                                        <li>
                                                                            <button type="button" class="dropdown-item" onclick="editKaryawan({{ $isLocal->id }}, '{{ $isLocal->nama }}', '{{ $isLocal->departemen }}', '{{ $isLocal->jabatan }}')">
                                                                                <i class="bi bi-pencil-square text-primary me-2"></i> Edit Data Web
                                                                            </button>
                                                                        </li>
                                                                        <li>
                                                                            <form action="{{ route('admin.mesin.karyawan.db.destroy', $isLocal->id) }}" method="POST" onsubmit="return confirm('Hapus karyawan ini DARI DATABASE WEB saja? (Tetap ada di mesin fisik)');">
                                                                                @csrf @method('DELETE')
                                                                                <button type="submit" class="dropdown-item">
                                                                                    <i class="bi bi-trash text-warning me-2"></i> Hapus dari Web
                                                                                </button>
                                                                            </form>
                                                                        </li>
                                                                        <li><hr class="dropdown-divider"></li>
                                                                    @endif
                                                                    <li>
                                                                        <form action="{{ route('admin.mesin.hapus', ['mesin' => 'solution', 'pin' => $u['pin']]) }}" method="POST" onsubmit="return confirm('Hapus permanen PIN {{ $u['pin'] }} DARI MESIN FISIK SOLUTION?');">
                                                                            @csrf @method('DELETE')
                                                                            <button type="submit" class="dropdown-item text-danger">
                                                                                <i class="bi bi-trash-fill text-danger me-2"></i> Hapus dari Mesin
                                                                            </button>
                                                                        </form>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr><td colspan="4" class="text-center py-4 text-muted small">Kosong</td></tr>
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
    </div>
</div>

<!-- Modal Add Device -->
<div class="modal fade" id="addDeviceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-primary text-white rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-hdd-network me-2"></i>Tambah Perangkat Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.mesin.device.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Vendor / Tipe Mesin</label>
                        <select name="machine_type" class="form-select bg-light border-0" required onchange="updateDefaultPort(this.value)">
                            <option value="solution">Solution / ZKTeco</option>
                            <option value="hikvision">Hikvision</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Nama Perangkat (Bebas)</label>
                        <input type="text" name="machine_name" class="form-control bg-light border-0" placeholder="Contoh: Solution Pintu Depan" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-8">
                            <label class="form-label fw-semibold small text-muted">IP Address</label>
                            <input type="text" name="machine_ip" class="form-control bg-light border-0" placeholder="192.168.1.xxx" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold small text-muted">Port</label>
                            <input type="number" name="port" id="inputPort" class="form-control bg-light border-0" value="4370" required>
                        </div>
                    </div>
                    
                    <div id="credentialsArea" style="display: none;">
                        <hr class="my-4">
                        <p class="small text-muted mb-3"><i class="bi bi-info-circle me-1"></i>Otorisasi khusus Hikvision SDK</p>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">Username</label>
                            <input type="text" name="username" class="form-control bg-light border-0" placeholder="admin">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">Password</label>
                            <input type="password" name="password" class="form-control bg-light border-0" placeholder="Password mesin">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light px-4 rounded-3 fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4 rounded-3 fw-bold">Simpan Perangkat</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Device -->
<div class="modal fade" id="editDeviceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-warning text-dark rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Perangkat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editDeviceForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Vendor / Tipe Mesin</label>
                        <select name="machine_type" id="edit_machine_type" class="form-select bg-light border-0" required onchange="updateEditDefaultPort(this.value)">
                            <option value="solution">Solution / ZKTeco</option>
                            <option value="hikvision">Hikvision</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Nama Perangkat</label>
                        <input type="text" name="machine_name" id="edit_machine_name" class="form-control bg-light border-0" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-8">
                            <label class="form-label fw-semibold small text-muted">IP Address</label>
                            <input type="text" name="machine_ip" id="edit_machine_ip" class="form-control bg-light border-0" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold small text-muted">Port</label>
                            <input type="number" name="port" id="edit_port" class="form-control bg-light border-0" required>
                        </div>
                    </div>
                    
                    <div id="editCredentialsArea" style="display: none;">
                        <hr class="my-4">
                        <p class="small text-muted mb-3"><i class="bi bi-info-circle me-1"></i>Otorisasi khusus Hikvision SDK</p>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">Username</label>
                            <input type="text" name="username" id="edit_username" class="form-control bg-light border-0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">Password (Kosongkan jika tidak diubah)</label>
                            <input type="password" name="password" id="edit_password" class="form-control bg-light border-0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light px-4 rounded-3 fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning px-4 rounded-3 fw-bold">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Karyawan (Web DB) -->
<div class="modal fade" id="editKaryawanModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-primary text-white rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-lines-fill me-2"></i>Edit Data Web Karyawan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="editKaryawanForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Nama Lengkap</label>
                        <input type="text" name="nama" id="edit_karyawan_nama" class="form-control bg-light border-0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Departemen</label>
                        <input type="text" name="departemen" id="edit_karyawan_departemen" class="form-control bg-light border-0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Jabatan</label>
                        <input type="text" name="jabatan" id="edit_karyawan_jabatan" class="form-control bg-light border-0">
                    </div>
                    <div class="alert alert-warning border-0 shadow-sm small py-2 mt-4 mb-0">
                        <i class="bi bi-info-circle-fill me-1"></i> Perubahan nama di sini hanya mengubah data di <b>Database Web</b>. Gunakan tombol "Kirim Data" jika ingin memperbarui nama di layar mesin fisik.
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light px-4 rounded-3 fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4 rounded-3 fw-bold">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function editKaryawan(id, nama, dept, jab) {
        document.getElementById('editKaryawanForm').action = "{{ url('admin/mesin-absensi/karyawan') }}/" + id;
        document.getElementById('edit_karyawan_nama').value = nama;
        document.getElementById('edit_karyawan_departemen').value = (dept === '-' || dept === 'null' || !dept) ? '' : dept;
        document.getElementById('edit_karyawan_jabatan').value = (jab === '-' || jab === 'null' || !jab) ? '' : jab;
        
        var modal = new bootstrap.Modal(document.getElementById('editKaryawanModal'));
        modal.show();
    }

    function updateDefaultPort(val) {
        if(val === 'hikvision') {
            document.getElementById('inputPort').value = '80';
            document.getElementById('credentialsArea').style.display = 'block';
        } else {
            document.getElementById('inputPort').value = '4370';
            document.getElementById('credentialsArea').style.display = 'none';
        }
    }

    function updateEditDefaultPort(val) {
        if(val === 'hikvision') {
            document.getElementById('editCredentialsArea').style.display = 'block';
        } else {
            document.getElementById('editCredentialsArea').style.display = 'none';
        }
    }

    function editDevice(id, name, type, ip, port, username) {
        document.getElementById('editDeviceForm').action = "{{ url('admin/mesin-absensi/device') }}/" + id;
        document.getElementById('edit_machine_name').value = name;
        document.getElementById('edit_machine_type').value = type;
        document.getElementById('edit_machine_ip').value = ip;
        document.getElementById('edit_port').value = port;
        document.getElementById('edit_username').value = username;
        
        updateEditDefaultPort(type);
        
        var modal = new bootstrap.Modal(document.getElementById('editDeviceModal'));
        modal.show();
    }

    // Real-Time Event Polling
    let pollingInterval = null;
    const selectMachine = document.getElementById('live_machine_id');
    const btnOpenDoor = document.getElementById('btnOpenDoor');
    const hiddenMachineId = document.getElementById('door_machine_id');
    const tableBody = document.getElementById('liveEventTable');

    function submitDoorOpen(e) {
        if (!hiddenMachineId.value) {
            e.preventDefault();
            alert("Pilih mesin terlebih dahulu!");
            return false;
        }
        return confirm('Yakin ingin membuka pintu secara remote?');
    }

    function fetchEvents(machineId) {
        fetch("{{ route('admin.mesin.door.events') }}?machine_id=" + machineId)
            .then(response => response.json())
            .then(data => {
                if(data.events && data.events.length > 0) {
                    tableBody.innerHTML = '';
                    data.events.forEach(evt => {
                        let colorClass = 'text-dark';
                        let icon = 'bi-record-circle';
                        
                        if (evt.event_type.includes('Unlocked') || evt.event_type.includes('Login')) {
                            colorClass = 'text-success'; icon = 'bi-unlock';
                        } else if (evt.event_type.includes('Locked')) {
                            colorClass = 'text-danger'; icon = 'bi-lock';
                        } else if (evt.event_type.includes('Button')) {
                            colorClass = 'text-warning'; icon = 'bi-box-arrow-right';
                        } else if (evt.event_type.includes('Authenticated')) {
                            colorClass = 'text-primary'; icon = 'bi-person-check';
                        }

                        let row = `<tr>
                            <td class="text-muted">\${evt.time}</td>
                            <td class="fw-semibold \${colorClass}"><i class="bi \${icon} me-1"></i>\${evt.event_type}</td>
                            <td class="fw-bold">\${evt.name !== 'unknown' && evt.name !== '-' ? evt.name : '<span class="text-muted">--</span>'}</td>
                            <td class="text-muted">\${evt.employee_id !== 'unknown' && evt.employee_id !== '-' ? evt.employee_id : '--'}</td>
                            <td class="text-muted">\${evt.verify_mode !== 'invalid' && evt.verify_mode !== '-' ? evt.verify_mode.replace('faceOrFpOrCardOrPw', 'Multi-Verify').replace('fingerprint', 'Fingerprint') : '--'}</td>
                        </tr>`;
                        tableBody.innerHTML += row;
                    });
                } else if(data.events && data.events.length === 0) {
                    tableBody.innerHTML = `<tr><td colspan="5" class="text-center py-3 text-muted">Belum ada event hari ini.</td></tr>`;
                }
            })
            .catch(error => {
                console.error("Error fetching events:", error);
            });
    }

    selectMachine.addEventListener('change', function() {
        if(pollingInterval) clearInterval(pollingInterval);
        
        const machineId = this.value;
        if(machineId) {
            btnOpenDoor.disabled = false;
            hiddenMachineId.value = machineId;
            tableBody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm me-2"></div>Memuat data live...</td></tr>`;
            
            fetchEvents(machineId); // Initial fetch
            pollingInterval = setInterval(() => fetchEvents(machineId), 3000); // Poll every 3 seconds
        } else {
            btnOpenDoor.disabled = true;
            hiddenMachineId.value = '';
            tableBody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-muted"><i class="bi bi-activity fs-3 d-block mb-2 opacity-50"></i>Pilih mesin di atas untuk mulai memantau *Real-Time Events*.</td></tr>`;
        }
    });
</script>
</body>
</html>
