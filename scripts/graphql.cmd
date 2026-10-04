@echo off
setlocal

set CONTAINER=aurevia-health-api

docker exec %CONTAINER% composer update nuwave/lighthouse --with-all-dependencies --no-interaction --no-progress
if errorlevel 1 exit /b %errorlevel%

docker exec %CONTAINER% php artisan optimize:clear
if errorlevel 1 exit /b %errorlevel%

docker exec %CONTAINER% php artisan lighthouse:validate-schema
if errorlevel 1 exit /b %errorlevel%

echo GraphQL dependencies installed and schema validated.
