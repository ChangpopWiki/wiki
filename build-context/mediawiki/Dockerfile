FROM mediawiki:1.46-fpm-alpine AS mediawiki

FROM dunglas/frankenphp:1-php8.5

WORKDIR /var/www/html

# 미디어위키 소스 복사
COPY --from=mediawiki /var/www/html /var/www/html

# opcache-recommended.ini 복사 (FrankenPHP에서 opcache 설정 유지)
COPY --from=mediawiki /usr/local/etc/php/conf.d/opcache-recommended.ini /usr/local/etc/php/conf.d/opcache-recommended.ini

# 시스템 의존성 설치 (미디어위키 공식 이미지 Dockerfile 유지+확장)
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        git \
        librsvg2-bin \
        imagemagick \
    	# Required for SyntaxHighlighting
        python3 \
        # Thmbro 확장에 필요 https://github.com/StarCitizenTools/mediawiki-extensions-Thumbro#requirements
        libvips-tools \
        webp \
        # entrypoint.sh에서 non-root로 권한 조정용
        gosu \
        # non-bundled 폴더 변경 시 자동으로 symlink-extensions.sh 실행용
        inotify-tools \
    ; \
    rm -rf /var/lib/apt/lists/*

# 추가 PHP 확장 설치 (FrankenPHP 방식)
RUN install-php-extensions \
    intl \
    mbstring \
    mysqli \
    opcache \
    apcu \
    luasandbox \
    calendar

# SQLite 데이터 디렉토리 설정
RUN set -eux; \
    mkdir -p /var/www/data; \
    chown -R www-data:www-data /var/www/data

COPY internal/ /internal/

COPY --chmod=+x entrypoint.sh /usr/local/bin/
COPY --chmod=+x symlink-extensions.sh /usr/local/bin/symlink-extensions

ENTRYPOINT ["entrypoint.sh"]

# 컨테이너 시작 명령 (FrankenPHP 실행)
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]