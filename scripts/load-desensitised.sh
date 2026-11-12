#!/usr/bin/env bash
# Load the desensitised DB + assets into the local docker compose stack.
# Usage: scripts/load-desensitised.sh [date] [aws-profile]
#   date        folder under s3://cita-assets-db-storage/desensitised/ (default 2026-11-09)
#   aws-profile your SSO profile for citanz-nonprod (default citanz-nonprod)
# Only the desensitised copy is ever downloaded. Files land in .local/ (not in git).
set -euo pipefail
C="docker compose${COMPOSE_FILES:+ $COMPOSE_FILES}"
cd "$(dirname "$0")/.."
DATE=${1:-2026-11-09}
PROFILE=${2-citanz-nonprod}   # pass "" to use the instance role (on the EC2)
DIR=.local/desensitised/$DATE

mkdir -p "$DIR"
echo "== download s3://cita-assets-db-storage/desensitised/$DATE/"
aws s3 cp ${PROFILE:+--profile "$PROFILE"} --only-show-errors --recursive \
  "s3://cita-assets-db-storage/desensitised/$DATE/" "$DIR/"
(cd "$DIR" && sha256sum -c SHA256SUMS)

echo "== database"
$C up -d --wait database
$C exec -T database sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot -e "DROP DATABASE IF EXISTS \`$MYSQL_DATABASE\`; CREATE DATABASE \`$MYSQL_DATABASE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"'
gunzip -c "$DIR/citanz-desensitised.sql.gz" \
  | $C exec -T database sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot --default-character-set=utf8mb4 "$MYSQL_DATABASE"'

echo "== assets (~430 MB)"
$C up -d web
$C exec -T web sh -c "tar xzf $DIR/citanz-assets-desensitised.tgz -C public --overwrite && chown -R www-data:www-data public/assets"

echo "== dev/build"
$C exec -T web vendor/bin/sake dev/build flush=all >/dev/null
echo "done -> http://localhost:8080   (admin login: SS_DEFAULT_ADMIN_* in your .env)"
