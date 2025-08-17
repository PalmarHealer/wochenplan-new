#!/usr/bin/env bash
set -euo pipefail

APP_DIR=${APP_DIR:-/var/www/html}
APP_USER=${APP_USER:-app}
APP_HOME="/home/${APP_USER}"

# Ensure runtime directories
mkdir -p /run/php /var/run/sshd

# Setup SSH authorized_keys from URL if provided; otherwise disable sshd
SSH_AUTHORIZED_KEYS_URL=${SSH_AUTHORIZED_KEYS_URL:-}
SSHD_SUP_CONF="/etc/supervisor/conf.d/sshd.conf"

if [ -n "${SSH_AUTHORIZED_KEYS_URL}" ]; then
  mkdir -p "${APP_HOME}/.ssh"
  chmod 700 "${APP_HOME}/.ssh"
  if curl -fsSL "${SSH_AUTHORIZED_KEYS_URL}" -o "${APP_HOME}/.ssh/authorized_keys"; then
    # Ensure file permissions
    chmod 600 "${APP_HOME}/.ssh/authorized_keys"
    chown -R ${APP_USER}:${APP_USER} "${APP_HOME}/.ssh"
    if [ ! -s "${APP_HOME}/.ssh/authorized_keys" ]; then
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

# Ensure proper permissions for Laravel writable dirs
mkdir -p "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
chown -R ${APP_USER}:www-data "$APP_DIR" "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
chmod -R ug+rwX "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

# Ensure .env exists
if [ ! -f "$APP_DIR/.env" ] && [ -f "$APP_DIR/.env.example" ]; then
  cp "$APP_DIR/.env.example" "$APP_DIR/.env"
fi

# Run first-run initialization if needed
/usr/local/bin/first-run.sh || true

# Start all services via supervisord
exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
