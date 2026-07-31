#!/bin/sh
set -e

echo "🚀 Iniciando ambiente Laravel no Coolify..."

# Cache & Config Optimizations
echo "📦 Otimizando configurações e rotas do Laravel..."
php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache

# Criar link simbólico do storage
php artisan storage:link || true

# Executar Migrations no banco
echo "🗄️ Executando migrations do banco..."
php artisan migrate --force

# Iniciar o Laravel Reverb em segundo plano se configurado
echo "⚡ Iniciando Laravel Reverb (WebSockets)..."
php artisan reverb:start --port=8080 &

# Iniciar PHP-FPM em segundo plano
echo "🐘 Iniciando PHP-FPM..."
php-fpm -D

# Iniciar Nginx em primeiro plano (mantém o container vivo)
echo "🌐 Iniciando Nginx..."
exec nginx -g "daemon off;"
