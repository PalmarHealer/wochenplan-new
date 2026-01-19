#!/usr/bin/env bash
set -euo pipefail

APP_DIR=${APP_DIR:-/var/www/html}
APP_USER="www-data"

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

# Ensure SQLite database file if using sqlite
if [ "${DB_CONNECTION}" = "sqlite" ]; then
  if [ -d "$APP_DIR/database" ]; then
    if [ ! -f "$APP_DIR/database/database.sqlite" ]; then
      su -s /bin/bash -c "touch '$APP_DIR/database/database.sqlite'" "$APP_USER" || true
    fi
  fi
fi

# Generate APP_KEY if missing (check environment variable, not .env file)
if [ -z "${APP_KEY:-}" ] || [ "$APP_KEY" = "" ]; then
  echo "=========================================="
  echo "APP_KEY not set - Generating new APP_KEY"
  echo "=========================================="

  # Generate key
  NEW_APP_KEY="base64:$(openssl rand -base64 32)"

  # Save to a file that supervisor will source
  echo "export APP_KEY='${NEW_APP_KEY}'" > /tmp/app_env.sh
  chmod 644 /tmp/app_env.sh

  # Source it for current shell
  export APP_KEY="${NEW_APP_KEY}"

  echo "IMPORTANT: Copy this APP_KEY to your docker-compose.yml for persistence:"
  echo ""
  echo "APP_KEY: ${NEW_APP_KEY}"
  echo ""
  echo "=========================================="
  sleep 3
fi

# Run the README first-deploy commands
su -s /bin/bash -c "cd '$APP_DIR' && php artisan migrate --force" "$APP_USER" || true
su -s /bin/bash -c "cd '$APP_DIR' && php artisan db:seed --force" "$APP_USER" || true

# Permissions for writable directories only
chown -R www-data:www-data "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" 2>/dev/null || true
chmod -R u+rwx "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" 2>/dev/null || true

