#!/usr/bin/env python3
# 미디어위키 코어에 번들되지 않은 확장과 스킨을 설치/업데이트하는 스크립트
import asyncio
import json
import os
import re
import sys
import tomllib
import shutil
import urllib.parse
import urllib.request
from dataclasses import dataclass, field
from typing import Literal

EXT_DIR  = "/non-bundled/extensions"
SKIN_DIR = "/non-bundled/skins"
LOADER_PATH = "/var/www/html/extensions-loader/ExtensionsLoader.php"

# ── 데이터 클래스 ──────────────────────────────────────────

@dataclass
class InstallTarget:
    name: str
    url: str | None = None
    branch: str | None = None
    submodules: bool = False
    latest_tag: bool = False

@dataclass
class Config:
    mediawiki_version: str
    max_concurrent: int
    extensions: list[InstallTarget] = field(default_factory=list)
    skins: list[InstallTarget] = field(default_factory=list)
    bundled_extensions: list[InstallTarget] = field(default_factory=list)
    bundled_skins: list[InstallTarget] = field(default_factory=list)


def load_config(path: str = "/config/extensions.toml") -> Config:
    print(f"설정 로드 중: {path}")

    try:
        with open(path, "rb") as f:
            raw = tomllib.load(f)
    except tomllib.TOMLDecodeError as e:
        print(f"❌ extensions.toml 파싱 실패: {e}", file=sys.stderr)
        print(f"   파일: {path}", file=sys.stderr)
        sys.exit(1)
    except FileNotFoundError:
        print(f"❌ 설정 파일을 찾을 수 없습니다: {path}", file=sys.stderr)
        sys.exit(1)

    cfg = raw.get("config", {})
    config = Config(
        mediawiki_version  = cfg.get("mediawiki_version", "REL1_46"),
        max_concurrent     = cfg.get("max_concurrent", 5),
        extensions         = [InstallTarget(name=k, **v) for k, v in raw.get("extensions", {}).items()],
        skins              = [InstallTarget(name=k, **v) for k, v in raw.get("skins", {}).items()],
        bundled_extensions = [InstallTarget(name=k, **v) for k, v in raw.get("bundled_extensions", {}).items()],
        bundled_skins      = [InstallTarget(name=k, **v) for k, v in raw.get("bundled_skins", {}).items()],
    )
    print(f"  확장 {len(config.extensions)}개, 스킨 {len(config.skins)}개, "
          f"번들 확장 {len(config.bundled_extensions)}개, 번들 스킨 {len(config.bundled_skins)}개")
    return config

# ── Git 유틸 ───────────────────────────────────────────────

async def _run(*cmd: str, cwd: str | None = None) -> tuple[int, str]:
    proc = await asyncio.create_subprocess_exec(
        *cmd,
        cwd=cwd,
        stdout=asyncio.subprocess.DEVNULL,
        stderr=asyncio.subprocess.PIPE,
    )
    _, stderr = await proc.communicate()
    return proc.returncode, stderr.decode().strip()

async def git_clone(url: str, dest: str, branch: str) -> tuple[bool, str]:
    code, err = await _run("git", "clone", "--depth", "1", "-b", branch, url, dest)
    return code == 0, err

async def git_update(dest: str, branch: str) -> bool:
    steps = [
        ("fetch", ["git", "fetch", "--depth", "1", "origin", branch]),
        ("reset", ["git", "reset", "--hard", "FETCH_HEAD"]),
        ("clean", ["git", "clean", "-fd"]),
    ]
    for step_name, cmd in steps:
        code, err = await _run(*cmd, cwd=dest)
        if code != 0:
            print(f"  ✗ git {step_name} 실패: {err}")
            return False
    return True

async def git_submodule_update(dest: str) -> bool:
    code, _ = await _run("git", "submodule", "update", "--init", "--depth", "1", cwd=dest)
    return code == 0

async def get_remote_hash(url: str, branch: str) -> str | None:
    for ref in (f"refs/heads/{branch}", f"refs/tags/{branch}"):
        proc = await asyncio.create_subprocess_exec(
            "git", "ls-remote", url, ref,
            stdout=asyncio.subprocess.PIPE,
            stderr=asyncio.subprocess.DEVNULL,
        )
        stdout, _ = await proc.communicate()
        if proc.returncode == 0 and stdout:
            parts = stdout.decode().split()
            if parts:
                return parts[0]
    return None

async def get_local_hash(dest: str) -> str | None:
    proc = await asyncio.create_subprocess_exec(
        "git", "rev-parse", "HEAD",
        cwd=dest,
        stdout=asyncio.subprocess.PIPE,
        stderr=asyncio.subprocess.DEVNULL,
    )
    stdout, _ = await proc.communicate()
    return stdout.decode().strip() if proc.returncode == 0 else None

async def resolve_branch(url: str, primary: str, has_custom_branch: bool) -> tuple[str, str] | None:
    """유효한 브랜치와 해시를 반환. 찾지 못하면 None."""
    fallbacks = [primary] + ([] if has_custom_branch else ["master", "main"])
    for b in fallbacks:
        h = await get_remote_hash(url, b)
        if h:
            return b, h
    return None

def _parse_gerrit(url: str) -> str | None:
    """
    Gerrit URL에서 project 경로 추출. 아니면 None.
    예: https://gerrit.wikimedia.org/r/mediawiki/extensions/Foo → mediawiki/extensions/Foo
    """
    m = re.match(r"https://gerrit\.wikimedia\.org/r/(.+?)(?:\.git)?$", url)
    return m.group(1) if m else None

def _parse_semver(tag: str) -> tuple[int, ...]:
    """태그명에서 숫자 튜플 추출. 정렬용."""
    parts = re.findall(r"\d+", tag)
    return tuple(int(p) for p in parts) if parts else (0,)

def _parse_github(url: str) -> tuple[str, str] | None:
    """GitHub URL에서 (owner, repo) 추출. 아니면 None."""
    m = re.match(r"https://github\.com/([^/]+)/([^/]+?)(?:\.git)?$", url)
    return (m.group(1), m.group(2)) if m else None

async def get_latest_github_release(url: str) -> tuple[str, str] | None:
    """
    GitHub Releases API로 latest release 태그명과 커밋 해시를 반환.
    GitHub URL이 아니거나 release가 없으면 None.
    """
    gh = _parse_github(url)
    if not gh:
        return None

    owner, repo = gh
    api_url = f"https://api.github.com/repos/{owner}/{repo}/releases/latest"

    try:
        req = urllib.request.Request(
            api_url,
            headers={"Accept": "application/vnd.github+json"},
        )
        token = os.getenv("GITHUB_TOKEN")
        if token:
            req.add_header("Authorization", f"Bearer {token}")
        with urllib.request.urlopen(req, timeout=10) as resp:
            data = json.load(resp)
    except Exception as e:
        print(f"  ⚠ GitHub API 요청 실패 ({api_url}): {e}")
        return None

    tag = data.get("tag_name")
    if not tag:
        return None

    commit_hash = await get_remote_hash(url, tag)
    return (tag, commit_hash) if commit_hash else None

async def get_latest_gerrit_tag(url: str) -> tuple[str, str] | None:
    """Gerrit Tags API로 latest 태그명과 커밋 해시를 반환."""
    project = _parse_gerrit(url)
    if not project:
        return None

    encoded = urllib.parse.quote(project, safe="")
    api_url = f"https://gerrit.wikimedia.org/r/projects/{encoded}/tags"

    try:
        req = urllib.request.Request(api_url, headers={"Accept": "application/json"})
        with urllib.request.urlopen(req, timeout=10) as resp:
            text = resp.read().decode()
    except Exception as e:
        print(f"  ⚠ Gerrit API 요청 실패 ({api_url}): {e}")
        return None

    # Gerrit XSSI 방지 prefix 제거
    text = re.sub(r"^\)]\}'\n", "", text)
    try:
        tags = json.loads(text)
    except json.JSONDecodeError as e:
        print(f"  ⚠ Gerrit API 응답 파싱 실패: {e}")
        return None

    candidates = []
    for tag in tags:
        name = tag.get("ref", "").removeprefix("refs/tags/")
        if not name:
            continue
        candidates.append((_parse_semver(name), name))

    if not candidates:
        return None

    _, best_tag = max(candidates)
    commit_hash = await get_remote_hash(url, best_tag)
    return (best_tag, commit_hash) if commit_hash else None

async def get_latest_tag(url: str) -> tuple[str, str] | None:
    """GitHub 또는 Gerrit URL을 감지해 latest 태그와 해시를 반환."""
    if _parse_github(url):
        return await get_latest_github_release(url)
    if _parse_gerrit(url):
        return await get_latest_gerrit_tag(url)
    print(f"  ⚠ latest_tag 지원 불가 URL (GitHub/Gerrit만 지원): {url}")
    return None

# ── 설치 로직 ──────────────────────────────────────────────

async def install(
    kind: Literal["extensions", "skins"],
    target: InstallTarget,
    cfg: Config,
    semaphore: asyncio.Semaphore,
):
    name = target.name
    base_dir = EXT_DIR if kind == "extensions" else SKIN_DIR
    dest = f"{base_dir}/{name}"
    os.makedirs(base_dir, exist_ok=True)

    url = target.url or f"https://gerrit.wikimedia.org/r/mediawiki/{kind}/{name}"
    primary = target.branch or cfg.mediawiki_version

    async with semaphore:
        # ── 브랜치/태그 결정 ──────────────────────────────
        if target.latest_tag:
            resolved = await get_latest_tag(url)
            if not resolved:
                print(f"[{name}] ⚠ latest 태그 확인 불가 — 스킵")
                return
            resolved_branch, remote_hash = resolved
            label = f"태그 {resolved_branch} (latest release)"
        else:
            resolved = await resolve_branch(url, primary, target.branch is not None)
            if not resolved:
                print(f"[{name}] ⚠ 원격 브랜치 확인 불가 — 스킵 (수동 확인 권장)")
                return
            resolved_branch, remote_hash = resolved
            label = f"{resolved_branch} (폴백)" if resolved_branch != primary else resolved_branch
        # ──────────────────────────────────────────────────

        if os.path.exists(dest):
            local_hash = await get_local_hash(dest)
            if not local_hash:
                print(f"[{name}] ⚠ 로컬 해시 확인 불가 — 스킵 (수동 확인 권장)")
                return
            if local_hash == remote_hash:
                print(f"[{name}] 최신 상태, 스킵 ({remote_hash[:8]})")
                return

            print(f"[{name}] 업데이트 감지 ({local_hash[:8]} → {remote_hash[:8]}) [{label}], 업데이트 중...")
            if not await git_update(dest, resolved_branch):
                raise RuntimeError(f"[{name}] 업데이트 실패: {url}")
            print(f"[{name}] ✓ 업데이트 완료")

        else:
            print(f"[{name}] {label} 클론 중...")
            ok, err = await git_clone(url, dest, resolved_branch)
            if not ok:
                raise RuntimeError(f"[{name}] 클론 실패: {url}\n    → {err}")
            print(f"[{name}] ✓ 클론 완료")

    if target.submodules:
        print(f"[{name}] 서브모듈 초기화 중...")
        if not await git_submodule_update(dest):
            raise RuntimeError(f"[{name}] 서브모듈 초기화 실패")
        print(f"[{name}] ✓ 서브모듈 완료")


async def install_ext(target: InstallTarget, cfg: Config, semaphore: asyncio.Semaphore):
    await install("extensions", target, cfg, semaphore)

async def install_skin(target: InstallTarget, cfg: Config, semaphore: asyncio.Semaphore):
    await install("skins", target, cfg, semaphore)

def generate_php(cfg: Config, path: str = LOADER_PATH):
    lines = ["<?php", "// 확장 설치 스크립트에 의해 자동 생성됨", ""]
    for t in cfg.bundled_extensions + cfg.extensions:
        lines.append(f"wfLoadExtension('{t.name}');")
    if cfg.bundled_skins or cfg.skins:
        lines.append("")
    for t in cfg.bundled_skins + cfg.skins:
        lines.append(f"wfLoadSkin('{t.name}');")
    with open(path, "w") as f:
        f.write("\n".join(lines) + "\n")
    print(f"✓ {path} 생성 완료 "
          f"(확장 {len(cfg.bundled_extensions) + len(cfg.extensions)}개, "
          f"스킨 {len(cfg.bundled_skins) + len(cfg.skins)}개)")

async def needs_sync(cfg: Config) -> bool:
    expected_ext  = {t.name for t in cfg.extensions}
    expected_skin = {t.name for t in cfg.skins}
    actual_ext    = {e for e in os.listdir(EXT_DIR) if os.path.isdir(f"{EXT_DIR}/{e}")}
    actual_skin   = {s for s in os.listdir(SKIN_DIR) if os.path.isdir(f"{SKIN_DIR}/{s}")}

    missing_ext   = expected_ext - actual_ext
    orphan_ext    = actual_ext - expected_ext
    missing_skin  = expected_skin - actual_skin
    orphan_skin   = actual_skin - expected_skin

    if missing_ext:
        print(f"  누락된 확장: {missing_ext}")
    if missing_skin:
        print(f"  누락된 스킨: {missing_skin}")

    if orphan_ext:
        print(f"  미등록 확장 디렉토리 삭제 중: {orphan_ext}")
        for name in orphan_ext:
            shutil.rmtree(f"{EXT_DIR}/{name}")
    if orphan_skin:
        print(f"  미등록 스킨 디렉토리 삭제 중: {orphan_skin}")
        for name in orphan_skin:
            shutil.rmtree(f"{SKIN_DIR}/{name}")

    return bool(missing_ext or orphan_ext or missing_skin or orphan_skin)

# ── 진입점 ─────────────────────────────────────────────────

async def main():
    cfg = load_config()

    if "--generate-loader" in sys.argv:
        generate_php(cfg)
        return

    if "--check" in sys.argv:
        print("동기화 상태 확인 중...")
        sync_needed = await needs_sync(cfg)
        print("동기화 필요" if sync_needed else "✓ 동기화 불필요")
        raise SystemExit(1 if sync_needed else 0)

    print(f"설치/업데이트 시작 ({len(cfg.extensions)}개 확장, {len(cfg.skins)}개 스킨)...")
    semaphore = asyncio.Semaphore(cfg.max_concurrent)

    tasks = (
        [install_ext(t, cfg, semaphore) for t in cfg.extensions] +
        [install_skin(t, cfg, semaphore) for t in cfg.skins]
    )

    results = await asyncio.gather(*tasks, return_exceptions=True)
    failures = [r for r in results if isinstance(r, Exception)]

    generate_php(cfg)

    if failures:
        print(f"\n[실패 목록] ({len(failures)}개)")
        for e in failures:
            print(f"  ✗ {e}")
        raise SystemExit(1)

    print(f"✓ 전체 {len(tasks)}개 완료")


asyncio.run(main())