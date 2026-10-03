@echo off
setlocal
set "COMPOSE_FILE=%~dp0..\infrastructure\docker\compose.yml"
set "ACTION=%~1"
if "%ACTION%"=="" set "ACTION=status"
if /I "%ACTION%"=="infra" goto infra
if /I "%ACTION%"=="up" goto up
if /I "%ACTION%"=="build" goto build
if /I "%ACTION%"=="down" goto down
if /I "%ACTION%"=="status" goto status
if /I "%ACTION%"=="logs" goto logs
if /I "%ACTION%"=="migrate" goto migrate
if /I "%ACTION%"=="test" goto test
echo Usage: scripts\docker.cmd ^<infra^|up^|build^|down^|status^|logs^|migrate^|test^>
exit /b 2
:infra
docker compose -f "%COMPOSE_FILE%" up -d postgres redis
exit /b %ERRORLEVEL%
:up
docker compose -f "%COMPOSE_FILE%" --profile app up -d --build
exit /b %ERRORLEVEL%
:build
docker compose -f "%COMPOSE_FILE%" --profile app build
exit /b %ERRORLEVEL%
:down
docker compose -f "%COMPOSE_FILE%" --profile app down
exit /b %ERRORLEVEL%
:status
docker compose -f "%COMPOSE_FILE%" --profile app ps
exit /b %ERRORLEVEL%
:logs
docker compose -f "%COMPOSE_FILE%" --profile app logs -f
exit /b %ERRORLEVEL%
:migrate
docker compose -f "%COMPOSE_FILE%" --profile app exec api php artisan migrate
exit /b %ERRORLEVEL%
:test
docker compose -f "%COMPOSE_FILE%" --profile app exec api php artisan test
if errorlevel 1 exit /b %ERRORLEVEL%
docker compose -f "%COMPOSE_FILE%" --profile app exec web npm test -- --watch=false
exit /b %ERRORLEVEL%
