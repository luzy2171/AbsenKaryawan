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
                                                
                                                    <span class="badge bg-primary text-white"><i class="bi bi-fingerprint me-1"></i>Solution</span>
                                                
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
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold mb-1">1. Pilih Mesin Tujuan</label>
                                    <select class="form-select bg-light border-0" name="mesin_tujuan" id="kirim_mesin_tujuan" required>
                                        <option value="">-- Mesin Tujuan --</option>
                                        
                                        <option value="solution">Hanya Solution</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold mb-1">2. Pilih Karyawan</label>
                                    <select class="form-select bg-light border-0" name="karyawan_id" id="kirim_karyawan_id" required disabled>
                                        <option value="">-- Pilih Mesin Terlebih Dahulu --</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 fw-bold rounded-3">
                                    <i class="bi bi-send me-1"></i> Kirim ke Mesin
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

            <!-- Data User di Mesin -->
            <div class="row g-4 mb-4 fade-in">
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white border-bottom-0 pt-4 pb-2 px-4 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold m-0"><i class="bi bi-people text-primary me-2"></i>Data Karyawan di Mesin Fisik</h6>
                        </div>
                        <div class="card-body p-0">
                            <div>
                                    <div class="d-flex justify-content-end p-3 bg-light border-bottom">
                                        <form action="{{ route('admin.mesin.clean', 'solution') }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus SEMUA user di mesin Solution yang tidak terdaftar di database Web?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-danger fw-semibold shadow-sm">
                                                <i class="bi bi-stars me-1"></i> Bersihkan Data Asing (Auto-Hapus)
                                            </button>
                                        </form>
                                    </div>
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
                        <select name="machine_type" class="form-select bg-light border-0" required><option value="solution">Solution / ZKTeco</option></select>
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
                        <select name="machine_type" id="edit_machine_type" class="form-select bg-light border-0" required><option value="solution">Solution / ZKTeco</option></select>
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

     

     

    function editDevice(id, name, type, ip, port, username) {
        document.getElementById('editDeviceForm').action = "{{ url('admin/mesin-absensi/device') }}/" + id;
        document.getElementById('edit_machine_name').value = name;
        document.getElementById('edit_machine_type').value = type;
        document.getElementById('edit_machine_ip').value = ip;
        document.getElementById('edit_port').value = port;
        document.getElementById('edit_username').value = username;
        
        
        
        var modal = new bootstrap.Modal(document.getElementById('editDeviceModal'));
        modal.show();
    }

    // Data sinkronisasi untuk fitur Kirim Data Pintar
    const localKaryawans = @json($karyawans);
    
    const usersSol = @json(array_column((array)$usersSol, 'pin'));

    const selectMesinKirim = document.getElementById('kirim_mesin_tujuan');
    const selectKaryawanKirim = document.getElementById('kirim_karyawan_id');

    selectMesinKirim.addEventListener('change', function() {
        const mesin = this.value;
        selectKaryawanKirim.innerHTML = '<option value="">-- Pilih Karyawan --</option>';
        
        if(!mesin) {
            selectKaryawanKirim.disabled = true;
            selectKaryawanKirim.innerHTML = '<option value="">-- Pilih Mesin Terlebih Dahulu --</option>';
            return;
        }

        selectKaryawanKirim.disabled = false;
        let countUnsynced = 0;

        localKaryawans.forEach(k => {
            let isRegistered = false;
            let pinStr = String(k.id_karyawan);
            
            if(mesin === 'solution') {
                isRegistered = usersSol.includes(pinStr);
            }

            if(!isRegistered) {
                let option = document.createElement('option');
                option.value = k.id;
                option.text = k.id_karyawan + ' - ' + k.nama;
                selectKaryawanKirim.appendChild(option);
                countUnsynced++;
            }
        });

        if(countUnsynced === 0) {
            selectKaryawanKirim.innerHTML = '<option value="">-- Semua Karyawan Sudah Sinkron --</option>';
            selectKaryawanKirim.disabled = true;
        }
    });

    </script>
</body>
</html>
