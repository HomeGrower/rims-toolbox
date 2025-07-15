#!/bin/bash

# RIMS Development Database Backup Script

set -e

echo "💾 RIMS Development Database Backup"
echo "==================================="

# Configuration
BACKUP_DIR="./backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Create backup directory if it doesn't exist
mkdir -p $BACKUP_DIR

# Backup database
echo "📦 Creating database backup..."
docker-compose exec -T db mysqldump \
    -ularavel \
    -psecret \
    rims_toolbox > "$BACKUP_DIR/dev_backup_${TIMESTAMP}.sql"

# Compress backup
echo "🗜️ Compressing backup..."
gzip "$BACKUP_DIR/dev_backup_${TIMESTAMP}.sql"

echo ""
echo "✅ Backup completed!"
echo "📁 File: $BACKUP_DIR/dev_backup_${TIMESTAMP}.sql.gz"
echo "📏 Size: $(ls -lh "$BACKUP_DIR/dev_backup_${TIMESTAMP}.sql.gz" | awk '{print $5}')"