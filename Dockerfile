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

# Set up PG4WP (Postgres support for WordPress) — curl tarball avoids git auth issues on Render
RUN curl -fsSL https://github.com/kevinoid/pg4wp/archive/refs/heads/master.tar.gz -o /tmp/pg4wp.tgz \
    && mkdir -p /var/www/html/wp-content/pg4wp \
    && tar -xzf /tmp/pg4wp.tgz -C /var/www/html/wp-content/pg4wp --strip-components=1 \
    && cp /var/www/html/wp-content/pg4wp/db.php /var/www/html/wp-content/db.php \
    && rm -f /tmp/pg4wp.tgz

# Ensure permissions are correct for Apache
RUN chown -R www-data:www-data /var/www/html

# Enable rewrite module for pretty permalinks
RUN a2enmod rewrite

COPY render-start.sh /usr/local/bin/render-start.sh
RUN chmod +x /usr/local/bin/render-start.sh

EXPOSE 80
CMD ["/usr/local/bin/render-start.sh"]
