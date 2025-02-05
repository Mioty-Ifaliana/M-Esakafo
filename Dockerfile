# Utiliser PHP 8.2.12 CLI
FROM php:8.2.12-cli

# Installer les dépendances nécessaires
RUN apt-get update && apt-get install -y \
    zip unzip git curl libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql opcache

# Définir le répertoire de travail
WORKDIR /app

# Copier tous les fichiers du projet dans /app
COPY . .

# Créer le dossier var et modifier ses permissions
RUN mkdir -p var && chmod -R 777 var

# Exposer le port 8000 (juste à titre indicatif, Render le gère automatiquement)
EXPOSE 8000

# Lancer le serveur PHP et pointer vers public/
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]