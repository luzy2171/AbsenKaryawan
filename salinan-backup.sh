#!/bin/sh
# Salinan redundan backup aplikasi.
#
# Kenapa: backup utama ada di storage/app/backups. Kalau folder itu terhapus
# (salah hapus, ransomware, atau storage bermasalah), salinan di sini masih
# ada karena lokasinya terpisah dari folder aplikasi.
#
# CATATAN: /var/backups ada di disk yang sama dengan server, jadi ini
# TIDAK melindungi dari kerusakan disk. Untuk itu tetap perlu salinan ke
# mesin lain - lihat BACKUP.md.

set -eu

SUMBER="/var/www/absen-karyawan/storage/app/backups"
TUJUAN="/var/backups/absen"
SIMPAN=14

[ -d "$SUMBER" ] || { echo "Sumber tidak ada: $SUMBER"; exit 0; }

mkdir -p "$TUJUAN"

# Hanya salin yang berubah / lebih baru (rsync) supaya hemat I/O.
if command -v rsync >/dev/null 2>&1; then
    rsync -a --delete "$SUMBER/" "$TUJUAN/"
else
    rm -rf "$TUJUAN"
    mkdir -p "$TUJUAN"
    cp -a "$SUMBER/." "$TUJUAN/"
fi

# Jaga agar tidak menumpuk: simpan N backup terbaru.
ls -1d "$TUJUAN"/[0-9]* 2>/dev/null | sort -r | tail -n +$((SIMPAN + 1)) | while read -r lama; do
    rm -rf "$lama"
done

Jumlah=$(ls -1d "$TUJUAN"/[0-9]* 2>/dev/null | wc -l)
echo "$(date '+%Y-%m-%d %H:%M') salinan backup: $Jumlah backup di $TUJUAN"
