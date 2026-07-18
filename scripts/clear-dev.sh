#!/bin/sh
set -eu

remove() {
    file="$1"
    if [ -f "$file" ]; then
        rm "$file" && echo "✓ $file (삭제됨)"
    else
        echo "✓ $file (없음)"
    fi
}

remove compose.override.yaml
remove config/mediawiki/DebugSettings.php
