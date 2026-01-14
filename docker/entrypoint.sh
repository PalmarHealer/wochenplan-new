#!/usr/bin/env bash
set -euo pipefail

APP_DIR=${APP_DIR:-/var/www/html}
APP_USER=${APP_USER:-app}

# Ensure runtime directories
mkdir -p /run/php /var/run/sshd
chown www-data:www-data /run/php

# Set Chromium path from Puppeteer if not already set
if [ -z "${LARAVEL_PDF_CHROME_PATH:-}" ]; then
  # Try to get the Chrome path from Puppeteer's cache directory
  PUPPETEER_CACHE_DIR=${PUPPETEER_CACHE_DIR:-/usr/local/share/puppeteer}

  # Find the chrome executable in the Puppeteer cache
  if [ -d "$PUPPETEER_CACHE_DIR" ]; then
    CHROMIUM_PATH=$(find "$PUPPETEER_CACHE_DIR" -type f -name "chrome" -executable 2>/dev/null | head -n 1)
  fi

  # If not found, try using puppeteer's executablePath() method
  if [ -z "$CHROMIUM_PATH" ]; then
    CHROMIUM_PATH=$(node -e "console.log(require('puppeteer').executablePath())" 2>/dev/null || echo "")
  fi

  if [ -n "$CHROMIUM_PATH" ] && [ -f "$CHROMIUM_PATH" ]; then
    export LARAVEL_PDF_CHROME_PATH="$CHROMIUM_PATH"
    export PUPPETEER_EXECUTABLE_PATH="$CHROMIUM_PATH"
    echo "Set LARAVEL_PDF_CHROME_PATH to: $CHROMIUM_PATH"

    # Add to PHP-FPM environment (official PHP image path)
    {
      echo "env[LARAVEL_PDF_CHROME_PATH] = $CHROMIUM_PATH"
      echo "env[PUPPETEER_EXECUTABLE_PATH] = $CHROMIUM_PATH"
      echo "env[PUPPETEER_CACHE_DIR] = $PUPPETEER_CACHE_DIR"
    } >> /usr/local/etc/php-fpm.d/www.conf
  else
    echo "WARNING: Chrome executable not found. PDF generation may not work." >&2
  fi
fi

# Setup SSH authorized_keys from URL if provided; otherwise disable sshd
SSH_AUTHORIZED_KEYS_URL=${SSH_AUTHORIZED_KEYS_URL:-}
SSHD_SUP_CONF="/etc/supervisor/conf.d/sshd.conf"
ROOT_HOME="/root"

if [ -n "${SSH_AUTHORIZED_KEYS_URL}" ]; then
  mkdir -p "${ROOT_HOME}/.ssh"
  chmod 700 "${ROOT_HOME}/.ssh"
  if curl -fsSL "${SSH_AUTHORIZED_KEYS_URL}" -o "${ROOT_HOME}/.ssh/authorized_keys"; then
    # Ensure file permissions
    chmod 600 "${ROOT_HOME}/.ssh/authorized_keys"
    chown -R root:root "${ROOT_HOME}/.ssh"
    if [ ! -s "${ROOT_HOME}/.ssh/authorized_keys" ]; then
      echo "authorized_keys is empty. Disabling sshd." >&2
      rm -f "$SSHD_SUP_CONF" || true
    fi
  else
    echo "Failed to fetch SSH authorized keys from URL. Disabling sshd." >&2
    rm -f "$SSHD_SUP_CONF" || true
  fi
else
  echo "SSH_AUTHORIZED_KEYS_URL not set. Disabling sshd." >&2
  rm -f "$SSHD_SUP_CONF" || true
fi

# Ensure .env exists
if [ ! -f "$APP_DIR/.env" ] && [ -f "$APP_DIR/.env.example" ]; then
  cp "$APP_DIR/.env.example" "$APP_DIR/.env"
fi

# Ensure proper permissions and ownership for writable directories only
mkdir -p "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
chown -R www-data:www-data "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
chmod -R u+rwx "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
if [ -f "$APP_DIR/.env" ]; then
  chown www-data:www-data "$APP_DIR/.env"
  chmod 664 "$APP_DIR/.env"
fi

# Run php commands
/usr/local/bin/db_setup.sh || true

# Start all services via supervisord
exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
