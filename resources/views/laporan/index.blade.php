<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kehadiran &amp; Jam Kerja - Absensi-BBM</title>
    @include('partials.favicon')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ \App\Helpers\Asset::url('css/custom.css') }}">
    <style>
        /* Tampilan bergaya Excel: gridlines aktif + border pada seluruh sel */
        .excel-sheet {
            background: #fff;
            border: 1px solid #c9d3e0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .08);
        }
        .excel-sheet .table {
            margin-bottom: 0;
            font-size: 12.5px;
        }
        .excel-sheet .table > thead > tr > th {
            background: #1f4e79;
            color: #fff;
            font-weight: 600;
            text-align: center;
            vertical-align: middle;
            border: 1px solid #1f4e79;
            padding: 10px 8px;
            white-space: nowrap;
        }
        .excel-sheet .table > tbody > tr > td {
            border: 1px solid #b4c6e7;
            padding: 7px 8px;
            vertical-align: middle;
        }
        .excel-sheet .table > tbody > tr:nth-child(even) > td {
            background-color: #f4f8fd;
        }
        .excel-sheet .table > tfoot > tr > td {
            background: #d9e1f2;
            font-weight: 700;
            border: 1px solid #1f4e79;
            padding: 9px 8px;
        }
        .sel-kanan { text-align: right; }
        .sel-tengah { text-align: center; }
        .sel-kartu {
            background: #e2efda;
            border: 1px solid #a9d08e;
            color: #1d5c2c;
            font-weight: 600;
        }
        .sel-kartu-peringatan {
            background: #fff2cc;
            border: 1px solid #ffd966;
            color: #7f6000;
            font-weight: 600;
        }
        .sel-kartu-bahaya {
            background: #fce4e4;
            border: 1px solid #f4a6a6;
            color: #9c1c1c;
            font-weight: 600;
        }
        .kartu-judul { font-size: 17px; font-weight: 800; color: #1f4e79; }
        .kartu-periode { font-size: 12.5px; color: #44546a; }
        .sel-legenda {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11.5px;
            padding: 4px 10px;
            border-radius: 20px;
            border: 1px solid #d0d7e2;
            background: #fff;
        }
        .titik { width: 9px; height: 9px; border-radius: 50%; display: inline-block; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
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
                <li class="nav-item mt-3">
                    <small class="text-muted px-3 fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">PENGATURAN</small>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/leaves*') ? 'active' : '' }}" href="{{ route('admin.leaves.index') }}">
                        <i class="bi bi-envelope-paper me-2"></i> Izin &amp; Cuti
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
                @if(auth()->user()->isSuperadmin())
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/settings*') ? 'active' : '' }}" href="{{ url('/admin/settings') }}">
                        <i class="bi bi-clock-history me-2"></i> Set Jam Kerja
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/perusahaan') ? 'active' : '' }}" href="{{ route('company-profile.index') }}">
                        <i class="bi bi-building me-2"></i> Profil Perusahaan
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
                    <h4 class="fw-bold m-0 mb-1"><i class="bi bi-clipboard-data text-success me-2"></i>Laporan Kehadiran &amp; Jam Kerja</h4>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-file-earmark-spreadsheet text-muted me-2"></i>
                        <small class="text-muted">Rekap per karyawan &mdash; {{ $total_karyawan }} karyawan</small>
                    </div>
                </div>
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false" style="color: inherit;">
                        <div class="text-end me-3">
                            <p class="mb-0 fw-semibold small">{{ auth()->user()->name }}</p>
                            <small class="text-muted">{{ match(auth()->user()->role) { 'superadmin' => 'Superadmin', 'approval' => 'Approval', default => 'Admin' } }}</small>
                        </div>
                        <div class="avatar-circle bg-success text-white" style="width: 45px; height: 45px; font-size: 18px;">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" aria-labelledby="dropdownUser">
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="#" data-bs-toggle="modal" data-bs-target="#profileModal">
                                <i class="bi bi-person-circle me-2 text-primary"></i> Edit Profil &amp; Password
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST" class="m-0">
                                @csrf
                                <button type="submit" class="dropdown-item d-flex align-items-center py-2 text-danger">
                                    <i class="bi bi-box-arrow-right me-2"></i> Keluar
                                </button>
                            </form>
                        </li>
                    </ul>
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

            <!-- Filter periode & karyawan -->
            <div class="card-custom p-4 bg-white mb-4 fade-in">
                <h5 class="fw-bold mb-3"><i class="bi bi-funnel me-2 text-primary"></i>Filter Laporan</h5>
                <form action="{{ route('laporan.kehadiran') }}" method="GET" class="row g-3" id="formLaporan">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">
                            <i class="bi bi-calendar text-muted me-1"></i>Dari Tanggal
                        </label>
                        <input type="date" name="tanggal_mulai" class="form-control" value="{{ $mulai }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">
                            <i class="bi bi-calendar-check text-muted me-1"></i>Sampai Tanggal
                        </label>
                        <input type="date" name="tanggal_selesai" class="form-control" value="{{ $selesai }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">
                            <i class="bi bi-person-badge text-muted me-1"></i>Karyawan
                        </label>
                        <div class="dropdown w-100">
                            <button class="btn btn-outline-secondary w-100 text-start dropdown-toggle" type="button" id="karyawanDropdown" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside">
                                <span id="karyawanDropdownLabel">Semua Karyawan</span>
                            </button>
                            <div class="dropdown-menu w-100 p-2 shadow" style="max-height: 320px; overflow-y: auto;" aria-labelledby="karyawanDropdown">
                                <input type="search" class="form-control form-control-sm mb-2" id="karyawanSearch" placeholder="Cari nama atau PIN..." autocomplete="off">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="checkAllKaryawan" onchange="toggleSemua(this)">
                                    <label class="form-check-label fw-semibold cursor-pointer w-100" for="checkAllKaryawan">
                                        <i class="bi bi-check-all me-1"></i>Semua Karyawan
                                    </label>
                                </div>
                                <hr class="dropdown-divider">
                                <div class="d-flex flex-column gap-1" id="karyawanList">
                                    @forelse($karyawans as $k)
                                        <div class="form-check py-1 hover-bg-light rounded px-2 m-0 d-flex align-items-center karyawan-item" style="padding-left: 0.5rem !important;" data-search="{{ strtolower($k->nama . ' ' . $k->id_karyawan) }}">
                                            <input class="form-check-input karyawan-check m-0 me-2" type="checkbox" name="karyawan_id[]" value="{{ $k->id }}" id="karyawan_{{ $k->id }}" {{ in_array($k->id, $karyawanIds) ? 'checked' : '' }} onchange="updateKaryawanDropdown()">
                                            <label class="form-check-label cursor-pointer w-100 m-0 p-0 text-truncate" for="karyawan_{{ $k->id }}" style="line-height: 1.2;">
                                                {{ $k->nama }} <span class="text-muted small ms-1">({{ $k->id_karyawan }})</span>
                                            </label>
                                        </div>
                                    @empty
                                        <div class="text-muted small px-2 py-2 text-center">Belum ada data karyawan.</div>
                                    @endforelse
                                </div>
                                <div class="text-muted small text-center px-2 py-2 d-none" id="karyawanEmptyState">Nama tidak ditemukan.</div>
                            </div>
                        </div>
                        <div id="selectedKaryawanTags" class="mt-1 d-flex flex-wrap gap-1"></div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary fw-semibold w-100">
                            <i class="bi bi-search me-1"></i> Tampilkan
                        </button>
                    </div>
                    <div class="col-md-12 d-flex flex-wrap gap-2">
                        <button type="button" onclick="submitLaporan('{{ route('laporan.kehadiran.cetak') }}', '_blank')" class="btn btn-outline-success">
                            <i class="bi bi-printer me-1"></i> Cetak / PDF
                        </button>
                        <button type="button" onclick="submitLaporan('{{ route('laporan.kehadiran.excel') }}', '_self')" class="btn btn-success">
                            <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
                        </button>
                        <a href="{{ route('laporan.kehadiran') }}" class="btn btn-light border">
                            <i class="bi bi-arrow-clockwise me-1"></i> Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Kop laporan -->
            <div class="excel-sheet rounded-top p-3 mb-0 fade-in" style="background:#f8fbff;">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div>
                        <div class="kartu-judul">LAPORAN KEHADIRAN &amp; JAM KERJA KARYAWAN</div>
                        <div class="kartu-periode">{{ $companyProfile->nama() }}</div>
                    </div>
                    <div class="text-end">
                        <div class="kartu-periode">Periode: <strong>{{ $periode_label }}</strong></div>
                        <div class="kartu-periode">{{ \Carbon\Carbon::parse($mulai)->format('d/m/Y') }} &ndash; {{ \Carbon\Carbon::parse($selesai)->format('d/m/Y') }}</div>
                        <div class="kartu-periode">
                            {{ empty($karyawanIds) ? 'Semua Karyawan' : count($karyawanIds) . ' karyawan terpilih' }}
                        </div>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <span class="sel-legenda"><span class="titik" style="background:#70ad47;"></span> Kehadiran sempurna</span>
                    <span class="sel-legenda"><span class="titik" style="background:#ffc000;"></span> Terlambat / Izin / Lembur</span>
                    <span class="sel-legenda"><span class="titik" style="background:#e06666;"></span> Alpha / Tanpa keterangan</span>
                </div>
            </div>

            <!-- Tabel laporan -->
            <div class="excel-sheet rounded-bottom fade-in overflow-auto" style="max-height: 65vh;">
                <table class="table table-bordered mb-0 align-middle" id="tabelLaporan">
                    <thead class="sticky-top" style="z-index: 2;">
                        <tr>
                            <th style="width:40px;">No</th>
                            <th style="width:95px;">ID Karyawan</th>
                            <th>Nama Karyawan</th>
                            <th style="width:150px;">Jabatan / Divisi</th>
                            <th style="width:95px;">Total Hari Kerja (Hari)</th>
                            <th style="width:95px;">Total Jam Kerja (Jam)</th>
                            <th style="width:100px;">Rata-rata Jam/Hari</th>
                            <th style="width:165px;">Riport Masuk / Status Kehadiran</th>
                            <th style="width:230px;">Catatan Operasional</th>
                            <th style="width:90px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($baris as $index => $b)
                            <tr>
                                <td class="sel-tengah text-muted">{{ $index + 1 }}</td>
                                <td class="sel-tengah">
                                    <span class="badge bg-light text-dark border px-2 py-1" style="font-family: 'Courier New', monospace;">
                                        {{ $b['id_karyawan'] }}
                                    </span>
                                </td>
                                <td class="fw-semibold">{{ $b['nama'] }}</td>
                                <td class="text-muted small">{{ $b['jabatan'] }}</td>
                                <td class="sel-tengah" data-nilai="{{ $b['total_hari_kerja'] }}">{{ $b['total_hari_kerja'] }}</td>
                                <td class="sel-kanan" data-nilai="{{ $b['total_jam_kerja'] }}">{{ number_format($b['total_jam_kerja'], 1, ',', '') }}</td>
                                <td class="sel-kanan" data-nilai="{{ $b['rata_rata_jam'] }}">{{ number_format($b['rata_rata_jam'], 1, ',', '') }}</td>
                                <td class="sel-tengah">
                                    <span class="{{ $b['kategori'] == 'success' ? 'sel-kartu' : ($b['kategori'] == 'warning' ? 'sel-kartu-peringatan' : 'sel-kartu-bahaya') }} d-inline-block px-2 py-1" style="font-size:11.5px;">
                                        {{ $b['riport'] }}
                                    </span>
                                </td>
                                <td class="text-muted small">{{ $b['catatan'] }}</td>
                                <td class="sel-tengah">
                                    <a href="{{ route('laporan.kehadiran.detail', [
                                        'karyawan' => $b['karyawan']->id,
                                        'tanggal_mulai' => $mulai,
                                        'tanggal_selesai' => $selesai,
                                    ]) }}" class="btn btn-sm btn-outline-success w-100" title="Rincian harian {{ $b['nama'] }}">
                                        <i class="bi bi-list-ul me-1"></i> Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5">
                                    <i class="bi bi-inbox fs-1 d-block mb-3 text-secondary opacity-50"></i>
                                    <p class="text-muted mb-1">Tidak ada data kehadiran pada periode ini.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="sel-tengah" colspan="4">TOTAL / RATA-RATA</td>
                            <td class="sel-tengah" id="totalHari">{{ $ringkasan['total_hari_kerja'] }}</td>
                            <td class="sel-kanan" id="totalJam">{{ number_format($ringkasan['total_jam_kerja'], 1, ',', '') }}</td>
                            <td class="sel-kanan" id="rataRataJam">{{ number_format($ringkasan['rata_rata_jam'], 1, ',', '') }}</td>
                            <td class="sel-tengah text-muted">Rata-rata</td>
                            <td class="text-muted small" id="ringkasanCatatan">
                                {{ $total_karyawan }} karyawan
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Kartu ringkasan -->
            <div class="row g-3 mt-1 fade-in">
                <div class="col-md-3">
                    <div class="card-custom p-3 bg-white h-100">
                        <small class="text-muted">Total Hari Kerja</small>
                        <div class="fs-4 fw-bold text-success">{{ $ringkasan['total_hari_kerja'] }} <small class="fs-6 text-muted">hari</small></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card-custom p-3 bg-white h-100">
                        <small class="text-muted">Total Jam Kerja</small>
                        <div class="fs-4 fw-bold text-success">{{ number_format($ringkasan['total_jam_kerja'], 1, ',', '') }} <small class="fs-6 text-muted">jam</small></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card-custom p-3 bg-white h-100">
                        <small class="text-muted">Rata-rata Jam / Hari</small>
                        <div class="fs-4 fw-bold text-success">{{ number_format($ringkasan['rata_rata_jam'], 1, ',', '') }} <small class="fs-6 text-muted">jam</small></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card-custom p-3 bg-white h-100">
                        <small class="text-muted">Total Keterlambatan</small>
                        <div class="fs-4 fw-bold text-warning">{{ $ringkasan['jumlah_terlambat'] }} <small class="fs-6 text-muted">kejadian</small></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function getKaryawanChecks() {
        return Array.from(document.querySelectorAll('.karyawan-check'));
    }

    function updateKaryawanDropdown() {
        const checks = getKaryawanChecks();
        const total = checks.length;
        const selected = checks.filter(c => c.checked);
        const checkAll = document.getElementById('checkAllKaryawan');
        const label = document.getElementById('karyawanDropdownLabel');
        const tags = document.getElementById('selectedKaryawanTags');

        if (checkAll) {
            checkAll.checked = total > 0 && selected.length === total;
            checkAll.indeterminate = selected.length > 0 && selected.length < total;
        }

        if (label) {
            if (selected.length === 0) {
                label.textContent = 'Semua Karyawan';
            } else if (selected.length === total) {
                label.textContent = 'Semua Karyawan (' + total + ')';
            } else {
                label.textContent = 'Terpilih ' + selected.length + ' karyawan';
            }
        }

        if (tags) {
            tags.innerHTML = '';
            selected.forEach(c => {
                const labelEl = document.querySelector('label[for="' + c.id + '"]');
                const nama = labelEl ? labelEl.textContent.trim() : c.value;
                const badge = document.createElement('span');
                badge.className = 'badge bg-success-subtle text-success border border-success-subtle';
                badge.textContent = nama;
                tags.appendChild(badge);
            });
        }
    }

    function toggleSemua(checkbox) {
        const wasCheckedAll = checkbox.checked;
        getKaryawanChecks().forEach(c => {
            c.checked = wasCheckedAll;
        });
        updateKaryawanDropdown();
    }

    function filterKaryawan() {
        const keyword = document.getElementById('karyawanSearch').value.trim().toLowerCase();
        let visible = 0;
        document.querySelectorAll('.karyawan-item').forEach(item => {
            const cocok = keyword === '' || item.dataset.search.includes(keyword);
            item.classList.toggle('d-none', !cocok);
            if (cocok) visible++;
        });
        document.getElementById('karyawanEmptyState').classList.toggle('d-none', visible > 0);
    }

    function submitLaporan(url, target) {
        const form = document.getElementById('formLaporan');
        form.action = url;
        form.target = target;
        form.submit();
    }

    /**
     * Baris ringkasan dihitung ulang di sisi klien (setara formula SUM / AVERAGE di Excel).
     */
    function hitungRingkasan() {
        const baris = Array.from(document.querySelectorAll('#tabelLaporan tbody tr'))
            .filter(tr => tr.querySelector('td[data-nilai]'));

        if (baris.length === 0) {
            return;
        }

        let totalHari = 0;
        let totalJam = 0;
        let jumlahRataRata = 0;

        baris.forEach(tr => {
            const sel = tr.querySelectorAll('td[data-nilai]');
            const hari = parseFloat(sel[0].dataset.nilai) || 0;
            const jam = parseFloat(sel[1].dataset.nilai) || 0;
            totalHari += hari;
            totalJam += jam;
            if (hari > 0) jumlahRataRata++;
        });

        const fmt = v => v.toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
        const rataRata = jumlahRataRata > 0 ? totalJam / jumlahRataRata : 0;

        document.getElementById('totalHari').textContent = totalHari;
        document.getElementById('totalJam').textContent = fmt(totalJam);
        document.getElementById('rataRataJam').textContent = fmt(rataRata);
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (document.getElementById('karyawanSearch')) {
            document.getElementById('karyawanSearch').addEventListener('input', filterKaryawan);
        }
        updateKaryawanDropdown();
        hitungRingkasan();
    });
</script>

<!-- Modal Edit Profil -->
<div class="modal fade" id="profileModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-circle me-2"></i>Edit Profil &amp; Password</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('profile.update') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Nama</label>
                        <input type="text" name="name" class="form-control" value="{{ auth()->user()->name }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Email</label>
                        <input type="email" name="email" class="form-control" value="{{ auth()->user()->email }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Password Baru <small class="text-muted">(kosongkan jika tidak diubah)</small></label>
                        <input type="password" name="password" class="form-control">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
