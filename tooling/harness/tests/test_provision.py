"""Unit tests for MVB-005: workspace provisioning."""

import os
import stat

import pytest

from tooling.harness.runtime.run_dir import create_run_dir
from tooling.harness.runtime.manifest import new_run_manifest
from tooling.harness.workspace.provision import (
    REFERENCE_DIR_NAME,
    ProvisionError,
    count_files,
    is_ignored,
    provision,
)
from tooling.harness.tests.conftest import git


@pytest.fixture
def run_dir(tmp_path):
    return create_run_dir("NOT-02", "demo-model", tmp_path / "runs")


@pytest.fixture
def manifest(run_dir):
    return new_run_manifest(run_dir.run_id, "NOT-02", model_id="demo-model")


def _mode(path):
    return stat.S_IMODE(os.stat(path).st_mode)


def test_provision_copies_reference_contents(git_repo, run_dir, manifest, senior_root):
    result = provision(git_repo, run_dir, manifest, material_roots=_material(senior_root))
    assert result.skipped is False
    assert result.reference_head == git(git_repo, "rev-parse", "HEAD").strip()
    target = run_dir.workspace_read / REFERENCE_DIR_NAME
    assert (target / "file.txt").read_text() == "one\n"
    assert (target / "sub" / "nested.txt").read_text() == "nested\n"
    # file count equals the source checkout (git metadata excluded)
    assert result.files_copied == count_files(git_repo)
    assert result.files_copied >= 2


def _material(senior_root):
    return {name: senior_root / name for name in (
        "docs", "bga-senior-engineer-skill", "tooling",
        "official-docs", "reference-projects",
    )}


def test_provision_copies_reference_material(git_repo, run_dir, manifest, senior_root):
    provision(git_repo, run_dir, manifest, material_roots=_material(senior_root))
    for name in _material(senior_root):
        assert (run_dir.workspace_read / name).is_dir(), f"missing material {name}"
        assert (run_dir.workspace_read / name / f"{name.replace('-', '_')}.txt").is_file()


def test_read_dir_is_read_only(git_repo, run_dir, manifest, senior_root):
    provision(git_repo, run_dir, manifest, material_roots=_material(senior_root))
    read_root = run_dir.workspace_read
    for path in read_root.rglob("*"):
        assert not os.access(path, os.W_OK), f"{path} is writable"
        assert _mode(path) & 0o222 == 0, f"{path} has write bits"
    assert _mode(read_root) & 0o222 == 0


def test_work_dir_stays_writable(git_repo, run_dir, manifest, senior_root):
    provision(git_repo, run_dir, manifest, material_roots=_material(senior_root))
    probe = run_dir.workspace_work / "probe.txt"
    probe.write_text("writable", encoding="utf-8")
    assert probe.read_text() == "writable"


def test_workspace_baseline_diff_exists_and_is_empty(git_repo, run_dir, manifest, senior_root):
    provision(git_repo, run_dir, manifest, material_roots=_material(senior_root))
    diff = run_dir.baseline / "workspace-baseline.diff"
    assert diff.is_file()
    assert diff.read_text() == ""


def test_provision_never_writes_to_reference(git_repo, run_dir, manifest, senior_root):
    before = {
        p.relative_to(git_repo): p.read_bytes()
        for p in git_repo.rglob("*")
        if p.is_file() and not is_ignored(p)
    }
    provision(git_repo, run_dir, manifest, material_roots=_material(senior_root))
    after = {
        p.relative_to(git_repo): p.read_bytes()
        for p in git_repo.rglob("*")
        if p.is_file() and not is_ignored(p)
    }
    assert after == before
    assert git(git_repo, "status", "--porcelain") == ""


def test_provision_is_idempotent_and_skips_at_same_head(git_repo, run_dir, manifest, senior_root):
    first = provision(git_repo, run_dir, manifest, material_roots=_material(senior_root))
    manifest.versions["reference_head"] = first.reference_head
    second = provision(git_repo, run_dir, manifest, material_roots=_material(senior_root))
    assert second.skipped is True
    assert second.reference_head == first.reference_head
    assert second.files_copied == first.files_copied


def test_provision_refuses_unknown_existing_workspace(git_repo, run_dir, manifest, senior_root):
    provision(git_repo, run_dir, manifest, material_roots=_material(senior_root))
    manifest.versions["reference_head"] = None
    with pytest.raises(ProvisionError):
        provision(git_repo, run_dir, manifest, material_roots=_material(senior_root))


def test_provision_refuses_different_head(git_repo, run_dir, manifest, senior_root):
    provision(git_repo, run_dir, manifest, material_roots=_material(senior_root))
    manifest.versions["reference_head"] = "deadbeef" * 5
    with pytest.raises(ProvisionError, match="refusing to overwrite"):
        provision(git_repo, run_dir, manifest, material_roots=_material(senior_root))


def test_provision_raises_on_missing_material_root(git_repo, run_dir, manifest, senior_root):
    with pytest.raises(ProvisionError, match="does not exist"):
        provision(
            git_repo, run_dir, manifest,
            material_roots={"docs": senior_root / "nope"},
        )


def test_provision_raises_on_non_git_reference(tmp_path, run_dir, manifest, senior_root):
    plain = tmp_path / "plain"
    plain.mkdir()
    (plain / "x.txt").write_text("x", encoding="utf-8")
    with pytest.raises(ProvisionError):
        provision(plain, run_dir, manifest, material_roots=_material(senior_root))


def test_count_files_excludes_ignored(tmp_path):
    root = tmp_path / "tree"
    (root / ".git" / "objects").mkdir(parents=True)
    (root / ".git" / "objects" / "obj.bin").write_text("x")
    (root / "a.py").write_text("x")
    (root / "b.pyc").write_text("x")
    (root / "__pycache__").mkdir()
    (root / "__pycache__" / "c.pyc").write_text("x")
    (root / "keep.txt").write_text("x")
    # .git subtree, *.pyc, and __pycache__ are excluded; a.py and keep.txt count
    assert count_files(root) == 2
