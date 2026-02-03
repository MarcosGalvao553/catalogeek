#!/bin/bash

# Aguardar o banco estar pronto
echo "Aguardando banco de dados..."
sleep 5

# Criar diretórios necessários PRIMEIRO
cd /var/www/backend
mkdir -p storage/app/private/temp_catalogs
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache
mkdir -p /var/log/supervisor

# Ajustar permissões
chown -R www-data:www-data storage
chown -R www-data:www-data bootstrap/cache

# AGORA pode cachear configurações
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Executar migrations
php artisan migrate --force

# Criar link simbólico do storage
php artisan storage:link

# Iniciar Supervisor (que gerencia nginx, php-fpm e queue worker)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
