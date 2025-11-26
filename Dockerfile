FROM php:8.3-fpm-alpine

# Arguments
ARG user=laravel
ARG uid=1000

# Installation des dépendances système
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    postgresql-dev \
    zip \
    unzip \
    bash \
    librdkafka-dev \
    autoconf \
    g++ \
    make

# Installation des extensions PHP
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    sockets

# Installation de l'extension rdkafka pour Kafka
RUN pecl install rdkafka && \
    docker-php-ext-enable rdkafka

# Vérifier l'installation de rdkafka
RUN php -m | grep rdkafka

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Création de l'utilisateur système
RUN adduser -D -G www-data -u $uid $user

# Définir le répertoire de travail
WORKDIR /var/www

# Changer les permissions
RUN chown -R $user:$user /var/www

# Passer à l'utilisateur non-root
USER $user

# Exposer le port
EXPOSE 8000
