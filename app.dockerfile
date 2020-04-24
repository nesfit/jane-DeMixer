FROM php:7.2-fpm-alpine

COPY ./demixer /var/www
RUN chown -R www-data:www-data /var/www

WORKDIR /root
RUN curl --silent --show-error https://getcomposer.org/installer | php
RUN ln -s ~/composer.phar /usr/bin/composer
RUN chmod +x ~/composer.phar

WORKDIR /var/www
RUN composer install
RUN cp  .env.example .env
RUN php artisan key:generate
RUN php artisan config:cache
RUN php artisan view:clear
EXPOSE 9000