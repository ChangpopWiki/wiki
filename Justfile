default: 
    just --list

up *args="--detach":
    docker compose up {{args}}

down *args:
    docker compose down {{args}}

pull *args:
    docker compose pull {{args}}

build *args:
    docker compose build {{args}}

logs *args:
    docker compose logs {{args}}

exec *args:
    docker compose exec {{args}}

# DB 백업 수동 실행
backup-db:
    docker compose exec backup backup_db.sh

# DB 복원
restore-db *args:
    docker compose exec -T backup restore_db.sh {{args}}

# DB 백업 파일을 리모트에서 가져오기
fetch-db-backup *args:
    docker compose exec backup fetch_db_backup.sh {{args}}

# 페이지 백업 수동 실행
backup-pages:
    docker compose exec backup backup_pages.py

# reset-opcache - LocalSettings.php를 수정하고 컨테이너 재시작 없이 변경사항을 적용할 때 사용할 수 있습니다.
reset-opcache:
    docker compose exec mediawiki curl -s http://localhost:9090/opcache-reset.php

# 미디어위키 유지보수 스크립트를 실행합니다. 예: just run update, just run runJob
run *args:
    docker compose exec mediawiki php maintenance/run.php {{args}}

# 미디어위키 스크립트 update.php 실행
update *args:
    just run update {{args}}

# 미디어위키 스크립트 runJobs.php 실행
runJobs *args:
    just run runJobs {{args}}

# 미디어위키 스크립트 showJobs.php 실행
showJobs *args:
    just run showJobs {{args}}

# 미디어위키 스크립트 manageJobs.php 실행
manageJobs *args:
    just run manageJobs {{args}}

# B2 리모트 버킷에 리텐션 정책을 적용
setup-b2-retention:
    ./scripts/setup_b2_retention.sh

# 개발 환경 설정 (기존 파일이 있으면 덮어쓰지 않음)
set-dev:
    ./scripts/set-dev.sh

# 개발 환경 해제 (compose.override.yaml, DebugSettings.php 삭제)
clear-dev:
    ./scripts/clear-dev.sh