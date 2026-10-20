FROM php:8.3-cli

WORKDIR /app

RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    git \
    libssl-dev \
    && docker-php-ext-install sockets zip pcntl pdo_mysql \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY src-php/composer.json ./
RUN composer install --no-dev --optimize-autoloader --ignore-platform-req=ext-mongodb

COPY src-php/ ./

CMD ["sleep", "infinity"]
