"""Workspace provisioning (MVB-005).

Implements harness §9.3: copies read-only reference material into
``workspace/read/`` (the ``bga-mercurio`` contents at the pinned HEAD,
plus ``docs/``, ``bga-senior-engineer-skill/``, ``tooling/``,
``official-docs/``, and ``reference-projects/`` from this repository),
records ``workspace-baseline.diff`` (empty at P0), and makes
``workspace/read/`` read-only while ``workspace/work/`` stays writable.

The reference repository is never opened for write.  Provisioning is
overwrite-protected: an existing provisioned workspace is skipped only
when the manifest records the same reference HEAD, and refused
otherwise.
"""

from __future__ import annotations

import os
import shutil
import stat
from dataclasses import dataclass
from fnmatch import fnmatch
from pathlib import Path

from tooling.harness.config import repo_root
from tooling.harness.runtime.run_dir import RunDir
from tooling.harness.runtime.manifest import RunManifest
from tooling.harness.util.proc import run_cmd

# Copied reference material is named after these directories; the
# reference repository itself is always ``workspace/read/bga-mercurio``.
REFERENCE_DIR_NAME = "bga-mercurio"

# Paths excluded from every copy (repository metadata and build junk).
IGNORED_PATTERNS = (".git", "__pycache__", "*.pyc")

# Relative path of the P0 workspace baseline diff inside the run (§9.1).
BASELINE_DIFF_RELPATH = "protocol/baseline/workspace-baseline.diff"


class ProvisionError(Exception):
    """Workspace provisioning failed or was refused."""


@dataclass(frozen=True)
class ProvisionResult:
    """Outcome of one provisioning call."""

    reference_head: str
    files_copied: int
    skipped: bool


def default_material_roots() -> dict[str, Path]:
    """Source directories copied from ``bga-senior-engineer`` into
    ``workspace/read/`` (backlog MVB-005)."""
    repo = repo_root()
    return {
        "docs": repo / "docs",
        "bga-senior-engineer-skill": repo / "bga-senior-engineer-skill",
        "tooling": repo / "tooling",
        "official-docs": repo / "official-docs",
        "reference-projects": repo / "reference-projects",
    }


def reference_head(repo: str | Path) -> str:
    """HEAD commit of the reference repository (read-only)."""
    result = run_cmd(["git", "rev-parse", "HEAD"], cwd=Path(repo))
    if result.exit_code != 0:
        raise ProvisionError(
            f"cannot resolve reference HEAD in {repo}: {result.stderr.strip()}"
        )
    return result.stdout.strip()


def is_ignored(relative_or_abs_path: Path, patterns: tuple[str, ...] = IGNORED_PATTERNS) -> bool:
    """True when any path component matches an ignore pattern."""
    return any(fnmatch(part, pattern) for part in relative_or_abs_path.parts for pattern in patterns)


def count_files(path: str | Path, patterns: tuple[str, ...] = IGNORED_PATTERNS) -> int:
    """Number of files under *path*, excluding ignored patterns."""
    root = Path(path)
    return sum(1 for p in root.rglob("*") if p.is_file() and not is_ignored(p, patterns))


def provision(
    reference: str | Path,
    run: RunDir,
    manifest: RunManifest,
    *,
    material_roots: dict[str, Path] | None = None,
) -> ProvisionResult:
    """Provision ``workspace/read/`` with the reference material.

    Parameters
    ----------
    reference:
        Path of the read-only ``bga-mercurio`` checkout (a git repo).
    run:
        The run directory produced by MVB-001.
    manifest:
        The run manifest (used read-only for overwrite protection).
    material_roots:
        Mapping of destination directory name to source path for the
        reference material from ``bga-senior-engineer``; defaults to
        :func:`default_material_roots`.

    Returns
    -------
    ProvisionResult
        With the reference HEAD, the number of files copied (or the
        current file count when skipped), and whether provisioning was
        skipped because the workspace was already provisioned at the
        same HEAD.
    """
    reference = Path(reference)
    target = run.workspace_read / REFERENCE_DIR_NAME
    head = reference_head(reference)

    if target.exists():
        recorded = manifest.versions.get("reference_head")
        if recorded == head:
            return ProvisionResult(
                reference_head=head, files_copied=count_files(target), skipped=True
            )
        raise ProvisionError(
            f"workspace/read already exists with reference HEAD {recorded!r}, "
            f"but the reference is now at {head!r}; refusing to overwrite. "
            "Remove the run's workspace/read to re-provision."
        )

    _copy_tree(reference, target)
    for name, source in (material_roots or default_material_roots()).items():
        if not source.is_dir():
            raise ProvisionError(f"reference material source {source} does not exist")
        _copy_tree(source, run.workspace_read / name)
    _make_read_only(run.workspace_read)

    baseline_diff = run.root / BASELINE_DIFF_RELPATH
    if not baseline_diff.exists():
        baseline_diff.write_text("", encoding="utf-8")

    return ProvisionResult(reference_head=head, files_copied=count_files(target), skipped=False)


def _copy_tree(source: Path, target: Path) -> None:
    shutil.copytree(
        source,
        target,
        symlinks=True,
        ignore=shutil.ignore_patterns(*IGNORED_PATTERNS),
    )


def _make_read_only(root: Path) -> None:
    """Make a directory tree read-only: files 0444, directories 0555."""
    for dirpath, dirnames, filenames in os.walk(root):
        os.chmod(dirpath, stat.S_IRUSR | stat.S_IRGRP | stat.S_IROTH | stat.S_IXUSR | stat.S_IXGRP | stat.S_IXOTH)
        for name in filenames:
            path = Path(dirpath) / name
            if path.is_symlink():
                continue
            os.chmod(path, stat.S_IRUSR | stat.S_IRGRP | stat.S_IROTH)
