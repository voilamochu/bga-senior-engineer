"""
Markdown parsing utilities for the Runtime Tooling Platform.

Provides table parsing, section extraction, and label-value lookup
for the specific Markdown structures used in release documents.

All functions operate on raw Markdown text and return plain Python
types.  No dependencies on other shared modules.
"""

from __future__ import annotations

import re
from typing import Any


def find_section(markdown_text: str, section_header: str) -> str:
    """Extract a section from a Markdown document by header text.

    Searches for a line containing *section_header* (e.g. ``"## 4.
    Runtime Inventory"``) and returns all text from that line up to
    (but not including) the next heading of equal or greater level, or
    the end of the document.

    Returns an empty string if the header is not found.
    """
    lines = markdown_text.splitlines()
    header_line: int | None = None

    for i, line in enumerate(lines):
        if section_header in line and line.strip().startswith("#"):
            header_line = i
            break

    if header_line is None:
        return ""

    header_level = _heading_level(lines[header_line])
    result: list[str] = [lines[header_line]]

    for i in range(header_line + 1, len(lines)):
        if lines[i].strip().startswith("#"):
            next_level = _heading_level(lines[i])
            if next_level <= header_level:
                break
        result.append(lines[i])

    return "\n".join(result)


def _heading_level(line: str) -> int:
    stripped = line.lstrip()
    count = 0
    for ch in stripped:
        if ch == "#":
            count += 1
        else:
            break
    return count


def parse_table(markdown_text: str, table_header: str | None = None) -> list[dict[str, str]]:
    """Parse the first Markdown table in *markdown_text*.

    If *table_header* is given, only consider the table that appears
    inside the section identified by *table_header*.

    Returns a list of dicts keyed by the (lowercased, stripped) column
    headers from the table's header row.
    """
    if table_header:
        markdown_text = find_section(markdown_text, table_header)

    lines = markdown_text.splitlines()

    # Find the first table — a line starting with '|'
    table_start: int | None = None
    for i, line in enumerate(lines):
        stripped = line.strip()
        if stripped.startswith("|") and stripped.endswith("|"):
            table_start = i
            break

    if table_start is None:
        return []

    # Header row
    header_line = lines[table_start].strip()
    headers = _split_row(header_line)

    # Separator row (skip it)
    if table_start + 1 < len(lines) and "---" in lines[table_start + 1]:
        data_start = table_start + 2
    else:
        data_start = table_start + 1

    rows: list[dict[str, str]] = []
    for i in range(data_start, len(lines)):
        stripped = lines[i].strip()
        if not stripped.startswith("|"):
            break
        cells = _split_row(stripped)
        row: dict[str, str] = {}
        for j, header in enumerate(headers):
            if j < len(cells):
                row[header] = cells[j].strip()
            else:
                row[header] = ""
        rows.append(row)

    return rows


def _split_row(row: str) -> list[str]:
    """Split a Markdown table row into cells."""
    cells: list[str] = []
    current: list[str] = []
    in_pipe = False
    for ch in row:
        if ch == "|":
            cells.append("".join(current).strip())
            current = []
            in_pipe = True
        else:
            current.append(ch)
    if current:
        cells.append("".join(current).strip())
    # Remove leading empty cell from opening pipe
    if cells and cells[0] == "":
        cells = cells[1:]
    # Remove trailing empty cell from closing pipe
    if cells and cells[-1] == "":
        cells = cells[:-1]
    return cells


def extract_stat(markdown_text: str, label: str) -> str | None:
    """Extract a statistic value from a key-value Markdown table.

    Finds a table row where any cell contains *label* (case-insensitive,
    substring match) and returns the content of the next cell in the
    same row.

    In a two-column table like ``| Total rules | 185 |`` searching for
    ``"total rules"`` returns ``"185"``.

    Returns ``None`` if the label is not found in any table.
    """
    all_tables = _find_all_tables(markdown_text)
    for table in all_tables:
        for row in table:
            keys = list(row.keys())
            for idx, key in enumerate(keys):
                val = row[key]
                if label.lower() in val.lower() or label.lower() in key.lower():
                    # Return the value in the next column, or the current value
                    if idx + 1 < len(keys):
                        return row[keys[idx + 1]]
                    return val
    return None


def _find_all_tables(markdown_text: str) -> list[list[dict[str, str]]]:
    """Return all Markdown tables found in the text."""
    lines = markdown_text.splitlines()
    tables: list[list[dict[str, str]]] = []
    i = 0

    while i < len(lines):
        stripped = lines[i].strip()
        if stripped.startswith("|") and stripped.endswith("|"):
            headers = _split_row(stripped)
            i += 1
            if i < len(lines) and "---" in lines[i]:
                i += 1
            rows: list[dict[str, str]] = []
            while i < len(lines):
                row_stripped = lines[i].strip()
                if not (row_stripped.startswith("|") and row_stripped.endswith("|")):
                    break
                cells = _split_row(row_stripped)
                row: dict[str, str] = {}
                for j, header in enumerate(headers):
                    if j < len(cells):
                        row[header] = cells[j].strip()
                    else:
                        row[header] = ""
                rows.append(row)
                i += 1
            if rows:
                tables.append(rows)
        else:
            i += 1

    return tables
