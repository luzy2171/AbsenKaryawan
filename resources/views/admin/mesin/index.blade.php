<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontrol Pusat - Absensi-BBM</title>
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ \App\Helpers\Asset::url('css/custom.css') }}">
    <style>
        .table-responsive { max-height: 400px; overflow-y: auto; }
        .table thead th { position: sticky; top: 0; background: #f8f9fa; z-index: 1; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 sidebar p-3">
            @include('partials.brand')
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link" href="{{ url('/dashboard') }}"><i class="bi bi-grid me-2"></i> Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/karyawan') }}"><i class="bi bi-people me-2"></i> Karyawan</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/absensi') }}"><i class="bi bi-calendar-check me-2"></i> Absensi</a></li>
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
                    <li class="nav-item mt-3"><small class="text-muted px-3 fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">PENGATURAN</small></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('admin.leaves.index') }}"><i class="bi bi-envelope-paper me-2"></i> Izin & Cuti</a></li>
                    @if(auth()->user()->isTrueApprover())
                    <li class="nav-item"><a class="nav-link" href="{{ route('admin.cuti.control') }}"><i class="bi bi-sliders me-2"></i> Kontrol Cuti</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('admin.cuti.control') }}"><i class="bi bi-sliders me-2"></i> Kontrol Cuti</a></li>
                    @endif
                    @if(auth()->user()->isTrueApprover())
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/tanda-tangan*') ? 'active' : '' }}" href="{{ route('signature.edit') }}">
                            <i class="bi bi-pen me-2"></i> Tanda Tangan
                        </a>
                    </li>
                    @endif
                    @if(auth()->user()->isSuperadmin())
                    <li class="nav-item"><a class="nav-link" href="{{ url('/admin/settings') }}"><i class="bi bi-clock-history me-2"></i> Set Jam Kerja</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->is('admin/perusahaan') ? 'active' : '' }}" href="{{ route('company-profile.index') }}"><i class="bi bi-building me-2"></i> Profil Perusahaan</a></li>
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
                                                <button type="button" class="btn btn-sm btn-outline-warning border-0 me-1" title="Edit Perangkat" onclick="editDevice({{ $m->id }})"><i class="bi bi-pencil"></i></button>
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
                            <p class="text-muted small">Kirim akun karyawan lokal ke mesin absensi fisik. Bisa pilih banyak karyawan sekaligus.</p>
                            <form action="{{ route('admin.mesin.kirim') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold mb-1">1. Pilih Mesin Tujuan</label>
                        <select class="form-select bg-light border-0" name="machine_id" id="kirim_mesin_tujuan" required>
                                            <option value="">-- Mesin Tujuan --</option>
                                            @foreach($machines as $machine)
                                                <option value="{{ $machine->id }}">{{ $machine->machine_name }} ({{ $machine->machine_ip }}:{{ $machine->port }})</option>
                                            @endforeach
                                        </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label text-muted small fw-bold mb-1">2. Pilih Karyawan (bisa lebih dari satu)</label>
                                    <select class="form-select bg-light border-0" name="karyawan_id[]" id="kirim_karyawan_id"
                                            multiple size="8" required disabled>
                                        <option value="">-- Pilih Mesin terlebih dahulu --</option>
                                    </select>
                                    <small class="text-muted d-block mt-1">Tahan Ctrl / Shift untuk memilih beberapa karyawan.</small>
                                    <div class="d-flex gap-2 mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="kirim_pilih_semua">
                                            <i class="bi bi-check2-all me-1"></i> Pilih Semua
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="kirim_batal_pilih">
                                            <i class="bi bi-x-lg me-1"></i> Batal Pilih
                                        </button>
                                    </div>
                                    <small class="text-muted d-block mt-2" id="kirim_hitung">Belum ada karyawan dipilih</small>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 fw-bold rounded-3">
                                    <i class="bi bi-send me-1"></i> Kirim Terpilih ke Mesin
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
                                    <select class="form-select bg-light border-0" name="machine_id" required>
                                        <option value="">-- Mesin Sumber --</option>
                                        @foreach($machines as $machine)
                                            <option value="{{ $machine->id }}">{{ $machine->machine_name }} ({{ $machine->machine_ip }}:{{ $machine->port }})</option>
                                        @endforeach
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

            <!-- Tarik Sidik Jari dari Mesin -->
            <div class="row g-4 mb-4 fade-in">
                <div class="col-md-5">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3"><i class="bi bi-fingerprint text-primary me-2"></i>Tarik Sidik Jari</h6>
                            <p class="text-muted small">
                                Daftarkan sidik jari di mesin terlebih dahulu, lalu tarik template-nya ke server
                                dengan protocol <code>GetUserTemplate</code>. Hanya untuk mesin bertipe Solution (SOAP).
                            </p>
                            <form action="{{ route('admin.mesin.sidik.tarik') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold mb-1">Mesin Sumber</label>
                                    <select class="form-select bg-light border-0" name="machine_id" required>
                                        <option value="">-- Mesin --</option>
                                        @foreach($machines as $machine)
                                            <option value="{{ $machine->id }}">{{ $machine->machine_name }} ({{ $machine->machine_ip }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold mb-1">Karyawan</label>
                                    <select class="form-select bg-light border-0" name="karyawan_id" required>
                                        <option value="">-- Pilih Karyawan --</option>
                                        @foreach(\App\Models\Karyawan::orderBy('nama')->get() as $k)
                                            <option value="{{ $k->id }}">{{ $k->nama }} (PIN: {{ $k->id_karyawan }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 fw-bold rounded-3">
                                    <i class="bi bi-cloud-arrow-down me-1"></i> Tarik Sidik Jari
                                </button>
                            </form>

                            <hr class="my-4">

                            <h6 class="fw-bold mb-2"><i class="bi bi-upload text-success me-2"></i>Kirim Sidik Jari ke Mesin</h6>
                            <p class="text-muted small">
                                Mesin ini tidak bisa dipicu untuk menampilkan layar pemindaian, jadi template
                                hasil export dari software PC mesin diunggah lewat <code>SetUserTemplate</code>.
                            </p>
                            <form action="{{ route('admin.mesin.sidik.kirim') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-2">
                                    <label class="form-label text-muted small fw-bold mb-1">Mesin Tujuan</label>
                                    <select class="form-select bg-light border-0" name="machine_id" required>
                                        <option value="">-- Mesin --</option>
                                        @foreach($machines as $machine)
                                            <option value="{{ $machine->id }}">{{ $machine->machine_name }} ({{ $machine->machine_ip }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label text-muted small fw-bold mb-1">Karyawan (PIN)</label>
                                    <select class="form-select bg-light border-0" name="karyawan_id" required>
                                        <option value="">-- Pilih Karyawan --</option>
                                        @foreach(\App\Models\Karyawan::orderBy('nama')->get() as $k)
                                            <option value="{{ $k->id }}">{{ $k->nama }} (PIN: {{ $k->id_karyawan }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label text-muted small fw-bold mb-1">Slot Jari</label>
                                    <select class="form-select bg-light border-0" name="finger_id" required>
                                        @for ($f = 0; $f <= 9; $f++)
                                            <option value="{{ $f }}">{{ $f }} — {{ (new \App\Models\Fingerprint(['finger_id' => $f]))->namaJari() }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold mb-1">File Template</label>
                                    <input type="file" name="template" class="form-control form-control-sm bg-light border-0" required>
                                    <small class="text-muted d-block mt-1">
                                        Base64 atau biner, maksimal 8 KB. Urutan: 0-5 jari kiri, 6-9 jari kanan.
                                    </small>
                                </div>
                                <button type="submit" class="btn btn-success w-100 fw-bold rounded-3">
                                    <i class="bi bi-send me-1"></i> Kirim ke Mesin
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-7">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3"><i class="bi bi-database text-success me-2"></i>Sidik Jari Tersimpan</h6>
                            <div class="table-responsive" style="max-height: 260px; overflow-y: auto;">
                                <table class="table table-sm table-bordered align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Karyawan</th>
                                            <th>Jari</th>
                                            <th>Slot</th>
                                            <th>Ukuran</th>
                                            <th>Mesin</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($fingerprints as $fp)
                                            <tr>
                                                <td>{{ $fp->karyawan->nama ?? '—' }}</td>
                                                <td class="small">{{ $fp->namaJari() }}</td>
                                                <td class="text-center">{{ $fp->finger_id }}</td>
                                                <td class="text-center">{{ number_format($fp->size) }} B</td>
                                                <td class="small text-muted">{{ $fp->machine->machine_name ?? '—' }}</td>
                                                <td class="text-end">
                                                    <form action="{{ route('admin.mesin.sidik.destroy', $fp->id) }}" method="POST"
                                                          onsubmit="return confirm('Hapus sidik jari ini dari database lokal? Data di mesin tidak berubah.')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn btn-sm btn-outline-danger border-0" title="Hapus dari database lokal">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center text-muted py-4">
                                                    Belum ada sidik jari yang ditarik dari mesin.
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

            <!-- Data User di Mesin -->
            <div class="row g-4 mb-4 fade-in">
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white border-bottom-0 pt-4 pb-2 px-4 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold m-0"><i class="bi bi-people text-primary me-2"></i>Data Karyawan di Mesin Fisik</h6>
                        </div>
                        <div class="card-body p-0">
                            <div>
                                    <div class="d-flex justify-content-end gap-2 p-3 bg-light border-bottom">
                                        <form id="cleanMachineForm" action="{{ route('admin.mesin.clean', $machines->first()?->id ?? 'solution') }}" method="POST" class="d-flex gap-2" onsubmit="return confirm('Yakin ingin menghapus user yang tidak terdaftar di database Web dari mesin ini?');">
                                            @csrf
                                            <select id="cleanMachine" class="form-select form-select-sm" required>
                                                @foreach($machines as $machine)
                                                    <option value="{{ $machine->id }}">{{ $machine->machine_name }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-danger fw-semibold shadow-sm">
                                                <i class="bi bi-stars me-1"></i> Bersihkan Data Asing
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
                                                    @php
                                                        // Cocokkan ke id_karyawan: mesin menandai user
                                                        // dengan <PIN2>, sedangkan <PIN> adalah record
                                                        // id internal yang dialokasikan mesin.
                                                        $idMesin = trim((string) ($u['pin2'] ?? '')) ?: (string) $u['pin'];
                                                        $isLocal = $karyawans->where('id_karyawan', $idMesin)->first();
                                                    @endphp
                                                    <tr>
                                                        <td class="ps-4 fw-bold {{ $isLocal ? 'text-primary' : 'text-danger' }}">{{ $idMesin }}</td>
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
                                                                            <button type="button" class="dropdown-item" onclick="editKaryawan({{ $isLocal->id }})">
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
                                                                        <form action="{{ route('admin.mesin.hapus', ['mesin' => $u['machine_id'], 'pin' => $u['pin']]) }}" method="POST" onsubmit="return confirm('Hapus permanen PIN {{ $u['pin'] }} dari {{ $u['machine_name'] }}?');">
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
                        <select name="machine_type" class="form-select bg-light border-0" required><option value="solution">Solution / X100C</option><option value="x100c">X100C</option></select>
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
                            <input type="number" name="port" id="inputPort" class="form-control bg-light border-0" value="4370" min="1" max="65535" required>
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
                        <select name="machine_type" id="edit_machine_type" class="form-select bg-light border-0" required><option value="solution">Solution / X100C</option><option value="x100c">X100C</option></select>
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
                            <input type="number" name="port" id="edit_port" class="form-control bg-light border-0" min="1" max="65535" required>
                        </div>
                    </div>
                     <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Username / Comm Key</label>
                        <input type="text" name="username" id="edit_username" class="form-control bg-light border-0" value="{{ old('username') }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Password (Kosongkan jika tidak diubah)</label>
                        <input type="password" name="password" id="edit_password" class="form-control bg-light border-0">
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
    const machineOptions = @json($machines->keyBy('id'));
    const karyawanOptions = @json($karyawans->keyBy('id'));
    const usersByMachine = @json($usersByMachine);

    function editKaryawan(id) {
        const karyawan = karyawanOptions[id];
        if (!karyawan) return;

        document.getElementById('editKaryawanForm').action = "{{ url('admin/mesin-absensi/karyawan') }}/" + encodeURIComponent(id);
        document.getElementById('edit_karyawan_nama').value = karyawan.nama || '';
        document.getElementById('edit_karyawan_departemen').value = karyawan.departemen && karyawan.departemen !== '-' ? karyawan.departemen : '';
        document.getElementById('edit_karyawan_jabatan').value = karyawan.jabatan && karyawan.jabatan !== '-' ? karyawan.jabatan : '';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('editKaryawanModal')).show();
    }

    function editDevice(id) {
        const machine = machineOptions[id];
        if (!machine) return;

        document.getElementById('editDeviceForm').action = "{{ url('admin/mesin-absensi/device') }}/" + encodeURIComponent(id);
        document.getElementById('edit_machine_name').value = machine.machine_name || '';
        document.getElementById('edit_machine_type').value = machine.machine_type || 'solution';
        document.getElementById('edit_machine_ip').value = machine.machine_ip || '';
        document.getElementById('edit_port').value = machine.port || 4370;
        document.getElementById('edit_username').value = '';
        document.getElementById('edit_password').value = '';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('editDeviceModal')).show();
    }

    const selectMesinKirim = document.getElementById('kirim_mesin_tujuan');
    const selectKaryawanKirim = document.getElementById('kirim_karyawan_id');

    selectMesinKirim.addEventListener('change', function() {
        const machineId = this.value;
        const registeredPins = usersByMachine[machineId] || [];
        selectKaryawanKirim.innerHTML = '<option value="">-- Pilih Karyawan --</option>';

        if (!machineId) {
            selectKaryawanKirim.disabled = true;
            selectKaryawanKirim.innerHTML = '<option value="">-- Pilih Mesin terlebih Dahulu --</option>';
            return;
        }

        selectKaryawanKirim.disabled = false;
        let countUnsynced = 0;

        Object.values(karyawanOptions).forEach(karyawan => {
            if (!registeredPins.includes(String(karyawan.id_karyawan))) {
                const option = document.createElement('option');
                option.value = karyawan.id;
                option.text = karyawan.id_karyawan + ' - ' + karyawan.nama;
                selectKaryawanKirim.appendChild(option);
                countUnsynced++;
            }
        });

        if (countUnsynced === 0) {
            selectKaryawanKirim.innerHTML = '<option value="">-- Semua Karyawan Sudah Sinkron --</option>';
            selectKaryawanKirim.disabled = true;
        }
    });

    // Pilih semua karyawan yang tampil (belum ada di mesin tujuan)
    const btnPilihSemua = document.getElementById('kirim_pilih_semua');
    if (btnPilihSemua && selectKaryawanKirim) {
        btnPilihSemua.addEventListener('click', function () {
            if (selectKaryawanKirim.disabled) {
                return;
            }
            Array.from(selectKaryawanKirim.options).forEach((o) => { o.selected = true; });
            perbaruiHitunganKirim();
        });
    }

    const btnBatalPilih = document.getElementById('kirim_batal_pilih');
    if (btnBatalPilih && selectKaryawanKirim) {
        btnBatalPilih.addEventListener('click', function () {
            Array.from(selectKaryawanKirim.options).forEach((o) => { o.selected = false; });
            perbaruiHitunganKirim();
        });
    }

    function perbaruiHitunganKirim() {
        const info = document.getElementById('kirim_hitung');
        if (!info || !selectKaryawanKirim) {
            return;
        }
        const n = Array.from(selectKaryawanKirim.selectedOptions).length;
        info.textContent = n > 0 ? n + ' karyawan dipilih' : 'Belum ada karyawan dipilih';
    }

    if (selectKaryawanKirim) {
        selectKaryawanKirim.addEventListener('change', perbaruiHitunganKirim);
    }

    const cleanMachine = document.getElementById('cleanMachine');
    if (cleanMachine) {
        cleanMachine.addEventListener('change', function() {
            document.getElementById('cleanMachineForm').action = "{{ url('admin/mesin-absensi/clean') }}/" + encodeURIComponent(this.value);
        });
    }
</script>
</body>
</html>
