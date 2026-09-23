FROM php:8.2-fpm

# Install dependensi sistem dasar secara bertahap agar stabil
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    unzip \
    git \
    curl \
    cron \
    && rm -rf /var/lib/apt/lists/*

# Konfigurasi dan install ekstensi PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql gd zip bcmath opcache intl sockets

WORKDIR /var/www/html

# Buat crontab otomatis
RUN mkdir -p /var/spool/cron/crontabs && \
    echo '* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1' > /var/spool/cron/crontabs/root && \
    chmod 600 /var/spool/cron/crontabs/root

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
