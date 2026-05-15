FROM php:8.2-cli-alpine

LABEL maintainer="erik <erik@erik.xyz>"

RUN apk add --no-cache \
    curl git unzip \
    mysql-client redis \
    libpq-dev \
    && docker-php-ext-install pdo pdo_mysql pcntl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY service/ /app/
RUN composer install --no-dev --optimize-autoloader

EXPOSE 8788
CMD ["php", "start.php", "start"]
