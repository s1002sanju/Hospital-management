#!/bin/sh
set -e

# Render tells us which port to listen on via $PORT (default 10000).
PORT="${PORT:-10000}"
sed -i -E "s/^Listen [0-9]+/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i -E "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Schema upgrade, first admin, storage bucket. Never blocks start-up.
php /var/www/html/database/init.php || echo "[init] start-up tasks failed (continuing)"

exec apache2-foreground
