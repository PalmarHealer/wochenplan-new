#!/bin/sh
# Run logrotate once an hour. Rotation is size-based, so an hourly tick is
# enough to keep the log volumes bounded even under heavy request load.
set -eu

STATE_FILE=/var/lib/logrotate/wochenplan.status
mkdir -p "$(dirname "$STATE_FILE")"

while true; do
    logrotate -s "$STATE_FILE" /etc/logrotate.d/wochenplan || true
    sleep 3600
done
