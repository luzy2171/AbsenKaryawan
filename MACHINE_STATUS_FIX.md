# Perbaikan Status Mesin Offline di Dashboard

## Masalah
Status mesin menampilkan "Offline" di dashboard meskipun mesin aktif dan bisa diakses di halaman kontrol mesin.

## Penyebab
Status mesin tidak diupdate secara otomatis saat melakukan koneksi ke mesin absensi. Status hanya diinisialisasi saat migration pertama kali dan tidak pernah diupdate lagi.

## Solusi yang Diterapkan

### 1. Update Status Mesin di AbsensiController
**File:** `app/Http/Controllers/AbsensiController.php`

Fungsi `tarikDataDariMesin()` sekarang:
- Mengukur response time koneksi ke mesin
- Update status menjadi **ONLINE** jika berhasil mengambil data
- Update status menjadi **OFFLINE** jika gagal koneksi atau data kosong
- Menyimpan response time untuk monitoring performa

**Cara Kerja:**
```php
// Setiap kali tarik data:
1. Mulai timer
2. Ambil data dari mesin
3. Jika berhasil → Update status "online" + response time
4. Jika gagal → Update status "offline"
```

### 2. Update Status Mesin di PengaturanController
**File:** `app/Http/Controllers/PengaturanController.php`

Fungsi yang diupdate:
- `index()` - Saat view users/logs/fingerprint templates
  - Setiap request ke mesin akan update status
  - Response time diukur dan disimpan

### 3. Update Status Mesin di KaryawanController
**File:** `app/Http/Controllers/KaryawanController.php`

Fungsi `syncDariMesin()` sekarang:
- Update status mesin saat sync karyawan dari mesin
- Menampilkan status koneksi real-time

### 4. Update Status Mesin di Scheduled Task
**File:** `app/Console/Kernel.php`

Auto-pull scheduled task sekarang:
- Update status mesin setiap kali otomatis tarik data
- Monitoring status mesin 24/7 jika auto-pull aktif

## Cara Testing

### Test 1: Manual Pull Data
1. Buka halaman Absensi
2. Klik tombol "Tarik Data dari Mesin"
3. Buka Dashboard
4. Status mesin harus menampilkan **ONLINE** dengan response time

### Test 2: Sync Karyawan
1. Buka halaman Karyawan
2. Klik "Sync dari Mesin"
3. Buka Dashboard
4. Status mesin harus menampilkan **ONLINE**

### Test 3: Kontrol Mesin
1. Buka halaman Pengaturan/Kontrol Mesin
2. Klik "Lihat User" atau "Lihat Log"
3. Buka Dashboard
4. Status mesin harus menampilkan **ONLINE**

### Test 4: Auto-Pull (Scheduled)
1. Pastikan auto-pull ON di halaman Absensi
2. Tunggu sesuai interval yang ditentukan
3. Cek Dashboard
4. Status mesin akan terupdate otomatis

## Status Mesin Dashboard

Dashboard sekarang menampilkan:
- **Badge Status**: Online (hijau) / Offline (merah)
- **Pulse Animation**: Animasi berkedip saat online
- **Last Ping**: Waktu terakhir koneksi ke mesin
- **IP Address**: IP mesin absensi
- **Response Time**: Kecepatan koneksi dalam ms

## Real-time Updates (Opsional)

Jika broadcasting diaktifkan (Pusher/Reverb):
- Status mesin akan update otomatis di semua browser
- Tidak perlu refresh halaman
- Notifikasi saat status berubah online/offline

Lihat file `BROADCASTING_SETUP.md` untuk setup real-time.

## Troubleshooting

### Status masih Offline setelah tarik data berhasil
**Solusi:**
1. Clear cache: `php artisan config:clear`
2. Refresh dashboard (F5)
3. Pastikan tabel `machine_status` memiliki data (minimal 1 row)

### Response time selalu 0
**Penyebab:** Koneksi sangat cepat atau mesin local
**Solusi:** Ini normal, artinya koneksi < 1ms

### Status tidak update otomatis
**Penyebab:** Broadcasting tidak aktif (BROADCAST_DRIVER=log)
**Solusi:** Refresh halaman manual atau aktifkan Pusher/Reverb

## Summary Perubahan

✅ Status mesin sekarang update otomatis saat:
- Tarik data absensi
- Sync karyawan dari mesin
- View users/logs di pengaturan
- Scheduled task auto-pull

✅ Dashboard menampilkan informasi lengkap:
- Status online/offline dengan badge berwarna
- Response time koneksi
- Last ping timestamp
- IP mesin

✅ Real-time monitoring (jika broadcasting aktif):
- Update status tanpa refresh
- Notifikasi perubahan status
- Animasi visual saat online

## Update Terakhir
2 September 2026 - Status mesin fully integrated
