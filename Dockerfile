FROM php:8.2-apache

# System dependencies
RUN apt-get update && apt-get install -y \
    git unzip curl libsqlite3-dev libzip-dev \
    && docker-php-ext-install pdo pdo_sqlite zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Use production PHP ini (display_errors=Off, log_errors=On)
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Apache: enable mod_rewrite, set document root to project root
RUN a2enmod rewrite
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Clone project from GitHub
ARG REPO_URL=https://github.com/CoderXYZ7/AnimaID.git
ARG BRANCH=master
RUN git clone --depth=1 --branch=${BRANCH} ${REPO_URL} /var/www/html \
    && git config --global --add safe.directory /var/www/html

WORKDIR /var/www/html

# Install PHP dependencies (production only)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-ansi --no-security-blocking

# Create required directories and set permissions
RUN mkdir -p database/backups uploads \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 database uploads

COPY docker/entrypoint.sh /entrypoint.sh
COPY docker/seed_admin.php /docker/seed_admin.php
RUN chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
