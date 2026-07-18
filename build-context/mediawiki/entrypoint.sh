#!/bin/sh
set -e

# LocalSettings.php 및 non-bundled 확장 심볼릭 링크
ln -snf /config/LocalSettings.php /var/www/html/LocalSettings.php
symlink-extensions

# non-bundled 확장 및 스킨 폴더 변경 감지하여
# 변경 시 symlink-extensions.sh 실행하여 심볼릭 링크 갱신
(
    while true; do
        event=$(inotifywait -q -e create,delete,move --format '%w%f %e' \
            /non-bundled/extensions \
            /non-bundled/skins
        )
        echo "[symlink-watcher] 감지: $event"
        symlink-extensions
    done
) &
WATCHER_PID=$!
trap "kill $WATCHER_PID 2>/dev/null" TERM INT

# 웹 서버 권한으로 실행
if [ "$(id -u)" = '0' ]; then
    chown -R www-data:www-data /var/www/html/images /var/www/html/cache
    chown -R www-data:www-data /data/caddy /etc/caddy
    exec gosu www-data "$@"
fi

exec "$@"