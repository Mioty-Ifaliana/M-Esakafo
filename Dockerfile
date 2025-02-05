# Utiliser PHP 8.2.12 CLI
FROM php:8.2.12-cli

# Installer les dépendances nécessaires
RUN apt-get update && apt-get install -y \
    zip unzip git curl libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql opcache

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définir le répertoire de travail
WORKDIR /app

# Copier composer.json et composer.lock d'abord
COPY composer.json composer.lock ./

# Installer les dépendances
RUN composer install --no-scripts --no-autoloader --no-dev

# Copier le reste des fichiers
COPY . .

# Générer l'autoloader optimisé
RUN composer dump-autoload --optimize --no-dev

# Créer le dossier var et modifier ses permissions
RUN mkdir -p var && chmod -R 777 var

# Vérifier que l'autoloader existe
RUN test -f vendor/autoload_runtime.php || (echo "autoload_runtime.php not found" && exit 1)

# Nettoyer le cache APT
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Exposer le port 8000
EXPOSE 8000

# Lancer le serveur PHP avec le bon chemin de travail
CMD ["php", "-S", "0.0.0.0:8000", "-t", "/app/public"]