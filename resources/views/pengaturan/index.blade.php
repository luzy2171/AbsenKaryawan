<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Perangkat - AbsensiPro</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .sidebar { height: 100vh; background-color: #fff; border-right: 1px solid #dee2e6; }
        .nav-link.menu { color: #333; border-radius: 8px; margin-bottom: 5px; }
        .nav-link.menu.active { background-color: #e8f5e9; color: #2e7d32; font-weight: bold; }
        .card-custom { border: none; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.04); }
        .status-indicator { width: 12px; height: 12px; border-radius: 50%; display: inline-block; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar Menu -->
        <div class="col-md-2 sidebar p-3 d-none d-md-block">
            <div class="d-flex align-items-center mb-4 px-2">
                <i class="bi bi-fingerprint text-success fs-3 me-2"></i>
                <h5 class="fw-bold m-0 text-success">AbsensiPro</h5>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link menu" href="{{ url('/dashboard') }}"><i class="bi bi-grid me-2"></i> Dashboard</a></li>
                <li class="nav-item"><a class="nav-link menu" href="{{ url('/karyawan') }}"><i class="bi bi-people me-2"></i> Karyawan</a></li>
                <li class="nav-item"><a class="nav-link menu" href="{{ url('/absensi') }}"><i class="bi bi-calendar-check me-2"></i> Absensi</a></li>
                <li class="nav-item"><a class="nav-link menu active" href="{{ url('/pengaturan') }}"><i class="bi bi-gear me-2"></i> Pengaturan</a></li>
            </ul>
        </div>

        <!-- Konten Utama: Pusat Kontrol Perangkat -->
        <div class="col-md-10 p-4">

            @if(session('status'))
                <div class="alert alert-success border-0 shadow-sm mb-3 alert-dismissible fade show" role="alert">
                    {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger border-0 shadow-sm mb-3 alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- PANEL ATAS: STATUS MESIN & AKSI CEPAT SDK -->
            <div class="row g-3 mb-4">
                <!-- Info Status Perangkat -->
                <div class="col-md-5">
                    <div class="card card-custom p-4 bg-white h-100">
                        <h6 class="text-muted small fw-bold mb-3 text-uppercase">Informasi Perangkat</h6>
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-cpu-fill fs-2 text-success me-3"></i>
                            <div>
                                <h5 class="fw-bold m-0">Solution C100X</h5>
                                <small class="text-muted">IP Perangkat: 10.10.10.237</small>
                            </div>
                        </div>
                        <hr class="text-muted my-2">
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <span class="small fw-semibold">Status Koneksi Jaringan:</span>
                            <span class="badge bg-success-subtle text-success px-2 py-1">
                                <span class="status-indicator bg-success me-1"></span> Terhubung (Online)
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Tombol Kendali Cepat SDK (Tidak Terpisah-pisah) -->
                <div class="col-md-7">
                    <div class="card card-custom p-4 bg-white h-100">
                        <h6 class="text-muted small fw-bold mb-3 text-uppercase">Konsol Kendali Cepat SDK</h6>
                        <div class="row g-2">
                            <!-- 1. Tarik Data Utama -->
                            <div class="col-6">
                                <form action="{{ route('absensi.tarik') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-light border w-100 text-start py-2 small fw-semibold">
                                        <i class="bi bi-cloud-arrow-down text-primary me-2"></i> Sinkronisasi Log Masuk
                                    </button>
                                </form>
                            </div>
                            <!-- 2. Sync Jam Alat -->
                            <div class="col-6">
                                <form action="{{ route('pengaturan.sync-time') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-light border w-100 text-start py-2 small fw-semibold">
                                        <i class="bi bi-stopwatch text-success me-2"></i> Samakan Waktu Server
                                    </button>
                                </form>
                            </div>
                            <!-- 3. Remote Reboot -->
                            <div class="col-6">
                                <form action="{{ route('pengaturan.restart') }}" method="POST" onsubmit="return confirm('Reboot mesin absensi? Perangkat tidak dapat memindai selama proses mulai ulang.');">
                                    @csrf
                                    <button type="submit" class="btn btn-light border w-100 text-start py-2 small fw-semibold">
                                        <i class="bi bi-arrow-clockwise text-warning me-2"></i> Restart Mesin Fisik
                                    </button>
                                </form>
                            </div>
                            <!-- 4. Kosongkan Log Transaksi -->
                            <div class="col-6">
                                <form action="{{ route('pengaturan.clear') }}" method="POST" onsubmit="return confirm('Hapus seluruh transaksi di dalam memori mesin fisik? Data lokal di database web tetap aman.');">
                                    @csrf
                                    <button type="submit" class="btn btn-light border w-100 text-start py-2 text-danger small fw-semibold">
                                        <i class="bi bi-trash text-danger me-2"></i> Bersihkan Log Mesin
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PANEL BAWAH: DATA VIEWER & DATA INTERAKSI SDK -->
            <div class="card card-custom p-4 bg-white">
                <ul class="nav nav-pills mb-3 border-bottom pb-2" id="sdkTab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active small" id="user-tab" data-bs-toggle="tab" href="#tab-user"><i class="bi bi-people me-1"></i> Data Karyawan Perangkat</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link small" id="fp-tab" data-bs-toggle="tab" href="#tab-fp"><i class="bi bi-fingerprint me-1"></i> Pengelola Sidik Jari</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link small" id="log-tab" data-bs-toggle="tab" href="#tab-log"><i class="bi bi-file-earmark-text me-1"></i> Riwayat Log Mentah</a>
                    </li>
                </ul>

                <div class="tab-content pt-2">
                    <!-- Tab User: Ambil Data & Hapus User dari Mesin -->
                    <div class="tab-pane fade show active" id="tab-user" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-md-7 border-end">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="text-muted small fw-semibold">Daftar Pengguna Aktif di Memori Alat</span>
                                    <a href="{{ route('pengaturan.index', ['view_users' => 1]) }}" class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-arrow-repeat me-1"></i> Load Data User
                                    </a>
                                </div>
                                @if(!empty($users))
                                <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                                    <table class="table table-sm table-hover align-middle small">
                                        <thead class="table-light">
                                            <tr><th>PIN Alat</th><th>Nama Terdaftar</th></tr>
                                        </thead>
                                        <tbody>
                                            @foreach($users as $u)
                                            <tr><td><code>{{ $u['pin'] }}</code></td><td class="fw-semibold text-dark">{{ $u['name'] }}</td></tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @else
                                <div class="text-center py-4 text-muted small">Klik tombol 'Load Data User' untuk menampilkan data dari mesin.</div>
                                @endif
                            </div>
                            <div class="col-md-5">
                                <div class="bg-light p-3 rounded-3">
                                    <h6 class="fw-bold text-danger small mb-1"><i class="bi bi-person-x-fill me-1"></i> Hapus User Perangkat</h6>
                                    <p class="text-muted extra-small mb-3">Hapus profil user permanen dari mesin absensi fisik menggunakan User ID.</p>
                                    <form action="{{ route('pengaturan.hapus-user') }}" method="POST" onsubmit="return confirm('Hapus akun pengguna dari memori alat?');">
                                        @csrf
                                        <div class="mb-3">
                                            <label class="form-label extra-small fw-semibold text-dark">UserID / PIN Alat</label>
                                            <input type="text" name="user_id" class="form-control form-control-sm" placeholder="Contoh: 1" required>
                                        </div>
                                        <button type="submit" class="btn btn-danger btn-sm w-100">Eksekusi Hapus Akun</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Sidik Jari: Download, Upload & Hapus Template -->
                    <div class="tab-pane fade" id="tab-fp" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-md-6 border-end">
                                <h6 class="fw-bold text-success small mb-2"><i class="bi bi-download me-1"></i> Ambil & Download Template</h6>
                                <form action="{{ route('pengaturan.index') }}" method="GET" class="row g-2 mb-3 align-items-end">
                                    <input type="hidden" name="download_fp" value="1">
                                    <div class="col-5"><input type="text" name="user_id" class="form-control form-control-sm" placeholder="User ID" required></div>
                                    <div class="col-4"><input type="number" name="finger_id" class="form-control form-control-sm" placeholder="Finger ID" value="0" required></div>
                                    <div class="col-3"><button type="submit" class="btn btn-success btn-sm w-100">Ambil</button></div>
                                </form>
                                @if(!empty($templates))
                                <div class="p-2 border rounded bg-light" style="max-height: 150px; overflow-y: auto;">
                                    @foreach($templates as $t)
                                    <div class="extra-small text-muted mb-1"><strong>Size:</strong> {{ $t['size'] }} | <strong>Valid:</strong> {{ $t['valid'] }}</div>
                                    <code class="extra-small text-dark text-break">{{ $t['template'] }}</code>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <div class="bg-light p-3 rounded-3 mb-2">
                                    <h6 class="fw-bold text-primary small mb-2"><i class="bi bi-cloud-arrow-up-fill me-1"></i> Upload / Hapus Sidik Jari Manual</h6>
                                    <form id="fpActionForm" method="POST">
                                        @csrf
                                        <div class="row g-2 mb-2">
                                            <div class="col-6"><input type="text" name="user_id" class="form-control form-control-sm" placeholder="User ID" required></div>
                                            <div class="col-6"><input type="number" name="finger_id" class="form-control form-control-sm" placeholder="Finger ID" value="0" required></div>
                                        </div>
                                        <div class="mb-2">
                                            <textarea name="template" id="fpTemplateArea" class="form-control form-control-sm" rows="2" placeholder="Paste string template sidik jari (khusus untuk upload)..."></textarea>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="submit" onclick="submitFpForm('{{ route('pengaturan.upload-fp') }}')" class="btn btn-primary btn-sm w-50">Upload Data</button>
                                            <button type="submit" onclick="submitFpForm('{{ route('pengaturan.hapus-fp') }}')" class="btn btn-outline-danger btn-sm w-50">Hapus Data</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Log Mentah: Log Transaksi Mesin Terkombinasi Nama -->
                    <div class="tab-pane fade" id="tab-log" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted small fw-semibold">Log Transaksi Aktivitas Mesin Terkini</span>
                            <a href="{{ route('pengaturan.index', ['view_logs' => 1]) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye me-1"></i> Tampilkan Log Mentah Perangkat
                            </a>
                        </div>
                        @if(!empty($logs))
                        <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                            <table class="table table-sm table-hover align-middle small">
                                <thead class="table-light text-muted">
                                    <tr><th>UserID</th><th>Nama Karyawan</th><th>Waktu Transaksi</th><th>Verifikasi</th><th>Status</th></tr>
                                </thead>
                                <tbody>
                                    @foreach($logs as $l)
                                    <tr>
                                        <td><code>{{ $l['pin'] }}</code></td>
                                        <td class="fw-semibold">{{ $l['nama'] }}</td>
                                        <td>{{ $l['datetime'] }}</td>
                                        <td><span class="text-muted extra-small">Method ({{ $l['verified'] }})</span></td>
                                        <td><span class="badge bg-secondary-subtle text-secondary">Code ({{ $l['status'] }})</span></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="text-center py-4 text-muted small">Klik tombol 'Tampilkan Log Mentah Perangkat' untuk melakukan pembacaan langsung.</div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Script pembantu perpindahan aksi form upload/delete sidik jari
    function submitFpForm(actionUrl) {
        const form = document.getElementById('fpActionForm');
        const templateArea = document.getElementById('fpTemplateArea');

        if (actionUrl.includes('hapus-fp')) {
            templateArea.removeAttribute('required');
            if(!confirm('Hapus template sidik jari dari mesin?')) return;
        } else {
            templateArea.setAttribute('required', 'required');
        }

        form.action = actionUrl;
        form.submit();
    }
</script>
</body>
</html>
