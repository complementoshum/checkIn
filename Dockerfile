FROM php:8.2-fpm-bullseye

# Dependencias del sistema
RUN apt-get update && apt-get install -y \
    gnupg2 \
    curl \
    unixodbc-dev \
    libgssapi-krb5-2 \
    libcurl4-openssl-dev \
    libssl-dev \
    libxml2-dev \
    libzip-dev \
    libonig-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libwebp-dev \
    zip \
    unzip \
    git

# SQL Server (opcional)
RUN curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add - \
    && curl https://packages.microsoft.com/config/debian/11/prod.list > /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y msodbcsql17 \
    && pecl install sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv

# PHP Extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install gd \
    && docker-php-ext-install pdo mbstring zip

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN composer install --no-dev --optimize-autoloader

RUN mkdir -p storage && chown -R www-data:www-data /var/www && chmod -R 755 /var/www/storage

EXPOSE 4259

# Aquí el cambio: CMD por defecto para correr el servidor integrado de Laravel
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=4259"]
