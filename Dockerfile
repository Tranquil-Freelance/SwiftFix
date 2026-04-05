FROM wordpress:php8.2-apache

# Install dependencies for Postgres support
RUN apt-get update && apt-get install -y \
    libpq-dev \
    curl \
    unzip \
    && docker-php-ext-install pgsql pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# The base image declares VOLUME /var/www/html — runtime starts with an empty volume.
# docker-entrypoint.sh copies FROM /usr/src/wordpress into /var/www/html only when
# CMD is apache2-foreground (argv[1] matches apache2*). Put all site files under
# /usr/src/wordpress so they are included in that copy.

COPY src/wp-content/themes/ /usr/src/wordpress/wp-content/themes/
COPY src/wp-config.php /usr/src/wordpress/wp-config.php

# PG4WP: maintained fork PostgreSQL-For-Wordpress/postgresql-for-wordpress (v3).
RUN curl -fsSL https://github.com/PostgreSQL-For-Wordpress/postgresql-for-wordpress/archive/refs/heads/v3.tar.gz -o /tmp/pfw.tgz \
    && tar -xzf /tmp/pfw.tgz -C /tmp \
    && mv /tmp/postgresql-for-wordpress-3/pg4wp /usr/src/wordpress/wp-content/pg4wp \
    && cp /usr/src/wordpress/wp-content/pg4wp/db.php /usr/src/wordpress/wp-content/db.php \
    && rm -rf /tmp/pfw.tgz /tmp/postgresql-for-wordpress-3

RUN chown -R www-data:www-data /usr/src/wordpress

RUN a2enmod rewrite

COPY render-entrypoint.sh /usr/local/bin/render-entrypoint.sh
RUN chmod +x /usr/local/bin/render-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/render-entrypoint.sh"]
CMD ["apache2-foreground"]

EXPOSE 80
