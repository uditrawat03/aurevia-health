#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
COMPOSE_FILE="$ROOT/infrastructure/docker/compose.yml"

if [ -f "$ROOT/apps/api/composer.json" ]; then
  echo "apps/api already contains a Laravel application. Bootstrap stopped." >&2
  exit 1
fi

if [ -f "$ROOT/apps/web/package.json" ]; then
  echo "apps/web already contains an Angular application. Bootstrap stopped." >&2
  exit 1
fi

docker compose -f "$COMPOSE_FILE" --profile tools build php-tooling

docker compose -f "$COMPOSE_FILE" --profile tools run --rm php-tooling \
  composer create-project laravel/laravel:^13.0 api

docker compose -f "$COMPOSE_FILE" --profile tools run --rm php-tooling sh -lc \
  'cd api && composer require laravel/sanctum laravel/horizon "nuwave/lighthouse:^6.71" && php artisan horizon:install && mkdir -p app/Domains app/CountryProfiles/Core app/Application app/Infrastructure && for d in Organization Identity Patient Consent Scheduling Encounter Clinical Orders Medication Laboratory Imaging Surgery Pharmacy Billing Coverage Authorization HIM Inventory Workforce Terminology Interoperability Workflow Notification Audit Analytics AI; do mkdir -p "app/Domains/$d"; done'

docker compose -f "$COMPOSE_FILE" --profile tools run --rm node-tooling \
  npx -y @angular/cli@22 new web \
  --routing \
  --style=scss \
  --standalone \
  --strict \
  --zoneless \
  --test-runner=vitest \
  --skip-git \
  --package-manager=npm

echo "Docker bootstrap complete."
echo "Next: scripts/docker.sh up"
echo "Then: scripts/docker.sh migrate"
