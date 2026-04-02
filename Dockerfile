FROM wordpress:php8.2-apache

# Install dependencies for Postgres support
RUN apt-get update && apt-get install -y \
    libpq-dev \
    git \
    unzip \
    && docker-php-ext-install pgsql pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copy themes
COPY src/wp-content/themes/ /var/www/html/wp-content/themes/

# Copy custom config
COPY src/wp-config.php /var/www/html/wp-config.php

# Set up PG4WP (Postgres support for WordPress)
RUN git clone https://github.com/kevinoid/pg4wp.git /var/www/html/wp-content/pg4wp \
    && cp /var/www/html/wp-content/pg4wp/db.php /var/www/html/wp-content/db.php

# Ensure permissions are correct for Apache
RUN chown -R www-data:www-data /var/www/html

# Enable rewrite module for pretty permalinks
RUN a2enmod rewrite

EXPOSE 80
