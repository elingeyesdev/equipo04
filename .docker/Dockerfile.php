FROM php:8.4-cli

# Instalar dependencias del sistema requeridas para PostgreSQL y Git/Unzip
RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    git \
    curl \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Instalar Node.js para Vite (se ocupará en el contenedor web)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm@latest

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
