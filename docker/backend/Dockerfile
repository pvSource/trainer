FROM php:8.3-fpm

#Установка
RUN apt-get update && apt-get install -Y

#Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

#Раюочая директория
WORKDIR /var/www/trainer/backend


