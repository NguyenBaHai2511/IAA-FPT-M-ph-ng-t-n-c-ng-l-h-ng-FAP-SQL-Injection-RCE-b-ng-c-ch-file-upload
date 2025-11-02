#!/bin/bash
set -e

# If DB not exists, run init script to create and seed DB
if [ ! -f /var/www/data/fap.db ]; then
  echo "[entrypoint] fap.db not found — initializing DB..."
  # run init (ignore failures but print output)
  php /var/www/src/init_db.php || true
fi

# Ensure upload dir exists and correct ownership so Apache can serve/upload
mkdir -p /var/www/html/uploads
chown -R www-data:www-data /var/www/html /var/www/data || true
chmod -R 775 /var/www/html/uploads || true

exec "$@"
