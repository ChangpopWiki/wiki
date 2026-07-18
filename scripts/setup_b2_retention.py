#!/usr/bin/env python3
"""
setup_b2_retention.py

Backblaze B2에 업로드되는 백업 파일의 리텐션 정책을 설정하는 스크립트.
daily/ 는 MAX_DAILY_BACKUPS일 후 삭제, monthly/ 는 과거 버전만 정리하고 최신본은 영구 보존.
버킷 최초 생성 시, 또는 정책 변경 시에만 실행하면 됩니다.

실행 방법: README.md 참고
"""

import os
import sys

from dotenv import load_dotenv
from b2sdk.v2 import B2Api, InMemoryAccountInfo, LifecycleRule

REQUIRED_VARS = [
    "B2_KEY_ID",
    "B2_KEY",
    "B2_BUCKET_NAME",
    "B2_BUCKET_PATH",
    "MAX_DAILY_BACKUPS",
]


def load_config():
    load_dotenv()
    missing = [name for name in REQUIRED_VARS if not os.environ.get(name)]
    if missing:
        sys.exit(f"필수 환경변수가 설정되어 있지 않습니다: {', '.join(missing)}")
    return {name: os.environ[name] for name in REQUIRED_VARS}


def build_lifecycle_rules(bucket_path: str, max_daily_backups: int) -> list[LifecycleRule]:

    # daily 백업은 max_daily_backups일 후 삭제.
    daily_rule = LifecycleRule(
        fileNamePrefix=f"{bucket_path}/daily/",
        daysFromUploadingToHiding=max_daily_backups,
        daysFromHidingToDeleting=1,
    )

    # monthly 백업은 daysFromUploadingToHiding 없음. 영구 보존.
    # 매일 ln -f로 교체되며 생기는 과거 hidden 버전은 1일 후 바로 정리.
    monthly_rule = LifecycleRule(
        fileNamePrefix=f"{bucket_path}/monthly/",
        daysFromHidingToDeleting=1,
    )

    return [daily_rule, monthly_rule]

def main():
    config = load_config()

    account_info = InMemoryAccountInfo()
    b2_api = B2Api(account_info)
    b2_api.authorize_account("production", config["B2_KEY_ID"], config["B2_KEY"])

    bucket = b2_api.get_bucket_by_name(config["B2_BUCKET_NAME"])
    rules = build_lifecycle_rules(config["B2_BUCKET_PATH"], int(config["MAX_DAILY_BACKUPS"]))

    # 주의: update()는 버킷의 라이프사이클 룰 전체를 덮어씀. 룰을 추가/변경할 때도
    # 항상 이 스크립트 하나에 전체 룰셋을 반영해서 관리할 것.
    bucket.update(lifecycle_rules=rules)
    print(f"'{config['B2_BUCKET_NAME']}' 버킷에 리텐션 정책을 적용했습니다.")


if __name__ == "__main__":
    main()