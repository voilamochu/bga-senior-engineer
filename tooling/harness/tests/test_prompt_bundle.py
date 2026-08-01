"""Unit tests for MVB-007: prompt bundle generation."""

import json
import stat

import pytest

from tooling.harness.config import default_skill_root, default_system_prompt_path
from tooling.harness.prompt.bundle import (
    BUNDLE_HEADER,
    SECTION_BENCHMARK_PROMPT,
    SECTION_ORDER,
    SECTION_SKILL_ARTIFACTS,
    SECTION_SYSTEM_PROMPT,
    artifact_hashes,
    build_prompt_bundle,
    skill_artifacts_section,
    write_bundle,
)
from tooling.harness.skill.loader import load_skill
from tooling.harness.runtime.run_dir import create_run_dir
from tooling.harness.tests.test_skill_loader import build_skill_package

SYSTEM_PROMPT = "# System Prompt\n\n## Role\n\nrole text.\n"
BENCHMARK_PROMPT = (
    "# BGA Senior Engineer — Benchmark Task\n\n"
    "## Task\n\n- Task ID: NOT-02\n\n## Submission\n\n- declaration.json\n"
)


@pytest.fixture
def real_package():
    return load_skill(default_skill_root())


def test_section_order_is_fixed():
    assert SECTION_ORDER == (
        SECTION_SYSTEM_PROMPT,
        SECTION_BENCHMARK_PROMPT,
        SECTION_SKILL_ARTIFACTS,
    )


def test_bundle_contains_all_artifact_groups(real_package):
    artifacts = real_package.collect_artifacts("migrate-notifications")
    bundle = build_prompt_bundle(
        system_prompt=SYSTEM_PROMPT,
        benchmark_prompt=BENCHMARK_PROMPT,
        package=real_package,
        artifacts=artifacts,
    )
    assert bundle.startswith(BUNDLE_HEADER)
    assert "# SYSTEM PROMPT" in bundle
    assert "# BENCHMARK PROMPT" in bundle
    assert "# SKILL ARTIFACTS" in bundle
    assert "## Skill Task: migrate-notifications" in bundle
    assert "### prompts/migrate-notifications.md" in bundle
    assert "## Mandatory Rules" in bundle
    assert "### rules/constitution.json" in bundle
    assert "### rules/notifications.json" in bundle
    assert "## Lazy Rule Declarations" in bundle
    assert "- `rules/migration.json` —" in bundle
    assert "## Checklists" in bundle
    assert "### checklists/pre-commit.json" in bundle
    assert "## Examples" in bundle
    assert "### examples/notification-example.json" in bundle
    assert "## References" in bundle
    assert "(none)" in bundle


def test_bundle_is_deterministic(real_package):
    artifacts = real_package.collect_artifacts("migrate-notifications")
    kwargs = dict(
        system_prompt=SYSTEM_PROMPT,
        benchmark_prompt=BENCHMARK_PROMPT,
        package=real_package,
        artifacts=artifacts,
    )
    assert build_prompt_bundle(**kwargs) == build_prompt_bundle(**kwargs)


def test_bundle_artifact_order_matches_declared_order(real_package):
    artifacts = real_package.collect_artifacts("migrate-manager")
    section = skill_artifacts_section(real_package, artifacts)
    assert section.index("### rules/constitution.json") < section.index(
        "### rules/architecture.json"
    )
    assert section.index("### examples/manager-example.json") < section.index(
        "### examples/model-example.json"
    )


def test_bundle_has_no_duplicate_artifact_sections(real_package):
    artifacts = real_package.collect_artifacts("new-feature")
    bundle = build_prompt_bundle(
        system_prompt=SYSTEM_PROMPT,
        benchmark_prompt=BENCHMARK_PROMPT,
        package=real_package,
        artifacts=artifacts,
    )
    for relpath in artifacts.all_files():
        assert bundle.count(f"### {relpath}") == 1, relpath


def test_phased_task_bundle_includes_phase_groups(real_package):
    artifacts = real_package.collect_artifacts("review-full")
    section = skill_artifacts_section(real_package, artifacts)
    assert "## Phase Groups" in section
    assert "### architecture" in section
    assert "### undo_animations" in section
    assert "**prompt_segment:**" in section


def test_lazy_rule_declarations_recorded_with_reasons(real_package):
    artifacts = real_package.collect_artifacts("debug-session")
    assert len(artifacts.lazy_rules) == 5
    section = skill_artifacts_section(real_package, artifacts)
    for lazy in artifacts.lazy_rules:
        assert f"- `{lazy.path}` — {lazy.reason}" in section


def test_artifact_hashes_match_file_content(real_package):
    artifacts = real_package.collect_artifacts("migrate-notifications")
    hashes = artifact_hashes(real_package, artifacts)
    assert set(hashes) == set(artifacts.all_files())
    for relpath, digest in hashes.items():
        assert len(digest) == 64
        assert (real_package.root / relpath).is_file()


def test_write_bundle_writes_hashes_and_read_only(tmp_path):
    from tooling.harness.util.hash import sha256_text

    run = create_run_dir("NOT-02", "demo-model", tmp_path / "runs")
    bundle = "bundle-content\n"
    digest = write_bundle(run, bundle, system_prompt=SYSTEM_PROMPT)
    bundle_path = run.root / "protocol" / "prompt-bundle.txt"
    sha_path = run.root / "protocol" / "prompt-bundle.sha256"
    system_path = run.root / "protocol" / "system-prompt.txt"
    assert bundle_path.read_text() == bundle
    assert sha_path.read_text() == f"{digest}  prompt-bundle.txt\n"
    assert system_path.read_text() == SYSTEM_PROMPT
    assert digest == sha256_text(bundle)
    for path in (bundle_path, sha_path, system_path):
        assert stat.S_IMODE(path.stat().st_mode) == 0o444


def test_bundle_metadata(tmp_path):
    from tooling.harness.prompt.bundle import bundle_metadata

    package = load_skill(build_skill_package(tmp_path))
    artifacts = package.collect_artifacts("migrate-notifications")
    hashes = artifact_hashes(package, artifacts)
    metadata = bundle_metadata(
        skill_task_id="migrate-notifications",
        package=package,
        artifacts=artifacts,
        bundle_sha256="ab" * 32,
        attached_documents=["docs/a.md"],
        hashes=hashes,
    )
    assert metadata["skill_task"] == "migrate-notifications"
    assert metadata["versions"] == {"skill": "1.0.0", "index": "1.0.0", "runtime": "v1.1"}
    assert metadata["bundle_sha256"] == "ab" * 32
    assert metadata["attached_documents"] == ["docs/a.md"]
    assert metadata["artifacts"]["rules"] == [
        "rules/constitution.json",
        "rules/notifications.json",
    ]
    assert metadata["artifacts"]["lazy_rules"] == {
        "rules/migration.json": "Load when wrapping legacy calls"
    }
    assert set(metadata["artifact_hashes"]) == set(artifacts.all_files())


def test_bundle_artifact_json(tmp_path):
    from tooling.harness.prompt.bundle import bundle_artifact_json

    package = load_skill(build_skill_package(tmp_path))
    artifacts = package.collect_artifacts("migrate-notifications")
    data = bundle_artifact_json(package, artifacts)
    assert data["task"] == "migrate-notifications"
    assert len(data["artifacts"]) == len(artifacts.all_files())
    for entry in data["artifacts"]:
        assert entry["path"] in artifacts.all_files()
        assert entry["size"] > 0
        assert len(entry["sha256"]) == 64
    json.dumps(data)  # serializable
