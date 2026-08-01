"""Session-wide pytest infrastructure: temporary-directory retention and
cleanup verification.

The harness suite creates large per-test artifacts (scratch git
repositories, benchmark run directories, evidence trees), so pytest's
default retention — keep the last 3 sessions under
``<tempdir>/pytest-of-<user>`` regardless of outcome — accumulates
hundreds of megabytes and, when sessions are interrupted, directories
pile up until the next successful session's cleanup runs.

``pytest.ini`` configures pytest's own policy (``failed`` / count 2);
this module complements it with three session hooks:

1. ``pytest_sessionstart`` prunes the pytest temp root so that sessions
   killed mid-run (which skip pytest's session-finish cleanup) cannot
   accumulate beyond the retention policy;
2. ``pytest_sessionfinish`` verifies, after pytest's own cleanup, that
   no abandoned numbered directories remain beyond the policy;
3. ``pytest_terminal_summary`` reports the cleanup outcome, including
   a working-tree check proving no test leaked files (workspaces,
   benchmark runs, scratch files) into the repository.

Lock files (``<dir>/.lock``) are respected with pytest's own staleness
semantics (a lock younger than ``LOCK_TIMEOUT`` protects a possibly
in-flight session).  When the operator passes ``--basetemp``
explicitly, pytest manages that directory itself and these hooks stand
aside.
"""

from __future__ import annotations

import getpass
import os
import shutil
import stat
import subprocess
import tempfile
import time
from pathlib import Path

import pytest

# Must match tmp_path_retention_count in pytest.ini.
RETENTION_COUNT = 2

_LOCK_NAME = ".lock"
# pytest's own LOCK_TIMEOUT: a cleanup lock younger than this is
# considered alive (the owning session may still be running) and its
# directory is never reclaimed.
_LOCK_TIMEOUT = 3 * 24 * 60 * 60

_VERIFICATION: dict = {}


def _force_rmtree(path: Path) -> None:
    """Remove a tree even when the harness froze parts of it read-only.

    The harness freezes evidence trees (0444 files, 0555 directories)
    and provisioned workspace material, so plain ``shutil.rmtree``
    (which pytest's per-test and basetemp cleanup use with
    ``ignore_errors=True``) silently leaves those subtrees behind.
    Directories are made owner-writable (and files owner-readable and
    -writable) before deletion, mirroring pytest's own ``rm_rf``.
    """
    if not path.exists():
        return
    for dirpath, dirnames, filenames in os.walk(path, topdown=False):
        for name in filenames:
            try:
                (Path(dirpath) / name).chmod(stat.S_IRUSR | stat.S_IWUSR)
            except OSError:
                pass
        try:
            Path(dirpath).chmod(stat.S_IRUSR | stat.S_IWUSR | stat.S_IXUSR)
        except OSError:
            pass
    shutil.rmtree(path, ignore_errors=True)


def _pytest_temp_root() -> Path:
    """The pytest numbered-dir root (mirrors _pytest.tmpdir resolution)."""
    temproot = Path(
        os.environ.get("PYTEST_DEBUG_TEMPROOT") or tempfile.gettempdir()
    )
    return temproot / f"pytest-of-{getpass.getuser()}"


def _numbered_dirs(root: Path) -> list[Path]:
    """All retained session directories, ordered oldest to newest."""
    return sorted(
        (
            p
            for p in root.glob("pytest-*")
            if p.is_dir() and p.name[len("pytest-"):].isdigit()
        ),
        key=lambda p: int(p.name[len("pytest-"):]),
    )


def _deletable(path: Path) -> bool:
    """True when *path* is not guarded by a fresh pytest cleanup lock."""
    lock = path / _LOCK_NAME
    try:
        if not lock.is_file():
            return True
        return lock.stat().st_mtime < time.time() - _LOCK_TIMEOUT
    except OSError:
        return False


def _prune(root: Path, keep: int) -> tuple[int, int]:
    """Delete all but the *keep* newest sessions plus garbage-* leftovers.

    Returns (removed, remaining).  Locked directories (possibly in use
    by another pytest process) are skipped.
    """
    if not root.is_dir():
        return 0, 0
    removed = 0
    dirs = _numbered_dirs(root)
    stale = dirs[:-keep] if keep else dirs
    for candidate in stale:
        if _deletable(candidate):
            _force_rmtree(candidate)
            removed += 1
    for garbage in root.glob("garbage-*"):
        if _deletable(garbage):
            _force_rmtree(garbage)
            removed += 1
    return removed, len(_numbered_dirs(root))


def _worktree_status() -> str:
    """``git status --porcelain`` of the repository working tree.

    Compared before and after the session to prove that no test leaked
    files (workspaces, benchmark runs, scratch output) into the
    repository — tests must clean up after themselves.
    """
    repo_root = Path(__file__).resolve().parent
    try:
        result = subprocess.run(
            ["git", "status", "--porcelain"],
            cwd=repo_root,
            capture_output=True,
            text=True,
            timeout=30,
        )
    except OSError:
        return ""
    return result.stdout if result.returncode == 0 else ""


def _basetemp_is_given(session) -> bool:
    """True when the operator passed ``--basetemp`` (pytest owns cleanup)."""
    factory = getattr(session.config, "_tmp_path_factory", None)
    return factory is None or getattr(factory, "_given_basetemp", None) is not None


@pytest.hookimpl()
def pytest_sessionstart(session) -> None:
    """Prune stale sessions left by interrupted runs (best effort)."""
    if _basetemp_is_given(session):
        return
    root = _pytest_temp_root()
    removed, _ = _prune(root, RETENTION_COUNT)
    _VERIFICATION["removed_at_start"] = removed
    _VERIFICATION["root"] = root
    _VERIFICATION["worktree_before"] = _worktree_status()


@pytest.hookimpl(trylast=True)
def pytest_sessionfinish(session, exitstatus) -> None:
    """Verify cleanup after pytest's own retention cleanup ran.

    pytest removes the session basetemp itself when the policy is
    ``failed`` and the session passed, but its plain ``rmtree`` leaves
    the harness's read-only evidence trees behind; the remainder is
    force-removed here.
    """
    if _basetemp_is_given(session):
        return
    factory = getattr(session.config, "_tmp_path_factory", None)
    root = _VERIFICATION.get("root")
    if root is None:
        return
    if factory is not None:
        basetemp = getattr(factory, "_basetemp", None)
        if basetemp is not None and int(exitstatus) == 0 and basetemp.is_dir():
            _force_rmtree(basetemp)
            _VERIFICATION["session_cleaned"] = True
        # remove the dead "pytest-current" symlink left by the removal
        dead = root / "pytest-current"
        if dead.is_symlink() and not dead.exists():
            try:
                dead.unlink()
            except OSError:
                pass
    _prune(root, RETENTION_COUNT)
    _VERIFICATION["remaining"] = _numbered_dirs(root)
    _VERIFICATION["worktree_after"] = _worktree_status()


@pytest.hookimpl(trylast=True)
def pytest_terminal_summary(terminalreporter, exitstatus, config) -> None:
    """Report the temporary-directory cleanup outcome."""
    if not _VERIFICATION:
        return
    terminalreporter.section("temporary-directory cleanup", sep="-")
    terminalreporter.write_line(
        f"retention policy: failed / {RETENTION_COUNT} "
        "(pytest.ini tmp_path_retention_*)"
    )
    terminalreporter.write_line(
        f"stale sessions removed at session start: "
        f"{_VERIFICATION.get('removed_at_start', 0)}"
    )
    if _VERIFICATION.get("session_cleaned"):
        terminalreporter.write_line(
            "current session basetemp: removed (all tests passed)"
        )
    remaining = _VERIFICATION.get("remaining", [])
    if remaining:
        names = ", ".join(p.name for p in remaining)
        terminalreporter.write_line(
            f"retained sessions after the run: {names} "
            "(kept for failed-test debugging)"
        )
    else:
        terminalreporter.write_line(
            "retained sessions after the run: none"
        )
    before = _VERIFICATION.get("worktree_before")
    after = _VERIFICATION.get("worktree_after")
    if before is not None and after == before:
        terminalreporter.write_line(
            "working tree: unchanged by tests "
            "(no orphan workspaces, benchmark runs, or scratch files)"
        )
    elif after is not None:
        terminalreporter.write_line(
            "working tree: CHANGED by tests (git status differs from "
            "session start)"
        )
