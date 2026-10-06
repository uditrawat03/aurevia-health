@echo off
setlocal
set "ROOT=%~dp0.."
set "COMPOSE_FILE=%ROOT%\infrastructure\docker\compose.e2e.yml"
set "ARTIFACT_DIR=%ROOT%\artifacts\browser-e2e"

if not exist "%ARTIFACT_DIR%" mkdir "%ARTIFACT_DIR%"

echo Cleaning previous Aurevia Health browser E2E stack...
docker compose -f "%COMPOSE_FILE%" down --volumes --remove-orphans >nul 2>&1

echo Running Aurevia Health browser E2E stack...
docker compose -f "%COMPOSE_FILE%" up --build --abort-on-container-exit --exit-code-from browser-e2e browser-e2e
set "EXIT_CODE=%ERRORLEVEL%"

if not "%EXIT_CODE%"=="0" (
  echo Browser E2E failed. Capturing service status and logs before teardown...
  docker compose -f "%COMPOSE_FILE%" ps -a
  docker compose -f "%COMPOSE_FILE%" logs --no-color postgres redis api-deps api web-deps web horizon browser-e2e
)

echo Tearing down Aurevia Health browser E2E stack...
docker compose -f "%COMPOSE_FILE%" down --volumes --remove-orphans

exit /b %EXIT_CODE%
