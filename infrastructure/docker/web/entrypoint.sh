#!/usr/bin/env sh
set -eu

if [ ! -f package.json ]; then
    echo >&2 "Aurevia Health web app has not been bootstrapped. Expected apps/web/package.json."
    exit 1
fi

if [ ! -x node_modules/.bin/ng ]; then
    echo "Installing Angular dependencies..."
    if [ -f package-lock.json ]; then npm ci; else npm install; fi
fi

exec "$@"
