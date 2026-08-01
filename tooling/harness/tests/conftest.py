"""Shared fixtures for harness tests.

All repository fixtures are scratch git repositories under ``tmp_path``;
the reference repository ``bga-mercurio`` is never used by tests.
"""

import os
import subprocess

import pytest
from pathlib import Path

GIT_ENV = {
    **os.environ,
    "GIT_CONFIG_GLOBAL": "/dev/null",
    "GIT_CONFIG_SYSTEM": "/dev/null",
    "GIT_AUTHOR_NAME": "harness-test",
    "GIT_AUTHOR_EMAIL": "harness-test@example.invalid",
    "GIT_COMMITTER_NAME": "harness-test",
    "GIT_COMMITTER_EMAIL": "harness-test@example.invalid",
}


def git(repo: Path, *args: str) -> str:
    """Run a git command inside *repo* with an isolated git environment."""
    result = subprocess.run(
        ["git", "-c", "user.name=harness-test", "-c", "user.email=harness-test@example.invalid", *args],
        cwd=repo,
        env=GIT_ENV,
        capture_output=True,
        text=True,
    )
    if result.returncode != 0:
        raise AssertionError(f"git {args} failed: {result.stderr}")
    return result.stdout


@pytest.fixture
def git_repo(tmp_path: Path) -> Path:
    """A scratch git repository with one commit and a clean working tree."""
    repo = tmp_path / "reference"
    repo.mkdir()
    git(repo, "init", "-b", "main")
    (repo / "file.txt").write_text("one\n", encoding="utf-8")
    (repo / "sub").mkdir()
    (repo / "sub" / "nested.txt").write_text("nested\n", encoding="utf-8")
    git(repo, "add", ".")
    git(repo, "commit", "-m", "first commit")
    return repo


@pytest.fixture
def senior_root(tmp_path: Path) -> Path:
    """A scratch stand-in for bga-senior-engineer's reference material."""
    root = tmp_path / "senior"
    for name in (
        "docs",
        "bga-senior-engineer-skill",
        "tooling",
        "official-docs",
        "reference-projects",
    ):
        directory = root / name
        directory.mkdir(parents=True)
        (directory / f"{name.replace('-', '_')}.txt").write_text(name, encoding="utf-8")
    return root
