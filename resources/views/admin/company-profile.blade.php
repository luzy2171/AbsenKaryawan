<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Perusahaan - Absensi-BBM</title>
    @include('partials.favicon')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ \App\Helpers\Asset::url('css/custom.css') }}">
    <style>
        .logo-preview {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            object-fit: contain;
            object-position: center;
            background: #fff;
            border: 2px solid var(--gray-200);
            padding: 6px;
            box-shadow: var(--shadow-md);
        }
        .logo-preview-fallback {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 44px;
        }
        .kop-preview {
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            background: #fff;
            overflow: hidden;
        }
    </style>
</head>

<body>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 sidebar p-3">
            @include('partials.brand')
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/dashboard') }}">
                        <i class="bi bi-grid me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/karyawan') }}">
                        <i class="bi bi-people me-2"></i> Karyawan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/absensi') }}">
                        <i class="bi bi-calendar-check me-2"></i> Absensi
                    </a>
                </li>
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
                @if(auth()->user()->isSuperadmin())
                <li class="nav-item mt-3">
                    <small class="text-muted px-3 fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">PENGATURAN</small>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/leaves*') ? 'active' : '' }}" href="{{ route('admin.leaves.index') }}">
                        <i class="bi bi-envelope-paper me-2"></i> Izin & Cuti
                    </a>
                </li>
                @if(auth()->user()->isTrueApprover())
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/cuti-control*') ? 'active' : '' }}" href="{{ route('admin.cuti.control') }}">
                        <i class="bi bi-sliders me-2"></i> Kontrol Cuti
                    </a>
                </li>
                @endif
                @if(auth()->user()->isTrueApprover())
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/tanda-tangan*') ? 'active' : '' }}" href="{{ route('signature.edit') }}">
                        <i class="bi bi-pen me-2"></i> Tanda Tangan
                    </a>
                </li>
                @endif
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/settings') ? 'active' : '' }}" href="{{ url('/admin/settings') }}">
                        <i class="bi bi-clock-history me-2"></i> Set Jam Kerja
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="{{ route('company-profile.index') }}">
                        <i class="bi bi-building me-2"></i> Profil Perusahaan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/pengaturan') }}">
                        <i class="bi bi-gear me-2"></i> Kontrol Mesin
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/maintenance*') ? 'active' : '' }}" href="{{ route('admin.maintenance.index') }}">
                        <i class="bi bi-database-fill-gear me-2"></i> Maintenance DB
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/admin/users') }}">
                        <i class="bi bi-person-gear me-2"></i> Manajemen User
                    </a>
                </li>
                @endif
                @if(auth()->user()->isTrueApprover())
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/admin/audit-logs') }}">
                        <i class="bi bi-journal-text me-2"></i> Audit Logs
                    </a>
                </li>
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
                    <h4 class="fw-bold m-0 mb-1"><i class="bi bi-building text-success me-2"></i>Profil Perusahaan</h4>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-info-circle text-muted me-2"></i>
                        <small class="text-muted">Logo dan nama PT ini otomatis dipakai di sidebar, halaman login, dan seluruh kop laporan PDF</small>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <a href="{{ route('company-profile.kop') }}" target="_blank"
                       class="btn btn-outline-success me-3">
                        <i class="bi bi-file-earmark-ruled me-1"></i> Lihat Kop A4
                    </a>
                    <div class="text-end me-3">
                        <p class="mb-0 fw-semibold small">{{ auth()->user()->name }}</p>
                        <small class="text-muted">Superadmin</small>
                    </div>
                    <div class="avatar-circle text-success">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4 fade-in" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i><strong>Berhasil!</strong> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><strong>Perhatian!</strong> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><strong>Gagal menyimpan!</strong>
                    <ul class="mb-0 mt-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form action="{{ route('company-profile.update') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row fade-in">
                    <div class="col-md-7">
                        <div class="card-custom p-4 bg-white mb-3">
                            <h5 class="fw-bold mb-1"><i class="bi bi-image me-2 text-success"></i>Logo Perusahaan</h5>
                            <p class="text-muted small mb-4">Logo ditampilkan bulat di samping nama PT pada sidebar dan di kop laporan</p>

                            <div class="d-flex align-items-center gap-4 mb-4">
                                @if($profil['logo'])
                                    <img src="{{ $companyProfile->logoUrl() }}" alt="Logo {{ $companyProfile->nama() }}" class="logo-preview" id="logoPreview">
                                @else
                                    <div class="logo-preview-fallback stat-icon bg-success text-white" id="logoPreviewFallback">
                                        <i class="bi bi-fingerprint"></i>
                                    </div>
                                @endif
                                <div class="flex-grow-1">
                                    <input type="file" name="logo" id="logoInput" class="form-control" accept="image/png,image/jpeg,image/webp,image/svg+xml">
                                    <small class="text-muted d-block mt-2">
                                        <i class="bi bi-info-circle me-1"></i>Format PNG / JPG / WEBP / SVG, maksimal 2 MB. Logo transparan disarankan.
                                    </small>
                                    @if($profil['logo'])
                                        <button type="submit" class="btn btn-sm btn-outline-danger mt-3"
                                                form="deleteLogoForm"
                                                onclick="return confirm('Hapus logo perusahaan dan kembali ke logo bawaan?')">
                                            <i class="bi bi-trash me-1"></i> Hapus Logo
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="card-custom p-4 bg-white">
                            <h5 class="fw-bold mb-1"><i class="bi bi-building me-2 text-success"></i>Identitas Perusahaan</h5>
                            <p class="text-muted small mb-4">Nama PT dan subjudul yang tampil di sidebar serta login</p>

                            <div class="setting-item">
                                <label class="form-label fw-bold mb-2" for="nama">
                                    <i class="bi bi-patch-question text-success me-2"></i>Nama PT / Perusahaan
                                </label>
                                <input type="text" name="nama" id="nama" class="form-control"
                                       value="{{ old('nama', $profil['nama']) }}" maxlength="191" required>
                            </div>

                            <div class="setting-item">
                                <label class="form-label fw-bold mb-2" for="subtitle">
                                    <i class="bi bi-card-text text-success me-2"></i>Subjudul Sidebar
                                </label>
                                <input type="text" name="subtitle" id="subtitle" class="form-control"
                                       value="{{ old('subtitle', $profil['subtitle']) }}" maxlength="191">
                                <small class="text-muted mt-2 d-block">
                                    <i class="bi bi-info-circle me-1"></i>Teks kecil di bawah nama PT, mis. "Attendance System"
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <div class="card-custom p-4 bg-white mb-3">
                            <h5 class="fw-bold mb-1"><i class="bi bi-file-earmark-text me-2 text-primary"></i>Data Kop Laporan</h5>
                            <p class="text-muted small mb-4">Dicetak pada header semua laporan PDF</p>

                            <div class="setting-item">
                                <label class="form-label fw-bold mb-2" for="alamat">
                                    <i class="bi bi-geo-alt text-primary me-2"></i>Alamat
                                </label>
                                <textarea name="alamat" id="alamat" rows="3" class="form-control" maxlength="500">{{ old('alamat', $profil['alamat']) }}</textarea>
                            </div>

                            <div class="setting-item">
                                <label class="form-label fw-bold mb-2" for="telepon">
                                    <i class="bi bi-telephone text-primary me-2"></i>Telepon
                                </label>
                                <input type="text" name="telepon" id="telepon" class="form-control"
                                       value="{{ old('telepon', $profil['telepon']) }}" maxlength="100">
                            </div>

                            <div class="setting-item">
                                <label class="form-label fw-bold mb-2" for="email">
                                    <i class="bi bi-envelope text-primary me-2"></i>Email
                                </label>
                                <input type="email" name="email" id="email" class="form-control"
                                       value="{{ old('email', $profil['email']) }}" maxlength="191">
                            </div>

                            <div class="setting-item">
                                <label class="form-label fw-bold mb-2" for="website">
                                    <i class="bi bi-globe text-primary me-2"></i>Website
                                </label>
                                <input type="text" name="website" id="website" class="form-control"
                                       value="{{ old('website', $profil['website']) }}" maxlength="191">
                            </div>
                        </div>

                        <div class="card-custom p-4 bg-white">
                            <h5 class="fw-bold mb-1"><i class="bi bi-pen me-2 text-warning"></i>Penanda Tangan Laporan</h5>
                            <p class="text-muted small mb-4">Kosongkan nama bila ingin memakai nama pengguna yang sedang login</p>

                            <div class="setting-item">
                                <label class="form-label fw-bold mb-2" for="jabatan_ttd">
                                    <i class="bi bi-briefcase text-warning me-2"></i>Jabatan
                                </label>
                                <input type="text" name="jabatan_ttd" id="jabatan_ttd" class="form-control"
                                       value="{{ old('jabatan_ttd', $profil['jabatan_ttd']) }}" maxlength="191">
                            </div>

                            <div class="setting-item">
                                <label class="form-label fw-bold mb-2" for="nama_ttd">
                                    <i class="bi bi-person text-warning me-2"></i>Nama Penanda Tangan
                                </label>
                                <input type="text" name="nama_ttd" id="nama_ttd" class="form-control"
                                       value="{{ old('nama_ttd', $profil['nama_ttd']) }}" maxlength="191">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-1 fade-in">
                    <div class="col-md-7">
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-lg py-3">
                                <i class="bi bi-check-circle me-2"></i> Simpan Profil Perusahaan
                            </button>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="card-custom p-3 bg-white">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-bold mb-0"><i class="bi bi-eye text-success me-2"></i>Pratinjau Kop</h6>
                                <a href="{{ route('company-profile.kop') }}" target="_blank" class="btn btn-sm btn-outline-success">
                                    <i class="bi bi-arrows-fullscreen me-1"></i> A4 Penuh
                                </a>
                            </div>
                            {{-- Pratinjau memakai partial kop yang sama persis dengan template
                                 cetak PDF, jadi apa yang terlihat di sini = hasil cetak. --}}
                            <div class="kop-preview p-3">
                                @include('partials.kop', [
                                    'kopJudul' => 'LAPORAN',
                                    'kopKanan' => [
                                        'Periode: <strong>—</strong>',
                                        'Dicetak: ' . date('d/m/Y H:i'),
                                    ],
                                    'kopPratinjau' => true,
                                ])
                            </div>
                            <small class="text-muted d-block mt-2">
                                <i class="bi bi-info-circle me-1"></i>Pratinjau diperbarui otomatis saat mengetik di atas.
                            </small>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<form id="deleteLogoForm" action="{{ route('company-profile.logo.destroy') }}" method="POST" class="d-none">
    @csrf
    @method('DELETE')
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (function () {
        var logoInput = document.getElementById('logoInput');
        var preview = document.getElementById('logoPreview');
        var fallback = document.getElementById('logoPreviewFallback');

        if (logoInput) {
            logoInput.addEventListener('change', function () {
                var file = this.files && this.files[0];
                if (!file) {
                    return;
                }

                var url = URL.createObjectURL(file);
                if (preview) {
                    preview.src = url;
                } else if (fallback) {
                    var img = document.createElement('img');
                    img.id = 'logoPreview';
                    img.className = 'logo-preview';
                    img.alt = 'Pratinjau logo';
                    img.src = url;
                    fallback.parentNode.replaceChild(img, fallback);
                }
            });
        }

        function livePreview() {
            var nama = document.getElementById('nama');
            var alamat = document.getElementById('alamat');
            var telepon = document.getElementById('telepon');
            var email = document.getElementById('email');
            var website = document.getElementById('website');

            // Pratinjau memakai partial kop yang sama dengan hasil cetak.
            var outNama = document.querySelector('.kop-surat__nama');
            var barisKontak = document.querySelectorAll('.kop-surat__kontak');

            if (nama && outNama) {
                outNama.textContent = nama.value || '-';
            }

            // baris 1 = alamat, baris 2 = telepon | email | website
            if (barisKontak[0]) {
                barisKontak[0].textContent = alamat ? alamat.value : '';
                barisKontak[0].style.display = (alamat && alamat.value) ? '' : 'none';
            }
            if (barisKontak[1]) {
                var parts = [];
                if (telepon && telepon.value) { parts.push('Telp. ' + telepon.value); }
                if (email && email.value) { parts.push(email.value); }
                if (website && website.value) { parts.push(website.value); }
                barisKontak[1].textContent = parts.join(' | ');
                barisKontak[1].style.display = parts.length ? '' : 'none';
            }
        }

        ['nama', 'alamat', 'telepon', 'email', 'website'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) {
                el.addEventListener('input', livePreview);
            }
        });
    })();
</script>
</body>

</html>
