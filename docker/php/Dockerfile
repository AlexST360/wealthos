FROM php:8.3-fpm

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Crear usuario no-root para el proceso
RUN groupadd -g 1000 wealthos && useradd -u 1000 -ms /bin/bash -g wealthos wealthos
RUN chown -R wealthos:wealthos /var/www/html
USER wealthos

EXPOSE 9000
CMD ["php-fpm"]
