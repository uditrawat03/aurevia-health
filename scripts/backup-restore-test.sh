#!/usr/bin/env bash
set -euo pipefail

container='aurevia-health-postgres'
restore_db='aurevia_health_rc_restore_check'

echo 'Running local synthetic backup/restore rehearsal...'
docker exec "$container" sh -lc "
set -eu
backup=/tmp/aurevia-health-rc.dump
user=\"\$POSTGRES_USER\"
source_db=\"\$POSTGRES_DB\"
restore_db='$restore_db'
rm -f \"\$backup\"
dropdb -U \"\$user\" --if-exists \"\$restore_db\"
pg_dump -U \"\$user\" -d \"\$source_db\" -Fc -f \"\$backup\"
createdb -U \"\$user\" \"\$restore_db\"
pg_restore -U \"\$user\" -d \"\$restore_db\" --no-owner --no-privileges \"\$backup\"
migration_count=\$(psql -U \"\$user\" -d \"\$restore_db\" -Atc 'select count(*) from migrations')
if [ \"\$migration_count\" -lt 1 ]; then
  echo 'Restored database does not contain migration history.' >&2
  exit 1
fi
psql -U \"\$user\" -d \"\$restore_db\" -Atc 'select count(*) from organizations' >/dev/null
psql -U \"\$user\" -d \"\$restore_db\" -Atc 'select count(*) from patients' >/dev/null
psql -U \"\$user\" -d \"\$restore_db\" -Atc 'select count(*) from audit_events' >/dev/null
dropdb -U \"\$user\" \"\$restore_db\"
rm -f \"\$backup\"
echo \"Backup/restore rehearsal passed (\$migration_count migrations restored).\"
"
