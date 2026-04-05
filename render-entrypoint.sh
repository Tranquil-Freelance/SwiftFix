#!/bin/sh
# Render sets PORT. Official WordPress docker-entrypoint.sh only copies core from
# /usr/src/wordpress when argv[1] matches apache2* — so we must end by exec'ing
# docker-entrypoint.sh apache2-foreground, not a random script name as CMD.
set -e
port="${PORT:-80}"
if [ -f /etc/apache2/ports.conf ]; then
  sed -i "s/^Listen .*/Listen ${port}/" /etc/apache2/ports.conf
fi
for f in /etc/apache2/sites-enabled/000-default.conf /etc/apache2/sites-available/000-default.conf; do
  if [ -f "$f" ]; then
    sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${port}>/" "$f"
  fi
done
exec /usr/local/bin/docker-entrypoint.sh "$@"
