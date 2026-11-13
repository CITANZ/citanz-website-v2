#!/usr/bin/env bash
# Deploy/refresh dev.cita.org.nz. Runs ON the nonprod EC2 as root (via SSM).
# Usage: scripts/deploy-dev.sh [--load-data DATE]
#   --load-data DATE   also (re)load desensitised DB + assets from S3 (first run)
# Secrets come from Parameter Store /citanz/dev (instance role can read only that path).
set -euo pipefail
cd "$(dirname "$0")/.."
DOMAIN=dev.cita.org.nz
REGION=ap-southeast-2
LOAD_DATE=""
[ "${1:-}" = "--load-data" ] && LOAD_DATE=${2:?date}

param(){ aws ssm get-parameter --region $REGION --with-decryption --name "/citanz/dev/$1" --query Parameter.Value --output text; }

echo "== .env from Parameter Store"
umask 077
DBPW=$(param SS_DATABASE_PASSWORD); ADMINPW=$(param SS_DEFAULT_ADMIN_PASSWORD)
OAUTH=$(param OAUTH_ENCRYPTION_KEY); GATEPW=$(param BASIC_AUTH_PASSWORD)
cat > .env <<ENV
SS_DATABASE_CLASS="MySQLDatabase"
SS_DATABASE_SERVER="database"
SS_DATABASE_USERNAME="root"
SS_DATABASE_PASSWORD="$DBPW"
SS_DATABASE_NAME="citanz"
SS_ERROR_LOG="silverstripe.log"
SS_ENVIRONMENT_TYPE="test"
SS_BASE_URL="https://$DOMAIN"
SS_TRUSTED_PROXY_IPS="*"
SS_DEFAULT_ADMIN_USERNAME="admin"
SS_DEFAULT_ADMIN_PASSWORD="$ADMINPW"
SS_SEND_ALL_EMAILS_TO="dev-null@example.com"
SS_SEND_ALL_EMAILS_FROM="dev-null@example.com"
OAUTH_ENCRYPTION_KEY="$OAUTH"
ENV
# root + www-data (gid 33 in the web container) only
chown 0:33 .env && chmod 640 .env
mkdir -p .local
HASH=$(docker run --rm caddy:2.8 caddy hash-password --plaintext "$GATEPW")
# gate cookie value: derived from the site password, so changing the password
# in Parameter Store and redeploying also invalidates old cookies
GATE=$(printf '%s' "dev-gate:$GATEPW" | sha256sum | cut -c1-40)
sed -e "s|__DOMAIN__|$DOMAIN|" -e "s|__BASIC_USER__|citanz|" -e "s|__BASIC_HASH__|$HASH|" \
  -e "s|__GATE_TOKEN__|$GATE|g" \
  docker/Caddyfile.dev.template > .local/Caddyfile
umask 022

C="docker compose -f docker-compose.yml -f docker-compose.dev.yml"
# 2 GB RAM is tight for webpack + MySQL; add 2 GB swap once
if ! swapon --show | grep -q /swapfile; then
  [ -f /swapfile ] || { fallocate -l 2G /swapfile && chmod 600 /swapfile && mkswap /swapfile >/dev/null; }
  swapon /swapfile; grep -q /swapfile /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab
fi
echo "== build + start"
$C build web
$C up -d database web
$C exec -T web sh docker/composer-seed/seed.sh
$C exec -T web composer install --no-interaction --no-progress --no-dev 2>&1 | tail -2

if [ -n "$LOAD_DATE" ]; then
  echo "== load desensitised data $LOAD_DATE"
  COMPOSE_FILES="-f docker-compose.yml -f docker-compose.dev.yml" scripts/load-desensitised.sh "$LOAD_DATE" ""
fi

echo "== OAuth (member login)"
# signing keys for this environment type (SS_ENVIRONMENT_TYPE=test); stay on the box, git-ignored
$C exec -T web sh -c 'd=app/certs/test; [ -f $d/private.key ] || { openssl genrsa -out $d/private.key 2048 2>/dev/null && openssl rsa -in $d/private.key -pubout -out $d/public.key 2>/dev/null; }; chown www-data $d/*.key; chmod 600 $d/*.key'
# the front end is built with the OAuth client ID (public, ends up in app.js); no client secret
CLIENT_ID=$($C exec -T database sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot -N "$MYSQL_DATABASE" -e "SELECT GUID FROM OAuth_Client WHERE Revoked=0 ORDER BY ID LIMIT 1"' 2>/dev/null || true)
printf 'VUE_APP_OAUTH_CLIENT_ID=%s\nVUE_APP_OAUTH_CLIENT_SECRET=\n' "$CLIENT_ID" > .env.local
[ -n "$CLIENT_ID" ] || echo "WARNING: no OAuth client in the DB yet; member login will not work until the data is loaded"

echo "== frontend"
$C run --rm --user 0:0 node
# publish app/client/dist etc. to public/_resources
$C exec -T web composer vendor-expose 2>&1 | tail -1

$C exec -T web vendor/bin/sake dev/build flush=all >/dev/null
$C up -d --force-recreate caddy
echo "done -> https://$DOMAIN"
