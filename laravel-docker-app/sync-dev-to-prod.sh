#!/bin/bash

# RIMS Development to Production Data Sync Script
# This script syncs development data to production (excluding users table)

set -e

echo "🔄 RIMS Dev-to-Prod Data Sync (Excluding Users)"
echo "==============================================="

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

echo -e "${YELLOW}⚠️  WARNING: This will update production data!${NC}"
echo -e "${YELLOW}⚠️  User accounts will NOT be affected.${NC}"
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

# Confirm action
echo -e "${RED}🚨 FINAL WARNING: You are about to update PRODUCTION data!${NC}"
read -p "Type 'PRODUCTION' to confirm: " CONFIRM
if [ "$CONFIRM" != "PRODUCTION" ]; then
    echo "Sync cancelled."
    exit 1
fi

# Step 1: Backup production database
echo ""
echo "📦 Backing up production database..."
mysqldump -h$PROD_HOST -P$PROD_PORT -u$PROD_USER -p$PROD_PASS \
    $PROD_DB > "$BACKUP_DIR/prod_backup_${TIMESTAMP}.sql"

if [ ! -f "$BACKUP_DIR/prod_backup_${TIMESTAMP}.sql" ] || [ ! -s "$BACKUP_DIR/prod_backup_${TIMESTAMP}.sql" ]; then
    echo -e "${RED}❌ Production backup failed!${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Production backup saved to: $BACKUP_DIR/prod_backup_${TIMESTAMP}.sql${NC}"

# Step 2: Export development data (excluding users)
echo ""
echo "📥 Exporting development data..."

# List of tables to sync (all except users and system tables)
TABLES=$(docker-compose exec -T db mysql -ularavel -psecret -N -e "
    SELECT GROUP_CONCAT(table_name SEPARATOR ' ')
    FROM information_schema.tables 
    WHERE table_schema = 'rims_toolbox' 
    AND table_name NOT IN ('users', 'migrations', 'password_reset_tokens', 'sessions', 'cache', 'cache_locks')
")

# Export selected tables
docker-compose exec -T db mysqldump -ularavel -psecret \
    rims_toolbox $TABLES > "$BACKUP_DIR/dev_export_${TIMESTAMP}.sql"

echo -e "${GREEN}✅ Development data exported (excluding users)${NC}"

# Step 3: Import to production
echo ""
echo "📤 Importing to production..."

# First, delete data from all tables except users
echo "Clearing production data (except users)..."
for TABLE in $TABLES; do
    mysql -h$PROD_HOST -P$PROD_PORT -u$PROD_USER -p$PROD_PASS $PROD_DB -e "DELETE FROM $TABLE;" 2>/dev/null || true
done

# Import development data
mysql -h$PROD_HOST -P$PROD_PORT -u$PROD_USER -p$PROD_PASS \
    $PROD_DB < "$BACKUP_DIR/dev_export_${TIMESTAMP}.sql"

echo -e "${GREEN}✅ Data imported to production${NC}"

# Step 4: Update environment-specific settings
echo ""
echo "🔧 Updating production-specific settings..."
mysql -h$PROD_HOST -P$PROD_PORT -u$PROD_USER -p$PROD_PASS $PROD_DB -e "
    UPDATE settings SET value = 'https://rims-toolbox.com' WHERE \`key\` = 'app_url';
    UPDATE settings SET value = 'production' WHERE \`key\` = 'app_env';
" 2>/dev/null || true

# Clean up old backups (keep last 20 production backups)
echo ""
echo "🧹 Cleaning up old backups..."
cd $BACKUP_DIR
ls -t1 prod_backup_*.sql 2>/dev/null | tail -n +21 | xargs -r rm
cd ..

echo ""
echo -e "${GREEN}🎉 Sync completed successfully!${NC}"
echo ""
echo "Summary:"
echo "- Production backup: $BACKUP_DIR/prod_backup_${TIMESTAMP}.sql"
echo "- Development export: $BACKUP_DIR/dev_export_${TIMESTAMP}.sql"
echo "- User accounts were preserved"
echo "- All other data was updated from development"
echo ""
echo -e "${YELLOW}⚠️  Remember to clear production caches!${NC}"