<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Absensi-BBM</title>
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
            <div class="col-md-2 sidebar p-3 d-none d-md-block">
                <div class="d-flex align-items-center mb-4 px-2 py-3">
                    <div class="stat-icon bg-success text-white me-2" style="width: 40px; height: 40px;">
                        <i class="bi bi-fingerprint"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold m-0 text-success" style="font-size: 18px;">Absensi-BBM</h5>
                        <small class="text-muted" style="font-size: 10px;">Attendance System</small>
                    </div>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}"
                            href="{{ url('/dashboard') }}">
                            <i class="bi bi-grid me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('karyawan*') ? 'active' : '' }}"
                            href="{{ url('/karyawan') }}">
                            <i class="bi bi-people me-2"></i> Karyawan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('absensi*') ? 'active' : '' }}"
                            href="{{ url('/absensi') }}">
                            <i class="bi bi-calendar-check me-2"></i> Absensi
                        </a>
                    </li>
                    @if(auth()->user()->isApprover())
                        <li class="nav-item mt-3">
                            <small class="text-muted px-3 fw-semibold"
                                style="font-size: 11px; letter-spacing: 0.5px;">PENGATURAN</small>
                        </li>
                                        <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/leaves*') ? 'active' : '' }}" href="{{ route('admin.leaves.index') }}">
                        <i class="bi bi-envelope-paper me-2"></i> Izin & Cuti
                    </a>
                </li>
                @if(auth()->user()->isSuperadmin())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('admin/settings*') ? 'active' : '' }}"
                                href="{{ url('/admin/settings') }}">
                                <i class="bi bi-clock-history me-2"></i> Set Jam Kerja
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('pengaturan*') ? 'active' : '' }}"
                                href="{{ url('/pengaturan') }}">
                                <i class="bi bi-gear me-2"></i> Kontrol Mesin
                            </a>
                        </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/maintenance*') ? 'active' : '' }}" href="{{ route('admin.maintenance.index') }}">
                        <i class="bi bi-database-fill-gear me-2"></i> Maintenance DB
                    </a>
                </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('admin/users*') ? 'active' : '' }}"
                                href="{{ url('/admin/users') }}">
                                <i class="bi bi-person-gear me-2"></i> Manajemen User
                            </a>
                        </li>
                        @endif
@if(auth()->user()->isTrueApprover())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('admin/audit-logs*') ? 'active' : '' }}"
                                href="{{ url('/admin/audit-logs') }}">
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
                        <h4 class="fw-bold m-0 mb-1">Dashboard Ringkasan</h4>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-calendar3 text-muted me-2"></i>
                            <small class="text-muted">{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</small>
                        </div>
                    </div>
                                        <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false" style="color: inherit;">
                            <div class="text-end me-3">
                                <p class="mb-0 fw-semibold small">{{ auth()->user()->name }}</p>
                                <small
                                    class="text-muted">{{ match(auth()->user()->role) { 'superadmin' => 'Superadmin', 'approval' => 'Approval', default => 'Admin' } }}</small>
                            </div>
                            <div class="avatar-circle bg-success text-white"
                                style="width: 45px; height: 45px; font-size: 18px;">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" aria-labelledby="dropdownUser">
                            <li>
                                <a class="dropdown-item d-flex align-items-center py-2" href="#" data-bs-toggle="modal" data-bs-target="#profileModal">
                                    <i class="bi bi-person-circle me-2 text-primary"></i> Edit Profil & Password
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

                <div class="row g-3 mb-4 fade-in">
                    <div class="col-md-3">
                        <div class="card card-custom stat-card p-3 bg-white">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-muted small mb-1 fw-semibold">Total Karyawan</p>
                                    <h3 class="fw-bold mb-0">{{ $totalKaryawan }}</h3>
                                </div>
                                <div class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-people"></i></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-custom stat-card p-3 bg-white">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-muted small mb-1 fw-semibold">Hadir Hari Ini</p>
                                    <h3 class="fw-bold mb-0 text-success hadir-counter">{{ $hadirHariIni }}</h3>
                                </div>
                                <div class="stat-icon bg-success-subtle text-success"><i class="bi bi-check-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-custom stat-card p-3 bg-white">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-muted small mb-1 fw-semibold">Terlambat</p>
                                    <h3 class="fw-bold mb-0 text-warning terlambat-counter">{{ $terlambat }}</h3>
                                </div>
                                <div class="stat-icon bg-warning-subtle text-warning"><i
                                        class="bi bi-exclamation-circle"></i></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-custom stat-card p-3 bg-white">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-muted small mb-1 fw-semibold">Tidak Hadir (Alpha)</p>
                                    <h3 class="fw-bold mb-0 text-danger alpha-counter">{{ $tidakHadir }}</h3>
                                </div>
                                <div class="stat-icon bg-danger-subtle text-danger"><i class="bi bi-x-circle"></i></div>
                            </div>
                        </div>
                    </div>
                </div>

                @if($machineStatus)
                    <div class="row mb-4 fade-in">
                        <div class="col-md-12">
                            <div class="card card-custom p-3 bg-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <div id="machine-status-indicator" class="me-3">
                                            <span
                                                class="badge badge-status {{ $machineStatus->isOnline() ? 'bg-success' : 'bg-danger' }}">
                                                <i class="bi bi-circle-fill me-2 {{ $machineStatus->isOnline() ? 'pulse-animation' : '' }}"
                                                    style="font-size: 8px;"></i>
                                                <span
                                                    id="machine-status-text">{{ $machineStatus->isOnline() ? 'Online' : 'Offline' }}</span>
                                            </span>
                                        </div>
                                        <div>
                                            <p class="mb-0 fw-bold">Status Mesin Absensi</p>
                                            <small class="text-muted" id="machine-last-ping">
                                                Terakhir ping:
                                                {{ $machineStatus->last_ping ? $machineStatus->last_ping->diffForHumans() : 'Belum pernah' }}
                                            </small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted d-block"><i class="bi bi-hdd-network me-1"></i>IP:
                                            {{ $machineStatus->machine_ip ?? '-' }}</small>
                                        <small class="text-muted"><i class="bi bi-speedometer2 me-1"></i>Response: <span
                                                id="machine-response-time">{{ $machineStatus->response_time ?? 0 }}</span>ms</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="row g-4 fade-in">
                    <div class="col-md-4">
                        <div class="card card-custom p-4 bg-white h-100">
                            <h5 class="fw-bold mb-3"><i class="bi bi-pie-chart me-2 text-success"></i>Grafik Kehadiran
                                Hari Ini</h5>
                            <div class="chart-container d-flex justify-content-center align-items-center"
                                style="min-height: 200px;">
                                <canvas id="donutChart" width="200" height="200"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="card card-custom p-4 bg-white h-100">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold m-0"><i class="bi bi-clock-history me-2 text-success"></i>Absensi
                                    Terbaru</h5>
                                <a href="{{ url('/absensi') }}" class="btn btn-sm btn-outline-success">
                                    <i class="bi bi-arrow-right-circle me-1"></i>Lihat Semua
                                </a>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover table-borderless align-middle">
                                    <thead>
                                        <tr class="text-muted small border-bottom">
                                            <th class="fw-bold">NAMA</th>
                                            <th class="fw-bold">JAM MASUK</th>
                                            <th class="fw-bold">STATUS</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($absensiTerbaru as $absen)
                                            <tr class="border-bottom">
                                                <td>
                                                    <div class="d-flex align-items-center py-1">
                                                        <div class="avatar-circle text-success fw-bold me-2 small">
                                                            {{ strtoupper(substr($absen->karyawan->nama, 0, 1)) }}
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold small">{{ $absen->karyawan->nama }}</div>
                                                            <small
                                                                class="text-muted extra-small">{{ $absen->karyawan->jabatan ?? 'Staf' }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="small fw-semibold">
                                                    {{ \Carbon\Carbon::parse($absen->jam_masuk)->format('H:i') }} WIB
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge {{ $absen->status == 'Hadir' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }} px-3 py-2 small fw-semibold">
                                                        {{ $absen->status }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center text-muted py-5">
                                                    <i class="bi bi-inbox fs-1 d-block mb-3 text-secondary opacity-50"></i>
                                                    <p class="mb-0 fw-semibold">Belum ada absensi hari ini</p>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mt-1 fade-in">
                    <div class="col-md-6">
                        <div class="card card-custom p-4 bg-white">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold m-0"><i class="bi bi-graph-up me-2 text-primary"></i>Tren Kehadiran
                                    Mingguan</h5>
                                <span class="badge bg-light text-dark fw-semibold">7 Hari Terakhir</span>
                            </div>
                            <div class="chart-container" style="height: 300px;">
                                <canvas id="weeklyTrendChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card card-custom p-4 bg-white">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold m-0"><i class="bi bi-calendar3 me-2 text-primary"></i>Tren Kehadiran
                                    Bulanan</h5>
                                <span class="badge bg-light text-dark fw-semibold">30 Hari Terakhir</span>
                            </div>
                            <div class="chart-container" style="height: 300px;">
                                <canvas id="monthlyTrendChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @if(env('BROADCAST_DRIVER') === 'reverb' || env('BROADCAST_DRIVER') === 'pusher')
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script>
        // Konfigurasi Pusher untuk real-time updates
        const pusher = new Pusher('{{ env('PUSHER_APP_KEY') }}', {
            cluster: '{{ env('PUSHER_APP_CLUSTER') }}',
            wsHost: '{{ env('PUSHER_HOST') }}',
            wsPort: {{ env('PUSHER_PORT') }},
            forceTLS: {{ env('PUSHER_SCHEME') === 'https' ? 'true' : 'false' }},
            disableStats: true,
            enabledTransports: ['ws', 'wss']
          });

        // Subscribe ke channel attendance untuk real-time absensi
        const attendanceChannel = pusher.subscribe('attendance');
        attendanceChannel.bind('attendance.recorded', function (data) {
            console.log('New attendance recorded:', data);

            // Update counter
            updateDashboardCounters();

            // Tambahkan row baru ke tabel absensi terbaru
            addNewAttendanceRow(data);

            // Tampilkan notifikasi
            showNotification('Absensi Baru', `${data.karyawan_nama} telah melakukan absensi`);
          });

        // Subscribe ke channel machine-status untuk status mesin
        const machineChannel = pusher.subscribe('machine-status');
        machineChannel.bind('machine.status.changed', function (data) {
            console.log('Machine status changed:', data);
            updateMachineStatus(data);
          });

        function updateDashboardCounters() {
            // Reload halaman atau fetch data baru via AJAX
            fetch('{{ url('/dashboard/stats') }}')
                .then(response => response.json())
                .then(data => {
                    // Update counters tanpa reload halaman
                    document.querySelector('.hadir-counter').textContent = data.hadirHariIni;
                    document.querySelector('.terlambat-counter').textContent = data.terlambat;
                    document.querySelector('.alpha-counter').textContent = data.tidakHadir;
                })
                .catch(error => console.error('Error fetching stats:', error));
        }

        function addNewAttendanceRow(data) {
            const tbody = document.querySelector('table tbody');
            const emptyRow = tbody.querySelector('td[colspan="3"]');

            if (emptyRow) {
                emptyRow.parentElement.remove();
            }

            const newRow = document.createElement('tr');
            newRow.className = 'border-bottom new-attendance-highlight';

            const jamMasuk = new Date('2000-01-01 ' + data.jam_masuk).toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit'
            });

            const badgeClass = data.status === 'Hadir' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning';

            newRow.innerHTML = `
            <td>
                <div class="d-flex align-items-center py-1">
                    <div class="avatar-circle bg-light text-success fw-bold me-2 small">
                        ${data.karyawan_nama.charAt(0).toUpperCase()}
                    </div>
                    <div>
                        <div class="fw-bold small">${data.karyawan_nama}</div>
                        <small class="text-muted extra-small">${data.karyawan_jabatan}</small>
                    </div>
                </div>
            </td>
            <td class="small">${jamMasuk} WIB</td>
            <td>
                <span class="badge ${badgeClass} px-2 py-1.5 small">
                    ${data.status}
                </span>
            </td>
        `;

            tbody.insertBefore(newRow, tbody.firstChild);

            // Highlight animation
            setTimeout(() => {
                newRow.classList.remove('new-attendance-highlight');
            }, 3000);

            // Keep only last 5 rows
            const rows = tbody.querySelectorAll('tr');
            if (rows.length > 5) {
                rows[rows.length - 1].remove();
            }
        }

        function showNotification(title, message) {
            // Simple browser notification (bisa diganti dengan toast library)
            if ('Notification' in window && Notification.permission === 'granted') {
                new Notification(title, { body: message });
            }
        }

        function updateMachineStatus(data) {
            // Update status mesin di dashboard
            console.log('Machine status:', data.status);

            const statusIndicator = document.getElementById('machine-status-indicator');
            const statusText = document.getElementById('machine-status-text');
            const lastPing = document.getElementById('machine-last-ping');
            const responseTime = document.getElementById('machine-response-time');

            if (statusIndicator && statusText) {
                const badge = statusIndicator.querySelector('.badge');

                if (data.status === 'online') {
                    badge.className = 'badge bg-success px-3 py-2';
                    statusText.textContent = 'Online';
                } else {
                    badge.className = 'badge bg-danger px-3 py-2';
                    statusText.textContent = 'Offline';
                }

                // Update last ping time
                if (lastPing) {
                    lastPing.textContent = 'Terakhir ping: baru saja';
                }

                // Update response time
                if (responseTime && data.response_time) {
                    responseTime.textContent = data.response_time;
                }

                // Show notification for status change
                showNotification(
                    'Status Mesin Berubah',
                    `Mesin absensi sekarang ${data.status === 'online' ? 'Online' : 'Offline'}`
                );
            }
        }

        // Request notification permission
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
    </script>
    @endif

    <script>
        // Donut Chart - Kehadiran Hari Ini
        const ctxDonut = document.getElementById('donutChart');
        if (ctxDonut) {
            new Chart(ctxDonut, {
                type: 'doughnut',
                data: {
                    labels: ['Hadir', 'Terlambat', 'Tidak Hadir'],
                    datasets: [{
                        data: [{{ $hadirHariIni }}, {{ $terlambat }}, {{ $tidakHadir }}],
                        backgroundColor: ['#2e7d32', '#f57c00', '#d32f2f'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                font: {
                                    size: 12
                                }
                            }
                        }
                    }
                }
            });
        }

        // Weekly Trend Chart - 7 Hari Terakhir
        const ctxWeekly = document.getElementById('weeklyTrendChart');
        if (ctxWeekly) {
            new Chart(ctxWeekly, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($trendMingguan['labels']) !!},
                    datasets: [
                        {
                            label: 'Hadir',
                            data: {!! json_encode($trendMingguan['hadir']) !!},
                            backgroundColor: '#2e7d32',
                            borderRadius: 6,
                            barThickness: 20
                        },
                        {
                            label: 'Terlambat',
                            data: {!! json_encode($trendMingguan['terlambat']) !!},
                            backgroundColor: '#f57c00',
                            borderRadius: 6,
                            barThickness: 20
                        },
                        {
                            label: 'Alpha',
                            data: {!! json_encode($trendMingguan['alpha']) !!},
                            backgroundColor: '#d32f2f',
                            borderRadius: 6,
                            barThickness: 20
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            stacked: false,
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            stacked: false,
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            },
                            grid: {
                                color: '#f0f0f0'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            align: 'end',
                            labels: {
                                boxWidth: 12,
                                boxHeight: 12,
                                padding: 15,
                                font: {
                                    size: 11
                                }
                            }
                        }
                    }
                }
            });
        }

        // Monthly Trend Chart - 30 Hari Terakhir
        const ctxMonthly = document.getElementById('monthlyTrendChart');
        if (ctxMonthly) {
            new Chart(ctxMonthly, {
                type: 'line',
                data: {
                    labels: {!! json_encode($trendBulanan['labels']) !!},
                    datasets: [
                        {
                            label: 'Hadir',
                            data: {!! json_encode($trendBulanan['hadir']) !!},
                            borderColor: '#2e7d32',
                            backgroundColor: 'rgba(46, 125, 50, 0.1)',
                            fill: true,
                            tension: 0.4,
                            borderWidth: 2,
                            pointRadius: 3,
                            pointHoverRadius: 5
                        },
                        {
                            label: 'Terlambat',
                            data: {!! json_encode($trendBulanan['terlambat']) !!},
                            borderColor: '#f57c00',
                            backgroundColor: 'rgba(245, 124, 0, 0.1)',
                            fill: true,
                            tension: 0.4,
                            borderWidth: 2,
                            pointRadius: 3,
                            pointHoverRadius: 5
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45,
                                font: {
                                    size: 9
                                }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            },
                            grid: {
                                color: '#f0f0f0'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            align: 'end',
                            labels: {
                                boxWidth: 12,
                                boxHeight: 12,
                                padding: 15,
                                font: {
                                    size: 11
                                }
                            }
                        }
                    }
                }
            });
        }
    </script>

<!-- Modal Edit Profil -->
<div class="modal fade" id="profileModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-circle me-2"></i>Edit Profil & Password</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('profile.update') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Lengkap</label>
                        <input type="text" name="name" class="form-control" value="{{ auth()->user()->name }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Username</label>
                        <input type="text" name="username" class="form-control" value="{{ auth()->user()->username }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="email" class="form-control" value="{{ auth()->user()->email }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Password Baru <small class="text-muted fw-normal">(Opsional)</small></label>
                        <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin mengubah password">
                        <small class="text-muted">Minimal 6 karakter jika ingin diubah.</small>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

