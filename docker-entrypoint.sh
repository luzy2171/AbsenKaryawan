#!/bin/sh
set -e

# Fix ownership storage agar PHP-FPM (www-data) bisa tulis meski artisan dijalankan root
if [ -d /var/www/html/storage ]; then
    chown -R www-data:www-data /var/www/html/storage
    chmod -R 777 /var/www/html/storage
fi

# Start cron daemon di background
if ! pidof cron > /dev/null 2>&1; then
    /usr/sbin/cron
fi

# Jalankan proses utama (PHP-FPM)
exec "$@"
