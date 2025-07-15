#!/bin/bash
set -e

# Configuration
BACKUP_DIR="/backups"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if backup name provided
if [ -z "$1" ]; then
    echo -e "${RED}Error: No backup name provided${NC}"
    echo "Usage: ./restore.sh <backup_name>"
    echo ""
    echo "Available backups:"
    ls -la ${BACKUP_DIR}/*_manifest.json 2>/dev/null | awk -F'/' '{print $NF}' | sed 's/_manifest.json//'
    exit 1
fi

BACKUP_NAME=$1

# Verify backup files exist
echo -e "${YELLOW}Verifying backup files...${NC}"
if [ ! -f "${BACKUP_DIR}/${BACKUP_NAME}_manifest.json" ]; then
    echo -e "${RED}Error: Backup manifest not found${NC}"
    exit 1
fi

# Verify checksums
if [ -f "${BACKUP_DIR}/${BACKUP_NAME}_checksums.sha256" ]; then
    echo -e "${YELLOW}Verifying checksums...${NC}"
    cd ${BACKUP_DIR}
    if sha256sum -c ${BACKUP_NAME}_checksums.sha256; then
        echo -e "${GREEN}✓ Checksums verified${NC}"
    else
        echo -e "${RED}✗ Checksum verification failed!${NC}"
        read -p "Continue anyway? (y/N) " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            exit 1
        fi
    fi
fi

# Confirmation
echo -e "${YELLOW}This will restore backup: ${BACKUP_NAME}${NC}"
echo -e "${RED}WARNING: This will overwrite current data!${NC}"
read -p "Are you sure you want to continue? (y/N) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Restore cancelled"
    exit 0
fi

# Put application in maintenance mode
echo -e "${YELLOW}Putting application in maintenance mode...${NC}"
cd /var/www/html
php artisan down

# 1. Restore Database
if [ -f "${BACKUP_DIR}/${BACKUP_NAME}_database.sql.gz" ]; then
    echo -e "${YELLOW}Restoring database...${NC}"
    
    # Create backup of current database first
    mysqldump -h db -u root -p${MYSQL_ROOT_PASSWORD} ${MYSQL_DATABASE} | gzip > ${BACKUP_DIR}/before_restore_$(date +%Y%m%d_%H%M%S).sql.gz
    
    # Drop and recreate database
    mysql -h db -u root -p${MYSQL_ROOT_PASSWORD} -e "DROP DATABASE IF EXISTS ${MYSQL_DATABASE}; CREATE DATABASE ${MYSQL_DATABASE};"
    
    # Restore backup
    gunzip < ${BACKUP_DIR}/${BACKUP_NAME}_database.sql.gz | mysql -h db -u root -p${MYSQL_ROOT_PASSWORD} ${MYSQL_DATABASE}
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Database restored${NC}"
    else
        echo -e "${RED}✗ Database restore failed${NC}"
        exit 1
    fi
else
    echo -e "${YELLOW}⚠ Database backup not found, skipping...${NC}"
fi

# 2. Restore Application Files
if [ -f "${BACKUP_DIR}/${BACKUP_NAME}_files.tar.gz" ]; then
    echo -e "${YELLOW}Restoring application files...${NC}"
    
    # Backup current files
    tar -czf ${BACKUP_DIR}/before_restore_files_$(date +%Y%m%d_%H%M%S).tar.gz -C /var/www/html .
    
    # Extract backup (excluding certain directories)
    tar -xzf ${BACKUP_DIR}/${BACKUP_NAME}_files.tar.gz -C /var/www/html \
        --exclude='vendor' \
        --exclude='node_modules' \
        --exclude='.env'
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Application files restored${NC}"
    else
        echo -e "${RED}✗ Application files restore failed${NC}"
    fi
else
    echo -e "${YELLOW}⚠ Application files backup not found, skipping...${NC}"
fi

# 3. Restore Storage Directory
if [ -f "${BACKUP_DIR}/${BACKUP_NAME}_storage.tar.gz" ]; then
    echo -e "${YELLOW}Restoring storage directory...${NC}"
    
    # Backup current storage
    if [ -d "/var/www/html/storage/app" ]; then
        tar -czf ${BACKUP_DIR}/before_restore_storage_$(date +%Y%m%d_%H%M%S).tar.gz -C /var/www/html/storage app
    fi
    
    # Extract storage backup
    tar -xzf ${BACKUP_DIR}/${BACKUP_NAME}_storage.tar.gz -C /var/www/html/storage
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Storage directory restored${NC}"
    else
        echo -e "${RED}✗ Storage restore failed${NC}"
    fi
else
    echo -e "${YELLOW}⚠ Storage backup not found, skipping...${NC}"
fi

# 4. Fix permissions
echo -e "${YELLOW}Fixing permissions...${NC}"
chown -R www:www /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 5. Clear caches
echo -e "${YELLOW}Clearing caches...${NC}"
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 6. Run migrations (in case of version differences)
echo -e "${YELLOW}Running migrations...${NC}"
php artisan migrate --force

# 7. Rebuild caches
echo -e "${YELLOW}Rebuilding caches...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 8. Install dependencies (if needed)
if [ ! -d "vendor" ]; then
    echo -e "${YELLOW}Installing PHP dependencies...${NC}"
    composer install --no-dev --optimize-autoloader
fi

# 9. Bring application back online
echo -e "${YELLOW}Bringing application back online...${NC}"
php artisan up

echo -e "${GREEN}Restore completed successfully!${NC}"
echo -e "Restored from backup: ${BACKUP_NAME}"
echo -e "Pre-restore backups saved with prefix: before_restore_"

# Show restored data info
if [ -f "${BACKUP_DIR}/${BACKUP_NAME}_manifest.json" ]; then
    echo -e "\n${YELLOW}Restored backup information:${NC}"
    cat ${BACKUP_DIR}/${BACKUP_NAME}_manifest.json | python -m json.tool 2>/dev/null || cat ${BACKUP_DIR}/${BACKUP_NAME}_manifest.json
fi

exit 0