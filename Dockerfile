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
RUN composer install --no-scripts --no-autoloader

# Copier tous les fichiers du projet dans /app
COPY . .

# Finaliser l'installation de Composer
RUN composer dump-autoload --optimize

# Créer le dossier var et modifier ses permissions
RUN mkdir -p var && chmod -R 777 var

# Donner les permissions d'exécution au script build.sh
RUN chmod +x build.sh

# Exposer le port 8000 (juste à titre indicatif, Render le gère automatiquement)
EXPOSE 8000

# Lancer le build script puis le serveur
CMD ["sh", "-c", "./build.sh && php -S 0.0.0.0:8000 -t public"]