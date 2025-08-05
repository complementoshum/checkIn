FROM php:8.2-fpm

# Instalación de dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libzip-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    libssl-dev \
    curl

# Solo instala SQLSRV si realmente lo necesitas, para MySQL o PostgreSQL no es necesario
# El siguiente bloque lo puedes comentar si no usas SQL Server
RUN apt-get update && \
    apt-get install -y gnupg2 unixodbc-dev libgssapi-krb5-2 && \
    curl -sSL https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor > /etc/apt/trusted.gpg.d/microsoft.gpg && \
    curl -sSL https://packages.microsoft.com/config/debian/12/prod.list > /etc/apt/sources.list.d/mssql-release.list && \
    apt-get update && ACCEPT_EULA=Y apt-get install -y msodbcsql17 && \
    pecl install pdo_sqlsrv && \
    docker-php-ext-enable pdo_sqlsrv

# Instala extensiones comunes de PHP para Laravel
RUN docker-php-ext-install pdo mbstring zip

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copiar solo composer.* para cachear mejor dependencias
COPY composer.json composer.lock ./

# Instalar dependencias PHP primero (si tienes un vendor previo, hazlo antes del COPY . .)
RUN composer install --no-dev --optimize-autoloader

# Luego copia el resto del código
COPY . .

# Crear storage y establecer permisos solo si existe
RUN mkdir -p storage && chown -R www-data:www-data /var/www && chmod -R 755 /var/www/storage

EXPOSE 4259
CMD ["php-fpm"]
