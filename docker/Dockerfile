FROM php:8.2-fpm-alpine

RUN apk --update --no-cache add \
    bash \
    git \
    curl \
    wget \
    nodejs \
    yarn

# Install PHP extensions
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions opcache \
                           intl \
                           pdo_mysql

# Install and update composer
RUN curl https://getcomposer.org/composer.phar -o /usr/bin/composer && chmod +x /usr/bin/composer
RUN composer self-update

## Install Symfony binary
RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.alpine.sh' | bash \
    && apk add symfony-cli

RUN rm -rf /var/cache/apk/*

WORKDIR /var/www