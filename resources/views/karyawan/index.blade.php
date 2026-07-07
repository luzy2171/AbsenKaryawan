<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Karyawan - Absensi-BBM</title>
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
        <div class="col-md-2 sidebar p-3 d-none d-md-block">
            <div class="d-flex align-items-center mb-4 px-2">
                <i class="bi bi-fingerprint text-success fs-3 me-2"></i>
                <h5 class="fw-bold m-0 text-success">Absensi-BBM</h5>
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
            </ul>
        </div>

        <div class="col-md-10 p-4">
            <div class="card card-custom p-4 bg-white">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="fw-bold m-0">Data Karyawan</h4>
                        <small class="text-muted">{{ count($karyawans) }} total record terdaftar</small>
                    </div>

                    <div class="d-flex gap-2">
                        <form action="{{ route('karyawan.sync-mesin') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menarik semua data user aktif dari mesin fisik ke database lokal web?');" class="m-0">
                            @csrf
                            <button type="submit" class="btn btn-outline-success">
                                <i class="bi bi-arrow-clockwise"></i> Sinkronisasi dari Mesin
                            </button>
                        </form>

                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTambahKaryawan">
                            <i class="bi bi-plus-lg me-1"></i> Tambah Karyawan
                        </button>
                    </div>
                </div>

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

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID Karyawan (PIN)</th>
                                <th>Nama</th>
                                <th>Departemen</th>
                                <th>Jabatan</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($karyawans as $k)
                            <tr>
                                <td class="fw-semibold"><code>{{ $k->id_karyawan }}</code></td>
                                <td class="fw-bold text-dark">{{ $k->nama }}</td>
                                <td>{{ $k->departemen ?? '-' }}</td>
                                <td>{{ $k->jabatan ?? '-' }}</td>
                                <td><span class="badge bg-success-subtle text-success px-2.5 py-1.5">{{ $k->status }}</span></td>
                                <td class="text-center">
                                    <form action="{{ route('karyawan.destroy', $k->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus karyawan ini dari sistem dan mesin fisik?');" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger border-0">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Belum ada data karyawan. Silakan klik tombol <strong>Sinkronisasi dari Mesin</strong> untuk memuat data.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambahKaryawan" tabindex="-1" aria-labelledby="modalTambahKaryawanLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalTambahKaryawanLabel">Form Tambah Karyawan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('karyawan.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">ID Karyawan / PIN Mesin</label>
                        <input type="text" name="id_karyawan" class="form-control" placeholder="Contoh: 6" required>
                        <div class="form-text text-muted extra-small">Pastikan ID berupa angka unik dan cocok dengan registrasi sidik jari di mesin.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" placeholder="Nama Karyawan" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Departemen</label>
                        <input type="text" name="departemen" class="form-control" placeholder="Contoh: IT, HRD, GA">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Jabatan</label>
                        <input type="text" name="jabatan" class="form-control" placeholder="Contoh: Software Engineer">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success btn-sm">Simpan ke Web & Mesin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
