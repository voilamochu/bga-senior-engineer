"""manual-review.md parsing (MS-07, MVB-021).

The scaffolded ``review/manual-review.md`` is the reviewer's working
file.  The score command extracts per-category records from its scoring
table (category, score, evidence citations, comments, deductions,
uncertainty, critical-failure flag), the observed C1-C9 codes, and the
reviewer identity.  Parsing is deterministic and tolerant of unfilled
cells (partial completion): an empty score or citation list is recorded
as such and validated by the scorer.
"""

from __future__ import annotations

import re

from tooling.harness.scoring.persist import CategoryRecord
from tooling.harness.scoring.rubric import CATEGORIES

SCORING_TABLE_HEADING = "## Category Scores"
CRITICAL_SECTION_HEADING = "## Critical Failures Observed (C1-C9)"

_CATEGORY_ROW = re.compile(
    r"^\|\s*(" + "|".join(re.escape(c) for c in CATEGORIES) + r")\s*\|(.*)\|\s*$"
)
_REVIEWER_ROW = re.compile(r"^\|\s*Reviewer\s*\|\s*(.*?)\s*\|")
# A critical-failure entry is a bullet (or bare) line whose *first* token
# is a condition code, e.g. "- C4 hidden information leak".  Codes inside
# instruction prose are not entries.
_CRITICAL_CODE = re.compile(r"^[-*]?\s*(C\d+)\b")
_SECTION_HEADING = re.compile(r"^## ")


def parse_reviewer(md_text: str) -> str | None:
    """The reviewer identity from the header table (``| Reviewer |``)."""
    for line in md_text.splitlines():
        match = _REVIEWER_ROW.match(line)
        if match:
            value = match.group(1).strip()
            return value or None
    return None


def parse_category_table(md_text: str) -> list[CategoryRecord]:
    """Per-category records from the scoring table.

    Each row: ``| Category | Score | Citations | Comments | Deductions |
    Uncertainty | Critical failure |``.  Empty cells yield ``None`` /
    empty lists, so partial reviews parse cleanly.
    """
    records: list[CategoryRecord] = []
    for line in md_text.splitlines():
        match = _CATEGORY_ROW.match(line)
        if match is None:
            continue
        category = match.group(1).strip()
        cells = _split_cells(match.group(2))
        records.append(
            CategoryRecord(
                category=category,
                score=_parse_score(cells[0]),
                evidence=_parse_citations(cells[1]),
                comments=cells[2].strip(),
                deductions=cells[3].strip(),
                uncertainty=cells[4].strip(),
                critical_failure=_parse_yes_no(cells[5]),
            )
        )
    return records


def parse_critical_codes(md_text: str) -> list[str]:
    """C1-C9 codes listed in the "Critical Failures Observed" section.

    The section spans from its heading to the next ``## `` heading;
    literal ``none`` yields an empty list.
    """
    lines = md_text.splitlines()
    try:
        start = next(
            i for i, line in enumerate(lines) if line.strip() == CRITICAL_SECTION_HEADING
        )
    except StopIteration:
        return []
    codes: list[str] = []
    for line in lines[start + 1:]:
        if _SECTION_HEADING.match(line):
            break
        stripped = line.strip()
        if stripped.lower() in ("none", "- none", "* none"):
            continue
        match = _CRITICAL_CODE.match(stripped)
        if match:
            codes.append(match.group(1))
    return sorted(set(codes))


def _split_cells(cells_text: str) -> list[str]:
    """Split the cell region of a table row into its six cells.

    Empty cells are legitimate (partial reviews); a short row is padded
    with empty cells so malformed rows parse leniently.
    """
    parts = [part.strip() for part in cells_text.split("|")]
    while len(parts) < 6:
        parts.append("")
    return parts[:6]


def _parse_score(cell: str) -> int | None:
    cell = cell.strip()
    if not cell or cell in ("-", "—", "n/a"):
        return None
    try:
        value = float(cell)
    except ValueError:
        return None
    if value != int(value):
        return None
    return int(value)


def _parse_citations(cell: str) -> list[str]:
    citations: list[str] = []
    for part in re.split(r"[;,]+", cell):
        cleaned = part.strip().strip("`").strip()
        if cleaned:
            citations.append(cleaned)
    return citations


def _parse_yes_no(cell: str) -> bool:
    return cell.strip().lower() in ("yes", "y", "true")
