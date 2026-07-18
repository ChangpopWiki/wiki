#!/usr/bin/env python3
# /// script
# requires-python = ">=3.12"
# dependencies = [
#     "requests==2.32.3",
#     "PyJWT==2.9.0",
#     "cryptography==43.0.1",
# ]
# ///
"""dump-pages backup tool

마디아위키 내부 엔드포인트(dump-pages.php)에서 두 개의 XML 덤프를 받아,
GitHub App installation token으로 인증한 뒤 GraphQL createCommitOnBranch
mutation으로 한 커밋에 반영한다. git 저장소를 로컬에 두지 않는다.
"""

import base64
import os
import sys
import time
from datetime import datetime, timedelta, timezone

import hashlib

import jwt
import requests

ENDPOINT = "http://mediawiki:9090"
APP_ID = os.environ["GITHUB_APP_ID"]
INSTALLATION_ID = os.environ["GITHUB_INSTALLATION_ID"]
REPO = os.environ["GITHUB_REPO"]
BRANCH = "main"
GITHUB_SECRET_KEY_PATH = os.environ["GITHUB_SECRET_KEY_PATH"]
KST = timezone(timedelta(hours=9))

USER_AGENT = "backup-pages"


def create_app_jwt() -> str:
    with open(GITHUB_SECRET_KEY_PATH, "r") as f:
        private_key = f.read()
    now = int(time.time())
    return jwt.encode(
        {"iat": now - 60, "exp": now + 540, "iss": APP_ID},
        private_key,
        algorithm="RS256",
    )


def get_installation_token(app_jwt: str) -> str:
    response = requests.post(
        f"https://api.github.com/app/installations/{INSTALLATION_ID}/access_tokens",
        headers={
            "Authorization": f"Bearer {app_jwt}",
            "Accept": "application/vnd.github+json",
            "User-Agent": USER_AGENT,
        },
    )
    response.raise_for_status()
    return response.json()["token"]


def get_branch_head_oid(token: str) -> str:
    response = requests.get(
        f"https://api.github.com/repos/{REPO}/git/ref/heads/{BRANCH}",
        headers={
            "Authorization": f"Bearer {token}",
            "Accept": "application/vnd.github+json",
            "User-Agent": USER_AGENT,
        },
    )
    response.raise_for_status()
    return response.json()["object"]["sha"]


def git_blob_sha(content: bytes) -> str:
    header = f"blob {len(content)}\0".encode()
    return hashlib.sha1(header + content).hexdigest()


def get_existing_oid(token: str, path: str) -> str | None:
    query = """
    query($repo: String!, $owner: String!, $expression: String!) {
      repository(owner: $owner, name: $repo) {
        object(expression: $expression) {
          ... on Blob { oid }
        }
      }
    }
    """
    owner, repo_name = REPO.split("/", 1)
    variables = {"owner": owner, "repo": repo_name, "expression": f"{BRANCH}:{path}"}
    response = requests.post(
        "https://api.github.com/graphql",
        json={"query": query, "variables": variables},
        headers={
            "Authorization": f"Bearer {token}",
            "User-Agent": USER_AGENT,
        },
    )
    response.raise_for_status()
    body = response.json()
    if body.get("errors"):
        raise RuntimeError(f"GraphQL 오류: {body['errors']}")
    obj = body["data"]["repository"]["object"]
    return obj["oid"] if obj else None

def create_commit(token: str, head_oid: str, message: str, all_pages: bytes, lyric_only: bytes) -> str:
    query = """
    mutation($repo: String!, $branch: String!, $oid: GitObjectID!, $msg: String!, $allContent: Base64String!, $lyricContent: Base64String!) {
      createCommitOnBranch(input: {
        branch: { repositoryNameWithOwner: $repo, branchName: $branch }
        message: { headline: $msg }
        fileChanges: {
          additions: [
            { path: "dump_allPages.xml", contents: $allContent },
            { path: "dump_lyricOnly.xml", contents: $lyricContent }
          ]
        }
        expectedHeadOid: $oid
      }) {
        commit { oid url }
      }
    }
    """
    variables = {
        "repo": REPO,
        "branch": BRANCH,
        "oid": head_oid,
        "msg": message,
        "allContent": base64.b64encode(all_pages).decode(),
        "lyricContent": base64.b64encode(lyric_only).decode(),
    }
    response = requests.post(
        "https://api.github.com/graphql",
        json={"query": query, "variables": variables},
        headers={
            "Authorization": f"Bearer {token}",
            "User-Agent": USER_AGENT,
        },
    )
    response.raise_for_status()
    body = response.json()

    if body.get("errors"):
        raise RuntimeError(f"GraphQL 오류: {body['errors']}")

    return body["data"]["createCommitOnBranch"]["commit"]["url"]


def main() -> int:
    print("덤프 파일 다운로드 중...")
    all_pages = requests.get(f"{ENDPOINT}/dump-pages.php")
    all_pages.raise_for_status()
    lyric_only = requests.get(f"{ENDPOINT}/dump-pages.php", params={"filter": "namespace:3200"})
    lyric_only.raise_for_status()

    app_jwt = create_app_jwt()
    token = get_installation_token(app_jwt)

    # GitHub 저장소에 이미 존재하는 dump_allPages.xml과 dump_lyricOnly.xml의 OID를 가져와서
    # 현재 다운로드한 덤프 파일의 SHA-1과 비교한다. 변경사항이 없으면 커밋을 생략한다.
    has_dump_changes = (
        get_existing_oid(token, "dump_allPages.xml") != git_blob_sha(all_pages.content)
        or get_existing_oid(token, "dump_lyricOnly.xml") != git_blob_sha(lyric_only.content)
    )

    if not has_dump_changes:
        print("변경사항 없음 — 커밋 생략")
        return 0

    head_oid = get_branch_head_oid(token)

    message = f"자동 백업: {datetime.now(KST):%Y-%m-%d %H:%M:%S} KST"
    commit_url = create_commit(token, head_oid, message, all_pages.content, lyric_only.content)

    print(f"백업 완료: {commit_url}")
    return 0


if __name__ == "__main__":
    try:
        sys.exit(main())
    except Exception as e:
        print(f"백업 실패: {e}", file=sys.stderr)
        sys.exit(1)