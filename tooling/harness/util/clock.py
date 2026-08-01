"""ISO-8601 UTC clock for the harness.

Harness spec §6.2 requires timestamps in ISO-8601 UTC (``Z`` suffix)
across all artifacts; §9.2 uses the compact ``YYYYMMDDTHHMMSSZ`` form
for run IDs.  All harness timestamps go through this module.
"""

from __future__ import annotations

import re
from datetime import datetime, timezone

# Full ISO-8601 UTC with optional fractional seconds, e.g. 2026-07-31T12:00:00Z
TIMESTAMP_PATTERN = re.compile(r"^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)?Z$")

# Compact run-ID form, e.g. 20260731T120000Z
RUN_ID_TIMESTAMP_PATTERN = re.compile(r"^\d{8}T\d{6}Z$")


def utc_now() -> datetime:
    """Current UTC time as a tz-aware datetime."""
    return datetime.now(timezone.utc)


def format_iso(dt: datetime | None = None) -> str:
    """Format *dt* (or now) as ISO-8601 UTC with ``Z`` and microsecond precision.

    Naive datetimes are assumed to be UTC.
    """
    dt = dt if dt is not None else utc_now()
    if dt.tzinfo is None:
        dt = dt.replace(tzinfo=timezone.utc)
    dt = dt.astimezone(timezone.utc)
    return dt.strftime("%Y-%m-%dT%H:%M:%S.%fZ")


def utc_now_iso() -> str:
    """Current time as an ISO-8601 UTC string ending in ``Z``."""
    return format_iso()


def run_id_timestamp(dt: datetime | None = None) -> str:
    """Compact timestamp for run IDs (harness §9.2): ``YYYYMMDDTHHMMSSZ``."""
    dt = dt if dt is not None else utc_now()
    if dt.tzinfo is None:
        dt = dt.replace(tzinfo=timezone.utc)
    dt = dt.astimezone(timezone.utc)
    return dt.strftime("%Y%m%dT%H%M%SZ")


def is_iso_utc(value: str) -> bool:
    """True when *value* matches the full ISO-8601 UTC timestamp form."""
    return bool(TIMESTAMP_PATTERN.match(value))


def is_run_id_timestamp(value: str) -> bool:
    """True when *value* matches the compact run-ID timestamp form."""
    return bool(RUN_ID_TIMESTAMP_PATTERN.match(value))
