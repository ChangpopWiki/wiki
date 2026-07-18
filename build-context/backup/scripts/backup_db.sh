#!/bin/sh
set -e

mkdir -p /backup/daily /backup/monthly

TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="/backup/daily/${TIMESTAMP}.sql.gz"
MONTHLY_FILE="/backup/monthly/monthly_$(date +"%Y%m").sql.gz"

echo "백업 시작: ${BACKUP_FILE}"

mariadb-dump \
  --host "${MARIADB_HOST}" \
  --user root \
  --password="${MARIADB_ROOT_PASSWORD}" \
  --single-transaction \
  --no-tablespaces \
  --databases changpopwiki changpopwiki_cargo | gzip > "${BACKUP_FILE}"

[ -s "${BACKUP_FILE}" ] || { echo "덤프 실패"; exit 1; }

echo "덤프 완료"

# 새로 생성된 백업 파일을 월간 백업 파일에 하드 링크
ln -f "${BACKUP_FILE}" "${MONTHLY_FILE}"

# 백업 파일 정리
ls -t /backup/daily/*.sql.gz \
    | grep -v "^/backup/monthly_" \
    | tail -n +"$((MAX_DAILY_BACKUPS + 1))" \
    | xargs -r rm -f

# 리모트에 백업 업로드
echo "리모트에 백업 업로드: ${RCLONE_REMOTE}"
rclone copy /backup "${RCLONE_REMOTE}" --config /dev/null

echo "완료"