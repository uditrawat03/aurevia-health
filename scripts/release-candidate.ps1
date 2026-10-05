$ErrorActionPreference = 'Stop'

function Invoke-Gate([string]$Name, [scriptblock]$Command) {
    Write-Host "`n==> $Name" -ForegroundColor Cyan
    & $Command
    if ($LASTEXITCODE -ne 0) {
        throw "$Name failed with exit code $LASTEXITCODE."
    }
}

Invoke-Gate 'Clear Laravel test log' { docker exec aurevia-health-api sh -lc ': > storage/logs/laravel.log' }
Invoke-Gate 'Validate GraphQL schema' { docker exec aurevia-health-api php artisan lighthouse:validate-schema }
Invoke-Gate 'Run backend tests' { docker exec aurevia-health-api php artisan test }
Invoke-Gate 'Audit Composer dependencies' { docker exec aurevia-health-api composer audit }
Invoke-Gate 'Run frontend tests' { docker exec aurevia-health-web npm test -- --watch=false }
Invoke-Gate 'Build frontend production bundle' { docker exec aurevia-health-web npm run build }
Invoke-Gate 'Audit production npm dependencies' { docker exec aurevia-health-web npm audit --omit=dev --audit-level=high }

Write-Host "`n==> Verify test log has no ERROR entries" -ForegroundColor Cyan
$errors = docker exec aurevia-health-api sh -lc "grep -n 'testing.ERROR' storage/logs/laravel.log || true"
if ($errors) {
    $errors | Write-Host
    throw 'Laravel test log contains testing.ERROR entries.'
}
Write-Host 'No testing.ERROR entries found.'

Invoke-Gate 'Check patch whitespace' { git diff --check }

Write-Host "`nRelease-candidate automated gates passed." -ForegroundColor Green
Write-Host 'Run scripts/backup-restore-test.ps1 and complete docs/V1_RELEASE_CANDIDATE_EVIDENCE.md before tagging an RC.'
