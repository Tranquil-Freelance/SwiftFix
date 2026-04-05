FROM wordpress:php8.2-apache

# Install dependencies for Postgres support
RUN apt-get update && apt-get install -y \
    libpq-dev \
    curl \
    unzip \
    && docker-php-ext-install pgsql pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copy themes
COPY src/wp-content/themes/ /var/www/html/wp-content/themes/

# Copy custom config
COPY src/wp-config.php /var/www/html/wp-config.php

# PG4WP: kevinoid/pg4wp is gone; maintained fork is PostgreSQL-For-Wordpress/postgresql-for-wordpress (branch v3).
# Archive root folder is postgresql-for-wordpress-3/; plugin lives in pg4wp/.
RUN curl -fsSL https://github.com/PostgreSQL-For-Wordpress/postgresql-for-wordpress/archive/refs/heads/v3.tar.gz -o /tmp/pfw.tgz \
    && tar -xzf /tmp/pfw.tgz -C /tmp \
    && mv /tmp/postgresql-for-wordpress-3/pg4wp /var/www/html/wp-content/pg4wp \
    && cp /var/www/html/wp-content/pg4wp/db.php /var/www/html/wp-content/db.php \
    && rm -rf /tmp/pfw.tgz /tmp/postgresql-for-wordpress-3

# Ensure permissions are correct for Apache
RUN chown -R www-data:www-data /var/www/html

# Enable rewrite module for pretty permalinks
RUN a2enmod rewrite

COPY render-start.sh /usr/local/bin/render-start.sh
RUN chmod +x /usr/local/bin/render-start.sh

EXPOSE 80
CMD ["/usr/local/bin/render-start.sh"]
