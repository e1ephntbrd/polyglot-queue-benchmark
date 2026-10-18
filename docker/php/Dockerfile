FROM php:8.2-cli

WORKDIR /app/app

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libssl-dev \
    librabbitmq-dev \
    && docker-php-ext-install zip pdo pdo_mysql bcmath sockets \
    && pecl install redis amqp mongodb \
    && docker-php-ext-enable redis amqp mongodb

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
