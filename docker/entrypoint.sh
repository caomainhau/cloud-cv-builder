#!/usr/bin/env bash
set -euo pipefail

PORT_VALUE="${PORT:-10000}"
sed -ri "s/^Listen [0-9]+/Listen ${PORT_VALUE}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT_VALUE}>/" /etc/apache2/sites-available/000-default.conf

attempt=1
max_attempts=30
until php /var/www/html/scripts/migrate.php; do
  if [ "$attempt" -ge "$max_attempts" ]; then
    echo "Database migration failed after ${max_attempts} attempts." >&2
    exit 1
  fi
  echo "Database is not ready yet. Retrying migration (${attempt}/${max_attempts})..."
  attempt=$((attempt + 1))
  sleep 2
done

exec "$@"
