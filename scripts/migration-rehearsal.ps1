$ErrorActionPreference = 'Stop'

$database = 'aurevia_health_rc_migration_check'
$postgres = 'aurevia-health-postgres'
$api = 'aurevia-health-api'

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

$postgresUser = (& docker exec $postgres printenv POSTGRES_USER).Trim()
if ($LASTEXITCODE -ne 0 -or [string]::IsNullOrWhiteSpace($postgresUser)) {
    throw 'Could not resolve POSTGRES_USER from the PostgreSQL container.'
}

try {
    Invoke-DockerChecked `
        -DockerArgs @('exec', $postgres, 'dropdb', '-U', $postgresUser, '--if-exists', $database) `
        -FailureMessage 'Could not clean the migration rehearsal database'

    Invoke-DockerChecked `
        -DockerArgs @('exec', $postgres, 'createdb', '-U', $postgresUser, $database) `
        -FailureMessage 'Could not create migration rehearsal database'

    Invoke-DockerChecked `
        -DockerArgs @('exec', '-e', "DB_DATABASE=$database", $api, 'php', 'artisan', 'migrate', '--force') `
        -FailureMessage 'Migration rehearsal failed'

    Invoke-DockerChecked `
        -DockerArgs @('exec', '-e', "DB_DATABASE=$database", $api, 'php', 'artisan', 'migrate:status') `
        -FailureMessage 'Migration status check failed'

    Write-Host 'Migration rehearsal passed.' -ForegroundColor Green
}
finally {
    & docker exec $postgres dropdb -U $postgresUser --if-exists $database | Out-Null
    if ($LASTEXITCODE -ne 0) {
        Write-Warning "Could not remove migration rehearsal database '$database'."
    }
}
