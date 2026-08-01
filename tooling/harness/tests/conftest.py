"""Shared fixtures for harness tests.

All repository fixtures are scratch git repositories under ``tmp_path``;
the reference repository ``bga-mercurio`` is never used by tests.
Immutable resources (the pristine one-commit git template and the
reference-material stand-in) are created once per session; mutable
state (per-test copies) is recreated for every test.
"""

import os
import shutil
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


@pytest.fixture(scope="session")
def _git_repo_template(tmp_path_factory: pytest.TempPathFactory) -> Path:
    """A pristine one-commit scratch repository, created once per session.

    Immutable: every ``git_repo`` test fixture is a deep copy of this
    template, so tests may mutate their copy freely without cross-test
    interference and without re-running ``git init``/``git commit``.
    """
    repo = tmp_path_factory.mktemp("git-template") / "reference"
    repo.mkdir()
    git(repo, "init", "-b", "main")
    (repo / "file.txt").write_text("one\n", encoding="utf-8")
    (repo / "sub").mkdir()
    (repo / "sub" / "nested.txt").write_text("nested\n", encoding="utf-8")
    git(repo, "add", ".")
    git(repo, "commit", "-m", "first commit")
    return repo


@pytest.fixture
def git_repo(tmp_path: Path, _git_repo_template: Path) -> Path:
    """A scratch git repository with one commit and a clean working tree.

    A deep copy of the session template: byte-identical to a freshly
    initialized repository, at a fraction of the cost.
    """
    repo = tmp_path / "reference"
    shutil.copytree(_git_repo_template, repo, symlinks=True)
    return repo


@pytest.fixture(scope="session")
def senior_root(tmp_path_factory: pytest.TempPathFactory) -> Path:
    """A scratch stand-in for bga-senior-engineer's reference material.

    Immutable (provisioning only ever copies from it), so it is created
    once per session instead of once per test.
    """
    root = tmp_path_factory.mktemp("senior")
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
