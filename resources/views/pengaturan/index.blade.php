<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontrol Mesin - Absensi-BBM</title>
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
                <div class="stat-icon bg-success text-white me-2"><i class="bi bi-fingerprint"></i></div>
                <div><h5 class="fw-bold m-0 text-success" style="font-size: 18px;">Absensi-BBM</h5><small class="text-muted" style="font-size: 10px;">Attendance System</small></div>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}" href="{{ url('/dashboard') }}"><i class="bi bi-grid me-2"></i> Dashboard</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->is('karyawan*') ? 'active' : '' }}" href="{{ url('/karyawan') }}"><i class="bi bi-people me-2"></i> Karyawan</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->is('absensi*') ? 'active' : '' }}" href="{{ url('/absensi') }}"><i class="bi bi-calendar-check me-2"></i> Absensi</a></li>
                @if(auth()->user()->isSuperadmin())
                <li class="nav-item mt-3"><small class="text-muted px-3 fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">PENGATURAN</small></li>
                                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/leaves*') ? 'active' : '' }}" href="{{ route('admin.leaves.index') }}">
                        <i class="bi bi-envelope-paper me-2"></i> Izin & Cuti
                    </a>
                </li>
<li class="nav-item"><a class="nav-link {{ request()->is('admin/settings*') ? 'active' : '' }}" href="{{ url('/admin/settings') }}"><i class="bi bi-clock-history me-2"></i> Set Jam Kerja</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->is('pengaturan*') ? 'active' : '' }}" href="{{ url('/pengaturan') }}"><i class="bi bi-gear me-2"></i> Kontrol Mesin</a></li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/maintenance*') ? 'active' : '' }}" href="{{ route('admin.maintenance.index') }}">
                        <i class="bi bi-database-fill-gear me-2"></i> Maintenance DB
                    </a>
                </li>
                <li class="nav-item"><a class="nav-link {{ request()->is('admin/users*') ? 'active' : '' }}" href="{{ url('/admin/users') }}"><i class="bi bi-person-gear me-2"></i> Manajemen User</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->is('admin/audit-logs*') ? 'active' : '' }}" href="{{ url('/admin/audit-logs') }}"><i class="bi bi-journal-text me-2"></i> Audit Logs</a></li>
                @endif
                <li class="nav-item mt-auto pt-3 border-top">
                    <form action="{{ route('logout') }}" method="POST">@csrf<button type="submit" class="nav-link text-danger w-100 text-start border-0 bg-transparent"><i class="bi bi-box-arrow-left me-2"></i> Keluar</button></form>
                </li>
            </ul>
        </div>

        <div class="col-md-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4 fade-in">
                <div>
                    <h4 class="fw-bold m-0 mb-1"><i class="bi bi-hdd-network text-success me-2"></i>Kontrol Mesin Absensi</h4>
                    <div class="d-flex align-items-center"><i class="bi bi-cpu text-muted me-2"></i><small class="text-muted">Kendali SDK dan manajemen perangkat keras</small></div>
                </div>
                <div class="d-flex align-items-center">
                    <div class="text-end me-3"><p class="mb-0 fw-semibold small">{{ auth()->user()->name }}</p><small class="text-muted">Superadmin</small></div>
                    <div class="avatar-circle text-success">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                </div>
            </div>

            @if(session('status'))
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4 fade-in" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>{{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4 fade-in" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- PANEL ATAS -->
            <div class="row g-3 mb-4 fade-in">
                <div class="col-md-4">
                    <div class="card-custom p-4 bg-white h-100">
                        <h6 class="fw-bold mb-3"><i class="bi bi-info-circle text-primary me-2"></i>Perangkat Aktif</h6>
                        @if($currentMachine)
                        <div class="d-flex align-items-center mb-3">
                            <div class="stat-icon {{ $currentMachine->isOnline() ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} me-3"><i class="bi bi-cpu-fill"></i></div>
                            <div><h5 class="fw-bold mb-0">{{ $currentMachine->machine_name }}</h5><small class="text-muted"><i class="bi bi-hdd-network me-1"></i>IP: {{ $currentMachine->machine_ip }}</small></div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-semibold small">Status:</span>
                            <span class="badge {{ $currentMachine->getStatusBadgeClass() }}">
                                <span class="status-indicator bg-{{ $currentMachine->isOnline() ? 'success' : 'danger' }} me-1"></span>
                                {{ $currentMachine->getStatusLabel() }}
                            </span>
                        </div>
                        <div class="mt-2 d-flex justify-content-between align-items-center"><small class="text-muted">Last ping:</small><small class="text-muted">{{ $currentMachine->getLastPingHuman() }}</small></div>
                        <div class="d-flex justify-content-between align-items-center"><small class="text-muted">Response:</small><small class="text-muted">{{ $currentMachine->getFormattedResponseTime() }}</small></div>
                        @else
                        <div class="text-center py-3"><i class="bi bi-exclamation-triangle fs-3 d-block mb-2 text-danger opacity-50"></i><p class="text-muted small mb-0">Belum ada perangkat aktif</p></div>
                        @endif
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card-custom p-4 bg-white h-100">
                        <h6 class="fw-bold mb-3"><i class="bi bi-toggles text-primary me-2"></i>Konsol Kendali</h6>
                        <div class="row g-2">
                            <div class="col-12">
                                <a href="{{ route('pengaturan.index', ['view_users' => 1]) }}" class="btn btn-outline-primary w-100 text-start"><i class="bi bi-cloud-download me-2"></i>Tarik Data Log</a>
                            </div>
                            <div class="col-6">
                                <form action="{{ route('pengaturan.sync-time') }}" method="POST">@csrf<button type="submit" class="btn btn-outline-success w-100 text-start"><i class="bi bi-clock-history me-2"></i>Sync Waktu</button></form>
                            </div>
                            <div class="col-6">
                                <form action="{{ route('pengaturan.restart') }}" method="POST" onsubmit="return confirm('Restart mesin?');">@csrf<button type="submit" class="btn btn-outline-warning w-100 text-start"><i class="bi bi-arrow-clockwise me-2"></i>Restart</button></form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card-custom p-4 bg-white h-100">
                        <h6 class="fw-bold mb-3"><i class="bi bi-plus-circle text-success me-2"></i>Tambah Perangkat</h6>
                        <form action="{{ route('pengaturan.machine.store') }}" method="POST">
                            @csrf
                            <div class="mb-2"><input type="text" name="machine_name" class="form-control form-control-sm" placeholder="Nama Perangkat" required></div>
                            <div class="mb-2"><input type="text" name="machine_ip" class="form-control form-control-sm" placeholder="IP Address" required></div>
                            <button type="submit" class="btn btn-success w-100"><i class="bi bi-plus-lg me-1"></i>Tambah Perangkat</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Daftar Perangkat -->
            <div class="card-custom p-4 bg-white mb-4 fade-in">
                <div class="d-flex justify-content-between align-items-center mb-3"><h6 class="fw-bold mb-0"><i class="bi bi-list-ul text-primary me-2"></i>Daftar Semua Perangkat</h6></div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr class="border-bottom">
                            <th class="fw-bold text-muted small">NAMA PERANGKAT</th>
                            <th class="fw-bold text-muted small">IP ADDRESS</th>
                            <th class="fw-bold text-muted small">STATUS</th>
                            <th class="fw-bold text-muted small">LAST PING</th>
                            <th class="fw-bold text-muted small">RESPONSE</th>
                            <th class="fw-bold text-muted small">AKSI</th>
                        </tr></thead>
                        <tbody>
                            @forelse($machineStatuses as $machine)
                            <tr class="border-bottom {{ $machine->isDefault() ? 'table-success' : '' }}">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon {{ $machine->isOnline() ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} me-2" style="width: 30px; height: 30px; font-size: 14px;"><i class="bi bi-cpu-fill"></i></div>
                                        <div><span class="fw-bold small">{{ $machine->machine_name }}</span>@if($machine->isDefault())<span class="badge bg-primary-subtle text-primary ms-1">Default</span>@endif</div>
                                    </div>
                                </td>
                                <td><code class="small">{{ $machine->machine_ip }}</code></td>
                                <td><span class="badge {{ $machine->getStatusBadgeClass() }}"><span class="status-indicator bg-{{ $machine->isOnline() ? 'success' : 'danger' }} me-1" style="font-size: 6px;"></span>{{ $machine->getStatusLabel() }}</span></td>
                                <td class="small text-muted">{{ $machine->getLastPingHuman() }}</td>
                                <td class="small text-muted">{{ $machine->getFormattedResponseTime() }}</td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <form action="{{ route('pengaturan.machine.ping', $machine->id) }}" method="POST" class="d-inline">@csrf<button type="submit" class="btn btn-sm btn-outline-info" title="Ping"><i class="bi bi-send"></i></button></form>
                                        @if(!$machine->isDefault())
                                        <form action="{{ route('pengaturan.machine.default', $machine->id) }}" method="POST" class="d-inline">@csrf<button type="submit" class="btn btn-sm btn-outline-primary" title="Default"><i class="bi bi-star"></i></button></form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center py-4"><i class="bi bi-exclamation-triangle fs-3 d-block mb-2 text-danger opacity-50"></i><p class="text-muted mb-0">Belum ada perangkat terdaftar</p></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- DATA VIEWER TABS -->
            <div class="card-custom p-4 bg-white fade-in">
                <ul class="nav nav-pills mb-4" id="sdkTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a href="{{ route('pengaturan.index', ['view_users' => 1]) }}" class="nav-link @if(request()->has('view_users') || (!request()->has('view_users') && !request()->has('download_fp') && !request()->has('view_logs'))) active @endif">
                            <i class="bi bi-people me-1"></i> Data Karyawan
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link @if(request()->has('download_fp')) active @endif" id="fp-tab" data-bs-toggle="tab" data-bs-target="#tab-fp" type="button" role="tab"><i class="bi bi-fingerprint me-1"></i> Sidik Jari</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a href="{{ route('pengaturan.index', ['view_logs' => 1]) }}" class="nav-link @if(request()->has('view_logs')) active @endif">
                            <i class="bi bi-file-earmark-text me-1"></i> Log Mentah
                        </a>
                    </li>
                </ul>
<div class="tab-content" id="sdkTabContent">
                    <div class="tab-pane fade @if(request()->has('view_users') || (!request()->has('view_users') && !request()->has('download_fp') && !request()->has('view_logs'))) show active @endif" id="tab-user" role="tabpanel">
                        @if(true) <!-- Selalu jalankan karena user di-autoload di controller -->
                            @if(!empty($users))
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead><tr class="border-bottom">
                                        <th class="fw-bold text-muted small">USER ID</th>
                                        <th class="fw-bold text-muted small">NAMA</th>
                                        <th class="fw-bold text-muted small">PIN</th>
                                        <th class="fw-bold text-muted small">AKSI</th>
                                    </tr></thead>
                                    <tbody>
                                        @forelse($users as $user)
                                        <tr class="border-bottom">
                                            <td><code class="small">{{ is_array($user) ? ($user['pin'] ?? '-') : ($user->pin ?? '-') }}</code></td>
                                            <td class="fw-semibold small">{{ is_array($user) ? ($user['name'] ?? '-') : ($user->name ?? '-') }}</td>
                                            <td>
                                                <span class="badge {{ (is_array($user) ? ($user['privilege'] ?? '0') : ($user->privilege ?? '0')) == '14' ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' }}">
                                                    {{ (is_array($user) ? ($user['privilege'] ?? '0') : ($user->privilege ?? '0')) == '14' ? 'ADMIN' : 'USER' }}
                                                </span>
                                            </td>
                                            <td>
                                                <form action="{{ route('pengaturan.hapus-user') }}" method="POST" onsubmit="return confirm('Yakin hapus PIN/User ini dari mesin?');">
                                                    @csrf
                                                    <input type="hidden" name="user_id" value="{{ is_array($user) ? ($user['pin'] ?? '') : ($user->pin ?? '') }}">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Hapus</button>
                                                </form>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="4" class="text-center py-4"><p class="text-muted mb-0">Tidak ada user ditemukan</p></td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <small class="text-muted">{{ count($users) }} user ditemukan</small>
                            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3 mt-3" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i>Data karyawan berhasil ditarik dari mesin ({{ count($users) }} user).<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            @else
                            <div class="text-center py-5"><i class="bi bi-exclamation-triangle fs-1 d-block mb-3 text-warning opacity-50"></i><p class="text-muted">Data karyawan dari mesin belum tersedia. Pastikan mesin menyala dan coba lagi.</p></div>
                            @endif
                        @else
                            <div class="text-center py-5"><i class="bi bi-people fs-1 d-block mb-3 text-secondary opacity-50"></i><p class="text-muted">Tekan "Tarik Data Log" untuk melihat data karyawan dari mesin</p></div>
                        @endif
                    </div>
                    <div class="tab-pane fade @if(request()->has('download_fp')) show active @endif" id="tab-fp" role="tabpanel">
                        @if(request()->has('download_fp'))
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                            <select name="user_id" id="fpUserId" class="form-select form-select-sm">
                                        <option value="1">User ID 1</option>
                                        @foreach($users as $user)
                                        @php $pin = is_array($user) ? ($user['pin'] ?? '') : ($user->pin ?? ''); @endphp
                                        <option value="{{ $pin }}" {{ ($request->input('user_id') ?? '1') == $pin ? 'selected' : '' }}>{{ is_array($user) ? ($user['name'] ?? '-') : ($user->name ?? '-') }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <select name="finger_id" id="fpFingerId" class="form-select form-select-sm">
                                        <option value="0" {{ ($request->input('finger_id') ?? '0') == '0' ? 'selected' : '' }}>Jari 0</option>
                                        <option value="1" {{ ($request->input('finger_id') ?? '0') == '1' ? 'selected' : '' }}>Jari 1</option>
                                        <option value="2" {{ ($request->input('finger_id') ?? '0') == '2' ? 'selected' : '' }}>Jari 2</option>
                                    </select>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <form action="{{ route('pengaturan.upload-fp') }}" method="POST" class="d-flex gap-2">
                                        @csrf
                                        <input type="hidden" name="user_id" id="uploadUserId" value="1">
                                        <input type="hidden" name="finger_id" id="uploadFingerId" value="0">
                                        <input type="hidden" name="template" id="fpTemplate" value="">
                                        <button type="submit" class="btn btn-success btn-sm w-100"><i class="bi bi-upload me-1"></i>Upload FP</button>
                                    </form>
                                </div>
                            </div>
                            @if(!empty($templates))
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead><tr class="border-bottom">
                                        <th class="fw-bold text-muted small">USER ID</th>
                                        <th class="fw-bold text-muted small">FINGER ID</th>
                                        <th class="fw-bold text-muted small">SIZE</th>
                                        <th class="fw-bold text-muted small">TEMPLATE</th>
                                    </tr></thead>
                                    <tbody>
                                        @forelse($templates as $tpl)
                                        <tr class="border-bottom">
                                            <td><code class="small">{{ is_array($tpl) ? ($tpl['pin'] ?? '-') : ($tpl->pin ?? '-') }}</code></td>
                                            <td><code class="small">{{ is_array($tpl) ? ($tpl['finger_id'] ?? '-') : ($tpl->finger_id ?? '-') }}</code></td>
                                            <td><code class="small">{{ is_array($tpl) ? ($tpl['size'] ?? '-') : ($tpl->size ?? '-') }}</code></td>
                                            <td><code class="small">{{ substr(is_array($tpl) ? ($tpl['template'] ?? '') : ($tpl->template ?? ''), 0, 30) }}...</code></td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="4" class="text-center py-4"><p class="text-muted mb-0">Tidak ada template</p></td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            @else
                            <div class="text-center py-4"><p class="text-muted">Tidak ada data template sidik jari</p></div>
                            @endif
                        @else
                            <div class="text-center py-5"><i class="bi bi-fingerprint fs-1 d-block mb-3 text-secondary opacity-50"></i><p class="text-muted">Tekan "Tarik Data Log" lalu pilih user untuk melihat/mengelola sidik jari</p></div>
                        @endif
                    </div>
                    <div class="tab-pane fade @if(request()->has('view_logs')) show active @endif" id="tab-log" role="tabpanel">
                        @if(request()->has('view_logs'))
                            @if(!empty($logs))
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead><tr class="border-bottom">
                                        <th class="fw-bold text-muted small">TANGGAL</th>
                                        <th class="fw-bold text-muted small">WAKTU (DATETIME)</th>
                                        <th class="fw-bold text-muted small">NAMA</th>
                                        <th class="fw-bold text-muted small">PIN</th>
                                        <th class="fw-bold text-muted small">VERIFY</th>
                                    </tr></thead>
                                    <tbody>
                                        @forelse($logs as $log)
                                        <tr class="border-bottom">
                                            <td class="small">{{ is_array($log) ? ($log['datetime'] ?? '-') : ($log->datetime ?? '-') }}</td>
                                            <td class="fw-semibold small">{{ is_array($log) ? ($log['name'] ?? '-') : ($log->name ?? '-') }}</td>
                                            <td><code class="small">{{ is_array($log) ? ($log['pin'] ?? '-') : ($log->pin ?? '-') }}</code></td>
                                            <td><span class="badge bg-success-subtle text-success">{{ (is_array($log) ? ($log['verified'] ?? '0') : ($log->verified ?? '0')) == '1' ? 'FINGERPRINT' : 'PASSWORD/LAINNYA' }}</span></td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="4" class="text-center py-4"><p class="text-muted mb-0">Tidak ada log mentah</p></td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <small class="text-muted">{{ count($logs) }} log ditemukan</small>
                            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3 mt-3" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i>Log mentah berhasil ditarik dari mesin ({{ count($logs) }} log).<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            @else
                            <div class="text-center py-5"><i class="bi bi-exclamation-triangle fs-1 d-block mb-3 text-warning opacity-50"></i><p class="text-muted">Tidak ada log mentah. Mesin mungkin offline atau koneksi gagal.</p></div>
                            @endif
                        @else
                            <div class="text-center py-5"><i class="bi bi-file-earmark-text fs-1 d-block mb-3 text-secondary opacity-50"></i><p class="text-muted">Tekan "Tarik Data Log" untuk melihat log mentah dari mesin</p></div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function submitFpForm() {
        var userId = document.getElementById('fpUserId').value;
        var fingerId = document.getElementById('fpFingerId').value;
        var uploadUserId = document.getElementById('uploadUserId');
        var uploadFingerId = document.getElementById('uploadFingerId');
        if (uploadUserId) uploadUserId.value = userId;
        if (uploadFingerId) uploadFingerId.value = fingerId;
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

