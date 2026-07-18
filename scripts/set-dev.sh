#!/bin/sh
set -eu

copy() {
    src="$1"
    dst="$2"
    if [ -f "$dst" ]; then
        echo "✓ $dst (이미 존재)"
    else
        cp --update=none "$src" "$dst" && echo "✓ $dst (생성됨)"
    fi
}

copy compose.dev.yaml compose.override.yaml
copy config/mediawiki/DebugSettings.example.php config/mediawiki/DebugSettings.php
