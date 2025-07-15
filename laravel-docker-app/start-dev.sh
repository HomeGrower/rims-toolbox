#!/bin/bash

# RIMS Development Start Script with Vite
echo "🚀 Starting RIMS Development Environment with Vite..."

# Check if containers are running
if ! docker-compose ps | grep -q "Up"; then
    echo "📦 Starting containers first..."
    docker-compose up -d
    sleep 5
fi

# Start Vite dev server in background
echo "🔥 Starting Vite dev server..."
docker-compose exec -d app npm run dev

# Wait a moment for Vite to start
sleep 3

echo ""
echo "🎉 Development environment with Vite is running!"
echo ""
echo "📍 Access Points:"
echo "   • Frontend (with hot reload): http://localhost:8080"
echo "   • Admin Panel: http://localhost:8080/admin"
echo "   • Vite Dev Server: http://localhost:5173"
echo "   • Login: debug@rims.live / levinistganztoll"
echo ""
echo "🔧 Development Commands:"
echo "   • View Vite logs: docker-compose logs -f app"
echo "   • Stop Vite: docker-compose exec app pkill -f 'npm run dev'"
echo "   • Restart Vite: docker-compose exec app npm run dev"
echo "   • Stop all: docker-compose down"
echo ""
echo "💡 Vite provides hot reload for Vue components and CSS!"
echo "   Edit files in resources/js/ and see changes instantly."
echo ""