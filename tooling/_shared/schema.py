"""
Schema v1.1 field definitions and validation primitives.

Provides the canonical field lists, type-checking functions, and
extra-field detection used by validators and other tools.
"""

from __future__ import annotations

import re
from typing import Any

# ---------------------------------------------------------------------------
# Canonical value lists (frozen per Schema v1.1)
# ---------------------------------------------------------------------------

VALID_DOMAINS: list[str] = [
    "constitution",
    "architecture",
    "state-machine",
    "actions",
    "persistence",
    "notifications",
    "client",
    "synchronization",
    "undo-replay",
    "testing",
    "animations",
    "migration",
]

VALID_COMPONENTS: list[str] = [
    "Game.php",
    "Actions",
    "States",
    "Managers",
    "Models",
    "Notifications",
    "Client",
    "Database",
    "Engine",
    "All components",
]

VALID_PREFIXES: list[str] = [
    "CORE",
    "ARCH",
    "STAT",
    "ACTN",
    "PERS",
    "NOTF",
    "CLNT",
    "SYNC",
    "UNDO",
    "TEST",
    "ANIM",
    "MIGR",
]

PREFIX_TO_DOMAIN: dict[str, str] = {
    "CORE": "constitution",
    "ARCH": "architecture",
    "STAT": "state-machine",
    "ACTN": "actions",
    "PERS": "persistence",
    "NOTF": "notifications",
    "CLNT": "client",
    "SYNC": "synchronization",
    "UNDO": "undo-replay",
    "TEST": "testing",
    "ANIM": "animations",
    "MIGR": "migration",
}

DOMAIN_TO_PREFIX: dict[str, str] = {v: k for k, v in PREFIX_TO_DOMAIN.items()}

# ---------------------------------------------------------------------------
# Schema v1.1 field definitions
# ---------------------------------------------------------------------------

FILE_META_REQUIRED_FIELDS: list[str] = [
    "domain",
    "version",
    "last_updated",
    "source",
]

RULE_REQUIRED_FIELDS: list[str] = [
    "id",
    "priority",
    "rule",
    "violation",
    "check",
    "fix",
    "tags",
]

RULE_OPTIONAL_FIELDS: list[str] = [
    "exceptions",
    "see_also",
    "rationale",
    "applies_to",
    "source",
]

ALL_RULE_FIELDS: list[str] = RULE_REQUIRED_FIELDS + RULE_OPTIONAL_FIELDS

# ---------------------------------------------------------------------------
# Type-checking primitives
# ---------------------------------------------------------------------------

VERSION_RE = re.compile(r"^\d+\.\d+\.\d+$")
DATE_RE = re.compile(r"^\d{4}-\d{2}-\d{2}$")
RULE_ID_RE = re.compile(r"^(CORE|ARCH|STAT|ACTN|PERS|NOTF|CLNT|SYNC|UNDO|TEST|ANIM|MIGR)-\d{3}$")


def is_string(value: Any) -> bool:
    return isinstance(value, str)


def is_non_empty_string(value: Any) -> bool:
    return isinstance(value, str) and len(value) > 0


def is_integer(value: Any) -> bool:
    return isinstance(value, int) and not isinstance(value, bool)


def is_integer_in_range(value: Any, lo: int = 1, hi: int = 5) -> bool:
    return is_integer(value) and lo <= value <= hi


def is_array(value: Any) -> bool:
    return isinstance(value, list)


def is_array_of_strings(value: Any) -> bool:
    if not is_array(value):
        return False
    return all(isinstance(item, str) for item in value)


def is_non_empty_array(value: Any) -> bool:
    return is_array(value) and len(value) > 0


def is_valid_version(value: Any) -> bool:
    return is_non_empty_string(value) and bool(VERSION_RE.match(value))


def is_valid_date(value: Any) -> bool:
    return is_non_empty_string(value) and bool(DATE_RE.match(value))


def is_valid_rule_id(value: Any) -> bool:
    return is_non_empty_string(value) and bool(RULE_ID_RE.match(value))


def is_valid_domain(value: Any) -> bool:
    return value in VALID_DOMAINS


def is_valid_component(value: Any) -> bool:
    return value in VALID_COMPONENTS


def get_prefix_from_rule_id(rule_id: str) -> str | None:
    match = RULE_ID_RE.match(rule_id)
    if match:
        return match.group(1)
    return None

# ---------------------------------------------------------------------------
# Extra-field detection
# ---------------------------------------------------------------------------

FILE_META_KNOWN_FIELDS: list[str] = FILE_META_REQUIRED_FIELDS + ["rules"]


def find_extra_file_fields(data: dict[str, Any]) -> list[str]:
    return [key for key in data if key not in FILE_META_KNOWN_FIELDS]


def find_extra_rule_fields(rule: dict[str, Any]) -> list[str]:
    return [key for key in rule if key not in ALL_RULE_FIELDS]
