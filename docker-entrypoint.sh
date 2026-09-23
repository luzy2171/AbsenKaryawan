#!/bin/sh
set -e

# Start cron daemon di background
if ! pidof cron > /dev/null 2>&1; then
    /usr/sbin/cron
fi

# Jalankan proses utama (PHP-FPM)
exec "$@"
