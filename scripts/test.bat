@echo off
echo ========================================================
echo   Running Analytics Platform Full Test Suite
echo ========================================================
echo.
echo [1/2] Running Laravel Feature & Unit Tests (PHPUnit)...
cd backend-laravel
call php artisan test
if %errorlevel% neq 0 (
    echo [ERROR] Laravel tests failed!
    cd ..
    exit /b %errorlevel%
)
cd ..

echo.
echo [2/2] Running Browser SDK TypeScript Unit Tests...
cd sdk/browser
call bun test
if %errorlevel% neq 0 (
    echo [ERROR] Browser SDK tests failed!
    cd ../..
    exit /b %errorlevel%
)
cd ../..

echo.
echo ========================================================
echo   ALL TESTS PASSED SUCCESSFULLY!
echo ========================================================
