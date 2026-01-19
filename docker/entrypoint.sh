#!/usr/bin/env bash
set -euo pipefail

APP_DIR=${APP_DIR:-/var/www/html}
APP_USER=${APP_USER:-app}

# Ensure runtime directories
mkdir -p /run/php /run/nginx /var/run/sshd /var/lib/nginx/logs /var/lib/nginx/tmp/client_body
chown www-data:www-data /run/php
chown -R www-data:www-data /var/lib/nginx

# Note: /run/nginx ownership kept as root for nginx master process

# Source APP_KEY if it was generated
if [ -f /tmp/app_env.sh ]; then
  source /tmp/app_env.sh
  echo "Loaded generated APP_KEY from /tmp/app_env.sh"
fi

# Set Chromium path from Puppeteer if not already set
if [ -z "${LARAVEL_PDF_CHROME_PATH:-}" ]; then
  # Use system Chromium on Alpine
  if [ -f "/usr/bin/chromium-browser" ]; then
    export LARAVEL_PDF_CHROME_PATH="/usr/bin/chromium-browser"
    export PUPPETEER_EXECUTABLE_PATH="/usr/bin/chromium-browser"
    echo "Set LARAVEL_PDF_CHROME_PATH to: /usr/bin/chromium-browser"
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
  echo "SSH_AUTHORIZED_KEYS_URL not set. Disabling sshd."
  rm -f "$SSHD_SUP_CONF" || true
fi


# Run php commands (this sources APP_KEY if generated)
/usr/local/bin/db_setup.sh || true

# If APP_KEY was generated, make sure supervisor knows about it
if [ -f /tmp/app_env.sh ]; then
  source /tmp/app_env.sh
fi

# Start all services via supervisord
exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
