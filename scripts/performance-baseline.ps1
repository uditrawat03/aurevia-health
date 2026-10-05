$ErrorActionPreference = 'Stop'

$database = 'aurevia_health_rc_performance'
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

Write-Host 'Running V1 GraphQL performance baseline against isolated PostgreSQL...' -ForegroundColor Cyan
Write-Host 'Metrics are local application-layer baselines, not production SLAs.' -ForegroundColor DarkGray

try {
    Invoke-DockerChecked `
        -DockerArgs @('exec', $postgres, 'dropdb', '-U', $postgresUser, '--if-exists', $database) `
        -FailureMessage 'Could not clean the performance baseline database'

    Invoke-DockerChecked `
        -DockerArgs @('exec', $postgres, 'createdb', '-U', $postgresUser, $database) `
        -FailureMessage 'Could not create the performance baseline database'

    Invoke-DockerChecked `
        -DockerArgs @(
            'exec',
            '-e', 'APP_ENV=testing',
            '-e', 'APP_DEBUG=false',
            '-e', 'DB_URL=',
            '-e', "DB_DATABASE=$database",
            '-e', 'CACHE_STORE=array',
            '-e', 'SESSION_DRIVER=array',
            '-e', 'QUEUE_CONNECTION=sync',
            '-e', 'MAIL_MAILER=array',
            '-e', 'LIGHTHOUSE_SCHEMA_CACHE_ENABLE=false',
            '-e', 'LIGHTHOUSE_SECURITY_DISABLE_INTROSPECTION=false',
            $api,
            'php', 'vendor/bin/phpunit',
            '--no-configuration',
            '--bootstrap', 'vendor/autoload.php',
            '--colors=never',
            'tests/Performance/V1PerformanceBaseline.php'
        ) `
        -FailureMessage 'Performance baseline failed'

    Write-Host 'Performance baseline completed. Record each AUREVIA_PERF line in docs/V1_RELEASE_CANDIDATE_EVIDENCE.md.' -ForegroundColor Green
}
finally {
    & docker exec $postgres dropdb -U $postgresUser --if-exists $database | Out-Null
    if ($LASTEXITCODE -ne 0) {
        Write-Warning "Could not remove performance baseline database '$database'."
    }
}
