"""Skill package loading and validation (MVB-008).

Loads the frozen skill package (``skill.json`` manifest + ``index.json``
task catalog) exactly as designed: the tiered loading model declared in
``skill.json``, keyword/ID task resolution, and per-task artifact
bundles (prompt, mandatory rules, lazy-rule declarations, checklists,
examples, references, and phase groups).  Malformed or internally
inconsistent packages are rejected with precise errors.
"""

from __future__ import annotations

import json
import re
from dataclasses import dataclass, field
from pathlib import Path

SKILL_MANIFEST_FILE = "skill.json"
SKILL_INDEX_FILE = "index.json"

# Field names required in skill.json.
REQUIRED_SKILL_FIELDS = (
    "name",
    "version",
    "description",
    "runtime",
    "validator",
    "entry_point",
    "capabilities",
    "loading_model",
    "compatibility",
)
# Field names required in index.json.
REQUIRED_INDEX_FIELDS = ("version", "task_order", "fallback_task", "tasks")

ARTIFACT_KEYS = ("rules", "checklists", "examples", "references")


class SkillError(Exception):
    """Base class for skill package errors."""


class SkillPackageError(SkillError):
    """The skill package is malformed or internally inconsistent."""


class TaskNotFoundError(SkillError):
    """The requested task is not in the skill package index."""


@dataclass(frozen=True)
class LazyRule:
    """A lazy-rule declaration: rule file path and its load-when reason."""

    path: str
    reason: str


@dataclass(frozen=True)
class PhaseGroup:
    """One phase group of a phased task (review-full, new-feature)."""

    name: str
    description: str
    prompt_segment: str
    rules: tuple[str, ...] = ()
    checklists: tuple[str, ...] = ()
    examples: tuple[str, ...] = ()


@dataclass(frozen=True)
class TaskArtifacts:
    """The complete, de-duplicated artifact set of a skill task."""

    task_id: str
    prompt: str
    rules: tuple[str, ...] = ()
    lazy_rules: tuple[LazyRule, ...] = ()
    checklists: tuple[str, ...] = ()
    examples: tuple[str, ...] = ()
    references: tuple[str, ...] = ()
    phase_groups: tuple[PhaseGroup, ...] = ()

    def all_files(self) -> list[str]:
        """Every artifact file path in bundle order (prompt first)."""
        files = [self.prompt]
        files += list(self.expanded_rules())
        files += [lazy.path for lazy in self.lazy_rules]
        files += list(self.expanded_checklists())
        files += list(self.expanded_examples())
        files += list(self.references)
        return _dedupe(files)

    def expanded_rules(self) -> tuple[str, ...]:
        """Task-level rules plus phase-group rules, de-duplicated."""
        return _dedupe(
            list(self.rules)
            + [rule for group in self.phase_groups for rule in group.rules]
        )

    def expanded_checklists(self) -> tuple[str, ...]:
        return _dedupe(
            list(self.checklists)
            + [item for group in self.phase_groups for item in group.checklists]
        )

    def expanded_examples(self) -> tuple[str, ...]:
        return _dedupe(
            list(self.examples)
            + [item for group in self.phase_groups for item in group.examples]
        )


@dataclass
class SkillPackage:
    """A loaded and validated skill package."""

    root: Path
    manifest: dict
    index: dict
    tasks: dict[str, dict] = field(default_factory=dict)

    def resolve_skill_task(self, task_id: str) -> dict:
        """Resolve a skill task by its index task ID."""
        task = self.tasks.get(task_id)
        if task is None:
            raise TaskNotFoundError(
                f"skill task {task_id!r} not found in index; "
                f"known tasks: {', '.join(sorted(self.tasks))}"
            )
        return task

    def collect_artifacts(self, task_id: str) -> TaskArtifacts:
        """Collect and validate the artifact bundle for a skill task.

        Artifacts are de-duplicated while preserving the declared order
        (phase-group artifacts appended after the task-level ones).
        """
        task = self.resolve_skill_task(task_id)
        prompt = task["prompt"]
        self.require_artifact(prompt)
        rules = tuple(self._resolve_many(task, "rules"))
        lazy = tuple(
            LazyRule(path=self._lazy_rule_path(name), reason=reason)
            for name, reason in (task.get("lazy_rules") or {}).items()
        )
        checklists = tuple(self._resolve_many(task, "checklists"))
        examples = tuple(self._resolve_many(task, "examples"))
        references = tuple(self._resolve_many(task, "references"))
        groups = tuple(
            PhaseGroup(
                name=name,
                description=str(group.get("description", "")),
                prompt_segment=str(group.get("prompt_segment", "")),
                rules=self._resolve_list(group, "rules"),
                checklists=self._resolve_list(group, "checklists"),
                examples=self._resolve_list(group, "examples"),
            )
            for name, group in (task.get("phase_groups") or {}).items()
        )
        return TaskArtifacts(
            task_id=task_id,
            prompt=prompt,
            rules=_dedupe(rules),
            lazy_rules=lazy,
            checklists=_dedupe(checklists),
            examples=_dedupe(examples),
            references=_dedupe(references),
            phase_groups=groups,
        )

    # ------------------------------------------------------------------
    # helpers
    # ------------------------------------------------------------------

    def _resolve_many(self, task: dict, key: str) -> list[str]:
        resolved = []
        for relpath in task.get(key, []):
            resolved.append(self.require_artifact(relpath))
        return resolved

    def _resolve_list(self, owner: dict, key: str) -> tuple[str, ...]:
        resolved = []
        for relpath in owner.get(key, []):
            resolved.append(self.require_artifact(relpath))
        return tuple(resolved)

    def _lazy_rule_path(self, filename: str) -> str:
        path = f"rules/{filename}"
        return self.require_artifact(path)

    def require_artifact(self, relpath: str) -> str:
        """Resolve a package-relative artifact path; reject outside-root
        paths and missing files."""
        path = (self.root / relpath).resolve()
        root = self.root.resolve()
        if root not in path.parents and path != root:
            raise SkillPackageError(f"artifact path escapes the skill root: {relpath}")
        if not path.is_file():
            raise SkillPackageError(f"skill artifact does not exist: {relpath}")
        return relpath


def _dedupe(items: list[str]) -> tuple[str, ...]:
    seen: set[str] = set()
    out: list[str] = []
    for item in items:
        if item not in seen:
            seen.add(item)
            out.append(item)
    return tuple(out)


def load_skill(
    root: str | Path,
    *,
    expected_runtime: str = "v1.1",
    expected_platform: str = "mercurio",
) -> SkillPackage:
    """Load and validate a skill package.

    Validates the manifest and index schemas, platform/runtime
    compatibility against the harness pins, artifact existence, and
    consistency between each task's index entry and its prompt file's
    frontmatter.
    """
    root = Path(root)
    if not root.is_dir():
        raise SkillPackageError(f"skill package directory does not exist: {root}")

    manifest_path = root / SKILL_MANIFEST_FILE
    index_path = root / SKILL_INDEX_FILE
    if not manifest_path.is_file():
        raise SkillPackageError(f"skill package missing {SKILL_MANIFEST_FILE}")
    if not index_path.is_file():
        raise SkillPackageError(f"skill package missing {SKILL_INDEX_FILE}")

    manifest = _read_json(manifest_path, "skill.json")
    index = _read_json(index_path, "index.json")

    _validate_manifest(manifest, index_path, expected_runtime, expected_platform)
    tasks = _validate_index(root, index)
    package = SkillPackage(root=root, manifest=manifest, index=index, tasks=tasks)
    _validate_prompt_consistency(package)
    return package


def _read_json(path: Path, what: str) -> dict:
    try:
        with open(path, encoding="utf-8") as f:
            data = json.load(f)
    except json.JSONDecodeError as exc:
        raise SkillPackageError(f"{what} is malformed JSON: {exc}") from exc
    if not isinstance(data, dict):
        raise SkillPackageError(f"{what} must be a JSON object")
    return data


def _validate_manifest(manifest: dict, index_path: Path, expected_runtime: str, expected_platform: str) -> None:
    missing = [name for name in REQUIRED_SKILL_FIELDS if name not in manifest]
    if missing:
        raise SkillPackageError(f"skill.json missing required fields: {missing}")
    for name in ("name", "version", "runtime", "validator", "entry_point"):
        if not isinstance(manifest[name], str) or not manifest[name]:
            raise SkillPackageError(f"skill.json {name!r} must be a non-empty string")
    if manifest["entry_point"] != SKILL_INDEX_FILE:
        raise SkillPackageError(
            f"skill.json entry_point is {manifest['entry_point']!r}, "
            f"expected {SKILL_INDEX_FILE!r}"
        )
    compatibility = manifest["compatibility"]
    if not isinstance(compatibility, dict):
        raise SkillPackageError("skill.json compatibility must be an object")
    platform = compatibility.get("platform")
    if platform != expected_platform:
        raise SkillPackageError(
            f"skill package platform {platform!r} is incompatible with "
            f"expected platform {expected_platform!r}"
        )
    runtime_version = compatibility.get("runtime_version")
    if runtime_version != expected_runtime:
        raise SkillPackageError(
            f"skill package runtime {runtime_version!r} is incompatible with "
            f"expected runtime {expected_runtime!r}"
        )
    loading_model = manifest["loading_model"]
    if not isinstance(loading_model, dict) or "tiers" not in loading_model:
        raise SkillPackageError("skill.json loading_model must declare tiers")
    tiers = loading_model["tiers"]
    for tier_name in ("tier_0", "tier_1", "tier_2"):
        if tier_name not in tiers:
            raise SkillPackageError(f"skill.json loading_model missing tier {tier_name}")
    if "skill.json" not in tiers["tier_0"].get("files", []):
        raise SkillPackageError("tier_0 must always load skill.json")


def _validate_index(root: Path, index: dict) -> dict[str, dict]:
    missing = [name for name in REQUIRED_INDEX_FIELDS if name not in index]
    if missing:
        raise SkillPackageError(f"index.json missing required fields: {missing}")
    task_order = index["task_order"]
    if not isinstance(task_order, list) or not task_order:
        raise SkillPackageError("index.json task_order must be a non-empty list")
    tasks = index["tasks"]
    if not isinstance(tasks, dict) or not tasks:
        raise SkillPackageError("index.json tasks must be a non-empty object")
    fallback = index["fallback_task"]
    if fallback not in tasks:
        raise SkillPackageError(
            f"index.json fallback_task {fallback!r} is not a defined task"
        )
    for task_id in task_order:
        if task_id not in tasks:
            raise SkillPackageError(
                f"index.json task_order lists unknown task {task_id!r}"
            )
    for task_id in tasks:
        if task_id not in task_order:
            raise SkillPackageError(
                f"index.json task {task_id!r} is missing from task_order"
            )
        _validate_task_entry(root, task_id, tasks[task_id])
    return tasks


def _validate_task_entry(root: Path, task_id: str, task: dict) -> None:
    if not isinstance(task, dict):
        raise SkillPackageError(f"task {task_id!r} must be an object")
    for name in ("description", "prompt", "rules", "checklists"):
        if name not in task:
            raise SkillPackageError(f"task {task_id!r} missing field {name!r}")
    for name in ("prompt", "rules", "checklists"):
        if not isinstance(task[name], (str if name == "prompt" else list)):
            raise SkillPackageError(f"task {task_id!r} field {name!r} has wrong type")
    if not (root / task["prompt"]).is_file():
        raise SkillPackageError(
            f"task {task_id!r} prompt file does not exist: {task['prompt']}"
        )
    for key in ARTIFACT_KEYS:
        for relpath in task.get(key, []):
            if not (root / relpath).is_file():
                raise SkillPackageError(
                    f"task {task_id!r} references missing artifact {relpath}"
                )
    for filename in (task.get("lazy_rules") or {}):
        if not (root / "rules" / filename).is_file():
            raise SkillPackageError(
                f"task {task_id!r} lazy rule file does not exist: rules/{filename}"
            )
    for group_name, group in (task.get("phase_groups") or {}).items():
        if not isinstance(group, dict):
            raise SkillPackageError(
                f"task {task_id!r} phase group {group_name!r} must be an object"
            )
        for key in ("rules", "checklists", "examples"):
            for relpath in group.get(key, []):
                if not (root / relpath).is_file():
                    raise SkillPackageError(
                        f"task {task_id!r} phase group {group_name!r} references "
                        f"missing artifact {relpath}"
                    )


def parse_prompt_frontmatter(prompt_path: str | Path) -> dict:
    """Parse the YAML-ish frontmatter of a skill prompt file.

    Returns ``{field: [values]}`` where single values are one-element
    lists and list fields collect ``- item`` lines.
    """
    text = Path(prompt_path).read_text(encoding="utf-8")
    if not text.startswith("---"):
        raise SkillPackageError(f"prompt file lacks frontmatter: {prompt_path}")
    end = text.find("\n---", 3)
    if end == -1:
        raise SkillPackageError(f"prompt file frontmatter is unterminated: {prompt_path}")
    data: dict[str, list[str]] = {}
    current: str | None = None
    for line in text[3:end].splitlines():
        match = re.match(r"^(\w+):\s*(.*)$", line)
        if match and not line.startswith(" "):
            current = match.group(1)
            data[current] = [match.group(2)] if match.group(2) else []
        elif line.startswith("  - ") and current is not None:
            data[current].append(line[4:])
    return data


def _validate_prompt_consistency(package: SkillPackage) -> None:
    """Each task's index entry must agree with its prompt's frontmatter."""
    for task_id, task in package.tasks.items():
        frontmatter = parse_prompt_frontmatter(package.root / task["prompt"])
        if frontmatter.get("task") != [task_id]:
            raise SkillPackageError(
                f"prompt frontmatter task {frontmatter.get('task')!r} does not "
                f"match index task {task_id!r}"
            )
        expected = {
            "rules": sorted(task["rules"]),
            "checklists": sorted(task.get("checklists", [])),
            "examples": sorted(task.get("examples", [])),
        }
        declared = {
            "rules": sorted(frontmatter.get("required_rules", [])),
            "checklists": sorted(frontmatter.get("required_checklists", [])),
            "examples": sorted(frontmatter.get("required_examples", [])),
        }
        for key in expected:
            if expected[key] != declared[key]:
                raise SkillPackageError(
                    f"task {task_id!r} index {key} {expected[key]} do not match "
                    f"prompt frontmatter {declared[key]}"
                )
        index_lazy = sorted(f"rules/{name}" for name in task.get("lazy_rules", {}))
        prompt_lazy = sorted(frontmatter.get("lazy_rules", []))
        if index_lazy != prompt_lazy:
            raise SkillPackageError(
                f"task {task_id!r} index lazy_rules {index_lazy} do not match "
                f"prompt frontmatter {prompt_lazy}"
            )


# Benchmark corpus task ID -> skill index task ID.
# The corpus and evaluation documents do not define this association; it is
# the MVB's curated per-task mapping (the original MVB-007 task config in
# the backlog).  Only tasks used by the benchmark are listed; any skill
# task can still be resolved directly by its index task ID.
BENCHMARK_TASK_TO_SKILL_TASK = {
    "NOT-02": "migrate-notifications",
}


def resolve_benchmark_task(package: SkillPackage, benchmark_task_id: str) -> dict:
    """Resolve a benchmark corpus task to its skill index entry.

    Benchmarks task IDs are used directly when they name a skill task;
    otherwise the curated corpus-to-skill mapping applies.
    """
    if benchmark_task_id in package.tasks:
        return package.resolve_skill_task(benchmark_task_id)
    skill_task_id = BENCHMARK_TASK_TO_SKILL_TASK.get(benchmark_task_id)
    if skill_task_id is None:
        raise TaskNotFoundError(
            f"no skill task associated with benchmark task {benchmark_task_id!r}"
        )
    return package.resolve_skill_task(skill_task_id)
