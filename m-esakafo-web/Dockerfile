# Utiliser PHP 8.2.12 CLI
FROM php:8.2.12-fpm

# Installer les dépendances système nécessaires
RUN apt-get update && apt-get install -y \
    zip \
    unzip \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    && pecl install grpc \
    && docker-php-ext-enable grpc \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        pdo \
        pdo_mysql \
        opcache \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Installer Composer
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# Configuration de Composer
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_HOME=/composer

# Définir le répertoire de travail
WORKDIR /app/m-esakafo-web

# Copier les fichiers de configuration de Composer
COPY m-esakafo-web/composer.json m-esakafo-web/composer.lock ./

# Configurer Composer pour autoriser les plugins
RUN composer config --no-plugins allow-plugins.symfony/flex true \
    && composer config --no-plugins allow-plugins.symfony/runtime true

# Installer les dépendances
RUN composer install --prefer-dist --no-dev --optimize-autoloader --no-scripts

# Copier le reste des fichiers du projet
COPY m-esakafo-web/ .

# Configuration de l'environnement
ENV APP_ENV=prod
ENV APP_DEBUG=0

# Permissions et configuration finale
RUN set -eux; \
    mkdir -p var/cache var/log; \
    composer dump-autoload --optimize --no-dev --classmap-authoritative; \
    chmod -R 777 var; \
    chmod +x bin/console; \
    sync

# Exposer le port
EXPOSE 8000

# Commande par défaut
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
