$ErrorActionPreference = 'Stop'

$container = 'aurevia-health-postgres'
$restoreDb = 'aurevia_health_rc_restore_check'
$backup = '/tmp/aurevia-health-rc.dump'

function Invoke-DockerChecked {
    param(
        [Parameter(Mandatory = $true)]
        [string[]] $DockerArgs,
        [Parameter(Mandatory = $true)]
        [string] $FailureMessage
    )

    & docker @DockerArgs
    if ($LASTEXITCODE -ne 0) {
        throw "$FailureMessage (exit code $LASTEXITCODE)."
    }
}

Write-Host 'Running local synthetic backup/restore rehearsal...' -ForegroundColor Cyan

$postgresUser = (& docker exec $container printenv POSTGRES_USER).Trim()
if ($LASTEXITCODE -ne 0 -or [string]::IsNullOrWhiteSpace($postgresUser)) {
    throw 'Could not resolve POSTGRES_USER from the PostgreSQL container.'
}

$sourceDb = (& docker exec $container printenv POSTGRES_DB).Trim()
if ($LASTEXITCODE -ne 0 -or [string]::IsNullOrWhiteSpace($sourceDb)) {
    throw 'Could not resolve POSTGRES_DB from the PostgreSQL container.'
}

try {
    & docker exec $container rm -f $backup | Out-Null

    Invoke-DockerChecked `
        -DockerArgs @('exec', $container, 'dropdb', '-U', $postgresUser, '--if-exists', $restoreDb) `
        -FailureMessage 'Could not clean the restore rehearsal database'

    Invoke-DockerChecked `
        -DockerArgs @('exec', $container, 'pg_dump', '-U', $postgresUser, '-d', $sourceDb, '-Fc', '-f', $backup) `
        -FailureMessage 'Could not create the rehearsal database backup'

    Invoke-DockerChecked `
        -DockerArgs @('exec', $container, 'createdb', '-U', $postgresUser, $restoreDb) `
        -FailureMessage 'Could not create the restore rehearsal database'

    Invoke-DockerChecked `
        -DockerArgs @('exec', $container, 'pg_restore', '-U', $postgresUser, '-d', $restoreDb, '--no-owner', '--no-privileges', $backup) `
        -FailureMessage 'Could not restore the rehearsal database backup'

    $migrationCount = (& docker exec $container psql -U $postgresUser -d $restoreDb -Atc 'select count(*) from migrations').Trim()
    if ($LASTEXITCODE -ne 0) {
        throw 'Could not read migration history from the restored database.'
    }

    [int] $parsedMigrationCount = 0
    if (-not [int]::TryParse($migrationCount, [ref] $parsedMigrationCount) -or $parsedMigrationCount -lt 1) {
        throw "Restored database has invalid migration history count '$migrationCount'."
    }

    foreach ($table in @('organizations', 'patients', 'audit_events')) {
        & docker exec $container psql -U $postgresUser -d $restoreDb -Atc "select count(*) from $table" | Out-Null
        if ($LASTEXITCODE -ne 0) {
            throw "Restored database table '$table' is not queryable."
        }
    }

    Write-Host "Backup/restore rehearsal passed ($parsedMigrationCount migrations restored)." -ForegroundColor Green
}
finally {
    & docker exec $container dropdb -U $postgresUser --if-exists $restoreDb | Out-Null
    if ($LASTEXITCODE -ne 0) {
        Write-Warning "Could not remove restore rehearsal database '$restoreDb'."
    }

    & docker exec $container rm -f $backup | Out-Null
    if ($LASTEXITCODE -ne 0) {
        Write-Warning "Could not remove rehearsal backup '$backup'."
    }
}
