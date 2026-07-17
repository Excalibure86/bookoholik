#!/bin/sh
# Backup script for Bookoholik database
BACKUP_DIR="/backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="${BACKUP_DIR}/home_library_backup_${TIMESTAMP}.sql.gz"

# Create backup directory if not exists
mkdir -p ${BACKUP_DIR}

# Perform backup
pg_dump -h ${PGHOST} -U ${PGUSER} -d ${PGDATABASE} | gzip > ${BACKUP_FILE}

# Keep only last 30 backups
ls -t ${BACKUP_DIR}/home_library_backup_*.sql.gz | tail -n +31 | xargs -r rm

echo "[$(date)] Backup completed: ${BACKUP_FILE}"
