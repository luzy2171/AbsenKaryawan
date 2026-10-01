<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tanda Tangan - {{ \App\Helpers\CompanyProfile::nama() }}</title>
    @include('partials.favicon')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ \App\Helpers\Asset::url('css/custom.css') }}">
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- SIDEBAR -->
        <div class="col-md-2 sidebar p-3">
            @include('partials.brand')
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
                <li class="nav-item mt-3">
                    <small class="text-muted px-3 fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">PENGATURAN</small>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/leaves*') ? 'active' : '' }}" href="{{ route('admin.leaves.index') }}">
                        <i class="bi bi-envelope-paper me-2"></i> Izin &amp; Cuti
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/cuti-control*') ? 'active' : '' }}" href="{{ route('admin.cuti.control') }}">
                        <i class="bi bi-sliders me-2"></i> Kontrol Cuti
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="{{ route('signature.edit') }}">
                        <i class="bi bi-pen me-2"></i> Tanda Tangan
                    </a>
                </li>

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
                    <h4 class="fw-bold m-0 mb-1"><i class="bi bi-pen text-primary me-2"></i>Tanda Tangan Digital</h4>
                    <small class="text-muted">Tanda tangan ini otomatis dicetak di Surat Persetujuan dan Surat Penolakan izin/sakit/cuti yang Anda putuskan.</small>
                </div>
                <a href="{{ route('admin.leaves.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali ke Pengajuan
                </a>
            </div>

            @if(session('status'))
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4">
                    <i class="bi bi-check-circle-fill me-2"></i>{{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="row g-4">
                <!-- Status saat ini -->
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-0 pt-3">
                            <h6 class="fw-bold mb-0"><i class="bi bi-card-heading me-2"></i>Tanda Tangan Saat Ini</h6>
                        </div>
                        <div class="card-body">
                            @if($signature && $signature->imageUrl())
                                <div class="text-center border rounded bg-white p-3 mb-3">
                                    <img src="{{ $signature->imageUrl() }}" alt="Tanda tangan" style="max-height: 90px; max-width: 220px;">
                                </div>
                                <table class="table table-sm mb-0">
                                    <tr>
                                        <td class="text-muted" style="width: 100px;">Nama</td>
                                        <td class="fw-semibold">{{ $signature->nama }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Jabatan</td>
                                        <td>{{ $signature->jabatan ?: '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Diperbarui</td>
                                        <td>{{ $signature->updated_at->format('d/m/Y H:i') }}</td>
                                    </tr>
                                </table>
                            @else
                                <div class="alert alert-warning mb-0">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    Belum ada tanda tangan. Surat tetap terbit, tetapi kolom tanda tangan kosong sampai Anda mengunggah gambar di bawah.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Form upload -->
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 pt-3">
                            <h6 class="fw-bold mb-0"><i class="bi bi-upload me-2"></i>{{ $signature ? 'Ganti' : 'Simpan' }} Tanda Tangan</h6>
                        </div>
                        <div class="card-body">
                            {{-- Route didaftarkan sebagai POST, jadi jangan pakai @method('PUT'):
                                 Laravel akan mengubah request jadi PUT dan route tidak cocok (405). --}}
                            <form action="{{ route('signature.update') }}" method="POST" enctype="multipart/form-data">
                                @csrf

                                <div class="mb-3">
                                    <label for="nama" class="form-label">Nama yang dicetak <span class="text-danger">*</span></label>
                                    <input type="text" name="nama" id="nama" required maxlength="100"
                                           class="form-control @error('nama') is-invalid @enderror"
                                           value="{{ old('nama', $signature->nama ?? auth()->user()->name) }}">
                                    @error('nama')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="jabatan" class="form-label">Jabatan</label>
                                    <input type="text" name="jabatan" id="jabatan" maxlength="100"
                                           class="form-control @error('jabatan') is-invalid @enderror"
                                           value="{{ old('jabatan', $signature->jabatan ?? '') }}"
                                           placeholder="Contoh: Manajer SDM">
                                    @error('jabatan')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Kosongkan bila ingin memakai nama akun dan jabatan bawaan sistem.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="image" class="form-label">Gambar tanda tangan <span class="text-danger">*</span></label>
                                    <input type="file" name="image" id="image" required accept=".png,.jpg,.jpeg"
                                           class="form-control @error('image') is-invalid @enderror">
                                    @error('image')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">
                                        PNG/JPG, maksimal 1 MB, lebar 120-2000 px. Gunakan gambar tulisan tangan
                                        berlatar transparan (PNG) agar hasil cetak rapi.
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-3 pt-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-upload"></i> {{ $signature ? 'Ganti Tanda Tangan' : 'Simpan Tanda Tangan' }}
                                    </button>
                                    <a href="{{ route('admin.leaves.index') }}" class="text-muted small">Kembali ke Pengajuan</a>
                                </div>
                            </form>

                            @if($signature)
                                <form action="{{ route('signature.destroy') }}" method="POST" class="mt-4 pt-3 border-top"
                                      onsubmit="return confirm('Hapus tanda tangan ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i> Hapus Tanda Tangan
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Nomor surat -->
            <div class="card border-0 shadow-sm mt-4 fade-in">
                <div class="card-header bg-white border-0 pt-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-hash me-2"></i>Format Nomor Surat</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small">
                        Format ini berlaku untuk semua surat persetujuan dan penolakan izin/sakit/cuti.
                        Nomor urut otomatis, jadi setiap surat pasti berbeda tanpa perlu diinput manual.
                    </p>
                    <form action="{{ route('signature.format_nomor') }}" method="POST" class="row g-2 align-items-end">
                        @csrf
                        @method('PUT')
                        <div class="col-md-6">
                            <label for="nomor_surat_format" class="form-label small fw-semibold mb-1">Format</label>
                            <input type="text" name="nomor_surat_format" id="nomor_surat_format" required maxlength="100"
                                   class="form-control @error('nomor_surat_format') is-invalid @enderror"
                                   value="{{ old('nomor_surat_format', \App\Helpers\NomorSurat::format()) }}">
                            @error('nomor_surat_format')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded bg-light px-3 py-2 small">
                                <span class="text-muted d-block">Contoh</span>
                                <span class="fw-bold">
                                    {{ str_replace(
                                        ['{nomor}', '{jenis}', '{bulan}', '{tahun}'],
                                        ['0001', 'CUTI', now()->format('m'), now()->format('Y')],
                                        old('nomor_surat_format', \App\Helpers\NomorSurat::format())
                                    ) }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check-lg"></i> Simpan</button>
                        </div>
                    </form>
                    <div class="mt-3 small text-muted">
                        Token yang bisa dipakai:
                        @foreach (\App\Helpers\NomorSurat::PLACEHOLDERS as $token => $arti)
                            <span class="badge bg-light text-dark border me-1">{{ $token }}</span>
                            <span class="me-2">{{ $arti }}</span>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Preview surat -->
            <div class="card border-0 shadow-sm mt-4 mb-4 fade-in">
                <div class="card-header bg-white border-0 pt-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-eye me-2"></i>Pratinjau Surat</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2 align-items-end mb-3">
                        <div>
                            <label class="form-label small fw-semibold mb-1">Jenis</label>
                            <select class="form-select form-select-sm" id="prevJenis">
                                @foreach (\App\Helpers\NomorSurat::JENIS as $j)
                                    <option value="{{ $j }}">{{ $j }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label small fw-semibold mb-1">Jenis Surat</label>
                            <select class="form-select form-select-sm" id="prevStatus">
                                <option value="Disetujui">Persetujuan</option>
                                <option value="Ditolak">Penolakan</option>
                            </select>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="prevMuat">
                            <i class="bi bi-arrow-clockwise"></i> Tampilkan
                        </button>
                        <a class="btn btn-sm btn-outline-secondary d-none" id="prevUnduh" target="_blank">
                            <i class="bi bi-download"></i> Buka PDF
                        </a>
                    </div>

                    <div class="alert alert-info small mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Ini pratinjau dengan data contoh, tidak tersimpan dan tidak memakai nomor urut sungguhan.
                    </div>

                    <div id="prevKosong" class="text-center text-muted py-4">
                        <i class="bi bi-file-earmark-text fs-2 d-block mb-2 opacity-50"></i>
                        Klik <strong>Tampilkan</strong> untuk melihat surat.
                    </div>
                    <iframe id="prevFrame" class="d-none w-100 border rounded bg-white" style="height: 780px;" title="Pratinjau surat"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (function () {
        const jenis = document.getElementById('prevJenis');
        const status = document.getElementById('prevStatus');
        const frame = document.getElementById('prevFrame');
        const kosong = document.getElementById('prevKosong');
        const unduh = document.getElementById('prevUnduh');
        const muat = document.getElementById('prevMuat');

        function tampil() {
            const url = '{{ route('signature.preview') }}?jenis=' + encodeURIComponent(jenis.value) +
                '&status=' + encodeURIComponent(status.value);

            kosong.classList.add('d-none');
            frame.classList.remove('d-none');
            frame.src = url;
            unduh.href = url;
            unduh.classList.remove('d-none');
        }

        muat.addEventListener('click', tampil);
        status.addEventListener('change', tampil);
    })();
</script>
</body>
</html>