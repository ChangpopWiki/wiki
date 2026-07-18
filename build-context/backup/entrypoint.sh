#!/bin/sh
chown -R 1000:1000 /backup

# CRON_TIME이 비어있거나 없으면 여기서 에러 메시지를 출력하고 즉시 종료
: "${CRON_TIME:?CRON_TIME이 설정되지 않았습니다}"

cat >/etc/crontab <<EOF
${CRON_TIME} backup_db.sh
${CRON_TIME} backup_pages.py
EOF

exec su-exec 1000:1000 supercronic /etc/crontab