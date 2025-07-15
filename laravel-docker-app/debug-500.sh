#!/bin/bash

echo "🔍 Debugging 500 Error..."

# Check logs
echo "📋 Checking Laravel logs..."
docker-compose exec app tail -n 50 storage/logs/laravel.log

echo ""
echo "🔧 Checking permissions..."
docker-compose exec app ls -la storage/
docker-compose exec app ls -la bootstrap/cache/

echo ""
echo "📦 Checking .env file..."
docker-compose exec app cat .env | head -20

echo ""
echo "🗄️ Testing database connection..."
docker-compose exec app php artisan tinker --execute="DB::select('SELECT 1');"

echo ""
echo "🧹 Clearing all caches..."
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan view:clear
docker-compose exec app php artisan route:clear

echo ""
echo "✅ Debug complete!"