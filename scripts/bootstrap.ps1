$ErrorActionPreference = "Stop"

$Root = Split-Path -Parent $PSScriptRoot
Set-Location $Root

function Require-Command {
    param([string]$Name)
    if (-not (Get-Command $Name -ErrorAction SilentlyContinue)) {
        throw "Required command '$Name' was not found in PATH."
    }
}

Require-Command php
Require-Command composer
Require-Command node
Require-Command npm

Write-Host "==> Creating Laravel 13 API"

if (Test-Path "apps/api") {
    if ((Get-ChildItem "apps/api" -Force -ErrorAction SilentlyContinue | Measure-Object).Count -gt 0) {
        throw "apps/api already exists and is not empty. Bootstrap stops to avoid overwriting work."
    }
}
Remove-Item "apps/api" -Force -Recurse -ErrorAction SilentlyContinue

composer create-project laravel/laravel:^13.0 apps/api

Push-Location "apps/api"
composer require laravel/sanctum laravel/horizon "nuwave/lighthouse:^6.71"
php artisan horizon:install

$domainDirs = @(
    "Organization","Identity","Patient","Consent","Scheduling","Encounter",
    "Clinical","Orders","Medication","Laboratory","Imaging","Surgery","Pharmacy",
    "Billing","Coverage","Authorization","HIM","Inventory","Workforce",
    "Terminology","Interoperability","Workflow","Notification","Audit","Analytics","AI"
)

New-Item -ItemType Directory -Path "app/Domains" -Force | Out-Null
foreach ($domain in $domainDirs) {
    New-Item -ItemType Directory -Path "app/Domains/$domain" -Force | Out-Null
}
New-Item -ItemType Directory -Path "app/CountryProfiles/Core" -Force | Out-Null
New-Item -ItemType Directory -Path "app/Application" -Force | Out-Null
New-Item -ItemType Directory -Path "app/Infrastructure" -Force | Out-Null
Pop-Location

Write-Host "==> Creating Angular 22 web application"

if (Test-Path "apps/web") {
    if ((Get-ChildItem "apps/web" -Force -ErrorAction SilentlyContinue | Measure-Object).Count -gt 0) {
        throw "apps/web already exists and is not empty. Bootstrap stops to avoid overwriting work."
    }
}
Remove-Item "apps/web" -Force -Recurse -ErrorAction SilentlyContinue

Push-Location "apps"
npx -y @angular/cli@22 new web `
    --routing `
    --style=scss `
    --standalone `
    --strict `
    --zoneless `
    --test-runner=vitest `
    --skip-git `
    --package-manager=npm
Pop-Location

Write-Host ""
Write-Host "Bootstrap complete."
Write-Host "Next:"
Write-Host "  docker compose -f infrastructure/docker/compose.yml up -d"
Write-Host "  configure apps/api/.env for PostgreSQL and Redis"
Write-Host "  cd apps/api; php artisan migrate; php artisan serve"
Write-Host "  cd apps/web; npm start"
