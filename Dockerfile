# Utiliser PHP 8.2.12 CLI
FROM php:8.2.12-cli

# Installer les dépendances nécessaires
RUN apt-get update && apt-get install -y \
    zip unzip git curl libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql opcache

# Installer Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Définir le répertoire de travail
WORKDIR /app

# Copier composer.json et composer.lock d'abord
COPY composer.json composer.lock ./

# Installer les dépendances
RUN composer install --no-dev

# Copier le reste des fichiers
COPY . .

# Générer l'autoloader optimisé et exécuter les scripts
RUN composer dump-autoload --optimize --no-dev && \
    composer run-script post-install-cmd

# Créer le dossier var et modifier ses permissions
RUN mkdir -p var && chmod -R 755 var && \
    chmod -R 755 vendor

# Nettoyer le cache APT
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Exposer le port 8000
EXPOSE 8000

# Vérifiez le fichier autoload_runtime.php
RUN test -f vendor/autoload_runtime.php || (echo "autoload_runtime.php not found" && exit 1)

# Autoriser les plugins Composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# Lancer le serveur PHP
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
