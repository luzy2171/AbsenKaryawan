<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Database - Absensi-BBM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <style>
        .month-card { transition: all 0.3s ease; border-left: 5px solid var(--primary-color); }
        .month-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-lg); }
        .month-card.empty-data { border-left-color: var(--gray-300); opacity: 0.7; }
        .stat-box { border-radius: 12px; padding: 15px; text-align: center; }
        .bg-red-soft { background-color: #fee2e2; color: #dc2626; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- SIDEBAR -->
        <div class="col-md-2 sidebar p-3 d-none d-md-block">
            <div class="d-flex align-items-center mb-4 px-2 py-3">
                <div class="stat-icon bg-success text-white me-2"><i class="bi bi-fingerprint"></i></div>
                <div><h5 class="fw-bold m-0 text-success" style="font-size: 18px;">Absensi-BBM</h5><small class="text-muted" style="font-size: 10px;">Attendance System</small></div>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}" href="{{ url('/dashboard') }}"><i class="bi bi-grid me-2"></i> Dashboard</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->is('karyawan*') ? 'active' : '' }}" href="{{ url('/karyawan') }}"><i class="bi bi-people me-2"></i> Karyawan</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->is('absensi*') ? 'active' : '' }}" href="{{ url('/absensi') }}"><i class="bi bi-calendar-check me-2"></i> Absensi</a></li>
                
@if(auth()->user()->isApprover())
                 <li class="nav-item mt-3"><small class="text-muted px-3 fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">PENGATURAN</small></li>
                                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/leaves*') ? 'active' : '' }}" href="{{ route('admin.leaves.index') }}">
                        <i class="bi bi-envelope-paper me-2"></i> Izin & Cuti
                    </a>
                </li>
                @if(auth()->user()->isSuperadmin())
@if(auth()->user()->isTrueApprover())
<li class="nav-item"><a class="nav-link" href="{{ url('/admin/settings') }}"><i class="bi bi-clock-history me-2"></i> Set Jam Kerja</a></li>
                @if(auth()->user()->isTrueApprover())
                <li class="nav-item"><a class="nav-link" href="{{ url('/pengaturan') }}"><i class="bi bi-gear me-2"></i> Kontrol Mesin</a></li>
                @if(auth()->user()->isTrueApprover())
                <li class="nav-item"><a class="nav-link active" href="{{ route('admin.maintenance.index') }}"><i class="bi bi-database-fill-gear me-2"></i> Maintenance DB</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/admin/users') }}"><i class="bi bi-person-gear me-2"></i> Manajemen User</a></li>
                @endif
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
            <div class="d-flex justify-content-between align-items-center mb-4 fade-in">
                <div>
                    <h4 class="fw-bold m-0 mb-1"><i class="bi bi-database-fill-gear text-danger me-2"></i>Maintenance Database</h4>
                    <div class="d-flex align-items-center"><i class="bi bi-shield-exclamation text-muted me-2"></i><small class="text-muted">Kelola kapasitas dan bersihkan data lama (Purge Data)</small></div>
                </div>
            </div>

            @if(session('status'))
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4 fade-in"><i class="bi bi-check-circle-fill me-2"></i>{{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4 fade-in"><i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            @endif

            <!-- STATISTIK DB -->
            <div class="row g-3 mb-4 fade-in">
                <div class="col-md-4">
                    <div class="card-custom bg-white p-3 d-flex align-items-center">
                        <div class="stat-icon bg-primary-subtle text-primary me-3 fs-3"><i class="bi bi-database"></i></div>
                        <div>
                            <h4 class="fw-bold m-0">{{ number_format($totalAbsensiDB) }}</h4>
                            <small class="text-muted">Total Baris Absensi di DB</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card-custom bg-white p-3 d-flex align-items-center">
                        <div class="stat-icon bg-info-subtle text-info me-3 fs-3"><i class="bi bi-clock-history"></i></div>
                        <div>
                            <h4 class="fw-bold m-0">{{ number_format($totalLemburDB) }}</h4>
                            <small class="text-muted">Total Baris Lembur di DB</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card-custom bg-white p-3 d-flex align-items-center">
                        <div class="stat-icon bg-warning-subtle text-warning me-3 fs-3"><i class="bi bi-hdd"></i></div>
                        <div>
                            <h4 class="fw-bold m-0">{{ $dbSizeMB }} MB</h4>
                            <small class="text-muted">Estimasi Ukuran Tabel</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FILTER TAHUN -->
            <div class="card-custom p-3 bg-white mb-4 fade-in d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <i class="bi bi-calendar-range fs-4 text-primary me-3"></i>
                    <div>
                        <h6 class="fw-bold mb-0">Rincian Data Bulanan</h6>
                        <small class="text-muted">Lihat jumlah data dan bersihkan per bulan</small>
                    </div>
                </div>
                <form action="{{ route('admin.maintenance.index') }}" method="GET" class="d-flex gap-2">
                    <select name="tahun" class="form-select border-primary" onchange="this.form.submit()">
                        @foreach($tahunTersedia as $t)
                            <option value="{{ $t }}" {{ $tahunDipilih == $t ? 'selected' : '' }}>Tahun {{ $t }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            <!-- LIST BULANAN -->
            <div class="row g-3 fade-in">
                @foreach($rekapBulanan as $rekap)
                <div class="col-md-4">
                    <div class="card-custom p-4 bg-white month-card {{ $rekap['jumlah_absensi'] == 0 ? 'empty-data' : '' }}">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h5 class="fw-bold text-dark m-0">{{ $rekap['bulan_nama'] }} <span class="text-muted">{{ $rekap['tahun'] }}</span></h5>
                            @if($rekap['jumlah_absensi'] > 0)
                                <span class="badge bg-success-subtle text-success"><i class="bi bi-hdd-fill me-1"></i>Ada Data</span>
                            @else
                                <span class="badge bg-light text-muted">Kosong</span>
                            @endif
                        </div>
                        
                        <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                            <div class="text-center w-50 border-end">
                                <h3 class="fw-bold text-primary m-0">{{ number_format($rekap['jumlah_absensi']) }}</h3>
                                <small class="text-muted" style="font-size: 11px;">Record Absen</small>
                            </div>
                            <div class="text-center w-50">
                                <h3 class="fw-bold text-info m-0">{{ number_format($rekap['jumlah_lembur']) }}</h3>
                                <small class="text-muted" style="font-size: 11px;">Record Lembur</small>
                            </div>
                        </div>

                        @if($rekap['jumlah_absensi'] > 0)
                            <button type="button" class="btn btn-outline-danger w-100" onclick="showPurgeModal('{{ $rekap['bulan_angka'] }}', '{{ $rekap['bulan_nama'] }}', '{{ $rekap['tahun'] }}', '{{ $rekap['jumlah_absensi'] }}')">
                                <i class="bi bi-trash3 me-1"></i> Kosongkan Bulan Ini
                            </button>
                        @else
                            <button type="button" class="btn btn-light w-100" disabled>Tidak ada data</button>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus Data -->
<div class="modal fade" id="purgeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-danger text-white border-0" style="border-radius: 16px 16px 0 0;">
                <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-octagon-fill me-2"></i>Peringatan Penghapusan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.maintenance.purge') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <input type="hidden" name="bulan" id="purgeBulan">
                    <input type="hidden" name="tahun" id="purgeTahun">
                    
                    <div class="text-center mb-4">
                        <i class="bi bi-trash-fill text-danger" style="font-size: 4rem;"></i>
                        <h4 class="fw-bold mt-2">Hapus <span id="lblDataCount" class="text-danger"></span> Data?</h4>
                        <p class="text-muted">Anda akan menghapus seluruh data absensi dan lembur pada bulan <strong id="lblBulanTahun" class="text-dark"></strong>.</p>
                    </div>

                    <div class="alert alert-warning border-0 bg-red-soft">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><strong>PERHATIAN!</strong> Data yang dihapus tidak dapat dikembalikan. Laporan PDF/Excel untuk bulan ini tidak akan bisa diakses lagi.
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Untuk melanjutkan, ketik "HAPUS PERMANEN" di bawah ini:</label>
                        <input type="text" name="confirm_text" class="form-control" autocomplete="off" required pattern="HAPUS PERMANEN" placeholder="HAPUS PERMANEN">
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0 bg-light" style="border-radius: 0 0 16px 16px;">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger px-4 fw-bold"><i class="bi bi-trash3 me-1"></i> Ya, Hapus Permanen</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function showPurgeModal(bulanAngka, bulanNama, tahun, dataCount) {
        document.getElementById('purgeBulan').value = bulanAngka;
        document.getElementById('purgeTahun').value = tahun;
        document.getElementById('lblBulanTahun').innerText = bulanNama + ' ' + tahun;
        document.getElementById('lblDataCount').innerText = dataCount;
        
        var myModal = new bootstrap.Modal(document.getElementById('purgeModal'));
        myModal.show();
    }
</script>
</body>
</html>