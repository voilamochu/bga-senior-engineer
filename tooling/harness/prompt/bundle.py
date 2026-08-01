"""Prompt bundle generation (MVB-007).

Assembles ``protocol/prompt-bundle.txt`` from the system prompt, the
materialized benchmark prompt, and the skill artifacts of the resolved
task (prompt, mandatory rules, lazy-rule declarations, checklists,
examples, references, phase groups) with deterministic section ordering
and no duplicated artifacts.  Identical inputs produce byte-identical
bundles.
"""

from __future__ import annotations

import json
import stat
from pathlib import Path

from tooling.harness.skill.loader import SkillPackage, TaskArtifacts
from tooling.harness.util.hash import sha256_file, sha256_text

BUNDLE_HEADER = "# BGA Senior Engineer Benchmark — Prompt Bundle"

# Bundle section order (deterministic).
SECTION_SYSTEM_PROMPT = "SYSTEM PROMPT"
SECTION_BENCHMARK_PROMPT = "BENCHMARK PROMPT"
SECTION_SKILL_ARTIFACTS = "SKILL ARTIFACTS"
SECTION_ORDER = (SECTION_SYSTEM_PROMPT, SECTION_BENCHMARK_PROMPT, SECTION_SKILL_ARTIFACTS)

BUNDLE_RELPATH = "protocol/prompt-bundle.txt"
BUNDLE_SHA_RELPATH = "protocol/prompt-bundle.sha256"
SYSTEM_PROMPT_RELPATH = "protocol/system-prompt.txt"


class BundleError(Exception):
    """Prompt bundle assembly failed."""


def _separator(title: str) -> str:
    return f"# {'=' * 70}\n# {title}\n# {'=' * 70}"


def skill_artifacts_section(package: SkillPackage, artifacts: TaskArtifacts) -> str:
    """Render the SKILL ARTIFACTS section from a task's artifact set.

    Sections appear in fixed order: skill prompt, mandatory rules,
    lazy-rule declarations, checklists, examples, references, and phase
    groups (when present).  Artifact files are embedded verbatim.
    """
    lines: list[str] = []
    lines.append(f"## Skill Task: {artifacts.task_id}")
    lines.append("")
    lines.append(f"### {artifacts.prompt}")
    lines.append(package.root.joinpath(artifacts.prompt).read_text(encoding="utf-8").rstrip())
    lines.append("")
    lines.append("## Mandatory Rules")
    for relpath in artifacts.expanded_rules():
        lines.append(f"### {relpath}")
        lines.append(_artifact_text(package, relpath))
    lines.append("")
    lines.append("## Lazy Rule Declarations")
    if artifacts.lazy_rules:
        for lazy in artifacts.lazy_rules:
            lines.append(f"- `{lazy.path}` — {lazy.reason}")
    else:
        lines.append("(none)")
    lines.append("")
    lines.append("## Checklists")
    for relpath in artifacts.expanded_checklists():
        lines.append(f"### {relpath}")
        lines.append(_artifact_text(package, relpath))
    lines.append("")
    lines.append("## Examples")
    for relpath in artifacts.expanded_examples():
        lines.append(f"### {relpath}")
        lines.append(_artifact_text(package, relpath))
    lines.append("")
    lines.append("## References")
    if artifacts.references:
        for relpath in artifacts.references:
            lines.append(f"### {relpath}")
            lines.append(_artifact_text(package, relpath))
    else:
        lines.append("(none)")
    if artifacts.phase_groups:
        lines.append("")
        lines.append("## Phase Groups")
        for group in artifacts.phase_groups:
            lines.append(f"### {group.name}")
            lines.append(f"**description:** {group.description}")
            if group.prompt_segment:
                lines.append(f"**prompt_segment:** {group.prompt_segment}")
            if group.rules:
                lines.append(f"**rules:** {', '.join(group.rules)}")
            if group.checklists:
                lines.append(f"**checklists:** {', '.join(group.checklists)}")
            if group.examples:
                lines.append(f"**examples:** {', '.join(group.examples)}")
    return "\n".join(lines)


def _artifact_text(package: SkillPackage, relpath: str) -> str:
    path = package.root / relpath
    try:
        return path.read_text(encoding="utf-8").rstrip()
    except OSError as exc:
        raise BundleError(f"cannot read skill artifact {relpath}: {exc}") from exc


def build_prompt_bundle(
    *,
    system_prompt: str,
    benchmark_prompt: str,
    package: SkillPackage,
    artifacts: TaskArtifacts,
) -> str:
    """Assemble the full prompt bundle text.

    ``build_prompt_bundle`` is a pure function of its inputs, so
    identical inputs always produce byte-identical bundles.
    """
    sections = [
        (SECTION_SYSTEM_PROMPT, system_prompt.rstrip()),
        (SECTION_BENCHMARK_PROMPT, benchmark_prompt.rstrip()),
        (SECTION_SKILL_ARTIFACTS, skill_artifacts_section(package, artifacts)),
    ]
    parts = [BUNDLE_HEADER]
    for title, body in sections:
        parts.append(_separator(title))
        parts.append(body)
    return "\n\n".join(parts) + "\n"


def artifact_hashes(package: SkillPackage, artifacts: TaskArtifacts) -> dict[str, str]:
    """SHA-256 of every loaded artifact file, keyed by package-relative path."""
    return {
        relpath: sha256_file(package.root / relpath)
        for relpath in artifacts.all_files()
    }


def write_bundle(run, bundle: str, *, system_prompt: str) -> str:
    """Write the bundle and its checksum into the run's ``protocol/``.

    Writes ``prompt-bundle.txt`` (read-only), ``prompt-bundle.sha256``,
    and the archived system prompt.  Returns the bundle SHA-256.
    """
    bundle_path = run.root / BUNDLE_RELPATH
    bundle_path.parent.mkdir(parents=True, exist_ok=True)
    bundle_path.write_text(bundle, encoding="utf-8")
    bundle_hash = sha256_text(bundle)
    sha_path = run.root / BUNDLE_SHA_RELPATH
    sha_path.write_text(f"{bundle_hash}  prompt-bundle.txt\n", encoding="utf-8")
    system_path = run.root / SYSTEM_PROMPT_RELPATH
    system_path.write_text(system_prompt, encoding="utf-8")
    # Bundle and system prompt are immutable from P2 onward (§3.2, §3.1.1).
    for path in (bundle_path, sha_path, system_path):
        path.chmod(stat.S_IRUSR | stat.S_IRGRP | stat.S_IROTH)
    return bundle_hash


def bundle_metadata(
    *,
    skill_task_id: str,
    package: SkillPackage,
    artifacts: TaskArtifacts,
    bundle_sha256: str,
    attached_documents: list[str],
    hashes: dict[str, str],
) -> dict:
    """Prompt-generation metadata recorded in the run manifest."""
    return {
        "skill_task": skill_task_id,
        "versions": {
            "skill": package.manifest.get("version"),
            "index": package.index.get("version"),
            "runtime": package.manifest.get("runtime"),
        },
        "bundle_sha256": bundle_sha256,
        "attached_documents": list(attached_documents),
        "artifacts": {
            "prompt": artifacts.prompt,
            "rules": list(artifacts.expanded_rules()),
            "lazy_rules": {lazy.path: lazy.reason for lazy in artifacts.lazy_rules},
            "checklists": list(artifacts.expanded_checklists()),
            "examples": list(artifacts.expanded_examples()),
            "references": list(artifacts.references),
            "phase_groups": [
                {
                    "name": group.name,
                    "description": group.description,
                    "prompt_segment": group.prompt_segment,
                    "rules": list(group.rules),
                    "checklists": list(group.checklists),
                    "examples": list(group.examples),
                }
                for group in artifacts.phase_groups
            ],
        },
        "artifact_hashes": {path: hashes[path] for path in sorted(hashes)},
    }


def bundle_artifact_json(package: SkillPackage, artifacts: TaskArtifacts) -> dict:
    """Machine-readable artifact index with sizes and hashes."""
    entries = []
    for relpath in artifacts.all_files():
        path = package.root / relpath
        entries.append(
            {
                "path": relpath,
                "size": path.stat().st_size,
                "sha256": sha256_file(path),
            }
        )
    return {"task": artifacts.task_id, "artifacts": entries}


def save_bundle_metadata_json(run, data: dict) -> Path:
    path = run.root / "protocol" / "prompt-bundle.json"
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(data, indent=2, sort_keys=True) + "\n", encoding="utf-8")
    return path
