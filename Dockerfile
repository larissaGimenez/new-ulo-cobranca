# Multi-stage build: Frontend Assets + PHP Production Runtime

# Stage 1: Build Assets com Node/Vite
FROM node:20-alpine AS node_builder
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# Stage 2: PHP + Nginx Environment
FROM php:8.3-fpm-alpine

# Instalar dependências de sistema e extensões do PHP (PostgreSQL, zip, pdo, etc)
RUN apk add --no-cache \
    nginx \
    postgresql-dev \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    curl \
    oniguruma-dev

RUN docker-php-ext-install pdo pdo_pgsql pgsql mbstring zip exif pcntl bcmath gd

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copiar código do projeto
COPY . .

# Copiar os assets compilados do stage 1
COPY --from=node_builder /app/public/build ./public/build

# Instalar dependências do PHP para produção
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Ajustar permissões das pastas de armazenamento
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Configuração do Nginx
COPY docker/nginx.conf /etc/nginx/http.d/default.conf

# Entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
