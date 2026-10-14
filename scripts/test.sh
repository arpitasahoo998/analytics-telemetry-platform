#!/usr/bin/env bash
set -e

echo "========================================================"
echo "   Running Analytics Platform Full Test Suite"
echo "========================================================"
echo ""
echo "[1/2] Running Laravel Feature & Unit Tests (PHPUnit)..."
cd backend-laravel
php artisan test
cd ..

echo ""
echo "[2/2] Running Browser SDK TypeScript Unit Tests..."
cd sdk/browser
bun test
cd ../..

echo ""
echo "========================================================"
echo "   ALL TESTS PASSED SUCCESSFULLY!"
echo "========================================================"
