"""Scoring rubric resolution (MS-07, MVB-020).

The task's category weight family is parsed from the pinned evaluation
specification (``benchmark-evaluation-spec.md``): each task section's
rubric header names its family (evaluation spec §3.x "Scoring Rubric"
line), and §2.5 maps families to the five category weights.  Some task
headers carry the weights inline (e.g. ``(family ARCH: 30 / 35 / 10 /
15 / 10)``); when they do not, the §2.5 table is the authority.  Both
forms are supported and the sum is always asserted to be 100.

The canonical category names and their order follow §2.4/§2.5:
Correctness, Architecture, Framework Compliance, Maintainability,
Testing.  ``Framework_Compliance`` (the underscore form used in the
MS-07 CLI's ``--scores`` JSON) normalizes to ``Framework Compliance``.
"""

from __future__ import annotations

import re
from pathlib import Path

CATEGORIES = (
    "Correctness",
    "Architecture",
    "Framework Compliance",
    "Maintainability",
    "Testing",
)

# Canonical order of the weight columns in the §2.5 table.
FAMILY_TABLE_HEADER = (
    "Correctness",
    "Architecture",
    "Framework Compliance",
    "Maintainability",
    "Testing",
)

# Task rubric headers, e.g. "#### Scoring Rubric (family NOTIF)" or
# "#### Scoring Rubric (family ARCH: 30 / 35 / 10 / 15 / 10)".
RUBRIC_HEADER = re.compile(
    r"^#### Scoring Rubric \(family ([A-Za-z0-9]+)(?::\s*([\d\s/]+))?\)"
)
# §2.5 family rows: "| NOTIF | 40 | 10 | 25 | 15 | 10 | NOT-01, NOT-02 |"
FAMILY_ROW = re.compile(r"^\|\s*([A-Za-z0-9]+)\s*(\|\s*\d+\s*){5}\|")


class RubricError(Exception):
    """The rubric cannot be resolved from the evaluation specification."""


def normalize_category(name: str) -> str:
    """Map a category key to its canonical name.

    ``Framework_Compliance`` (the ``--scores`` JSON key form) and
    case-insensitive variants normalize to the canonical names.
    """
    key = name.strip().replace("_", " ").lower()
    for canonical in CATEGORIES:
        if canonical.lower() == key:
            return canonical
    raise RubricError(
        f"unknown rubric category {name!r}; expected one of "
        + ", ".join(CATEGORIES)
    )


def task_family(eval_path: str | Path, task_id: str) -> str:
    """The weight family declared by *task_id*'s rubric header (§3.x)."""
    lines = _read_lines(eval_path)
    in_task = False
    for line in lines:
        if re.match(rf"^### \d+\.\d+ {re.escape(task_id)}\s*$", line):
            in_task = True
            continue
        if in_task and re.match(r"^### ", line):
            break
        if not in_task:
            continue
        match = RUBRIC_HEADER.match(line.strip())
        if match:
            return match.group(1)
    raise RubricError(
        f"no scoring rubric found for task {task_id!r} in the evaluation spec"
    )


def family_weights(eval_path: str | Path, family: str) -> dict[str, int]:
    """Weights of *family* from the §2.5 table, or its inline definition."""
    lines = _read_lines(eval_path)
    for line in lines:
        match = FAMILY_ROW.match(line)
        if match is None or match.group(1) != family:
            continue
        cells = [c.strip() for c in line.split("|")]
        weights = {
            FAMILY_TABLE_HEADER[index]: int(cells[index + 2])
            for index in range(len(FAMILY_TABLE_HEADER))
        }
        _assert_weights(family, weights)
        return weights
    raise RubricError(
        f"weight family {family!r} not found in the evaluation spec §2.5 table"
    )


def task_weights(eval_path: str | Path, task_id: str) -> dict[str, int]:
    """Resolve *task_id*'s rubric: family and the five category weights.

    When the task's rubric header carries inline weights they are used
    (asserted to sum to 100); otherwise the family is looked up in the
    §2.5 table.  Returns the canonical category → weight mapping.
    """
    lines = _read_lines(eval_path)
    in_task = False
    for line in lines:
        if re.match(rf"^### \d+\.\d+ {re.escape(task_id)}\s*$", line):
            in_task = True
            continue
        if in_task and re.match(r"^### ", line):
            break
        if not in_task:
            continue
        match = RUBRIC_HEADER.match(line.strip())
        if match is None:
            continue
        family = match.group(1)
        if match.group(2):
            values = [int(part) for part in re.split(r"\s*/\s*", match.group(2).strip())]
            if len(values) != len(FAMILY_TABLE_HEADER):
                raise RubricError(
                    f"task {task_id!r} inline weights have {len(values)} "
                    f"values; expected {len(FAMILY_TABLE_HEADER)}"
                )
            weights = dict(zip(FAMILY_TABLE_HEADER, values))
            _assert_weights(family, weights)
            return weights
        return family_weights(eval_path, family)
    raise RubricError(
        f"no scoring rubric found for task {task_id!r} in the evaluation spec"
    )


def _assert_weights(family: str, weights: dict[str, int]) -> None:
    if set(weights) != set(FAMILY_TABLE_HEADER):
        raise RubricError(
            f"family {family!r} weights must cover exactly the categories "
            + ", ".join(FAMILY_TABLE_HEADER)
        )
    total = sum(weights.values())
    if total != 100:
        raise RubricError(
            f"family {family!r} weights sum to {total}, expected 100"
        )


def _read_lines(path: str | Path) -> list[str]:
    try:
        return Path(path).read_text(encoding="utf-8").splitlines()
    except OSError as exc:
        raise RubricError(f"cannot read evaluation spec {path}: {exc}") from exc
