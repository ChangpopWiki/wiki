#!/bin/sh
set -e

# 확장 및 스킨 폴더 심볼릭 링크
for item in /non-bundled/extensions/*/; do
    name=$(basename "$item")
    ln -snf "$item" "/var/www/html/extensions/$name"
done

for item in /non-bundled/skins/*/; do
    name=$(basename "$item")
    ln -snf "$item" "/var/www/html/skins/$name"
done