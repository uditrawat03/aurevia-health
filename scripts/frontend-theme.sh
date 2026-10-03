#!/usr/bin/env sh
set -eu

ROOT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)"
cd "$ROOT_DIR"

if [ ! -f apps/web/package.json ]; then
  echo "apps/web is missing. Bootstrap the Angular application first." >&2
  exit 1
fi

if ! docker inspect aurevia-health-web >/dev/null 2>&1; then
  echo "aurevia-health-web is not running. Start the stack first with scripts/docker.sh up" >&2
  exit 1
fi

echo "Installing Tailwind CSS 4.3 and PostCSS integration..."
docker exec aurevia-health-web npm install --save-dev tailwindcss@4.3.0 @tailwindcss/postcss@4.3.0 postcss@^8.5.0

echo "Applying Aurevia Health frontend foundation..."
docker exec -w /workspace aurevia-health-web node scripts/setup-aurevia-theme.mjs

echo
echo "Aurevia Health theme setup complete."
echo "Angular watch mode should rebuild automatically."
echo "Open http://localhost:4200"
