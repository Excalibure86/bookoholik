#!/bin/sh
# Backup script for Bookoholik database
BACKUP_DIR="/backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="${BACKUP_DIR}/home_library_backup_${TIMESTAMP}.sql.gz"

# Create backup directory if not exists
mkdir -p ${BACKUP_DIR}

# Create .pgpass file for secure authentication (not visible in /proc)
PGPASS_FILE="$HOME/.pgpass"
echo "${PGHOST}:5432:${PGDATABASE}:${PGUSER}:${PGPASSWORD}" > "${PGPASS_FILE}"
chmod 600 "${PGPASS_FILE}"

# Perform backup using .pgpass (no password in command line or env)
pg_dump -h ${PGHOST} -U ${PGUSER} -d ${PGDATABASE} --no-password | gzip > ${BACKUP_FILE}

# Remove .pgpass after use
rm -f "${PGPASS_FILE}"

# Keep only last 30 backups
ls -t ${BACKUP_DIR}/home_library_backup_*.sql.gz 2>/dev/null | tail -n +31 | xargs -r rm

# Restrict backup file permissions
chmod 600 ${BACKUP_FILE}

echo "[$(date)] Backup completed: ${BACKUP_FILE}"
