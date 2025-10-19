# DviceBilet - Modern Otobüs Bileti Satış Platformu
# Dockerfile

FROM php:8.2-apache

# Sistem güncellemeleri ve gerekli paketler
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    sqlite3 \
    libsqlite3-dev \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-install pdo pdo_sqlite mbstring exif pcntl bcmath gd zip

# Composer kurulumu
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Apache modülleri
RUN a2enmod rewrite
RUN a2enmod headers
RUN a2enmod ssl

# Apache yapılandırması
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Çalışma dizini
WORKDIR /var/www/html

# Uygulama dosyalarını kopyala
COPY . .

# Veritabanı dosyasını oluştur (eğer yoksa)
RUN if [ ! -f bus_tickets.db ]; then \
        sqlite3 bus_tickets.db "CREATE TABLE IF NOT EXISTS test (id INTEGER PRIMARY KEY);"; \
    fi

# İzinleri ayarla
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html
RUN chmod -R 777 /var/www/html/bus_tickets.db

# Apache yapılandırması
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html\n\
    <Directory /var/www/html>\n\
        AllowOverride All\n\
        Require all granted\n\
        Options Indexes FollowSymLinks\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
    LogLevel warn\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# PHP yapılandırması
RUN echo "upload_max_filesize = 10M\n\
post_max_size = 10M\n\
max_execution_time = 300\n\
memory_limit = 256M\n\
date.timezone = Europe/Istanbul\n\
session.gc_maxlifetime = 3600" > /usr/local/etc/php/conf.d/custom.ini

# Port
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# Başlatma komutu
CMD ["apache2-foreground"]
