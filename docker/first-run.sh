#!/usr/bin/env bash
set -euo pipefail

APP_DIR=${APP_DIR:-/var/www/html}
APP_USER="www-data"
STAMP_FILE="$APP_DIR/.first_run_done"

if [ -f "$STAMP_FILE" ]; then
  exit 0
fi

# Wait for MariaDB if configured
DB_HOST=${DB_HOST:-}
DB_PORT=${DB_PORT:-3306}
DB_CONNECTION=${DB_CONNECTION:-}

if [ "$DB_CONNECTION" = "mysql" ] || [ -n "$DB_HOST" ]; then
  host=${DB_HOST:-db}
  port=${DB_PORT}
  echo "Waiting for database $host:$port ..."
  for i in $(seq 1 60); do
    if (echo > "/dev/tcp/$host/$port") >/dev/null 2>&1; then
      echo "Database is up."
      break
    fi
    sleep 1
    if [ "$i" = "60" ]; then
      echo "Database wait timed out." >&2
    fi
  done
fi

# Wait for Redis if configured
REDIS_HOST=${REDIS_HOST:-}
REDIS_PORT=${REDIS_PORT:-6379}
if [ -n "$REDIS_HOST" ]; then
  host=$REDIS_HOST
  port=$REDIS_PORT
  echo "Waiting for redis $host:$port ..."
  for i in $(seq 1 60); do
    if (echo > "/dev/tcp/$host/$port") >/dev/null 2>&1; then
      echo "Redis is up."
      break
    fi
    sleep 1
  done
fi


# Trust repository path for all users and the runtime user to avoid Git dubious ownership
su -s /bin/bash -c "git config --global --add safe.directory '$APP_DIR'" "$APP_USER" || true
# Composer install (optimize autoloader)
if [ -f "$APP_DIR/composer.json" ]; then
  su -s /bin/bash -c "cd '$APP_DIR' && composer install --no-interaction --prefer-dist --optimize-autoloader" "$APP_USER"
fi

# Ensure SQLite database file if using sqlite
if [ "${DB_CONNECTION}" = "sqlite" ]; then
  if [ -d "$APP_DIR/database" ]; then
    if [ ! -f "$APP_DIR/database/database.sqlite" ]; then
      su -s /bin/bash -c "touch '$APP_DIR/database/database.sqlite'" "$APP_USER" || true
    fi
  fi
fi

# Generate APP_KEY if missing
if ! grep -q '^APP_KEY=' "$APP_DIR/.env" || grep -q '^APP_KEY=$' "$APP_DIR/.env"; then
  su -s /bin/bash -c "cd '$APP_DIR' && php artisan key:generate --force" "$APP_USER" || true
fi

# Run the README first-deploy commands
su -s /bin/bash -c "cd '$APP_DIR' && php artisan migrate --force" "$APP_USER" || true
su -s /bin/bash -c "cd '$APP_DIR' && php artisan db:seed --force" "$APP_USER" || true

# Storage link
su -s /bin/bash -c "cd '$APP_DIR' && php artisan storage:link" "$APP_USER" || true

# Permissions
chown -R www-data:www-data "$APP_DIR"
chmod -R u+rwx "$APP_DIR"

# Mark as done
touch "$STAMP_FILE"
