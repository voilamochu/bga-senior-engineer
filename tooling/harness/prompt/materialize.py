"""Benchmark prompt materialization (MVB-009).

Renders the benchmark prompt per harness spec §3.2 and Appendix B from
the corpus task entry and the evaluation spec task section, with the
repository safety section verbatim.  Both source documents are pinned
(v1.0), so the rendering is deterministic: identical inputs produce
identical bytes.
"""

from __future__ import annotations

import re
from pathlib import Path

CORPUS_ENTRY_HEADING = re.compile(r"^### ([A-Z0-9-]+):\s*(.+)$")
EVAL_ENTRY_HEADING = re.compile(r"^### \d+\.\d+ ([A-Z0-9-]+)\s*$")
FIELD_LABEL = re.compile(r"^\*\*(.+?):\*\*\s*(.*)$")
EVAL_SUBSECTION = re.compile(r"^#### (.+?)\s*$")


class TaskSectionError(Exception):
    """The task section is missing or malformed in a benchmark document."""


def parse_corpus_task(corpus_path: str | Path, task_id: str) -> dict:
    """Extract the task entry fields from ``benchmark-task-corpus.md``.

    Returns ``{id, title, category, difficulty, effort, background,
    objective}`` with section text kept verbatim.
    """
    lines = _read_lines(corpus_path)
    start, title = _find_corpus_entry(lines, task_id)
    end = _section_end(lines, start, CORPUS_ENTRY_HEADING)
    fields = _parse_labeled_fields(lines[start:end])
    required = {
        "Category": "category",
        "Difficulty": "difficulty",
        "Estimated Effort": "effort",
        "Background": "background",
        "Objective": "objective",
    }
    missing = [label for label in required if not fields.get(label)]
    if missing:
        raise TaskSectionError(
            f"corpus entry {task_id!r} is missing fields: {', '.join(missing)}"
        )
    return {
        "id": task_id,
        "title": title,
        "category": fields["Category"],
        "difficulty": fields["Difficulty"],
        "effort": fields["Estimated Effort"],
        "background": fields["Background"],
        "objective": fields["Objective"],
    }


def parse_eval_task(eval_path: str | Path, task_id: str) -> dict:
    """Extract the task section fields from ``benchmark-evaluation-spec.md``.

    Returns ``{id, title, expected_outcomes, success_criteria,
    required_evidence, primary_rules, key_standards}`` with section text
    kept verbatim (the success criteria table is included in full).
    """
    lines = _read_lines(eval_path)
    start = _find_eval_entry(lines, task_id)
    end = _section_end(lines, start, EVAL_ENTRY_HEADING)
    section = lines[start:end]

    title = ""
    for line in section:
        match = re.match(r"^\*\*(.+?)\*\*$", line.strip())
        if match:
            title = match.group(1).strip()
            break

    def subsection(name: str) -> str:
        buffer: list[str] = []
        capture = False
        for line in section:
            match = EVAL_SUBSECTION.match(line.strip())
            if match:
                capture = match.group(1) == name
                continue
            if capture:
                buffer.append(line)
        return "\n".join(buffer).strip()

    def table_row(label: str) -> str:
        for line in section:
            cells = [cell.strip() for cell in line.split("|")]
            if len(cells) >= 3 and cells[1] == label:
                return cells[2].strip()
        return ""

    result = {
        "id": task_id,
        "title": title,
        "expected_outcomes": subsection("Expected Outcomes"),
        "success_criteria": subsection("Success Criteria"),
        "required_evidence": subsection("Required Evidence"),
        "primary_rules": table_row("Primary rules"),
        "key_standards": table_row("Key standards"),
    }
    if not result["title"]:
        raise TaskSectionError(f"evaluation section for {task_id!r} has no title")
    for name in ("expected_outcomes", "success_criteria", "required_evidence"):
        if not result[name]:
            raise TaskSectionError(
                f"evaluation section for {task_id!r} is missing {name}"
            )
    return result


def primary_rule_files(primary_rules: str) -> list[str]:
    """Rule file names mentioned in the §3.11 "Primary rules" row."""
    return [
        token.strip()
        for token in primary_rules.replace("`", "").split(",")
        if token.strip().endswith(".json")
    ]


def _read_lines(path: str | Path) -> list[str]:
    try:
        return Path(path).read_text(encoding="utf-8").splitlines()
    except OSError as exc:
        raise TaskSectionError(f"cannot read {path}: {exc}") from exc


def _find_corpus_entry(lines: list[str], task_id: str) -> tuple[int, str]:
    for index, line in enumerate(lines):
        match = CORPUS_ENTRY_HEADING.match(line)
        if match and match.group(1) == task_id:
            return index, match.group(2).strip()
    raise TaskSectionError(
        f"corpus task entry {task_id!r} not found in the task corpus"
    )


def _find_eval_entry(lines: list[str], task_id: str) -> int:
    for index, line in enumerate(lines):
        match = EVAL_ENTRY_HEADING.match(line)
        if match and match.group(1) == task_id:
            return index
    raise TaskSectionError(
        f"evaluation section for task {task_id!r} not found in the evaluation spec"
    )


def _section_end(lines: list[str], start: int, heading: re.Pattern) -> int:
    for index in range(start + 1, len(lines)):
        if heading.match(lines[index]):
            return index
    return len(lines)


def _parse_labeled_fields(section: list[str]) -> dict[str, str]:
    """Parse ``**Label:** value`` fields and multi-line paragraph fields."""
    fields: dict[str, str] = {}
    index = 0
    while index < len(section):
        match = FIELD_LABEL.match(section[index])
        if not match:
            index += 1
            continue
        label, inline = match.group(1).strip(), match.group(2).strip()
        if inline:
            fields[label] = inline
            index += 1
            continue
        buffer: list[str] = []
        index += 1
        while index < len(section) and not FIELD_LABEL.match(section[index]):
            buffer.append(section[index])
            index += 1
        fields[label] = "\n".join(buffer).strip()
    return fields


def extract_safety_section(system_prompt: str) -> str:
    """Extract the Repository Safety section of the system prompt verbatim.

    The section spans from the ``## Repository Safety Protocol`` heading
    up to (not including) the next ``## `` heading.
    """
    lines = system_prompt.splitlines()
    start = next(
        (i for i, line in enumerate(lines) if "Repository Safety Protocol" in line),
        None,
    )
    if start is None:
        raise ValueError("system prompt lacks a Repository Safety Protocol section")
    end = next(
        (i for i in range(start + 1, len(lines)) if lines[i].startswith("## ")),
        len(lines),
    )
    return "\n".join(lines[start:end]).strip()


def materialize_benchmark_prompt(
    corpus_task: dict,
    eval_task: dict,
    *,
    safety_section: str,
    attached_documents: list[str],
) -> str:
    """Render the benchmark prompt per harness Appendix B.

    The rendering is deterministic and follows the Appendix B section
    order; all task content comes from the corpus/eval sections verbatim.
    """
    lines: list[str] = []
    lines.append("# BGA Senior Engineer — Benchmark Task")
    lines.append("")
    lines.append("## Repository Safety (MANDATORY)")
    lines.append(safety_section)
    lines.append("")
    lines.append("## Task")
    lines.append(f"- Task ID: {corpus_task['id']}")
    lines.append(f"- Title: {corpus_task['title']}")
    lines.append(f"- Category: {corpus_task['category']}")
    lines.append(f"- Difficulty: {corpus_task['difficulty']}")
    lines.append(f"- Effort: {corpus_task['effort']}")
    lines.append("")
    lines.append("**Background:**")
    lines.append(corpus_task["background"])
    lines.append("")
    lines.append("**Objective:**")
    lines.append(corpus_task["objective"])
    lines.append("")
    lines.append("## Expected Outcomes")
    lines.append(eval_task["expected_outcomes"])
    lines.append("")
    lines.append("## Success Criteria")
    lines.append(eval_task["success_criteria"])
    lines.append("")
    lines.append("## Required Evidence")
    lines.append(eval_task["required_evidence"])
    lines.append("")
    lines.append("## Environment")
    lines.append("- Reference codebase: `workspace/read/` (READ ONLY)")
    lines.append("- Working directory: `workspace/work/` (WRITABLE)")
    lines.append("- Prohibited: any write to bga-mercurio or any read-only path")
    lines.append("")
    lines.append("## Submission")
    lines.append("Produce in `workspace/work/`:")
    lines.append("- reasoning.md, architecture.md, subsystems.md")
    lines.append("- testing-evidence.md, validation-evidence.md")
    lines.append("- changes/ (diff bundle)")
    lines.append("- declaration.json")
    lines.append("")
    lines.append("## Attached Documents")
    for document in attached_documents:
        lines.append(f"- {document}")
    return "\n".join(lines) + "\n"


def default_attached_documents(
    eval_task: dict,
    *,
    skill_rule_files: list[str],
    standards_base: str = "docs/standards/",
) -> list[str]:
    """Attached document list per §3.3: evaluation spec and corpus always,
    plus the task's primary rule files and key standards."""
    documents = [
        "docs/evaluation/benchmark-evaluation-spec.md",
        "docs/evaluation/benchmark-task-corpus.md",
    ]
    for rule_file in skill_rule_files:
        documents.append(f"bga-senior-engineer-skill/rules/{rule_file}")
    standard = eval_task["key_standards"].replace("`", "").strip()
    if standard:
        documents.append(f"{standards_base}{standard}")
    return documents
