#!/bin/bash

# Aguardar o banco estar pronto
echo "Aguardando banco de dados..."
sleep 5

# Executar migrations
cd /var/www/backend
php artisan migrate --force

# Criar link simbólico do storage
php artisan storage:link

# Limpar e cachear configurações
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Criar diretórios necessários
mkdir -p /var/www/backend/storage/app/private/temp_catalogs
mkdir -p /var/log/supervisor

# Ajustar permissões
chown -R www-data:www-data /var/www/backend/storage
chown -R www-data:www-data /var/www/backend/bootstrap/cache

# Iniciar Supervisor (que gerencia nginx, php-fpm e queue worker)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
