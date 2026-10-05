#!/usr/bin/env bash
set -euo pipefail

database='aurevia_health_rc_migration_check'
postgres='aurevia-health-postgres'
api='aurevia-health-api'

cleanup() {
  docker exec "$postgres" sh -lc "dropdb -U \"\$POSTGRES_USER\" --if-exists '$database'" >/dev/null 2>&1 || true
}
trap cleanup EXIT

docker exec "$postgres" sh -lc "dropdb -U \"\$POSTGRES_USER\" --if-exists '$database' && createdb -U \"\$POSTGRES_USER\" '$database'"
docker exec -e DB_DATABASE="$database" "$api" php artisan migrate --force
docker exec -e DB_DATABASE="$database" "$api" php artisan migrate:status
echo 'Migration rehearsal passed.'
