"""Unit tests for MVB-008: skill package loader."""

import json
from pathlib import Path

import pytest

from tooling.harness.config import default_skill_root
from tooling.harness.skill.loader import (
    BENCHMARK_TASK_TO_SKILL_TASK,
    TaskArtifacts,
    TaskNotFoundError,
    load_skill,
    parse_prompt_frontmatter,
    resolve_benchmark_task,
)
from tooling.harness.skill.loader import SkillPackageError


def _write(root: Path, relpath: str, content: str) -> Path:
    path = root / relpath
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(content, encoding="utf-8")
    return path


SKILL_JSON = {
    "name": "bga-senior-engineer",
    "version": "1.0.0",
    "description": "test skill",
    "runtime": "v1.1",
    "validator": "^1.0.0",
    "entry_point": "index.json",
    "capabilities": ["review-full"],
    "loading_model": {
        "tiers": {
            "tier_0": {"description": "always", "files": ["skill.json"], "max_tokens": 200},
            "tier_1": {"description": "task", "max_tokens": 3000, "max_files": 10},
            "tier_2": {"description": "lazy", "max_tokens": 600, "max_files": 3},
        }
    },
    "compatibility": {"platform": "mercurio", "min_platform_version": "1.0.0", "runtime_version": "v1.1"},
}

PROMPT_TEMPLATE = """---
task: {task}
version: 1.0.0
last_updated: 2026-07-30
source: test
required_rules:
{rules}
required_checklists:
{checklists}
required_examples:
{examples}
lazy_rules:
{lazy}
---

# {task} prompt
"""


def build_skill_package(
    tmp_path: Path,
    *,
    tasks: dict | None = None,
    skill: dict | None = None,
) -> Path:
    root = tmp_path / "skill"
    _write(root, "skill.json", json.dumps(skill or SKILL_JSON))
    tasks = tasks or {
        "migrate-notifications": {
            "description": "test task",
            "prompt": "prompts/migrate-notifications.md",
            "rules": ["rules/constitution.json", "rules/notifications.json"],
            "lazy_rules": {"migration.json": "Load when wrapping legacy calls"},
            "checklists": ["checklists/pre-commit.json"],
            "examples": ["examples/notification-example.json"],
            "references": [],
        }
    }
    index = {
        "version": "1.0.0",
        "last_updated": "2026-07-30",
        "loading_instructions": "test",
        "fallback_task": list(tasks)[0],
        "task_order": list(tasks),
        "tasks": tasks,
    }
    _write(root, "index.json", json.dumps(index))
    # artifacts referenced by the default task
    for task_id, task in tasks.items():
        _write(
            root,
            task["prompt"],
            PROMPT_TEMPLATE.format(
                task=task_id,
                rules="\n".join(f"  - {r}" for r in task.get("rules", [])),
                checklists="\n".join(f"  - {c}" for c in task.get("checklists", [])),
                examples="\n".join(f"  - {e}" for e in task.get("examples", [])),
                lazy="\n".join(f"  - rules/{k}" for k in task.get("lazy_rules", {})),
            ),
        )
    for relpath in ("rules/constitution.json", "rules/notifications.json", "rules/migration.json",
                    "checklists/pre-commit.json", "examples/notification-example.json"):
        _write(root, relpath, json.dumps({"id": relpath}))
    return root


def test_load_real_skill_package():
    package = load_skill(default_skill_root())
    assert package.manifest["name"] == "bga-senior-engineer"
    assert package.manifest["version"] == "1.0.0"
    assert package.manifest["runtime"] == "v1.1"
    assert package.index["fallback_task"] == "review-full"
    assert len(package.tasks) == 13
    assert len(package.index["task_order"]) == 13


def test_every_real_task_loads_and_all_artifacts_resolve():
    package = load_skill(default_skill_root())
    for task_id in package.tasks:
        artifacts = package.collect_artifacts(task_id)
        assert artifacts.task_id == task_id
        for relpath in artifacts.all_files():
            assert (package.root / relpath).is_file(), relpath
        assert artifacts.prompt.endswith(".md")
        assert artifacts.rules, task_id


def test_real_task_artifact_sets():
    package = load_skill(default_skill_root())
    artifacts = package.collect_artifacts("migrate-notifications")
    assert artifacts.rules == ("rules/constitution.json", "rules/notifications.json")
    assert artifacts.checklists == ("checklists/pre-commit.json",)
    assert artifacts.examples == ("examples/notification-example.json",)
    assert artifacts.references == ()
    assert len(artifacts.lazy_rules) == 1
    assert artifacts.lazy_rules[0].path == "rules/migration.json"
    assert "notifyAllPlayers" in artifacts.lazy_rules[0].reason


def test_phased_tasks_load_phase_groups():
    package = load_skill(default_skill_root())
    for task_id in ("review-full", "new-feature"):
        artifacts = package.collect_artifacts(task_id)
        assert artifacts.phase_groups, task_id
        for group in artifacts.phase_groups:
            assert group.prompt_segment
            for relpath in group.rules + group.checklists + group.examples:
                assert (package.root / relpath).is_file()
        # phase-group rules that repeat task-level rules are de-duplicated
        all_files = artifacts.all_files()
        assert len(all_files) == len(set(all_files))


def test_phased_artifact_dedupe(tmp_path):
    tasks = {
        "phased": {
            "description": "phased",
            "prompt": "prompts/phased.md",
            "rules": ["rules/constitution.json"],
            "lazy_rules": {},
            "checklists": ["checklists/pre-commit.json"],
            "examples": [],
            "references": [],
            "phase_groups": {
                "one": {
                    "description": "phase one",
                    "rules": ["rules/constitution.json", "rules/notifications.json"],
                    "checklists": ["checklists/pre-commit.json"],
                    "examples": [],
                    "prompt_segment": "## Phase 1",
                }
            },
        }
    }
    root = build_skill_package(tmp_path, tasks=tasks)
    package = load_skill(root)
    artifacts = package.collect_artifacts("phased")
    # task-level rules stay as declared; phase-group rules are expanded and
    # de-duplicated against them
    assert artifacts.rules == ("rules/constitution.json",)
    assert artifacts.expanded_rules() == (
        "rules/constitution.json",
        "rules/notifications.json",
    )
    assert artifacts.expanded_checklists() == ("checklists/pre-commit.json",)
    assert len(artifacts.all_files()) == len(set(artifacts.all_files()))


def test_resolve_benchmark_task_maps_not_02():
    package = load_skill(default_skill_root())
    assert BENCHMARK_TASK_TO_SKILL_TASK == {"NOT-02": "migrate-notifications"}
    task = resolve_benchmark_task(package, "NOT-02")
    assert task["prompt"] == "prompts/migrate-notifications.md"
    # direct skill task IDs resolve too
    assert resolve_benchmark_task(package, "review-full")["prompt"] == "prompts/review-full.md"


def test_resolve_unknown_task_raises(tmp_path):
    root = build_skill_package(tmp_path)
    package = load_skill(root)
    with pytest.raises(TaskNotFoundError):
        package.resolve_skill_task("nope")
    with pytest.raises(TaskNotFoundError):
        resolve_benchmark_task(package, "ZZZ-99")


def test_missing_skill_manifest(tmp_path):
    empty = tmp_path / "empty"
    empty.mkdir()
    with pytest.raises(SkillPackageError, match="skill.json"):
        load_skill(empty)
    with pytest.raises(SkillPackageError, match="does not exist"):
        load_skill(tmp_path / "missing")


def test_malformed_skill_manifest(tmp_path):
    root = build_skill_package(tmp_path)
    (root / "skill.json").write_text("{not json", encoding="utf-8")
    with pytest.raises(SkillPackageError, match="malformed JSON"):
        load_skill(root)


def test_missing_index(tmp_path):
    root = build_skill_package(tmp_path)
    (root / "index.json").unlink()
    with pytest.raises(SkillPackageError, match="index.json"):
        load_skill(root)


def test_incompatible_runtime(tmp_path):
    skill = dict(SKILL_JSON)
    skill["compatibility"] = {**skill["compatibility"], "runtime_version": "v2.0"}
    root = build_skill_package(tmp_path, skill=skill)
    with pytest.raises(SkillPackageError, match="incompatible"):
        load_skill(root)


def test_wrong_entry_point(tmp_path):
    skill = dict(SKILL_JSON)
    skill["entry_point"] = "other.json"
    root = build_skill_package(tmp_path, skill=skill)
    with pytest.raises(SkillPackageError, match="entry_point"):
        load_skill(root)


def test_missing_task_artifact(tmp_path):
    tasks = {
        "broken": {
            "description": "broken",
            "prompt": "prompts/broken.md",
            "rules": ["rules/missing.json"],
            "checklists": [],
            "examples": [],
            "references": [],
            "lazy_rules": {},
        }
    }
    root = build_skill_package(tmp_path, tasks=tasks)
    with pytest.raises(SkillPackageError, match="missing artifact"):
        load_skill(root)


def test_bad_fallback_task(tmp_path):
    tasks = {
        "one": {
            "description": "one",
            "prompt": "prompts/one.md",
            "rules": ["rules/constitution.json"],
            "checklists": [],
            "examples": [],
            "references": [],
            "lazy_rules": {},
        }
    }
    root = build_skill_package(tmp_path, tasks=tasks)
    index_path = root / "index.json"
    index = json.loads(index_path.read_text())
    index["fallback_task"] = "missing"
    index_path.write_text(json.dumps(index))
    with pytest.raises(SkillPackageError, match="fallback_task"):
        load_skill(root)


def test_inconsistent_prompt_frontmatter_rejected(tmp_path):
    root = build_skill_package(tmp_path)
    # index declares constitution + notifications; prompt claims extra rule
    prompt_path = root / "prompts" / "migrate-notifications.md"
    prompt_path.write_text(
        prompt_path.read_text().replace(
            "  - rules/constitution.json\n  - rules/notifications.json",
            "  - rules/constitution.json\n  - rules/notifications.json\n  - rules/client.json",
        ),
        encoding="utf-8",
    )
    with pytest.raises(SkillPackageError, match="frontmatter"):
        load_skill(root)


def test_parse_prompt_frontmatter():
    data = parse_prompt_frontmatter(
        Path(default_skill_root()) / "prompts" / "migrate-notifications.md"
    )
    assert data["task"] == ["migrate-notifications"]
    assert "rules/constitution.json" in data["required_rules"]
    assert "rules/migration.json" in data["lazy_rules"]
