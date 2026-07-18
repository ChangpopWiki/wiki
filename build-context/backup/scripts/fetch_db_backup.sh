#!/bin/sh
set -e

# ------------------------------------------------------------------
# 사용법: fetch_db_backup.sh <파일명>
# 클라우드(RCLONE_REMOTE)에서 /backup/ 으로 백업 파일을 받아옵니다.
# 이미 로컬에 있으면 아무것도 하지 않습니다.
# ------------------------------------------------------------------

if [ -z "$1" ]; then
    echo "사용법: fetch_backup.sh <파일명>"
    echo "예시: fetch_backup.sh daily_20260628_060000.sql.gz"
    exit 1
fi

FILE="$1"
LOCAL_PATH="/backup/${FILE}"

if [ -f "${LOCAL_PATH}" ]; then
    echo "이미 로컬에 있음: ${LOCAL_PATH}"
    exit 0
fi

echo "클라우드에서 다운로드 중: ${FILE}"
mkdir -p "$(dirname "${LOCAL_PATH}")"
rclone copyto "${RCLONE_REMOTE}/${FILE}" "${LOCAL_PATH}"
[ -f "${LOCAL_PATH}" ] || { echo "다운로드 실패: ${FILE}"; exit 1; }
echo "다운로드 완료: ${LOCAL_PATH}"