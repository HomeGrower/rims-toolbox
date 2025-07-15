#!/bin/bash

# RIMS Development Setup Script
echo "🚀 Setting up RIMS Development Environment..."
echo ""

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker is not running. Please start Docker and try again."
    exit 1
fi

# Kill existing RIMS containers and volumes for fresh start
echo "🧹 Cleaning up existing RIMS Docker containers and volumes..."
docker-compose -p laravel-docker-app down -v 2>/dev/null || true
docker rm -f rims_app rims_db rims_webserver 2>/dev/null || true
docker volume rm laravel-docker-app_dbdata 2>/dev/null || true

# Remove existing .env files for fresh setup
rm -f .env 2>/dev/null || true
rm -f src/.env 2>/dev/null || true

echo "✅ RIMS Docker cleanup completed"
echo ""

# Copy environment files
echo "📄 Setting up environment files..."
echo ""

# Main .env
if [ ! -f .env ]; then
    cp .env.dev .env
    echo "✅ Created .env from .env.dev"
    
    echo ""
    echo "🔐 Passwort-Konfiguration"
    echo "========================"
    echo ""
    
    # Get MySQL root password
    echo "MySQL Root Passwort eingeben (min. 8 Zeichen):"
    read -s -p "> " MYSQL_ROOT_PASS
    echo ""
    
    # Validate password length
    while [ ${#MYSQL_ROOT_PASS} -lt 8 ]; do
        echo "❌ Passwort muss mindestens 8 Zeichen lang sein!"
        read -s -p "> " MYSQL_ROOT_PASS
        echo ""
    done
    
    # Get MySQL user password
    echo ""
    echo "MySQL Laravel User Passwort eingeben (min. 8 Zeichen):"
    read -s -p "> " MYSQL_USER_PASS
    echo ""
    
    # Validate password length
    while [ ${#MYSQL_USER_PASS} -lt 8 ]; do
        echo "❌ Passwort muss mindestens 8 Zeichen lang sein!"
        read -s -p "> " MYSQL_USER_PASS
        echo ""
    done
    
    # Update passwords in .env
    sed -i.bak "s/MYSQL_ROOT_PASSWORD=.*/MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASS}/" .env
    sed -i.bak "s/DB_PASSWORD=.*/DB_PASSWORD=${MYSQL_USER_PASS}/" .env
    sed -i.bak "s/MYSQL_PASSWORD=.*/MYSQL_PASSWORD=${MYSQL_USER_PASS}/" .env
    rm .env.bak
    
    echo ""
    echo "✅ Passwörter wurden in .env gespeichert"
    
    # # Levin's special check ;)
    # echo ""
    # echo "🎯 Letzte Frage bevor es weitergeht..."
    # echo "Bitte gib den folgenden Satz ein:"
    # echo ""
    # echo "➡️  levin ist ganz toll"
    # echo ""
    # 
    # while true; do
    #     read -p "> " LEVIN_CHECK
    #     if [ "$LEVIN_CHECK" = "levin ist ganz toll" ]; then
    #         echo ""
    #         echo "✅ Perfekt! Du hast es geschafft! 🎉"
    #         break
    #     else
    #         echo ""
    #         echo "❌ komm schon du weist es doch auch"
    #         echo ""
    #         echo "Versuch's nochmal:"
    #     fi
    # done
else
    echo "ℹ️  .env already exists"
    # Read the MySQL root password from existing .env
    MYSQL_ROOT_PASS=$(grep MYSQL_ROOT_PASSWORD .env | cut -d '=' -f2 | tr -d '"' | tr -d "'")
fi

# Laravel .env in src directory
if [ ! -f src/.env ]; then
    cp src/.env.example src/.env
    echo "✅ Created src/.env from src/.env.example"
    
    # Copy database settings from main .env to src/.env
    DB_HOST=$(grep "^DB_HOST=" .env | cut -d '=' -f2)
    DB_PORT=$(grep "^DB_PORT=" .env | cut -d '=' -f2)
    DB_DATABASE=$(grep "^DB_DATABASE=" .env | cut -d '=' -f2)
    DB_USERNAME=$(grep "^DB_USERNAME=" .env | cut -d '=' -f2)
    DB_PASSWORD=$(grep "^DB_PASSWORD=" .env | cut -d '=' -f2 | tr -d '"' | tr -d "'")
    
    # Update src/.env with values from main .env
    sed -i.bak "s/^DB_HOST=.*/DB_HOST=${DB_HOST}/" src/.env
    sed -i.bak "s/^DB_PORT=.*/DB_PORT=${DB_PORT}/" src/.env
    sed -i.bak "s/^DB_DATABASE=.*/DB_DATABASE=${DB_DATABASE}/" src/.env
    sed -i.bak "s/^DB_USERNAME=.*/DB_USERNAME=${DB_USERNAME}/" src/.env
    sed -i.bak "s/^DB_PASSWORD=.*/DB_PASSWORD=${DB_PASSWORD}/" src/.env
    
    # Set fixed APP_KEY for development
    sed -i.bak "s/^APP_KEY=.*/APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=/" src/.env
    rm src/.env.bak
    
    echo "✅ Synchronized database settings to src/.env"
else
    echo "ℹ️  src/.env already exists, skipping"
fi

# Start containers
echo ""
echo "🐳 Starting Docker containers..."
docker-compose up -d --build

# Wait for MySQL to be ready
echo "⏳ Waiting for MySQL to be ready..."
ATTEMPTS=0
MAX_ATTEMPTS=30
until docker-compose exec db mysql -u root -p${MYSQL_ROOT_PASS} -e "SELECT 1" > /dev/null 2>&1; do
    ATTEMPTS=$((ATTEMPTS+1))
    if [ $ATTEMPTS -gt $MAX_ATTEMPTS ]; then
        echo ""
        echo "❌ MySQL konnte nicht gestartet werden nach $MAX_ATTEMPTS Versuchen."
        echo ""
        echo "🔧 Mögliche Lösungen:"
        echo "   1. Führe 'docker-compose down -v' aus um alle Volumes zu löschen"
        echo "   2. Starte das Setup-Skript erneut"
        echo ""
        echo "Falls das Problem weiterhin besteht, nutze das debug script:"
        echo "   ./debug-500.sh"
        exit 1
    fi
    echo "   MySQL ist noch nicht bereit... warte (Versuch $ATTEMPTS/$MAX_ATTEMPTS)"
    sleep 5
done
echo "✅ MySQL is ready!"

# Install PHP dependencies
echo "📦 Installing PHP dependencies..."
docker-compose exec app composer install

# Clear any existing configuration
echo "🧹 Clearing configuration..."
docker-compose exec app php artisan config:clear

# Install Node dependencies and fix vulnerabilities
echo "📦 Installing Node dependencies..."
docker-compose exec app npm install
echo "🔧 Fixing npm vulnerabilities..."
docker-compose exec app npm audit fix || true

# Build frontend assets for initial setup
echo "🏗️ Building initial frontend assets..."
docker-compose exec app npm run build

# Run migrations
echo "🗄️ Running database migrations..."
docker-compose exec app php artisan migrate:fresh --force

# Run minimal seeder (only admin user)
echo "🌱 Creating admin user..."
docker-compose exec app php artisan db:seed --class=MinimalSeeder --force

# Set permissions
echo "🔒 Setting permissions..."
docker-compose exec app chmod -R 775 storage bootstrap/cache
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache

# Create storage link
echo "🔗 Creating storage link..."
docker-compose exec app php artisan storage:link

# Clear all caches for clean start
echo "🧹 Clearing caches..."
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear

# Start Vite dev server in background for hot reload
echo "🔥 Starting Vite dev server for hot reload..."
docker-compose exec -d app npm run dev

# Wait a moment for Vite to start
echo "⏳ Waiting for Vite to start..."
sleep 5

# Check if Vite is running
if docker-compose exec app pgrep -f vite > /dev/null; then
    echo "✅ Vite dev server is running!"
else
    echo "⚠️  Vite might not have started properly. Check logs with: docker-compose logs -f app"
fi

echo ""
echo "🎉 Development environment with Vite is ready!"
echo ""
echo "📍 Access Points:"
echo "   • Frontend (with hot reload): http://localhost:8080"
echo "   • Admin Panel: http://localhost:8080/admin"
echo "   • Vite Dev Server: http://localhost:5173"
echo "   • Login: admin@rims.live / kaffeistkalt14"
echo ""
echo "📊 Database Connection:"
echo "   • Host: localhost (from host machine)"
echo "   • Host: db (from within containers)"
echo "   • Port: 3308 (from host machine)"
echo "   • Port: 3306 (from within containers)"
echo "   • Database: rims_toolbox"
echo "   • Username: laravel"
echo "   • Password: secret"
echo ""
echo "🔧 Development Commands:"
echo "   • View Vite logs: docker-compose logs -f app"
echo "   • Stop Vite: docker-compose exec app pkill -f vite"
echo "   • Restart Vite: docker-compose exec app npm run dev"
echo "   • Stop containers: docker-compose down"
echo "   • Fresh setup: docker-compose down -v && ./setup-dev.sh"
echo ""
echo "💡 Vite provides hot reload for Vue components and CSS!"
echo "   Edit files in resources/js/ and see changes instantly."
echo "   If Vite stops working, restart with: docker-compose exec app npm run dev"
echo ""