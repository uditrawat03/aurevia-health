#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
COMPOSE_FILE="$ROOT/infrastructure/docker/compose.yml"
ACTION="${1:-status}"
case "$ACTION" in
  infra) docker compose -f "$COMPOSE_FILE" up -d postgres redis ;;
  up) docker compose -f "$COMPOSE_FILE" --profile app up -d --build ;;
  build) docker compose -f "$COMPOSE_FILE" --profile app build ;;
  down) docker compose -f "$COMPOSE_FILE" --profile app down ;;
  status) docker compose -f "$COMPOSE_FILE" --profile app ps ;;
  logs) docker compose -f "$COMPOSE_FILE" --profile app logs -f ;;
  migrate) docker compose -f "$COMPOSE_FILE" --profile app exec api php artisan migrate ;;
  test)
    docker compose -f "$COMPOSE_FILE" --profile app exec api php artisan test
    docker compose -f "$COMPOSE_FILE" --profile app exec web npm test -- --watch=false
    ;;
  *) echo "Usage: scripts/docker.sh <infra|up|build|down|status|logs|migrate|test>" >&2; exit 2 ;;
esac
