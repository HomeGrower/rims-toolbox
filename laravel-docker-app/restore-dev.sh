#!/bin/bash

# RIMS Development Database Restore Script

set -e

echo "🔄 RIMS Development Database Restore"
echo "===================================="

# Configuration
BACKUP_DIR="./backups"

echo "📊 Current Development Database Settings:"
echo "   Host: db (Docker container)"
echo "   Port: 3306"  
echo "   Database: rims_toolbox"
echo "   Username: laravel"
echo "   Password: secret"
echo ""

# List available backups
echo "📋 Available backups:"
echo ""
ls -lht $BACKUP_DIR/dev_backup_*.sql* 2>/dev/null | head -20 || echo "No backups found!"
echo ""

read -p "Enter backup filename to restore (or full path): " BACKUP_FILE

# Check if file exists
if [ ! -f "$BACKUP_FILE" ] && [ ! -f "$BACKUP_DIR/$BACKUP_FILE" ]; then
    echo "❌ Backup file not found!"
    exit 1
fi

# Use full path if not found in backup dir
if [ ! -f "$BACKUP_FILE" ]; then
    BACKUP_FILE="$BACKUP_DIR/$BACKUP_FILE"
fi

echo ""
echo "⚠️  WARNING: This will replace ALL data in your development database!"
read -p "Are you sure? (yes/no): " -n 3 -r
echo ""
if [[ ! $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
    echo "Restore cancelled."
    exit 1
fi

# Decompress if needed
if [[ $BACKUP_FILE == *.gz ]]; then
    echo "🗜️ Decompressing backup..."
    gunzip -c "$BACKUP_FILE" > "${BACKUP_FILE%.gz}.tmp"
    RESTORE_FILE="${BACKUP_FILE%.gz}.tmp"
else
    RESTORE_FILE="$BACKUP_FILE"
fi

# Drop and recreate database
echo "🗑️ Dropping existing database..."
docker-compose exec -T db mysql -ularavel -psecret -e "DROP DATABASE IF EXISTS rims_toolbox; CREATE DATABASE rims_toolbox;"

# Restore database
echo "📤 Restoring database..."
docker-compose exec -T db mysql -ularavel -psecret rims_toolbox < "$RESTORE_FILE"

# Clean up temp file
if [[ $BACKUP_FILE == *.gz ]]; then
    rm -f "$RESTORE_FILE"
fi

# Run migrations
echo "🔧 Running migrations..."
docker-compose exec app php artisan migrate --force

# Clear caches
echo "🧹 Clearing caches..."
docker-compose exec app php artisan optimize:clear

echo ""
echo "✅ Restore completed successfully!"