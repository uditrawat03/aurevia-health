#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

for cmd in php composer node npm; do
  command -v "$cmd" >/dev/null 2>&1 || {
    echo "Required command '$cmd' was not found in PATH." >&2
    exit 1
  }
done

echo "==> Creating Laravel 13 API"

if [ -d apps/api ] && [ "$(find apps/api -mindepth 1 -maxdepth 1 | head -n 1)" ]; then
  echo "apps/api already exists and is not empty. Bootstrap stops to avoid overwriting work." >&2
  exit 1
fi

rm -rf apps/api
composer create-project laravel/laravel:^13.0 apps/api

pushd apps/api >/dev/null
php artisan install:api
composer require laravel/horizon
php artisan horizon:install

mkdir -p app/Domains/{Organization,Identity,Patient,Consent,Scheduling,Encounter,Clinical,Orders,Medication,Laboratory,Imaging,Surgery,Pharmacy,Billing,Coverage,Authorization,HIM,Inventory,Workforce,Terminology,Interoperability,Workflow,Notification,Audit,Analytics,AI}
mkdir -p app/CountryProfiles/Core app/Application app/Infrastructure
popd >/dev/null

echo "==> Creating Angular 22 web application"

if [ -d apps/web ] && [ "$(find apps/web -mindepth 1 -maxdepth 1 | head -n 1)" ]; then
  echo "apps/web already exists and is not empty. Bootstrap stops to avoid overwriting work." >&2
  exit 1
fi

rm -rf apps/web
pushd apps >/dev/null
npx -y @angular/cli@22 new web \
  --routing \
  --style=scss \
  --standalone \
  --strict \
  --zoneless \
  --test-runner=vitest \
  --skip-git \
  --package-manager=npm
popd >/dev/null

cat <<'EOF'

Bootstrap complete.

Next:
  docker compose -f infrastructure/docker/compose.yml up -d
  configure apps/api/.env for PostgreSQL and Redis
  cd apps/api && php artisan migrate && php artisan serve
  cd apps/web && npm start
EOF
