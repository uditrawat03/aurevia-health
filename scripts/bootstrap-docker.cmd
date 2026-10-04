@echo off
setlocal

set "ROOT=%~dp0.."
set "COMPOSE_FILE=%ROOT%\infrastructure\docker\compose.yml"

if exist "%ROOT%\apps\api\composer.json" (
  echo apps\api already contains a Laravel application. Bootstrap stopped.
  exit /b 1
)

if exist "%ROOT%\apps\web\package.json" (
  echo apps\web already contains an Angular application. Bootstrap stopped.
  exit /b 1
)

echo ==^> Building PHP tooling image
docker compose -f "%COMPOSE_FILE%" --profile tools build php-tooling
if errorlevel 1 exit /b %ERRORLEVEL%

echo ==^> Creating Laravel 13 API
docker compose -f "%COMPOSE_FILE%" --profile tools run --rm php-tooling composer create-project laravel/laravel:^13.0 api
if errorlevel 1 exit /b %ERRORLEVEL%

echo ==^> Installing Sanctum, Lighthouse, and Horizon support
docker compose -f "%COMPOSE_FILE%" --profile tools run --rm php-tooling sh -lc "cd api && composer require laravel/sanctum laravel/horizon 'nuwave/lighthouse:^6.71' && php artisan horizon:install && mkdir -p app/Domains app/CountryProfiles/Core app/Application app/Infrastructure && for d in Organization Identity Patient Consent Scheduling Encounter Clinical Orders Medication Laboratory Imaging Surgery Pharmacy Billing Coverage Authorization HIM Inventory Workforce Terminology Interoperability Workflow Notification Audit Analytics AI; do mkdir -p \"app/Domains/$d\"; done"
if errorlevel 1 exit /b %ERRORLEVEL%

echo ==^> Creating Angular 22 application
docker compose -f "%COMPOSE_FILE%" --profile tools run --rm node-tooling npx -y @angular/cli@22 new web --routing --style=scss --standalone --strict --zoneless --test-runner=vitest --skip-git --package-manager=npm
if errorlevel 1 exit /b %ERRORLEVEL%

echo.
echo Docker bootstrap complete.
echo Next: scripts\docker.cmd up
echo Then: scripts\docker.cmd migrate
