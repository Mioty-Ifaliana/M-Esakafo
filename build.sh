#!/bin/bash

# Install PHP dependencies
composer install --no-interaction --prefer-dist --optimize-autoloader

# Clear cache
php bin/console cache:clear --env=prod --no-debug

# Warm up cache
php bin/console cache:warmup --env=prod --no-debug

# Create database if it doesn't exist
php bin/console doctrine:database:create --if-not-exists

# Run migrations
php bin/console doctrine:migrations:migrate --no-interaction

# Optional: Load fixtures if you have any
# php bin/console doctrine:fixtures:load --no-interaction
