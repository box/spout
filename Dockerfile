FROM php:7.2-cli

RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y \
    libzip-dev \
    locales \
    locales-all \
    zip \
  && docker-php-ext-configure zip --with-libzip \
  && docker-php-ext-install zip

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
RUN chmod +x /usr/local/bin/composer
