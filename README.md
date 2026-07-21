# ChangpopWiki/wiki

창팝위키 서버의 미디어위키 docker compose 스택입니다.

## 준비
**환경변수 파일**:
```bash
cp .env.example .env
```
[.env.example](.env.example) 파일을 .env 파일로 복사하고 필요한 환경 변수를 채워주세요.

**시크릿**: `./secrets` 디렉토리에 `backup` 서비스에서 페이지 덤프 백업용으로 사용되는 `backup-pages-github-app.pem` 파일이 존재해야 합니다.

### 개발 환경

[scripts/set-dev.sh](scripts/set-dev.sh) 스크립트를 실행하면 개발 환경이 활성화됩니다. 
이 스크립트는 [compose.dev.yaml](compose.dev.yaml)을 compose.override.yaml으로, 
[DebugSettings.example.php](config/mediawiki/DebugSettings.example.php)을 DebugSettings.php로 복사합니다. 
기존 파일이 존재하면 덮어쓰지 않습니다. 
[scripts/clear-dev.sh](scripts/clear-dev.sh) 스크립트는 해당 파일들을 삭제해 개발 환경을 해제합니다. 
각각 `just set-dev`, `just clear-dev`로 빠른 실행이 가능합니다.

개발 환경에서의 기본 서비스 포트는 http://localhost:10597 입니다. `DEV_SERVICE_PORT` 환경 변수로 변경 가능합니다.

## 서비스

### mediawiki

미디어위키 서비스입니다.

* Dockerfile: [build-context/mediawiki/Dockerfile](build-context/mediawiki/Dockerfile)

frankenphp를 사용하기 위해 커스텀 빌드 이미지를 사용합니다.

- 미디어위키 설정 - [LocalSettings.php](config/mediawiki/LocalSettings.php)
- 사용하는 확장/스킨 정의 - [extensions.toml](config/mediawiki/extensions.toml)

#### 컨테이너 재시작 없이 LocalSettings.php 변경사항 즉시 반영하기

```bash
docker compose exec mediawiki curl -s http://localhost:9090/opcache-reset.php
```
또는 `just reset-opcache`

개발 환경에서는 [php-dev.ini](dev/php-dev.ini)가 마운트되며 
해당 파일 안에 `opcache.revalidate_freq=0`이 설정되어 있어 변경사항이 항상 즉시 반영되므로 필요하지 않습니다.

### setup

[install_extensions.py](build-context/setup/install_extensions.py)를 실행하여 
[extensions.toml](config/mediawiki/extensions.toml)에 정의된 확장/스킨을 설치합니다. 
확장 목록이 변경되었을 때 수동으로 실행할 수도 있습니다. 
(`docker compose up setup` 또는 `just up setup`)

* Dockerfile: [build-context/setup/Dockerfile](build-context/setup/Dockerfile)

### setup-force

setup 서비스와 달리, 확장 목록이 기존에서 변경되지 않았더라도 설치 스크립트를 강제로 실행합니다.
(`docker compose up setup-force` 또는 `just up setup-force`)

### database
데이터베이스 서비스입니다. 미디어위키 권장인 MariaDB를 사용합니다. 창팝위키는 Cargo 확장용 데이터베이스를 별도로 사용하므로, 초기 실행 시 두 개의 DB를 생성하기 위해 별도로 [init-db.sh](mounted/database/init-db.sh) 파일을 사용합니다

### backup

백업은 `CRON_TIME` 환경 변수에 정의된 시간에 supercronic을 통해 실행됩니다.

* Dockerfile: [build-context/backup/Dockerfile](build-context/backup/Dockerfile)

#### DB 백업

데이터베이스를 덤프하고 리모트 백업 저장소인 Backblaze B2에 백업합니다. 생성된 DB 백업은 로컬에서는 [./data/db_dump](./data/db_dump)에서 찾을 수 있습니다.
`just backup-db`으로 백업을 직접 시작할 수도 있습니다.

자세한 구현은 [backup_db.sh](build-context/backup/scripts/backup_db.sh)을 참고하세요.

##### 복원하기

세 가지 방식으로 복원할 수 있습니다.

1. [./data/db_dump](./data/db_dump)에 존재하는 로컬 백업 파일을 이용하여 복원합니다. `just restore-db {파일경로}`와 같이 실행할 수 있습니다. {{file}}에는 복원할 파일 이름을 지정해야 합니다.
2. 호스트에 존재하는 덤프 파일을 이용하여 복원합니다. `just restore-db < {파일경로}`와 같이 실행할 수 있습니다
3. 리모트 백업에서 덤프 파일을 스트리밍 받아 복원합니다. `just restore-db remote:{파일경로}`와 같이 복원할 수 있습니다.

자세한 구현은 [restore_db.sh](build-context/backup/scripts/restore_db.sh)을 참고하세요.

##### B2 리텐션 정책 적용

daily/monthly 리모트 백업 삭제 정책은 B2 버킷의 라이프사이클 룰로 관리되며, 적용 스크립트 [setup_b2_retention.py](scripts/setup_b2_retention.py)에 정의되어 있습니다. 1회용 도커 컨테이너를 통해 실행되므로 실행하려면 래퍼 스크립트인 [setup_b2_retention.sh](scripts/setup_b2_retention.sh)을 실행하세요. `just setup-b2-retention`으로도 실행할 수 있습니다.

#### 페이지 백업

깃허브 저장소([ChangpopWiki/dump](https://github.com/ChangpopWiki/dump))에 공개 페이지 백업도 수행됩니다. `just backup-pages`으로 수동으로 백업할 수 있습니다. 

##### 작동 방식

mediawiki 컨테이너에 dumpBackup 스크립트의 결과를 보내기 위한 내부 엔드포인트인 [dump-pages.php](build-context/mediawiki/internal/dump-pages.php)가 존재합니다. [backup_pages.py](build-context/backup/scripts/backup_pages.py) 스크립트는 이 엔드포인트를 호출하여 페이지 덤프를 받아온 뒤 Github API를 통해 백업합니다.



## 쓸모 있는 명령어

자주 사용되거나 필요할 수 있는 명령어를 모았습니다.

[just](https://just.systems/)가 설치되어 있다면 `just <단축 명령>`으로 간단하게 실행이 가능합니다. 단축 명령어는 아래 표를 참조하세요. 또는 just로 실행 가능한 목록을 보기 위해 `just`만 입력할 수 있습니다. (`just --list`가 실행됩니다.) 

| 명령                                                                                                                             | just 단축               | 설명                                                                                                                                                                                 |
|--------------------------------------------------------------------------------------------------------------------------------|-----------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `docker compose exec mediawiki curl -s http://localhost:9090/opcache-reset.php`                                                | `reset-opcache`       | LocalSettings.php와 같은 파일의 변경 사항을 컨테이너 재시작 없이 즉시 반영할 수 있습니다. 이는 프로덕션에서 도움이 되는 명령으로, 개발 환경에서는 dev/php-dev.ini가 마운트되며 해당 파일 안에 opcache.revalidate_freq=0이 설정되어 있어 즉시 반영되므로 필요하지 않습니다. |
| `docker compose exec mediawiki php maintenance/run.php {{args}}`                                                               | `run {{args}}`        | 미디어위키 유지보수 스크립트를 실행합니다. {{args}}에 실행할 스크립트 이름을 지정하세요. (예: `just run update`) 일부 스크립트들은 run까지 생략한 단축을 제공합니다 (예: `just update`) 자세한 목록은 [Justfile](Justfile)을 참고하세요.                 |
| `docker compose exec backup backup-db.sh`                                                                                      | `backup-db`           | DB 백업을 수동으로 실행합니다.                                                                                                                                                                 |
| `docker compose exec backup restore-db.sh {{file}}`                                                                            | `restore-db {{file}}` | DB 덤프로부터 DB를 복원합니다. 덤프 파일이 `./data/db_dump`에 존재해야 합니다. {{file}} 에는 파일 이름을 지정해야 합니다.                                                                                                |
| `docker compose exec backup fetch_db_backup.sh`                                                                                | `fetch-db-backup`     | DB 백업 파일을 리모트에서 가져옵니다.                                                                                                                                                             |
| `docker compose exec backup backup_pages.py`                                                                                   | `backup-pages`        | 위키 페이지 백업을 수동으로 실행합니다.                                                                                                                                                             |
| `docker compose exec database sh -c 'mariadb -u root -p$MARIADB_ROOT_PASSWORD changpopwiki -e "TRUNCATE TABLE ed_url_cache;"'` | -                     | 미디어위키 업그레이드 후 [External Data](https://www.mediawiki.org/wiki/Extension:External_Data) 확장의 `ed_url_cache` 테이블이 문제를 일으킨 경우가 있었습니다. 테이블을 날려서 문제를 해결합니다.                               |
| `docker compose up setup`                                                                                                      | `up setup`            | `setup` 서비스를 수동으로  실행합니다. 이 서비스는 전체 compose up 시에 이미 실행되지만, 수동 시작은 컨테이너 재시작 없이 [extensions.toml](config/mediawiki/extensions.toml) 목록을 바꾸고 변경 사항을 반영하는 데 유용합니다.                    |
| `docker compose up setup-force`                                                                                                | `up setup-force`      | `setup` 서비스는 확장 목록이 기존에서 변경되지 않았다면 설치 스크립트를 진행하지 않습니다. 확장 강제 업데이트가 필요한 경우 대신 setup-force 서비스를 이용할 수 있습니다.                                                                          |
| `./scripts/setup_b2_retention.sh`                                                                                              | `setup-b2-retention`  | B2 리모트 버킷에 리텐션 정책을 적용합니다.                                                                                                                                                          |
| `./scripts/set-dev.sh`                                                                                                         | `set-dev`             | 개발 환경 설정 (compose.override.yaml, DebugSettings.php 생성). 기존 파일은 덮어쓰지 않습니다.                                                                                                          |
| `./scripts/clear-dev.sh`                                                                                                       | `clear-dev`           | 개발 환경 해제 (compose.override.yaml, DebugSettings.php 삭제).                                                                                                                            |