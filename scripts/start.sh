#!/usr/bin/env bash
set -e

echo "========================================================"
echo "   Starting Analytics Platform (Laravel + React)"
echo "========================================================"
echo ""
echo "Running Laravel database migrations..."
cd backend-laravel
php artisan migrate --force
cd ..

echo ""
echo "Starting Laravel API Server on http://127.0.0.1:8000 ..."
cd backend-laravel
php artisan serve --host=127.0.0.1 --port=8000 &
LARAVEL_PID=$!
cd ..

echo "Starting React Frontend on http://localhost:5173 ..."
cd frontend
bun run dev &
FRONTEND_PID=$!
cd ..

echo ""
echo "========================================================"
echo "   Servers started!"
echo "   - Restaurant Demo & Dashboard: http://localhost:5173"
echo "   - Laravel API Backend:         http://127.0.0.1:8000"
echo "   - API Health Check:            http://127.0.0.1:8000/api/health"
echo "========================================================"

wait $LARAVEL_PID $FRONTEND_PID
