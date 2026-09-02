# Broadcasting Setup - Absensi Karyawan

## Status Saat Ini

Broadcasting driver diset ke **`log`** untuk menghindari error Pusher class not found.

Event broadcasting tetap berjalan, tapi hanya akan di-log ke file `storage/logs/laravel.log` dan **tidak akan real-time** di browser.

## Cara Mengaktifkan Real-time Updates

### Opsi 1: Menggunakan Pusher Cloud (Recommended untuk Production)

1. Daftar akun gratis di [https://pusher.com](https://pusher.com)
2. Buat aplikasi baru dan dapatkan credentials
3. Update file `.env`:
```env
BROADCAST_DRIVER=pusher

PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_APP_CLUSTER=ap1
```

4. Clear cache:
```bash
php artisan config:clear
```

### Opsi 2: Menggunakan Laravel Reverb (Self-hosted WebSocket)

1. Install dan jalankan Reverb server:
```bash
php artisan reverb:start
```

2. Update `.env`:
```env
BROADCAST_DRIVER=reverb

REVERB_APP_ID=local
REVERB_APP_KEY=local-key
REVERB_APP_SECRET=local-secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
```

3. Update `resources/views/dashboard.blade.php` - bagian Pusher config untuk menggunakan Reverb endpoint

### Opsi 3: Tetap Menggunakan Log (Current - No Real-time)

Tidak perlu setup tambahan. Event akan ter-log tapi tidak ada real-time updates di browser.

## Testing Real-time Features

Setelah setup salah satu opsi di atas:

1. Buka dashboard di 2 browser/tab berbeda
2. Lakukan absensi atau ubah status mesin
3. Dashboard di browser lain harus update otomatis tanpa refresh

## Features yang Menggunakan Broadcasting

1. **Real-time Attendance Updates**
   - Event: `AttendanceRecorded`
   - Channel: `attendance`
   - Trigger: Saat karyawan melakukan absensi

2. **Real-time Machine Status**
   - Event: `MachineStatusChanged`
   - Channel: `machine-status`
   - Trigger: Saat status mesin berubah (online/offline)

## Troubleshooting

- **Error "Pusher class not found"**: Pastikan `BROADCAST_DRIVER=log` di `.env`
- **Real-time tidak jalan**: Cek WebSocket connection di browser console
- **Event tidak ter-broadcast**: Cek `storage/logs/laravel.log` untuk debugging

## Notes

Untuk development/testing, menggunakan `BROADCAST_DRIVER=log` sudah cukup. Fitur real-time bersifat opsional dan tidak mempengaruhi fungsi utama aplikasi.
