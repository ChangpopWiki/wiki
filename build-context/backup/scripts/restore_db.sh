#!/bin/sh
set -e

# ------------------------------------------------------------------
# 사용법:
#   restore_db.sh <파일명> [--database <DB이름>]
#   restore_db.sh remote:<경로> [--database <DB이름>]
#   restore_db.sh [--database <DB이름>] < {호스트에 있는 파일} # stdin에서 읽어 복원
#
# --database: 덤프 안에 USE 문이 없는 덤프에서 복원할 때, 대상 DB를 명시적으로 지정.
#             backup_db.sh로 만들어진 백업은 덤프 안에 이미 USE 문이 포함되어 있으므로 불필요.
#
# .sql / .sql.gz 어느 쪽이든 자동으로 판별해서 복원하므로 압축 여부를 신경 쓸 필요 없음.
#
# 예시:
#   restore_db.sh daily/20260628_060000.sql.gz
#   restore_db.sh monthly/monthly_202606.sql.gz
#   restore_db.sh remote:daily/20260628_060000.sql.gz
#   restore_db.sh legacy_wiki.sql --database changpopwiki
#   docker compose exec -T backup restore_db.sh < my-backup.sql.gz
#   just restore < my-backup.sql.gz
# ------------------------------------------------------------------

ARG=""
TARGET_DB=""

# 위치 인자(파일명/remote:.../없음)와 --database 옵션을 분리해서 파싱
while [ $# -gt 0 ]; do
    case "$1" in
        --database)
            TARGET_DB="$2"
            shift 2
            ;;
        *)
            ARG="$1"
            shift
            ;;
    esac
done

if [ -z "${ARG}" ] && [ -t 0 ]; then
    # 터미널에 연결된 상태에서 대상 파일도 없이 실행 → 실수로 친 것으로 간주, 안내 후 종료
    echo "사용법: restore_db.sh [<파일명> | remote:<경로>] [--database <DB이름>]"
    echo "  인자 없이 실행하면 stdin에서 읽습니다 (예: ... < backup.sql.gz)"
    exit 1
fi

echo "복원 전 현재 DB 백업 중..."
PRE_RESTORE_FILE="/backup/daily/prerestore_$(date +"%Y%m%d_%H%M%S").sql.gz"
mariadb-dump \
  --host "${MARIADB_HOST}" \
  --user root \
  --password="${MARIADB_ROOT_PASSWORD}" \
  --single-transaction \
  --no-tablespaces \
  --databases changpopwiki changpopwiki_cargo | gzip > "${PRE_RESTORE_FILE}" || echo "기존 DB 없음, 백업 건너뜀"

# 주어진 파일 경로가 gzip인지 판별 (매직바이트 1f8b)
is_gzip_file() {
    [ "$(head -c2 "$1" | od -An -tx1 | tr -d ' ')" = "1f8b" ]
}

run_mariadb() {
    # TARGET_DB가 지정된 경우에만 mariadb 커맨드라인에 DB를 붙여서 컨텍스트를 잡음
    if [ -n "${TARGET_DB}" ]; then
        mariadb --host "${MARIADB_HOST}" --user root --password="${MARIADB_ROOT_PASSWORD}" "${TARGET_DB}"
    else
        mariadb --host "${MARIADB_HOST}" --user root --password="${MARIADB_ROOT_PASSWORD}"
    fi
}

restore_from_stream() {
    # stdin을 임시 파일에 받아 gzip 여부를 자동 판별 후 복원
    TMP_FILE=$(mktemp)
    trap 'rm -f "${TMP_FILE}"' EXIT
    cat > "${TMP_FILE}"

    if is_gzip_file "${TMP_FILE}"; then
        gunzip -c "${TMP_FILE}"
    else
        cat "${TMP_FILE}"
    fi | run_mariadb

    rm -f "${TMP_FILE}"
}

restore_local_file() {
    # 이미 디스크에 있는 파일은 임시 파일로 재복사하지 않고 바로 판별
    if is_gzip_file "$1"; then
        gunzip -c "$1"
    else
        cat "$1"
    fi | run_mariadb
}

case "${ARG}" in
    "")
        echo "복원 시작: stdin"
        restore_from_stream
        ;;
    remote:*)
        REMOTE_PATH="${ARG#remote:}"
        echo "복원 시작: ${RCLONE_REMOTE}/${REMOTE_PATH}"
        rclone cat "${RCLONE_REMOTE}/${REMOTE_PATH}" --config /dev/null | restore_from_stream
        ;;
    *)
        LOCAL_PATH="/backup/${ARG}"
        if [ ! -f "${LOCAL_PATH}" ]; then
            echo "파일 없음: ${LOCAL_PATH}"
            echo "클라우드에서 바로 복원하려면: restore_db.sh remote:${ARG}"
            exit 1
        fi
        echo "복원 시작: ${LOCAL_PATH}"
        restore_local_file "${LOCAL_PATH}"
        ;;
esac

echo "복원 완료"