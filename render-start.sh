#!/bin/sh
# Render sets PORT; Apache in the WordPress image listens on 80 by default.
set -e
port="${PORT:-80}"
sed -i "s/^Listen .*/Listen ${port}/" /etc/apache2/ports.conf
for f in /etc/apache2/sites-enabled/000-default.conf /etc/apache2/sites-available/000-default.conf; do
  if [ -f "$f" ]; then
    sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${port}>/" "$f"
  fi
done
exec docker-php-entrypoint apache2-foreground
