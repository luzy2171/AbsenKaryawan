<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi Karyawan - Absensi-BBM</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">

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
                    <h4 class="fw-bold m-0 mb-1"><i class="bi bi-calendar-check text-success me-2"></i>Data Absensi</h4>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-database text-muted me-2"></i>
                        <small class="text-muted">{{ count($attendances) }} catatan absensi ditemukan</small>
                    </div>
                </div>
                                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false" style="color: inherit;">
                            <div class="text-end me-3">
                                <p class="mb-0 fw-semibold small">{{ auth()->user()->name }}</p>
                                <small
                                    class="text-muted">{{ match(auth()->user()->role) { 'superadmin' => 'Superadmin', 'approval' => 'Approval', default => 'Admin' } }}</small>
                            </div>
                            <div class="avatar-circle bg-success text-white"
                                style="width: 45px; height: 45px; font-size: 18px;">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" aria-labelledby="dropdownUser">
                            <li>
                                <a class="dropdown-item d-flex align-items-center py-2" href="#" data-bs-toggle="modal" data-bs-target="#profileModal">
                                    <i class="bi bi-person-circle me-2 text-primary"></i> Edit Profil & Password
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST" class="m-0">
                                    @csrf
                                    <button type="submit" class="dropdown-item d-flex align-items-center py-2 text-danger">
                                        <i class="bi bi-box-arrow-right me-2"></i> Keluar
                                    </button>
                                </form>
                            </li>
                        </ul>
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

            <div class="row g-3 mb-4 fade-in">
                <div class="col-md-8">
                    <div class="switch-container d-flex justify-content-between align-items-center">
                        <form action="{{ route('absensi.toggle-auto') }}" method="POST" id="formToggleAuto" class="d-flex align-items-center gap-3 m-0 w-100">
                            @csrf
                            <div class="flex-grow-1">
                                <label class="fw-bold text-dark d-block mb-0" style="cursor: pointer;" for="autoPullSwitch">
                                    <i class="bi bi-arrow-repeat me-2"></i>Tarik Otomatis dari Mesin
                                </label>
                                <small class="text-muted">Sinkronisasi data absensi secara berkala</small>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="autoPullSwitch"
                                       value="ON" {{ ($autoPullStatus ?? 'OFF') == 'ON' ? 'checked' : '' }} onchange="submitToggleAuto()">
                                <input type="hidden" name="status" id="statusHidden" value="{{ $autoPullStatus ?? 'OFF' }}">
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-md-4">
                    <form action="{{ route('absensi.tarik') }}" method="POST" onsubmit="return confirm('Mulai tarik log kehadiran 3 bulan terakhir dari mesin absensi fisik?');" class="m-0 h-100">
                        @csrf
                        <button type="submit" class="btn btn-success fw-semibold w-100 h-100">
                            <i class="bi bi-cloud-download me-2"></i> Tarik Data dari Mesin
                        </button>
                    </form>
                </div>
            </div>

            <div class="card-custom p-4 bg-white mb-4 fade-in">
                <h5 class="fw-bold mb-3"><i class="bi bi-funnel me-2 text-primary"></i>Filter & Pencarian</h5>
                <form action="{{ route('absensi.index') }}" method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">
                            <i class="bi bi-search text-muted me-1"></i>Cari Karyawan
                        </label>
                        <input type="text" name="search" class="form-control" placeholder="Nama karyawan..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">
                            <i class="bi bi-calendar text-muted me-1"></i>Tanggal
                        </label>
                        <input type="date" name="tanggal" class="form-control" value="{{ $tanggalFilter ?? date('Y-m-d') }}" onchange="this.form.submit()">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">
                            <i class="bi bi-filter text-muted me-1"></i>Status
                        </label>
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="Semua Status" {{ ($statusFilter ?? 'Semua Status') == 'Semua Status' ? 'selected' : '' }}>Semua Status</option>
                            <option value="Hadir" {{ ($statusFilter ?? '') == 'Hadir' ? 'selected' : '' }}>Hadir</option>
                            <option value="Terlambat" {{ ($statusFilter ?? '') == 'Terlambat' ? 'selected' : '' }}>Terlambat</option>
                            <option value="Alpha" {{ ($statusFilter ?? '') == 'Alpha' ? 'selected' : '' }}>Alpha</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <a href="{{ route('absensi.index') }}" class="btn btn-light border w-100">
                            <i class="bi bi-arrow-clockwise me-1"></i>Reset
                        </a>
                    </div>
                </form>
            </div>

            <div class="card-custom p-4 bg-white mb-4 fade-in">
                <h5 class="fw-bold mb-3"><i class="bi bi-printer me-2 text-primary"></i>Cetak Laporan</h5>
                <form action="{{ route('absensi.cetak') }}" method="GET" target="_blank" id="formLaporan" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">
                            <i class="bi bi-person-badge text-muted me-1"></i>Karyawan
                        </label>
                        <input type="hidden" name="karyawan_id" id="karyawanIdsInput" value="">
                        <div id="selectedKaryawanTags" class="mt-1 d-flex flex-wrap gap-1"></div>
                        <div class="dropdown w-100">
                            <button class="btn btn-outline-secondary w-100 text-start dropdown-toggle" type="button" id="karyawanDropdown" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside">
                                <span id="karyawanDropdownLabel">Pilih Karyawan</span>
                            </button>
                            <div class="dropdown-menu w-100 p-2 shadow" style="max-height: 300px; overflow-y: auto;" aria-labelledby="karyawanDropdown">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="checkAllKaryawan" value="semua" onchange="toggleSemua(this)">
                                    <label class="form-check-label fw-semibold cursor-pointer w-100" for="checkAllKaryawan">
                                        <i class="bi bi-check-all me-1"></i>Semua Karyawan
                                    </label>
                                </div>
                                <hr class="dropdown-divider">
                                <div class="d-flex flex-column gap-1">
                                    @foreach($karyawans as $k)
                                        <div class="form-check py-1 hover-bg-light rounded px-2 m-0 d-flex align-items-center" style="padding-left: 0.5rem !important;">
                                            <input class="form-check-input karyawan-check m-0 me-2" type="checkbox" name="karyawan_id[]" value="{{ $k->id }}" id="karyawan_{{ $k->id }}" data-id="{{ $k->id }}" data-nama="{{ $k->nama }}" data-kode="{{ $k->id_karyawan }}" onchange="updateKaryawanDropdown()">
                                            <label class="form-check-label cursor-pointer w-100 m-0 p-0 text-truncate" for="karyawan_{{ $k->id }}" style="line-height: 1.2;">
                                                {{ $k->nama }} <span class="text-muted small ms-1">({{ $k->id_karyawan }})</span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">
                            <i class="bi bi-calendar text-muted me-1"></i>Dari Tanggal
                        </label>
                        <input type="date" name="tanggal_mulai" class="form-control" value="{{ date('Y-m-01') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">
                            <i class="bi bi-calendar-check text-muted me-1"></i>Sampai Tanggal
                        </label>
                        <input type="date" name="tanggal_selesai" class="form-control" value="{{ date('Y-m-t') }}" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <button type="button" onclick="submitAction('{{ route('absensi.cetak') }}', '_blank')" class="btn btn-outline-success w-50">
                            <i class="bi bi-printer me-1"></i> PDF
                        </button>
                        <button type="button" onclick="submitAction('{{ route('absensi.export-excel') }}', '_self')" class="btn btn-success w-50">
                            <i class="bi bi-file-earmark-excel me-1"></i> Excel
                        </button>
                    </div>
                </form>
            </div>

            <div class="card-custom p-4 bg-white fade-in">
                <h5 class="fw-bold mb-4"><i class="bi bi-table me-2 text-primary"></i>Daftar Absensi</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr class="border-bottom">
                                <th class="fw-bold text-muted small">ID / PIN</th>
                                <th class="fw-bold text-muted small">NAMA</th>
                                <th class="fw-bold text-muted small">TANGGAL</th>
                                <th class="fw-bold text-muted small">JAM MASUK</th>
                                <th class="fw-bold text-muted small">JAM PULANG</th>
                                <th class="fw-bold text-muted small">STATUS</th>
                                <th class="fw-bold text-muted small">LEMBUR</th>
                                <th class="fw-bold text-muted small">VERIFIKASI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($attendances as $item)
                            <tr class="border-bottom">
                                <td>
                                    <span class="badge bg-light text-dark px-3 py-2" style="font-family: 'Courier New', monospace;">
                                        {{ $item->karyawan->id_karyawan ?? $item->id_karyawan }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle text-success me-2" style="width: 32px; height: 32px; font-size: 13px;">
                                            {{ strtoupper(substr($item->karyawan->nama ?? $item->nama, 0, 1)) }}
                                        </div>
                                        <span class="fw-semibold">{{ $item->karyawan->nama ?? $item->nama }}</span>
                                    </div>
                                </td>
                                <td class="text-muted">
                                    <i class="bi bi-calendar3 me-1"></i>{{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') }}
                                </td>
                                <td class="fw-semibold">
                                    <i class="bi bi-box-arrow-in-right text-success me-1"></i>
                                    {{ $item->jam_masuk ? \Carbon\Carbon::parse($item->jam_masuk)->format('H:i') : '-' }}
                                </td>
                                <td class="fw-semibold">
                                    <i class="bi bi-box-arrow-right text-danger me-1"></i>
                                    {{ $item->jam_pulang ? \Carbon\Carbon::parse($item->jam_pulang)->format('H:i') : '-' }}
                                </td>
                                <td>
                                    <span class="badge {{ $item->status == 'Hadir' ? 'bg-success-subtle text-success' : ($item->status == 'Terlambat' ? 'bg-warning-subtle text-warning' : 'bg-danger-subtle text-danger') }}">
                                        <i class="bi bi-circle-fill me-1" style="font-size: 6px;"></i>{{ $item->status }}
                                    </span>
                                </td>
                                <td>
                                    @if($item->lembur)
                                        <span class="badge bg-info-subtle text-info">
                                            <i class="bi bi-clock me-1"></i>{{ $item->lembur->lama_lembur }} menit
                                        </span>
                                    @else
                                        <form method="POST" action="{{ route('absensi.lembur.store') }}" class="d-flex align-items-center gap-1" style="font-size: 11px;">
                                            @csrf
                                            <input type="hidden" name="attendance_id" value="{{ $item->id }}">
                                            <input type="time" name="jam_lembur_mulai" class="form-control form-control-sm" value="18:00" style="height:28px; padding:2px 4px; font-size:11px;" required>
                                            <input type="time" name="jam_lembur_selesai" class="form-control form-control-sm" style="height:28px; padding:2px 4px; font-size:11px;" required>
                                            <button type="submit" class="btn btn-sm btn-outline-success" style="padding:2px 6px; font-size:10px;">
                                                <i class="bi bi-plus-circle"></i> Simpan
                                            </button>
                                        </form>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-muted">
                                        <i class="bi bi-fingerprint me-1"></i>Mesin
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <i class="bi bi-inbox fs-1 d-block mb-3 text-secondary opacity-50"></i>
                                    <p class="text-muted mb-1">Tidak ada data absensi untuk kriteria pencarian ini.</p>
                                    <small class="text-muted">Klik tombol <strong>Tarik Data dari Mesin</strong> untuk memperbarui data</small>
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

<script>
    function submitToggleAuto() {
        const checkbox = document.getElementById('autoPullSwitch');
        const hiddenInput = document.getElementById('statusHidden');

        if (checkbox.checked) {
            hiddenInput.value = 'ON';
        } else {
            hiddenInput.value = 'OFF';
        }
        document.getElementById('formToggleAuto').submit();
    }

    function submitAction(url, target) {
        const form = document.getElementById('formLaporan');
        form.action = url;
        form.target = target;
        form.submit();
    }
</script>


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