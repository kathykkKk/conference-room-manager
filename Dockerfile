FROM php:8.3-fpm

# Устанавливаем системные пакеты, Nginx, Supervisor и клиент Postgres
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    supervisor \
    nginx \
    postgresql-client \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Устанавливаем расширения PHP для работы с Postgres и другие нужные Laravel
RUN docker-php-ext-install pdo_pgsql pgsql mbstring exif pcntl bcmath gd sockets

# Ставим Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Копируем конфиги
COPY docker/nginx.conf /etc/nginx/sites-available/default
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

# Даем права на запуск скрипта
RUN chmod +x /usr/local/bin/entrypoint.sh

# Копируем проект (кроме того что в .dockerignore)
COPY . .

# Выставляем права на папки Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

# Запускаем наш автоматический скрипт
RUN chmod 1777 /tmp
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]