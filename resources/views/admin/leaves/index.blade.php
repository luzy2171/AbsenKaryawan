<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Izin & Cuti - Absensi-BBM</title>
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
        <!-- SIDEBAR -->
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

        <!-- MAIN CONTENT -->
        <div class="col-md-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4 fade-in">
                <div>
                    <h4 class="fw-bold m-0 mb-1"><i class="bi bi-envelope-paper text-primary me-2"></i>Pengajuan Izin, Sakit & Cuti</h4>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-info-circle text-muted me-2"></i>
                        <small class="text-muted">Data ini akan otomatis diisi ke laporan Absensi sebagai pengganti Alpha.</small>
                    </div>
                </div>
                @if(auth()->user()->canEdit())
                <div>
                    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addLeaveModal">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Pengajuan
                    </button>
                </div>
                @endif
            </div>

            @if(session('status'))
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4 fade-in">
                    <i class="bi bi-check-circle-fill me-2"></i>{{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4 fade-in">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- FILTER -->
            <div class="card-custom p-4 bg-white mb-4 fade-in">
                <form action="{{ route('admin.leaves.index') }}" method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small text-muted">Cari Nama Karyawan</label>
                        <input type="text" name="search" class="form-control" placeholder="Ketik nama..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small text-muted">Bulan</label>
                        <select name="bulan" class="form-select">
                            <option value="">Semua Bulan</option>
                            @for($i=1; $i<=12; $i++)
                                <option value="{{ $i }}" {{ request('bulan') == $i ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small text-muted">Tahun</label>
                        <select name="tahun" class="form-select">
                            <option value="">Semua Tahun</option>
                            @for($i=date('Y'); $i>=date('Y')-3; $i--)
                                <option value="{{ $i }}" {{ request('tahun') == $i ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary w-50"><i class="bi bi-search"></i></button>
                        <a href="{{ route('admin.leaves.index') }}" class="btn btn-light border w-50"><i class="bi bi-arrow-clockwise"></i></a>
                    </div>
                </form>
            </div>

            <!-- TABEL DATA -->
            <div class="card-custom p-4 bg-white fade-in">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr class="border-bottom">
                                <th class="text-muted small fw-bold">TANGGAL</th>
                                <th class="text-muted small fw-bold">NAMA KARYAWAN</th>
                                <th class="text-muted small fw-bold">JENIS</th>
                                <th class="text-muted small fw-bold">STATUS</th>
                                <th class="text-muted small fw-bold">LAMA HARI</th>
                                <th class="text-muted small fw-bold">KETERANGAN</th>
                                <th class="text-muted small fw-bold">DOKUMEN</th>
                                <th class="text-muted small fw-bold text-end">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($leaves as $leave)
                            <tr class="border-bottom">
                                <td>
                                    <div class="fw-semibold">{{ $leave->tanggal_mulai->format('d/m/Y') }}</div>
                                    @if($leave->tanggal_mulai != $leave->tanggal_selesai)
                                        <small class="text-muted">s/d {{ $leave->tanggal_selesai->format('d/m/Y') }}</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle text-success me-2" style="width: 35px; height: 35px; font-size: 14px;">
                                            {{ strtoupper(substr($leave->karyawan->nama ?? '-', 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="fw-bold">{{ $leave->karyawan->nama ?? 'Data Terhapus' }}</div>
                                            <small class="text-muted">PIN: {{ $leave->karyawan->id_karyawan ?? '-' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $badgeClass = match($leave->jenis) {
                                            'Cuti' => 'bg-primary-subtle text-primary',
                                            'Sakit' => 'bg-warning-subtle text-warning',
                                            'Izin' => 'bg-info-subtle text-info',
                                            default => 'bg-secondary'
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }} px-3 py-2" style="font-size: 12px;">{{ $leave->jenis }}</span>
                                </td>
                                <td>
                                    @if($leave->status === 'Disetujui')
                                        <span class="badge bg-success-subtle text-success px-3 py-2" style="font-size: 12px;">
                                            <i class="bi bi-check-circle-fill me-1"></i> Disetujui
                                        </span>
                                        <div class="small text-muted mt-1" style="font-size: 10px;">
                                            Oleh: {{ $leave->approver->name ?? 'Sistem' }}
                                        </div>
                                    @else
                                        @php
                                            $currentCount = $leave->leaveApprovals->count();
                                        @endphp
                                        <span class="badge bg-warning-subtle text-warning px-3 py-2" style="font-size: 12px;">
                                            <i class="bi bi-hourglass-split me-1"></i> Menunggu Approval 
                                            @if($requiredApprovals > 1)
                                                ({{ $currentCount }}/{{ $requiredApprovals }})
                                            @endif
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ $leave->tanggal_mulai->diffInDays($leave->tanggal_selesai) + 1 }} Hari</span>
                                </td>
                                <td>
                                    <span class="text-muted small d-inline-block text-truncate" style="max-width: 200px;" title="{{ $leave->keterangan }}">
                                        {{ $leave->keterangan ?? '-' }}
                                    </span>
                                </td>
                                <td>
                                    @if($leave->dokumen)
                                        <a href="{{ asset('storage/' . $leave->dokumen) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-file-earmark-text"></i> Lihat File
                                        </a>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($leave->status === 'Menunggu' && auth()->user()->isTrueApprover())
                                        @php
                                            $hasApproved = $leave->leaveApprovals->where('user_id', auth()->id())->isNotEmpty();
                                        @endphp
                                        @if(!$hasApproved)
                                        <form action="{{ route('admin.leaves.approve', $leave->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin akan menyetujui pengajuan ini?');">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="btn btn-sm btn-outline-success">
                                                <i class="bi bi-check-circle"></i> Setuju
                                            </button>
                                        </form>
                                        @else
                                        <span class="badge bg-success-subtle text-success me-1" title="Anda sudah menyetujui">
                                            <i class="bi bi-check-all"></i> Disetujui Anda
                                        </span>
                                        @endif
                                    @endif
                                    @if(auth()->user()->isTrueApprover())
                                    <form action="{{ route('admin.leaves.destroy', $leave->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus data pengajuan ini? Ini juga akan menghapus cap absensi Izin/Cuti/Sakit untuk tanggal tersebut di Laporan Absensi.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i> Hapus
                                        </button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <i class="bi bi-inbox fs-1 d-block mb-3 text-secondary opacity-50"></i>
                                    <p class="text-muted mb-0">Belum ada data pengajuan izin/cuti.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="mt-4">
                    {{ $leaves->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Cuti/Izin -->
<div class="modal fade" id="addLeaveModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                <h5 class="modal-title fw-bold"><i class="bi bi-envelope-paper me-2"></i>Tambah Izin / Cuti / Sakit</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.leaves.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 mb-4 small">
                        <i class="bi bi-info-circle-fill me-2"></i>Data ini akan secara otomatis menggantikan status <strong>Alpha</strong> menjadi Izin/Cuti/Sakit di tabel Laporan Absensi.
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pilih Karyawan</label>
                        <select name="karyawan_id" class="form-select" required>
                            <option value="">-- Pilih Karyawan --</option>
                            @foreach($karyawans as $k)
                                <option value="{{ $k->id }}">[{{ $k->id_karyawan }}] {{ $k->nama }} - Sisa Cuti: {{ $k->sisaCuti() }} Hari</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Dari Tanggal</label>
                            <input type="date" name="tanggal_mulai" class="form-control" required value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Sampai Tanggal</label>
                            <input type="date" name="tanggal_selesai" class="form-control" required value="{{ date('Y-m-d') }}">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Jenis Pengajuan</label>
                        <select name="jenis" class="form-select" required>
                            <option value="Sakit">Sakit</option>
                            <option value="Izin">Izin</option>
                            <option value="Cuti">Cuti</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Keterangan / Alasan</label>
                        <textarea name="keterangan" class="form-control" rows="3" placeholder="Tuliskan keterangan detail..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Surat / Dokumen <small class="text-muted fw-normal">(Opsional)</small></label>
                        <input type="file" name="dokumen" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                        <small class="text-muted">Format: JPG, PNG, PDF. Maks: 2MB.</small>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold">Simpan & Sinkronkan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>