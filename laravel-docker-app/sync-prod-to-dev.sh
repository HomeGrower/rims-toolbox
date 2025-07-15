#!/bin/bash

# RIMS Production to Development Database Sync Script
# This script syncs production data to development environment

set -e

echo "🔄 RIMS Prod-to-Dev Database Sync"
echo "=================================="

# Configuration
BACKUP_DIR="./backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Create backup directory if it doesn't exist
mkdir -p $BACKUP_DIR

echo -e "${YELLOW}⚠️  WARNING: This will replace ALL data in your development database!${NC}"
echo ""
echo "📊 Current Development Database Settings:"
echo "   Host: db (Docker container)"
echo "   Port: 3306"
echo "   Database: rims_toolbox"
echo "   Username: laravel"
echo "   Password: secret"
echo ""
echo "You have two options:"
echo "1. Provide a SQL dump file from production"
echo "2. Connect directly to production database (requires VPN/SSH)"
echo ""
read -p "Choose option (1 or 2): " OPTION

if [ "$OPTION" == "1" ]; then
    # Option 1: Use existing SQL dump
    echo ""
    read -p "Enter path to production SQL dump file: " PROD_DUMP_FILE
    
    if [ ! -f "$PROD_DUMP_FILE" ]; then
        echo -e "${RED}❌ File not found: $PROD_DUMP_FILE${NC}"
        exit 1
    fi
    
    cp "$PROD_DUMP_FILE" "$BACKUP_DIR/prod_import_${TIMESTAMP}.sql"
    
elif [ "$OPTION" == "2" ]; then
    # Option 2: Direct database connection
    echo ""
    echo "Enter production database credentials:"
    read -p "Host: " PROD_HOST
    read -p "Port (default 3306): " PROD_PORT
    PROD_PORT=${PROD_PORT:-3306}
    read -p "Database name: " PROD_DB
    read -p "Username: " PROD_USER
    read -s -p "Password: " PROD_PASS
    echo ""
    
    echo ""
    echo "📥 Exporting production database..."
    mysqldump -h$PROD_HOST -P$PROD_PORT -u$PROD_USER -p$PROD_PASS \
        $PROD_DB > "$BACKUP_DIR/prod_import_${TIMESTAMP}.sql"
    
    if [ ! -f "$BACKUP_DIR/prod_import_${TIMESTAMP}.sql" ] || [ ! -s "$BACKUP_DIR/prod_import_${TIMESTAMP}.sql" ]; then
        echo -e "${RED}❌ Production export failed!${NC}"
        exit 1
    fi
else
    echo -e "${RED}❌ Invalid option${NC}"
    exit 1
fi

# Step 1: Backup current development database
echo ""
echo "📦 Backing up current development database..."
docker-compose exec -T db mysqldump \
    -ularavel \
    -psecret \
    rims_toolbox > "$BACKUP_DIR/dev_backup_${TIMESTAMP}.sql"
echo -e "${GREEN}✅ Development backup saved to: $BACKUP_DIR/dev_backup_${TIMESTAMP}.sql${NC}"

# Step 2: Import to development database
echo ""
echo "📤 Importing production data to development..."

# Drop and recreate database
docker-compose exec -T db mysql -ularavel -psecret -e "DROP DATABASE IF EXISTS rims_toolbox; CREATE DATABASE rims_toolbox;"

# Import the production data
docker-compose exec -T db mysql -ularavel -psecret rims_toolbox < "$BACKUP_DIR/prod_import_${TIMESTAMP}.sql"

echo -e "${GREEN}✅ Data imported to development${NC}"

# Step 3: Run migrations
echo ""
echo "🔧 Running migrations..."
docker-compose exec app php artisan migrate --force

# Step 4: Clear caches
echo ""
echo "🧹 Clearing caches..."
docker-compose exec app php artisan optimize:clear

# Step 5: Update APP_URL in settings if needed
echo ""
echo "🔧 Updating settings for development..."
docker-compose exec app php artisan tinker --execute="
    \App\Models\Setting::where('key', 'app_url')->update(['value' => 'http://localhost:8080']);
    echo 'Settings updated';
"

# Clean up old backups (keep last 10)
echo ""
echo "🧹 Cleaning up old backups..."
cd $BACKUP_DIR
ls -t1 dev_backup_*.sql 2>/dev/null | tail -n +11 | xargs -r rm
ls -t1 prod_import_*.sql 2>/dev/null | tail -n +11 | xargs -r rm
cd ..

echo ""
echo -e "${GREEN}🎉 Sync completed successfully!${NC}"
echo ""
echo "Backup files:"
echo "- Dev backup: $BACKUP_DIR/dev_backup_${TIMESTAMP}.sql"
echo "- Prod import: $BACKUP_DIR/prod_import_${TIMESTAMP}.sql"
echo ""
echo "You can now login with the same credentials as in production."