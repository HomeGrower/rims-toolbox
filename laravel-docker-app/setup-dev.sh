#!/bin/bash

# RIMS Development Setup Script
echo "🚀 Setting up RIMS Development Environment..."

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker is not running. Please start Docker and try again."
    exit 1
fi

# Copy environment file
if [ ! -f .env ]; then
    echo "📄 Copying environment file..."
    cp .env.dev .env
fi

# Start containers
echo "🐳 Starting Docker containers..."
docker-compose up -d --build

# Wait for MySQL to be ready
echo "⏳ Waiting for MySQL to be ready..."
until docker-compose exec db mysql -u root -psecret -e "SELECT 1" > /dev/null 2>&1; do
    echo "   MySQL is not ready yet... waiting"
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

# Run seeders
echo "🌱 Seeding database..."
docker-compose exec app php artisan db:seed --force

# Create admin user if it doesn't exist
echo "👤 Creating admin user..."
docker-compose exec app php artisan tinker --execute="
    if (!\App\Models\User::where('email', 'admin@rims.live')->exists()) {
        \App\Models\User::create([
            'name' => 'Admin User',
            'email' => 'admin@rims.live',
            'password' => bcrypt('kaffeistkalt14'),
            'role' => 'super_admin',
            'is_super_admin' => true,
            'email_verified_at' => now(),
        ]);
        echo 'Admin user created successfully!';
    } else {
        echo 'Admin user already exists.';
    }
"

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
sleep 3

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