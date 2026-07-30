"""
Rule file loader.

Discovers JSON rule files, parses them, validates file headers,
builds the :class:`RuleCollection`, and returns it for downstream
consumption by validators, report generators, and statistics engines.
"""

from __future__ import annotations

import json
import os
from pathlib import Path
from typing import Any

from tooling._shared.schema import FILE_META_REQUIRED_FIELDS, RULE_REQUIRED_FIELDS
from tooling._shared.types import FileInfo, Rule, RuleCollection, ValidationError

_LOAD_WARNINGS: list[ValidationError] = []


def load_rules(rules_path: str | os.PathLike) -> RuleCollection:
    """Load all rule files from a directory.

    Parameters
    ----------
    rules_path:
        Path to a directory containing ``*.json`` rule files (non-recursive),
        or a path to a single JSON file.

    Returns
    -------
    RuleCollection
        The loaded rules, file metadata, and built indices.

    Raises
    ------
    FileNotFoundError
        If *rules_path* does not exist.
    ValueError
        If a file contains invalid JSON.
    """
    global _LOAD_WARNINGS
    _LOAD_WARNINGS = []

    path = Path(rules_path)

    if not path.exists():
        raise FileNotFoundError(str(path))

    json_files: list[Path] = []
    if path.is_file():
        json_files.append(path)
    else:
        json_files = sorted(path.glob("*.json"))

    collection = RuleCollection()

    for filepath in json_files:
        _load_file(filepath, collection)

    _build_indices(collection)

    return collection


def read_raw_json(filepath: str | os.PathLike) -> dict[str, Any] | None:
    """Read a JSON file and return its contents as a dict, or ``None`` on failure.

    Unlike :func:`_parse_json` (which raises on malformed input), this
    function silently returns ``None`` for any read or parse error.
    """
    path = Path(filepath)
    try:
        with open(path, encoding="utf-8") as f:
            data: Any = json.load(f)
    except (json.JSONDecodeError, OSError):
        return None
    if not isinstance(data, dict):
        return None
    return data


def get_load_warnings() -> list[ValidationError]:
    """Return warnings accumulated during the last :func:`load_rules` call."""
    return _LOAD_WARNINGS.copy()


def _load_file(filepath: Path, collection: RuleCollection) -> None:
    data = _parse_json(filepath)
    if data is None:
        return

    file_meta = _extract_file_meta(filepath, data)
    line_count = _count_lines(filepath)

    file_info = FileInfo(
        path=str(filepath),
        domain=file_meta.get("domain", ""),
        version=file_meta.get("version", ""),
        last_updated=file_meta.get("last_updated", ""),
        source=file_meta.get("source", ""),
        line_count=line_count,
    )

    rules_data = data.get("rules", [])
    if not isinstance(rules_data, list):
        rules_data = []

    rule_ids: list[str] = []
    for idx, rule_dict in enumerate(rules_data):
        if not isinstance(rule_dict, dict):
            continue
        rule = _dict_to_rule(rule_dict, filepath, file_meta.get("domain", ""))
        if rule.id in collection.rules:
            _LOAD_WARNINGS.append(
                ValidationError(
                    validator="loader",
                    rule_id=rule.id,
                    file=str(filepath),
                    reason=f"Duplicate rule ID {rule.id} in {filepath.name}",
                    severity="warning",
                )
            )
        collection.rules[rule.id] = rule
        rule_ids.append(rule.id)

    file_info.rule_count = len(rule_ids)
    collection.files.append(file_info)
    collection.file_index[str(filepath)] = rule_ids


def _parse_json(filepath: Path) -> dict[str, Any] | None:
    try:
        with open(filepath, encoding="utf-8") as f:
            data: Any = json.load(f)
    except json.JSONDecodeError as exc:
        raise ValueError(f"{filepath.name}: {exc}") from exc

    if not isinstance(data, dict):
        raise ValueError(f"{filepath.name}: top-level value must be a JSON object")

    return data


def _extract_file_meta(
    filepath: Path, data: dict[str, Any]
) -> dict[str, Any]:
    meta: dict[str, Any] = {}
    for field in FILE_META_REQUIRED_FIELDS:
        val = data.get(field)
        if val is not None and val != "":
            meta[field] = val
        else:
            _LOAD_WARNINGS.append(
                ValidationError(
                    validator="loader",
                    file=str(filepath),
                    reason=f"Missing or empty file-level field '{field}' in {filepath.name}",
                    severity="warning",
                )
            )
            meta[field] = ""
    return meta


def _dict_to_rule(
    d: dict[str, Any], filepath: Path, file_domain: str
) -> Rule:
    missing: list[str] = []
    for field in RULE_REQUIRED_FIELDS:
        if field not in d or d[field] is None or d[field] == "":
            missing.append(field)
        elif isinstance(d[field], list) and len(d[field]) == 0:
            missing.append(field)

    if missing:
        rule_id = d.get("id", "<unknown>")
        _LOAD_WARNINGS.append(
            ValidationError(
                validator="loader",
                rule_id=str(rule_id),
                file=str(filepath),
                reason=f"Rule {rule_id} in {filepath.name} is missing required fields: {', '.join(missing)}",
                severity="warning",
            )
        )

    return Rule(
        id=str(d.get("id", "")),
        priority=int(d["priority"]) if isinstance(d.get("priority"), int) else d.get("priority", 0),
        rule=str(d.get("rule", "")),
        violation=d.get("violation", []),
        check=str(d.get("check", "")),
        fix=str(d.get("fix", "")),
        tags=d.get("tags", []),
        applies_to=d.get("applies_to"),
        exceptions=d.get("exceptions"),
        see_also=d.get("see_also"),
        rationale=d.get("rationale"),
        source=d.get("source"),
        file_path=str(filepath),
        file_domain=file_domain,
    )


def _count_lines(filepath: Path) -> int:
    try:
        with open(filepath, encoding="utf-8") as f:
            return sum(1 for _ in f)
    except OSError:
        return 0


def _build_indices(collection: RuleCollection) -> None:
    domain_index: dict[str, list[str]] = {}
    for rule_id, rule in collection.rules.items():
        domain = rule.file_domain
        if domain not in domain_index:
            domain_index[domain] = []
        domain_index[domain].append(rule_id)

    collection.domain_index = domain_index

    crossref_index: dict[str, list[str]] = {}
    for rule_id, rule in collection.rules.items():
        targets = rule.see_also if isinstance(rule.see_also, list) else []
        for target in targets:
            if target not in crossref_index:
                crossref_index[target] = []
            crossref_index[target].append(rule_id)

    collection.crossref_index = crossref_index
