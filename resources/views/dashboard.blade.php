<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Absensi-BBM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #2e7d32;
            --primary-light: #4caf50;
            --primary-dark: #1b5e20;
            --success-color: #2e7d32;
            --warning-color: #f57c00;
            --danger-color: #d32f2f;
            --info-color: #0288d1;
            --gray-50: #fafafa;
            --gray-100: #f5f5f5;
            --gray-200: #eeeeee;
            --gray-300: #e0e0e0;
            --gray-600: #757575;
            --gray-800: #424242;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        .sidebar {
            height: 100vh;
            background: linear-gradient(180deg, #ffffff 0%, #fafafa 100%);
            border-right: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
        }

        .nav-link {
            color: var(--gray-800);
            border-radius: 10px;
            margin-bottom: 6px;
            padding: 12px 16px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav-link:hover {
            background-color: var(--gray-100);
            transform: translateX(4px);
        }

        .nav-link.active {
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            color: var(--primary-color);
            font-weight: 600;
            box-shadow: var(--shadow-sm);
        }

        .card-custom {
            border: none;
            border-radius: 16px;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            background: white;
            overflow: hidden;
        }

        .card-custom:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }

        .avatar-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
        }

        .new-attendance-highlight {
            animation: highlightFade 3s ease-in-out;
        }

        @keyframes highlightFade {
            0% {
                background-color: #e8f5e9;
                transform: scale(1.01);
            }

            100% {
                background-color: transparent;
                transform: scale(1);
            }
        }

        .stat-card {
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.3) 0%, transparent 70%);
            border-radius: 50%;
            transform: translate(30%, -30%);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .badge-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .table-hover tbody tr:hover {
            background-color: var(--gray-50);
            transition: background-color 0.2s ease;
        }

        h3,
        h4,
        h5 {
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .btn {
            border-radius: 10px;
            padding: 8px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .chart-container {
            position: relative;
            padding: 20px;
            background: var(--gray-50);
            border-radius: 12px;
            margin-top: 10px;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }

        .pulse-animation {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.6;
            }
        }
    </style>
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
                    @if(auth()->user()->isSuperadmin())
                        <li class="nav-item mt-3">
                            <small class="text-muted px-3 fw-semibold"
                                style="font-size: 11px; letter-spacing: 0.5px;">PENGATURAN</small>
                        </li>
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
                            <a class="nav-link {{ request()->is('admin/users*') ? 'active' : '' }}"
                                href="{{ url('/admin/users') }}">
                                <i class="bi bi-person-gear me-2"></i> Manajemen User
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('admin/audit-logs*') ? 'active' : '' }}"
                                href="{{ url('/admin/audit-logs') }}">
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
                        <h4 class="fw-bold m-0 mb-1">Dashboard Ringkasan</h4>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-calendar3 text-muted me-2"></i>
                            <small class="text-muted">{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="text-end me-3">
                            <p class="mb-0 fw-semibold small">{{ auth()->user()->name }}</p>
                            <small
                                class="text-muted">{{ auth()->user()->isSuperadmin() ? 'Superadmin' : 'Admin' }}</small>
                        </div>
                        <div class="avatar-circle bg-success text-white"
                            style="width: 45px; height: 45px; font-size: 18px;">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
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
</body>

</html>