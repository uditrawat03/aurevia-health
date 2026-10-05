#!/usr/bin/env bash
set -euo pipefail

run_gate() {
  printf '\n==> %s\n' "$1"
  shift
  "$@"
}

run_gate "Clear Laravel test log" docker exec aurevia-health-api sh -lc ': > storage/logs/laravel.log'
run_gate "Validate GraphQL schema" docker exec aurevia-health-api php artisan lighthouse:validate-schema
run_gate "Run backend tests" docker exec aurevia-health-api php artisan test
run_gate "Audit Composer dependencies" docker exec aurevia-health-api composer audit
run_gate "Run frontend tests" docker exec aurevia-health-web npm test -- --watch=false
run_gate "Build frontend production bundle" docker exec aurevia-health-web npm run build
run_gate "Audit production npm dependencies" docker exec aurevia-health-web npm audit --omit=dev --audit-level=high

printf '\n==> Verify test log has no ERROR entries\n'
errors="$(docker exec aurevia-health-api sh -lc "grep -n 'testing.ERROR' storage/logs/laravel.log || true")"
if [[ -n "$errors" ]]; then
  printf '%s\n' "$errors"
  echo 'Laravel test log contains testing.ERROR entries.' >&2
  exit 1
fi
echo 'No testing.ERROR entries found.'

run_gate "Check patch whitespace" git diff --check
printf '\nRelease-candidate automated gates passed.\n'
echo 'Run scripts/backup-restore-test.sh and complete docs/V1_RELEASE_CANDIDATE_EVIDENCE.md before tagging an RC.'
