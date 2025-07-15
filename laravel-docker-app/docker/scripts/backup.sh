#!/bin/bash
set -e

# Configuration
BACKUP_DIR="/backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_NAME="rims_backup_${TIMESTAMP}"
RETENTION_DAYS=${BACKUP_RETENTION_DAYS:-30}

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}Starting backup process...${NC}"

# Create backup directory if it doesn't exist
mkdir -p ${BACKUP_DIR}

# 1. Database Backup
echo -e "${YELLOW}Backing up database...${NC}"
mysqldump -h db -u root -p${MYSQL_ROOT_PASSWORD} ${MYSQL_DATABASE} | gzip > ${BACKUP_DIR}/${BACKUP_NAME}_database.sql.gz
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Database backup completed${NC}"
else
    echo -e "${RED}✗ Database backup failed${NC}"
    exit 1
fi

# 2. Application Files Backup
echo -e "${YELLOW}Backing up application files...${NC}"
cd /var/www/html
tar -czf ${BACKUP_DIR}/${BACKUP_NAME}_files.tar.gz \
    --exclude='vendor' \
    --exclude='node_modules' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='storage/logs/*' \
    --exclude='bootstrap/cache/*' \
    .

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Files backup completed${NC}"
else
    echo -e "${RED}✗ Files backup failed${NC}"
    exit 1
fi

# 3. Storage Directory Backup (user uploads)
echo -e "${YELLOW}Backing up storage directory...${NC}"
if [ -d "/var/www/html/storage/app" ]; then
    tar -czf ${BACKUP_DIR}/${BACKUP_NAME}_storage.tar.gz -C /var/www/html/storage app
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Storage backup completed${NC}"
    else
        echo -e "${RED}✗ Storage backup failed${NC}"
    fi
fi

# 4. Environment Configuration Backup
echo -e "${YELLOW}Backing up environment configuration...${NC}"
cp /var/www/html/.env ${BACKUP_DIR}/${BACKUP_NAME}_env.txt
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Environment backup completed${NC}"
else
    echo -e "${RED}✗ Environment backup failed${NC}"
fi

# 5. Create manifest file
echo -e "${YELLOW}Creating backup manifest...${NC}"
cat > ${BACKUP_DIR}/${BACKUP_NAME}_manifest.json <<EOF
{
  "timestamp": "${TIMESTAMP}",
  "date": "$(date)",
  "app_version": "$(cd /var/www/html && php artisan --version)",
  "files": {
    "database": "${BACKUP_NAME}_database.sql.gz",
    "application": "${BACKUP_NAME}_files.tar.gz",
    "storage": "${BACKUP_NAME}_storage.tar.gz",
    "environment": "${BACKUP_NAME}_env.txt"
  },
  "sizes": {
    "database": "$(du -h ${BACKUP_DIR}/${BACKUP_NAME}_database.sql.gz | cut -f1)",
    "application": "$(du -h ${BACKUP_DIR}/${BACKUP_NAME}_files.tar.gz | cut -f1)",
    "storage": "$(du -h ${BACKUP_DIR}/${BACKUP_NAME}_storage.tar.gz 2>/dev/null | cut -f1 || echo 'N/A')"
  }
}
EOF

# 6. Create checksum file
echo -e "${YELLOW}Creating checksums...${NC}"
cd ${BACKUP_DIR}
sha256sum ${BACKUP_NAME}_* > ${BACKUP_NAME}_checksums.sha256

# 7. Cleanup old backups
echo -e "${YELLOW}Cleaning up old backups...${NC}"
find ${BACKUP_DIR} -name "rims_backup_*" -type f -mtime +${RETENTION_DAYS} -delete
DELETED_COUNT=$(find ${BACKUP_DIR} -name "rims_backup_*" -type f -mtime +${RETENTION_DAYS} | wc -l)
if [ ${DELETED_COUNT} -gt 0 ]; then
    echo -e "${GREEN}✓ Deleted ${DELETED_COUNT} old backup files${NC}"
fi

# 8. Upload to S3 (if configured)
if [ ! -z "${AWS_ACCESS_KEY_ID}" ] && [ ! -z "${AWS_BUCKET}" ]; then
    echo -e "${YELLOW}Uploading to S3...${NC}"
    aws s3 cp ${BACKUP_DIR}/${BACKUP_NAME}_database.sql.gz s3://${AWS_BUCKET}/backups/ --storage-class STANDARD_IA
    aws s3 cp ${BACKUP_DIR}/${BACKUP_NAME}_files.tar.gz s3://${AWS_BUCKET}/backups/ --storage-class STANDARD_IA
    aws s3 cp ${BACKUP_DIR}/${BACKUP_NAME}_storage.tar.gz s3://${AWS_BUCKET}/backups/ --storage-class STANDARD_IA
    aws s3 cp ${BACKUP_DIR}/${BACKUP_NAME}_manifest.json s3://${AWS_BUCKET}/backups/
    aws s3 cp ${BACKUP_DIR}/${BACKUP_NAME}_checksums.sha256 s3://${AWS_BUCKET}/backups/
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ S3 upload completed${NC}"
        # Remove local files if S3 upload successful (keep only recent)
        if [ "${KEEP_LOCAL_AFTER_S3}" != "true" ]; then
            find ${BACKUP_DIR} -name "${BACKUP_NAME}_*" -type f -mtime +7 -delete
        fi
    else
        echo -e "${RED}✗ S3 upload failed${NC}"
    fi
fi

# 9. Send notification (if configured)
if [ ! -z "${BACKUP_NOTIFICATION_EMAIL}" ]; then
    echo -e "${YELLOW}Sending notification...${NC}"
    BACKUP_SIZE=$(du -sh ${BACKUP_DIR}/${BACKUP_NAME}_* | awk '{sum+=$1} END {print sum}')
    
    # Use Laravel to send email
    cd /var/www/html
    php artisan tinker --execute="
        Mail::raw(
            'Backup completed successfully.\n\nTimestamp: ${TIMESTAMP}\nTotal Size: ${BACKUP_SIZE}\n\nFiles created:\n- Database backup\n- Application files\n- Storage files\n- Environment config',
            function(\$message) {
                \$message->to('${BACKUP_NOTIFICATION_EMAIL}')
                    ->subject('[RIMS] Backup Completed - ${TIMESTAMP}');
            }
        );
    "
fi

echo -e "${GREEN}Backup process completed successfully!${NC}"
echo -e "Backup files created in: ${BACKUP_DIR}"
echo -e "Backup name: ${BACKUP_NAME}"

# Return success
exit 0