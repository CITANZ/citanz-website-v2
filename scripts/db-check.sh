#!/usr/bin/env bash
# db-check.sh — import an old-site DB dump into a throwaway MySQL 5.7 container
# and report what is in it.
#
# RUN THIS ONLY INSIDE AWS (nonprod EC2). Raw member data never goes to a
# personal machine. The report prints counts and column NAMES only — never
# row values — so it is safe to paste into chat.
#
# Usage:
#   scripts/db-check.sh <dump.sql | dump.sql.gz> [--keep]
#
#   --keep   leave the container "citanz-dbcheck" running afterwards, so
#            scripts/desensitise.sql can be applied to it (see bottom).
#
# Requires: docker. Pulls mysql:5.7 if needed.

set -euo pipefail

DUMP="${1:-}"
KEEP="${2:-}"
NAME=citanz-dbcheck
IMAGE=mysql:5.7
EXPECTED_CUSTOMERS=449   # [B · Gabriel/Leo, 2026-11] approx. member count

[[ -f "$DUMP" ]] || { echo "usage: $0 <dump.sql|dump.sql.gz> [--keep]" >&2; exit 1; }

cat_dump() { case "$DUMP" in *.gz) gzip -dc "$DUMP" ;; *) cat "$DUMP" ;; esac; }

echo "== 1. Dump file"
echo "file:    $(basename "$DUMP")"
echo "size:    $(du -h "$DUMP" | cut -f1)"
echo "sha256:  $(sha256sum "$DUMP" | cut -d' ' -f1)"
echo "header:"; cat_dump | head -5 | grep -E '^-- (MySQL dump|Host|Server version)' | sed 's/^/  /' || true
if cat_dump | tail -3 | grep -q 'Dump completed'; then
  echo "footer:  'Dump completed' present  -> dump finished normally"
else
  echo "footer:  'Dump completed' MISSING   -> dump is probably TRUNCATED"
fi
echo "CREATE TABLE statements: $(cat_dump | grep -c '^CREATE TABLE' || true)"
echo "INSERT statements:       $(cat_dump | grep -c '^INSERT INTO' || true)"
echo

# --- start throwaway MySQL 5.7 (no published port) ---------------------------
docker rm -f "$NAME" >/dev/null 2>&1 || true
PW="$(head -c 24 /dev/urandom | base64 | tr -dc 'A-Za-z0-9')"
docker run -d --name "$NAME" -e MYSQL_ROOT_PASSWORD="$PW" -e MYSQL_DATABASE=citanz \
  "$IMAGE" --character-set-server=utf8mb4 --collation-server=utf8mb4_unicode_ci \
  --max-allowed-packet=256M >/dev/null
cleanup() { [[ "$KEEP" == "--keep" ]] || docker rm -f "$NAME" >/dev/null 2>&1 || true; }
trap cleanup EXIT

q() { docker exec -i -e MYSQL_PWD="$PW" "$NAME" mysql -uroot -N -B "$@"; }

echo -n "waiting for MySQL"
for _ in $(seq 1 90); do
  if docker logs "$NAME" 2>&1 | grep -q 'MySQL init process done' && q -e 'SELECT 1' >/dev/null 2>&1; then
    echo " ok"; break; fi
  echo -n .; sleep 2
done

echo "importing..."
cat_dump | q citanz
# A dump may carry its own CREATE DATABASE/USE: pick the schema with most tables.
DB="$(q -e "SELECT table_schema FROM information_schema.tables
            WHERE table_schema NOT IN ('mysql','sys','information_schema','performance_schema')
            GROUP BY table_schema ORDER BY COUNT(*) DESC LIMIT 1")"
echo "schema:  $DB"
echo

echo "== 2. Exact row count per table (largest first)"
q "$DB" -e "SELECT CONCAT('SELECT ''', table_name, ''', COUNT(*) FROM \`', table_name, '\`;')
            FROM information_schema.tables WHERE table_schema = '$DB'" \
  | q "$DB" | sort -t$'\t' -k2,2nr | awk -F'\t' '{printf "  %-50s %8d\n", $1, $2}'
echo "tables:  $(q -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB'")"
echo

echo "== 3. Key checks"
has() { [[ "$(q -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB' AND table_name='$1'")" == 1 ]]; }
if has Cita_eCommerce_Customer; then
  N="$(q "$DB" -e 'SELECT COUNT(*) FROM Cita_eCommerce_Customer')"
  echo "  customers:            $N   (expected ~$EXPECTED_CUSTOMERS)"
  q "$DB" -e "SELECT CONCAT('  verified:             ', SUM(Verified=1)),
                     CONCAT('  students:             ', SUM(isStudent=1)),
                     CONCAT('  active (not expired): ', SUM(NeverExpire=1 OR Expiry>=CURDATE())),
                     CONCAT('  newest Created:       ', MAX(Created)),
                     CONCAT('  newest LastLoggedIn:  ', MAX(LastLoggedIn))
              FROM Cita_eCommerce_Customer" | tr '\t' '\n'
else
  echo "  !! table Cita_eCommerce_Customer NOT FOUND"
fi
for t in Cita_eCommerce_Order StudentDiscountApplication JobApplication ContactSubmission Member SiteTree_Live File; do
  has "$t" && q "$DB" -e "SELECT CONCAT('  ', RPAD('$t',28,' '), COUNT(*), ' rows, newest ', IFNULL(MAX(LastEdited),'-')) FROM \`$t\`"
done
has File && q "$DB" -e "SELECT CONCAT('  files under customers/ (student IDs): ', COUNT(*)) FROM File WHERE FileFilename LIKE 'customers/%'"
echo

echo "== 4. Columns that look like personal data or secrets (names only)"
q -e "SELECT CONCAT('  ', table_name, '.', column_name)
      FROM information_schema.columns
      WHERE table_schema = '$DB'
        AND column_name REGEXP 'mail|phone|name|surname|address|postcode|suburb|dob|birth|password|salt|hash|token|secret|apikey|api_key|wechat|linkedin|github|^ip$|clientip|cv$|coverletter|storeddetails|message|comment'
        AND column_name NOT IN ('ClassName','Name','Title','URLSegment','MenuTitle','FileHash')
      ORDER BY table_name, column_name"
echo

if [[ "$KEEP" == "--keep" ]]; then
  cat <<EOF
== Container kept: $NAME  (schema: $DB)
Desensitise, then export (raw data never leaves this EC2):

  # optional: bcrypt hash for the test accounts' password
  HASH=\$(docker run --rm php:7.4-cli php -r 'echo password_hash(\$argv[1], PASSWORD_DEFAULT);' 'CHOOSE-A-TEST-PASSWORD')
  { echo "SET @test_pw_hash='\$HASH';"; cat scripts/desensitise.sql; } \\
    | docker exec -i $NAME sh -c 'MYSQL_PWD="\$MYSQL_ROOT_PASSWORD" mysql -uroot $DB'

  docker exec $NAME sh -c 'MYSQL_PWD="\$MYSQL_ROOT_PASSWORD" mysqldump -uroot --single-transaction $DB' \\
    | gzip > citanz-desensitised-\$(date +%F).sql.gz

  docker rm -f $NAME     # when done
EOF
fi
