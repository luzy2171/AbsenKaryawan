# Backup & Pemulihan

Alat ini menutup tiga bagian yang bisa hilang:

| Bagian | Isi | Sumber pemulihan |
|---|---|---|
| **database** | Dump SQL seluruh tabel | `database.sql` (dump murni PHP, tanpa mysqldump) |
| **file upload** | Logo perusahaan, dokumen cuti, dll | `files-public.tar.gz` |
| **mesin absensi** | Daftar user + seluruh template sidik jari | `machine.json` |

Bagian **mesin** yang paling penting: template sidik jari hanya ada di perangkat
keras, tidak ada di database aplikasi. Kalau template rusak atau terhapus di
mesin, satu-satunya sumber pemulihan adalah `machine.json`.

## Backup

```bash
php artisan backup:buat
```

Backup tersimpan di `storage/app/backups/<stempel>/`. Selesai dalam beberapa
detik dan tidak menghentikan aplikasi.

Jadwalkan otomatis (harian jam 02:00, simpan 14 hari terakhir):

```bash
* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
```

`CACHE_DRIVER=file` dan `QUEUE_CONNECTION=sync` di `.env`, jadi jadwal bawaan
Laravel tidak_queue. Kalau memang memakai queue, pindahkan ke scheduler sistem
sesuai setup Anda.

## Melihat & menghapus

```bash
php artisan backup:kelola                      # daftar backup
php artisan backup:kelola --hapus=20260930-172549
```

## Memulihkan

```bash
# database saja
php artisan backup:kelola --pulasikan=20260930-172549 --yang=database

# file upload saja
php artisan backup:kelola --pulasikan=20260930-172549 --yang=file

# semua (database + file)
php artisan backup:kelola --pulasikan=20260930-172549 --yang=semua
```

Setiap bagian meminta konfirmasi terpisah karena menimpa data yang ada.

## Memulihkan mesin absensi

Ini menulis ke perangkat keras, jadi dipisah dan dua tahap: lihat dulu, lalu
eksekusi.

```bash
# 1. pratinjau (tidak menulis apa pun)
php artisan backup:mesin --backup=20260930-172549

# 2. eksekusi
php artisan backup:mesin --backup=20260930-172549 --pulihkan
```

Untuk user yang namanya `(nama kosong)` di backup, nama harus diisi manual
sebelum ditulis — mesin akan menolak user tanpa nama. Buka `machine.json`,
isi bagian `nama_user`, lalu jalankan ulang.

Format template: `SetUserTemplate` menerima **base64**, dan `<Size>` harus
sama dengan panjang base64 tersebut. Isi `<Template>` disimpan mesin apa
adanya, jadi jangan pernah `trim()` file biner.

## Salinan di server

`salinan-backup.sh` menyalin `storage/app/backups/` ke `/var/backups/absen/`
(di luar folder aplikasi), dijadwalkan tiap jam 04:00, menyimpan 14 backup
terbaru. Ini melindungi dari `storage/app/backups` yang terhapus.

**Tidak melindungi dari kerusakan disk** — `/var/backups` masih di disk yang
sama. Untuk itu tetap perlu mesin lain, lihat di bawah.

Jadwal lengkap:

| Waktu | Yang jalan |
|---|---|
| 02:00 | `backup:buat` (lewat `php artisan schedule:run`) |
| 03:00 | `backup:bersihkan` (hapus yang lama) |
| 04:00 | `salinan-backup.sh` → `/var/backups/absen` |

## Aturan penting

**Salin backup ke mesin lain.** Folder di `storage/` ikut hilang kalau disk
server rusak, dan `/var/backups` ada di disk yang sama:

```bash
rsync -av /var/backups/absen/ user@mesin-lain:/backup/absen/
```

Mesin lain yang terjangkau dari server ini: `10.10.10.10` (SSH terbuka),
`10.10.10.4` dan `10.10.10.254` (HTTP). Pastikan tujuan memang mesin milik Anda
sebelum mengirim data ke sana.

**Jalankan `php artisan backup:buat` sebelum perubahan berisiko** — migrasi
data, pembaruan mesin, atau apa pun yang menyentuh template sidik jari.

**Uji restore-nya.** Backup yang tidak pernah dipulihkan tidak berguna.

```bash
php artisan backup:kelola --pulasikan=<id-terbaru> --yang=database
```

 lalu bandingkan jumlah baris tiap tabel dengan `php artisan tinker`.
