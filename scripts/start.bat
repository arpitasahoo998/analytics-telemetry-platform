@echo off
echo ========================================================
echo   Starting Analytics Platform (Laravel + React)
echo ========================================================
echo.
echo Running Laravel database migrations...
cd backend-laravel
php artisan migrate --force
cd ..

echo.
echo Starting Laravel API Server on http://127.0.0.1:8000 ...
cd backend-laravel
start "Analytics Backend (Laravel)" php artisan serve --host=127.0.0.1 --port=8000
cd ..

echo Starting React Frontend on http://localhost:5173 ...
cd frontend
start "Analytics Frontend (Vite)" bun run dev
cd ..

echo.
echo ========================================================
echo   Servers started!
echo   - Restaurant Demo & Dashboard: http://localhost:5173
echo   - Laravel API Backend:         http://127.0.0.1:8000
echo   - API Health Check:            http://127.0.0.1:8000/api/health
echo ========================================================
