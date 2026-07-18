# 빌드 인자 정의
ARG TARGETARCH
ARG SUPERCRONIC_VERSION=v0.2.29

# Supercronic 다운로드 (amd64 버전)
FROM scratch AS supercronic-amd64
ARG SUPERCRONIC_VERSION
ADD --checksum=sha256:87625cd179eff21226f0be6f2f47dd357037064598e6c1f9ffcbd0335d402bbd \
    https://github.com/aptible/supercronic/releases/download/${SUPERCRONIC_VERSION}/supercronic-linux-amd64 \
    /supercronic

# Supercronic 다운로드 (arm64 버전)
FROM scratch AS supercronic-arm64
ARG SUPERCRONIC_VERSION
ADD --checksum=sha256:063799a43c1eac082d83ac59a43a6896b50d69aa1f533c2cc6a5376cb2bfff89 \
    https://github.com/aptible/supercronic/releases/download/${SUPERCRONIC_VERSION}/supercronic-linux-arm64 \
    /supercronic

# 타겟 아키텍처에 해당하는 Supercronic 선택
FROM supercronic-${TARGETARCH} AS supercronic

# 최종 이미지: Python Alpine 기반
FROM python:alpine

# 필수 도구 설치 (MariaDB 클라이언트, Rclone, su-exec)
RUN apk add --no-cache mariadb-client rclone su-exec

# 타임존 설정 (Asia/Seoul)
RUN apk add --no-cache tzdata \
    && cp /usr/share/zoneinfo/Asia/Seoul /etc/localtime \
    && echo "Asia/Seoul" > /etc/timezone \
    && apk del tzdata

# Supercronic 바이너리 복사
COPY --chmod=+x --from=supercronic /supercronic /usr/local/bin/supercronic

# 실행 스크립트 복사
COPY --chmod=+x scripts/ /usr/local/bin/

# 스크립트에서 의존성 설치
RUN pip install --no-cache-dir --break-system-packages --requirements-from-script /usr/local/bin/backup_pages.py

# 엔트리포인트 스크립트 복사
COPY --chmod=+x entrypoint.sh /entrypoint.sh

# 컨테이너 시작 시 실행할 엔트리포인트 설정
ENTRYPOINT ["/entrypoint.sh"]