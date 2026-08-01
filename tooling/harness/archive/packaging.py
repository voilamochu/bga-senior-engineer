"""Result packaging (MS-08, MVB-024).

Defines what a packaged (archived) run contains: the canonical §9.1
layout artifacts — manifest, status, protocol, evidence (frozen),
validation, review (including scoring records), reports, and the
ARCHIVED marker.  Temporary files, caches, and build junk are never
packageable; the mutable workspace state (``workspace/``) is excluded
from the packaged verification scope — the agent's work area is mutable
by contract, and its content is captured frozen in the evidence set
(E2, E8).

Verification is mechanical: the packaged run must contain exactly the
canonical layout, nothing more.
"""

from __future__ import annotations

from pathlib import Path

from tooling.harness.runtime.run_dir import RunDir

ARCHIVE_MARKER = "ARCHIVED"

# Canonical top-level entries of a packaged run (harness §9.1 + marker).
EXPECTED_TOP_LEVEL = (
    "manifest.json",
    "status.json",
    "protocol",
    "workspace",
    "evidence",
    "validation",
    "review",
    "reports",
    ARCHIVE_MARKER,
)

# Path components that are never packageable (caches and temp files).
EXCLUDED_COMPONENTS = (
    "__pycache__",
    ".pytest_cache",
    ".DS_Store",
    "*.pyc",
    "*.tmp",
    "*.bak",
    "*.swp",
    "*.log~",
)

# Mutable workspace state: excluded from the packaged verification scope
# (its frozen snapshot lives in the evidence set as E2/E8).
EXCLUDED_TREES = ("workspace",)


class PackagingError(Exception):
    """The run directory cannot be packaged."""


def verify_packaging(run: RunDir) -> list[str]:
    """Verify the run directory is a clean, packageable archive.

    Returns precise divergences (empty when the packaged run contains
    exactly the canonical layout and no caches or temporary files).
    """
    divergences: list[str] = []
    if not run.root.is_dir():
        return [f"run directory missing: {run.root}"]

    for child in sorted(run.root.iterdir()):
        if child.name not in EXPECTED_TOP_LEVEL:
            divergences.append(
                f"unexpected top-level entry in packaged run: {child.name}"
            )

    for dirpath, dirnames, filenames in _walk_excluding(run.root):
        for name in dirnames:
            if _excluded(name):
                divergences.append(
                    f"non-packageable cache/temp directory: "
                    f"{Path(dirpath, name).relative_to(run.root)}"
                )
        for name in filenames:
            if _excluded(name):
                divergences.append(
                    f"non-packageable cache/temp file: "
                    f"{Path(dirpath, name).relative_to(run.root)}"
                )
    return divergences


def _walk_excluding(root: Path):
    """os.walk-like traversal that prunes the excluded trees."""
    import os

    for dirpath, dirnames, filenames in os.walk(root):
        dirnames[:] = [
            name for name in dirnames
            if Path(dirpath, name).relative_to(root).parts[0] not in EXCLUDED_TREES
        ]
        yield dirpath, dirnames, filenames


def _excluded(name: str) -> bool:
    from fnmatch import fnmatch

    return any(fnmatch(name, pattern) for pattern in EXCLUDED_COMPONENTS)
