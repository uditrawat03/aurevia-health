#!/usr/bin/env bash
set -euo pipefail

CONTAINER="aurevia-health-api"

docker exec "$CONTAINER" composer update nuwave/lighthouse --with-all-dependencies --no-interaction --no-progress
docker exec "$CONTAINER" php artisan optimize:clear
docker exec "$CONTAINER" php artisan lighthouse:validate-schema

echo "GraphQL dependencies installed and schema validated."
