"""
Shared data types for the Runtime Tooling Platform.

All tools import these types. No dependencies on other modules.
"""

from __future__ import annotations

from dataclasses import dataclass, field
from enum import Enum
from typing import Any


@dataclass
class Rule:
    """A single rule from a Runtime Specification v1.1 rule file.

    Fields map directly to the JSON schema defined in the canonical schema.
    ``file_path`` and ``file_domain`` are populated by the loader, not from JSON.
    """

    id: str
    priority: int
    rule: str
    violation: list[str]
    check: str
    fix: str
    tags: list[str]
    applies_to: list[str] | None = None
    exceptions: list[str] | None = None
    see_also: list[str] | None = None
    rationale: str | None = None
    source: str | None = None

    # Populated by the loader — not read from JSON
    file_path: str = ""
    file_domain: str = ""


@dataclass
class FileInfo:
    """Metadata for a single rule file."""

    path: str
    domain: str
    version: str
    last_updated: str
    source: str
    rule_count: int = 0
    line_count: int = 0


@dataclass
class RuleCollection:
    """In-memory collection of all loaded rules and their indices.

    This is the primary data structure passed to validators, report
    generators, and statistics engines.
    """

    rules: dict[str, Rule] = field(default_factory=dict)
    files: list[FileInfo] = field(default_factory=list)
    crossref_index: dict[str, list[str]] = field(default_factory=dict)
    domain_index: dict[str, list[str]] = field(default_factory=dict)
    file_index: dict[str, list[str]] = field(default_factory=dict)


@dataclass
class ValidationError:
    """A single validation error or warning."""

    validator: str
    rule_id: str | None = None
    file: str | None = None
    reason: str = ""
    severity: str = "error"


@dataclass
class ValidatorResult:
    """Output of a single validator run."""

    name: str
    status: str = "pass"
    errors: list[ValidationError] = field(default_factory=list)


class ReportFormat(Enum):
    """Supported report output formats."""

    HUMAN = "human"
    JSON = "json"
    CI = "ci"


class ExitCode:
    """Standard exit codes for CLI tools."""

    SUCCESS = 0
    FAILURE = 1
    ERROR = 2
