@echo off
setlocal

cd /d "%~dp0\.."

if not exist "apps\web\package.json" (
  echo apps\web is missing. Bootstrap the Angular application first.
  exit /b 1
)

docker inspect aurevia-health-web >nul 2>&1
if errorlevel 1 (
  echo aurevia-health-web is not running. Start the stack first with scripts\docker.cmd up
  exit /b 1
)

echo Installing Tailwind CSS 4.3 and PostCSS integration...
docker exec aurevia-health-web npm install --save-dev tailwindcss@4.3.0 @tailwindcss/postcss@4.3.0 postcss@^8.5.0
if errorlevel 1 exit /b 1

echo Applying Aurevia Health frontend foundation...
docker exec -w /workspace aurevia-health-web node scripts/setup-aurevia-theme.mjs
if errorlevel 1 exit /b 1

echo.
echo Aurevia Health theme setup complete.
echo Angular watch mode should rebuild automatically.
echo Open http://localhost:4200
