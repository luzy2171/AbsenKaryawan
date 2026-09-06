<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - Absensi-BBM</title>
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
                    <h4 class="fw-bold m-0 mb-1"><i class="bi bi-people-fill text-success me-2"></i>Manajemen User</h4>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-person-badge text-muted me-2"></i>
                        <small class="text-muted">{{ count($users) }} akun pengguna terdaftar</small>
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

            <div class="card-custom p-4 bg-white fade-in">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="fw-bold mb-1">Daftar User</h5>
                        <p class="text-muted small mb-0">Kelola akun pengguna dan hak akses sistem</p>
                    </div>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTambahUser">
                        <i class="bi bi-person-plus me-1"></i> Tambah User
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr class="border-bottom">
                                <th class="fw-bold text-muted small">NAMA</th>
                                <th class="fw-bold text-muted small">USERNAME</th>
                                <th class="fw-bold text-muted small">EMAIL</th>
                                <th class="fw-bold text-muted small">ROLE</th>
                                <th class="fw-bold text-muted small">DIBUAT</th>
                                <th class="fw-bold text-muted small text-center">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $u)
                            <tr class="border-bottom">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle text-success me-2" style="width: 35px; height: 35px; font-size: 14px;">
                                            {{ strtoupper(substr($u->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <span class="fw-bold">{{ $u->name }}</span>
                                            @if($u->id === auth()->id())
                                                <span class="badge bg-info-subtle text-info ms-1">
                                                    <i class="bi bi-person-check-fill me-1" style="font-size: 9px;"></i>Anda
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark px-3 py-2" style="font-family: 'Courier New', monospace;">
                                        {{ $u->username }}
                                    </span>
                                </td>
                                <td class="text-muted">{{ $u->email }}</td>
                                <td>
                                    <span class="badge {{ match($u->role) {
                                        'superadmin' => 'bg-danger-subtle text-danger',
                                        'approval' => 'bg-info-subtle text-info',
                                        default => 'bg-success-subtle text-success'
                                    } }}">
                                        <i class="bi {{ match($u->role) {
                                            'superadmin' => 'bi-shield-fill-check',
                                            'approval' => 'bi-person-check-fill',
                                            default => 'bi-person-check'
                                        } }} me-1"></i>
                                        {{ match($u->role) {
                                            'superadmin' => 'Superadmin',
                                            'approval' => 'Approval',
                                            default => 'Admin'
                                        } }}
                                    </span>
                                </td>
                                <td class="text-muted small">
                                    <i class="bi bi-calendar3 me-1"></i>{{ \Carbon\Carbon::parse($u->created_at)->translatedFormat('d M Y') }}
                                </td>
                                <td class="text-center">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-success"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalEditUser"
                                            data-id="{{ $u->id }}"
                                            data-name="{{ $u->name }}"
                                            data-username="{{ $u->username }}"
                                            data-email="{{ $u->email }}"
                                            data-role="{{ $u->role }}"
                                            data-self="{{ $u->id === auth()->id() ? 1 : 0 }}">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    @if($u->id !== auth()->id())
                                    <form action="{{ route('admin.users.destroy', $u->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus user ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <i class="bi bi-people fs-1 d-block mb-3 text-secondary opacity-50"></i>
                                    <p class="text-muted mb-0">Belum ada user terdaftar</p>
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

<!-- Modal Tambah User -->
<div class="modal fade" id="modalTambahUser" tabindex="-1" aria-labelledby="modalTambahUserLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold mb-1" id="modalTambahUserLabel">
                        <i class="bi bi-person-plus text-success me-2"></i>Tambah User Baru
                    </h5>
                    <small class="text-muted">Buat akun pengguna baru untuk sistem</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.users.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-person text-muted me-1"></i>Nama Lengkap
                        </label>
                        <input type="text" name="name" class="form-control" placeholder="Nama Pengguna" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-at text-muted me-1"></i>Username
                        </label>
                        <input type="text" name="username" class="form-control" placeholder="Username untuk login" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-envelope text-muted me-1"></i>Email
                        </label>
                        <input type="email" name="email" class="form-control" placeholder="email@contoh.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-key text-muted me-1"></i>Password
                        </label>
                        <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-shield-check text-muted me-1"></i>Role
                        </label>
                        <select name="role" class="form-select">
                            <option value="admin" selected>Admin</option>
                            <option value="approval">Approval (Setuju Izin & Log Audit)</option>
                            <option value="superadmin">Superadmin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Batal
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-lg me-1"></i>Simpan User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit User -->
<div class="modal fade" id="modalEditUser" tabindex="-1" aria-labelledby="modalEditUserLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold mb-1" id="modalEditUserLabel">
                        <i class="bi bi-pencil-square text-primary me-2"></i>Edit User
                    </h5>
                    <small class="text-muted">Perbarui informasi pengguna</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditUser" action="" method="POST">
                @csrf
                <input type="hidden" name="user_id" id="editUserId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-person text-muted me-1"></i>Nama Lengkap
                        </label>
                        <input type="text" name="name" id="editName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-at text-muted me-1"></i>Username
                        </label>
                        <input type="text" name="username" id="editUsername" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-envelope text-muted me-1"></i>Email
                        </label>
                        <input type="email" name="email" id="editEmail" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-key text-muted me-1"></i>Password
                        </label>
                        <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin mengubah">
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>Kosongkan jika tidak ingin mengubah password
                        </small>
                    </div>
                    <div class="mb-3" id="roleSelectContainer">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-shield-check text-muted me-1"></i>Role
                        </label>
                        <select name="role" id="editRole" class="form-select">
                            <option value="admin">Admin</option>
                            <option value="approval">Approval (Setuju Izin & Log Audit)</option>
                            <option value="superadmin">Superadmin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Batal
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Update User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const modalEditUser = document.getElementById('modalEditUser');
    modalEditUser.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const userId = button.getAttribute('data-id');
        const name = button.getAttribute('data-name');
        const username = button.getAttribute('data-username');
        const email = button.getAttribute('data-email');
        const role = button.getAttribute('data-role');
        const isSelf = button.getAttribute('data-self') === '1';

        document.getElementById('editUserId').value = userId;
        document.getElementById('editName').value = name;
        document.getElementById('editUsername').value = username;
        document.getElementById('editEmail').value = email;
        document.getElementById('editRole').value = role;

        // Set form action
        document.getElementById('formEditUser').action = "{{ url('/admin/users/update') }}/" + userId;

        // Disable role select if editing own account
        const roleSelect = document.getElementById('editRole');
        const roleContainer = document.getElementById('roleSelectContainer');
        if (isSelf) {
            roleSelect.disabled = true;
            roleContainer.innerHTML += '<small class="text-warning"><i class="bi bi-info-circle me-1"></i>Tidak dapat mengubah role akun sendiri</small>';
        } else {
            roleSelect.disabled = false;
        }
    });
</script>
</body>
</html>


