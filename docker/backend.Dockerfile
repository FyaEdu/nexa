# Image backend NEXA: PHP 7.3 + CodeIgniter 3 + SQLite.
# Ekstensi zip & gd disiapkan untuk PhpSpreadsheet (laporan Excel, Tahap 5).
FROM php:7.3-cli-alpine

RUN apk add --no-cache git unzip curl sqlite-dev libzip-dev zlib-dev libpng-dev libjpeg-turbo-dev freetype-dev \
 && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ --with-png-dir=/usr/include/ \
 && docker-php-ext-configure zip --with-libzip \
 && docker-php-ext-install -j"$(nproc)" gd zip \
 && (php -m | grep -qi '^sqlite3$' || docker-php-ext-install sqlite3) \
 && php -m | grep -qi '^sqlite3$'

# Composer 2.2 LTS (masih mendukung PHP 7.3)
COPY --from=composer:2.2 /usr/bin/composer /usr/bin/composer

COPY docker/backend-entrypoint.sh /usr/local/bin/nexa-entrypoint
# Buang CRLF kalau file sempat tersimpan dengan format Windows
RUN sed -i 's/\r$//' /usr/local/bin/nexa-entrypoint && chmod +x /usr/local/bin/nexa-entrypoint

WORKDIR /app
EXPOSE 8080
ENTRYPOINT ["nexa-entrypoint"]
