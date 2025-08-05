FROM php:8.3-fpm-bullseye

# Instalar dependencias
RUN apt-get update && apt-get upgrade -y && apt-get install -y --no-install-recommends \
    build-essential \
    openjdk-17-jdk \
    libmcrypt-dev \
    nano \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    screen \
    gnupg2 \
    libjpeg62-turbo-dev \
    libwebp-dev \
    wget \
    curl \
    && docker-php-ext-install pdo_mysql pdo_pgsql zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd \
    && curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add - \
    && curl https://packages.microsoft.com/config/debian/11/prod.list > /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y msodbcsql17 mssql-tools unixodbc-dev \
    && pecl install sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /app

# Copy project files
COPY . .

# Composer and Roadrunner
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY --from=spiralscout/roadrunner:2.4.2 /usr/bin/rr /usr/bin/rr

# Install PHP dependencies
RUN composer update --no-dev

# Expose Laravel port
EXPOSE 4259

# ENTRYPOINT para configurar storage y lanzar Laravel
ENTRYPOINT bash -c "\
    if [ ! -L public/storage ]; then \
        echo 'Creating storage symlink...'; \
        php artisan storage:link; \
    fi && \
    chmod -R 775 storage bootstrap/cache && \
    php artisan serve --host=0.0.0.0 --port=4259"
