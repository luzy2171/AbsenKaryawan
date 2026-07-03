<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi Karyawan - Absensi-BBM</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .sidebar { height: 100vh; background-color: #fff; border-right: 1px solid #dee2e6; }
        .nav-link { color: #333; border-radius: 8px; margin-bottom: 5px; }
        .nav-link.active { background-color: #e8f5e9; color: #2e7d32; font-weight: bold; }
        .card-custom { border: none; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.04); }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar Menu Navigasi -->
        <div class="col-md-2 sidebar p-3 d-none d-md-block">
            <div class="d-flex align-items-center mb-4 px-2">
                <i class="bi bi-fingerprint text-success fs-3 me-2"></i>
                <h5 class="fw-bold m-0 text-success">Absensi-BBM</h5>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link" href="{{ url('/dashboard') }}"><i class="bi bi-grid me-2"></i> Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/karyawan') }}"><i class="bi bi-people me-2"></i> Karyawan</a></li>
                <li class="nav-item"><a class="nav-link active" href="{{ url('/absensi') }}"><i class="bi bi-calendar-check me-2"></i> Absensi</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/pengaturan') }}"><i class="bi bi-gear me-2"></i> Pengaturan</a></li>
            </ul>
        </div>

        <!-- Bagian Konten Utama Halaman Absensi -->
        <div class="col-md-10 p-4">
            <div class="card card-custom p-4 bg-white">

                <!-- Header Atas (Aksi Cepat & Kontrol Otomatisasi) -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="fw-bold m-0">Absensi</h4>
                        <small class="text-muted">{{ count($attendances) }} catatan absensi ditemukan</small>
                    </div>

                    <div class="d-flex gap-3 align-items-center">
                        <!-- FORM SAKELAR ON/OFF SINKRONISASI OTOMATIS -->
                        <form action="{{ route('absensi.toggle-auto') }}" method="POST" id="formToggleAuto" class="border rounded px-3 py-2 bg-light d-flex align-items-center gap-3 m-0">
                            @csrf
                            <div class="lh-sm">
                                <label class="fw-bold text-dark d-block mb-0" style="font-size: 0.85rem; cursor: pointer;" for="autoPullSwitch">Tarik Otomatis</label>
                                <small class="text-muted d-block" style="font-size: 0.75rem;"> </small>
                            </div>
                            <div class="form-check form-switch mb-0 fs-5" style="padding-left: 2.5rem;">
                                <input class="form-check-input m-0" type="checkbox" role="switch" id="autoPullSwitch"
                                       value="ON" {{ ($autoPullStatus ?? 'OFF') == 'ON' ? 'checked' : '' }} onchange="submitToggleAuto()" style="cursor: pointer;">
                                <input type="hidden" name="status" id="statusHidden" value="{{ $autoPullStatus ?? 'OFF' }}">
                            </div>
                        </form>

                        <!-- Tombol Tarik Data Manual -->
                        <form action="{{ route('absensi.tarik') }}" method="POST" onsubmit="return confirm('Mulai tarik log kehadiran 3 bulan terakhir dari mesin absensi fisik?');" class="m-0">
                            @csrf
                            <button type="submit" class="btn btn-success fw-semibold">
                                <i class="bi bi-arrow-clockwise me-1"></i> Tarik Data Mesin
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Notifikasi Status Flash Session Laravel -->
                @if(session('status'))
                    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>{{ session('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <!-- Form Filter Pencarian Data Harian -->
                <form action="{{ route('absensi.index') }}" method="GET" class="row g-2 mb-3 align-items-center">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" name="search" class="form-control border-start-0" placeholder="Cari karyawan..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <input type="date" name="tanggal" class="form-control" value="{{ $tanggalFilter ?? date('Y-m-d') }}" onchange="this.form.submit()">
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="Semua Status" {{ ($statusFilter ?? 'Semua Status') == 'Semua Status' ? 'selected' : '' }}>Semua Status</option>
                            <option value="Hadir" {{ ($statusFilter ?? '') == 'Hadir' ? 'selected' : '' }}>Hadir</option>
                            <option value="Terlambat" {{ ($statusFilter ?? '') == 'Terlambat' ? 'selected' : '' }}>Terlambat</option>
                            <option value="Alpha" {{ ($statusFilter ?? '') == 'Alpha' ? 'selected' : '' }}>Alpha</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ route('absensi.index') }}" class="btn btn-light border w-100 fw-semibold text-secondary">Reset</a>
                    </div>
                </form>

                <!-- Form Filter Cetak Absensi Bulanan / Rentang Tanggal dengan Pilihan Karyawan (Fix visual dari image_eb1786.png) -->
                <div class="card bg-light border-0 p-3 mb-4 rounded-3 shadow-sm">
                    <form action="{{ route('absensi.cetak') }}" method="GET" target="_blank" class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <span class="small fw-bold text-dark d-block mb-1"><i class="bi bi-person-badge-fill text-success me-1"></i> Pilih ID / Nama:</span>
                            <select name="karyawan_id" class="form-select form-select-sm" style="padding-top: 0.38rem; padding-bottom: 0.38rem;">
                                <option value="semua">— Semua Karyawan —</option>
                                @foreach($karyawans as $k)
                                    <option value="{{ $k->id }}">[{{ $k->id_karyawan }}] {{ $k->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <span class="small fw-bold text-dark d-block mb-1"><i class="bi bi-printer-fill text-success me-1"></i> Cetak Laporan Dari:</span>
                            <input type="date" name="tanggal_mulai" class="form-control form-control-sm" value="{{ date('Y-m-01') }}" required style="padding-top: 0.38rem; padding-bottom: 0.38rem;">
                        </div>
                        <div class="col-md-3">
                            <span class="small fw-bold text-dark d-block mb-1">Sampai Tanggal:</span>
                            <input type="date" name="tanggal_selesai" class="form-control form-control-sm" value="{{ date('Y-m-t') }}" required style="padding-top: 0.38rem; padding-bottom: 0.38rem;">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-sm btn-outline-success w-100 fw-semibold" style="padding-top: 0.38rem; padding-bottom: 0.38rem;">
                                <i class="bi bi-file-earmark-pdf me-1"></i> Buka Halaman Cetak
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tabel Riwayat Kehadiran Database Internal -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light text-muted small">
                            <tr>
                                <th>ID</th>
                                <th>NAMA</th>
                                <th>TANGGAL</th>
                                <th>JAM MASUK</th>
                                <th>JAM PULANG</th>
                                <th>STATUS</th>
                                <th>VERIFIKASI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($attendances as $item)
                            <tr class="small">
                                <td class="text-muted fw-semibold">
                                    <code>{{ $item->karyawan->id_karyawan ?? $item->id_karyawan }}</code>
                                </td>
                                <td class="fw-bold text-dark">
                                    {{ $item->karyawan->nama ?? $item->nama }}
                                </td>
                                <td>
                                    {{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') }}
                                </td>
                                <td>
                                    {{ $item->jam_masuk ? \Carbon\Carbon::parse($item->jam_masuk)->format('H:i') : '-' }} WIB
                                </td>
                                <td>
                                    {{ $item->jam_pulang ? \Carbon\Carbon::parse($item->jam_pulang)->format('H:i') : '-' }} WIB
                                </td>
                                <td>
                                    <span class="badge {{ $item->status == 'Hadir' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }} px-2.5 py-1.5">
                                        {{ $item->status }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-muted small">
                                        <i class="bi bi-shield-check me-1 text-success"></i> Mesin SOAP
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted small">
                                    <i class="bi bi-database-dash d-block fs-3 mb-2 text-secondary"></i>
                                    Tidak ada data absensi untuk kriteria pencarian ini. Silakan klik <strong>Tarik Data Mesin</strong> untuk memperbarui data 3 bulan terakhir.
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

<!-- Script Pemicu Submit Sakelar Otomatis -->
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
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
