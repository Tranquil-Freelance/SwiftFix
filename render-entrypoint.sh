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

# A Render disk (or earlier failed deploy) can leave a *partial* /var/www/html: e.g.
# wp-includes/ exists but index.php does not. Upstream docker-entrypoint.sh then skips
# the install tar entirely (it requires BOTH index.php and wp-includes to be absent).
# Merge any missing paths from the image so Apache always gets a full root.
cd /var/www/html
if [ ! -f index.php ] && [ -f /usr/src/wordpress/index.php ]; then
  echo >&2 "swiftfix: merging WordPress files from /usr/src/wordpress into /var/www/html"
  (cd /usr/src/wordpress && tar cf - .) | (cd /var/www/html && tar xf - --skip-old-files)
  chown -R www-data:www-data /var/www/html 2>/dev/null || true
fi

exec /usr/local/bin/docker-entrypoint.sh "$@"
