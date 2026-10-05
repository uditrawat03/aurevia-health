#!/usr/bin/env sh
set -eu

database='aurevia_health_rc_performance'
postgres='aurevia-health-postgres'
api='aurevia-health-api'
postgres_user="$(docker exec "$postgres" printenv POSTGRES_USER)"

cleanup() {
  docker exec "$postgres" dropdb -U "$postgres_user" --if-exists "$database" >/dev/null 2>&1 || true
}
trap cleanup EXIT

echo 'Running V1 GraphQL performance baseline against isolated PostgreSQL...'
echo 'Metrics are local application-layer baselines, not production SLAs.'

docker exec "$postgres" dropdb -U "$postgres_user" --if-exists "$database"
docker exec "$postgres" createdb -U "$postgres_user" "$database"

docker exec \
  -e APP_ENV=testing \
  -e APP_DEBUG=false \
  -e DB_URL= \
  -e DB_DATABASE="$database" \
  -e CACHE_STORE=array \
  -e SESSION_DRIVER=array \
  -e QUEUE_CONNECTION=sync \
  -e MAIL_MAILER=array \
  -e LIGHTHOUSE_SCHEMA_CACHE_ENABLE=false \
  -e LIGHTHOUSE_SECURITY_DISABLE_INTROSPECTION=false \
  "$api" \
  php vendor/bin/phpunit \
  --no-configuration \
  --bootstrap vendor/autoload.php \
  --colors=never \
  tests/Performance/V1PerformanceBaseline.php

echo 'Performance baseline completed. Record each AUREVIA_PERF line in docs/V1_RELEASE_CANDIDATE_EVIDENCE.md.'
