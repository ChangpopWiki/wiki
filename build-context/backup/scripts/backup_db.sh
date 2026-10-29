#!/bin/sh
# pipefail: mariadb-dump가 실패해도 파이프라인 끝의 gzip이 성공하면 실패를 감지하지 못하는 문제 방지
set -eo pipefail

mkdir -p /backup/daily /backup/monthly

TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="/backup/daily/${TIMESTAMP}.sql.gz"
MONTHLY_FILE="/backup/monthly/monthly_$(date +"%Y%m").sql.gz"

# 덤프 도중 실패하면 불완전한 파일이 정상 백업처럼 로테이션/업로드되지 않도록 임시 파일에 먼저 기록
TMP_FILE="${BACKUP_FILE}.part"
trap 'rm -f "${TMP_FILE}"' EXIT

echo "백업 시작: ${BACKUP_FILE}"

mariadb-dump \
  --host "${MARIADB_HOST}" \
  --user root \
  --password="${MARIADB_ROOT_PASSWORD}" \
  --single-transaction \
  --no-tablespaces \
  --databases changpopwiki changpopwiki_cargo | gzip > "${TMP_FILE}"

# mariadb-dump는 정상 종료 시 마지막 줄에 "-- Dump completed" 주석을 남김 (gzip 손상 여부도 함께 확인됨)
gunzip -c "${TMP_FILE}" | tail -n 1 | grep -q "^-- Dump completed" \
    || { echo "덤프 실패: 덤프가 끝까지 완료되지 않았습니다"; exit 1; }

mv "${TMP_FILE}" "${BACKUP_FILE}"

echo "덤프 완료"

# 새로 생성된 백업 파일을 월간 백업 파일에 하드 링크
ln -f "${BACKUP_FILE}" "${MONTHLY_FILE}"

# 백업 파일 정리
ls -t /backup/daily/*.sql.gz \
    | tail -n +"$((MAX_DAILY_BACKUPS + 1))" \
    | xargs -r rm -f

# 리모트에 백업 업로드
echo "리모트에 백업 업로드: ${RCLONE_REMOTE}"
rclone copy /backup "${RCLONE_REMOTE}" --config /dev/null --exclude "*.part"

echo "완료"
