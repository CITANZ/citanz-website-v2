#!/usr/bin/env bash
# Install the site's daily tasks as systemd timers on the EC2 host.
# Same tasks and time as the crontab on SiteHost (2026-11-13, from Leo):
#   0 8 * * *  sake dev/tasks/expiry-reminder
#   0 8 * * *  sake dev/tasks/expire-students
# Amazon Linux 2023 has no cron by default, so this uses systemd timers.
# Time zone is Pacific/Auckland (assumed for SiteHost; not confirmed).
#
# Usage (as root, from the repo root):
#   scripts/install-cron.sh "-f docker-compose.yml -f docker-compose.dev.yml"
# Logs:   journalctl -u 'citanz-task@*'
# Status: systemctl list-timers 'citanz-*'
set -euo pipefail
cd "$(dirname "$0")/.."
COMPOSE_FILES=${1:?compose file flags, e.g. "-f docker-compose.yml -f docker-compose.dev.yml"}
DIR=$(pwd)
DOCKER=$(command -v docker)
TASKS="expiry-reminder expire-students"
WHEN="*-*-* 08:00:00 Pacific/Auckland"

cat > /etc/systemd/system/citanz-task@.service <<UNIT
[Unit]
Description=CITANZ daily task %i
After=docker.service

[Service]
Type=oneshot
WorkingDirectory=$DIR
ExecStart=$DOCKER compose $COMPOSE_FILES exec -T web vendor/bin/sake dev/tasks/%i
UNIT

for t in $TASKS; do
  cat > /etc/systemd/system/citanz-$t.timer <<UNIT
[Unit]
Description=Run CITANZ task $t daily (as on SiteHost)

[Timer]
OnCalendar=$WHEN
Unit=citanz-task@$t.service

[Install]
WantedBy=timers.target
UNIT
done

systemctl daemon-reload
for t in $TASKS; do systemctl enable --now citanz-$t.timer >/dev/null; done
systemctl list-timers 'citanz-*' --no-pager
