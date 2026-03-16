#!/bin/bash

# Создаем недостающие папки внутри storage, если их нет
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/testing
mkdir -p storage/framework/views
mkdir -p storage/logs

# Установка зависимостей PHP, если папки vendor нет
if [ ! -d "vendor" ]; then
    echo "Installing composer dependencies..."
    composer install --no-interaction --optimize-autoloader --ignore-platform-reqs
fi

# Настройка .env если его нет
if [ ! -f ".env" ]; then
    echo "Creating .env file..."
    cp .env.example .env
    php artisan key:generate
fi

# ВАЖНО: Исправляем права доступа ПЕРЕД запуском
# Мы даем права пользователю www-data, под которым работает PHP-FPM
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Ждем базу данных
echo "Waiting for Postgres..."
until pg_isready -h postgres -p 5432 -U sail; do
  sleep 1
done

# Запуск миграций и очистка кеша
echo "Running migrations and clearing cache..."
php artisan migrate --force
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Запуск Supervisor
echo "Starting Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf