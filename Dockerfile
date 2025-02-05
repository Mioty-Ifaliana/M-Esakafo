# Utiliser PHP 8.2.12 CLI
FROM php:8.2.12-cli

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
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configuration de Composer
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_HOME=/composer
ENV PATH="${PATH}:/composer/vendor/bin"

# Définir le répertoire de travail
WORKDIR /app

# Copier les fichiers de configuration de Composer
COPY composer.json composer.lock ./

# Installer Symfony Flex
RUN composer global require "symfony/flex" --prefer-dist --no-progress --no-suggest --classmap-authoritative

# Installer les dépendances sans les scripts
RUN composer install --prefer-dist --no-dev --no-scripts --no-progress --no-suggest --optimize-autoloader

# Copier le reste des fichiers du projet
COPY . .

# Configuration de l'environnement
ENV APP_ENV=prod
ENV APP_DEBUG=0

# Permissions et configuration finale
RUN mkdir -p var/cache var/log \
    && chmod -R 777 var \
    && chmod +x bin/console \
    && composer dump-autoload --optimize --no-dev --classmap-authoritative \
    && composer run-script --no-dev post-install-cmd \
    && php bin/console cache:clear --env=prod --no-debug

# Exposer le port
EXPOSE 8000

# Commande par défaut
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
