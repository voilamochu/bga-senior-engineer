"""Run directory layout and run ID scheme (MVB-001).

Implements harness spec §9.1 (run directory skeleton) and §9.2 (run ID
format ``run-{task}-{model-slug}-{YYYYMMDDTHHMMSSZ}-{seq}``).  The runs
root lives outside both repositories (§4.1) and is resolved by
:mod:`tooling.harness.config`.
"""

from __future__ import annotations

import re
from dataclasses import dataclass
from datetime import datetime
from pathlib import Path

from tooling.harness.util.clock import run_id_timestamp, utc_now

RUN_ID_PATTERN = re.compile(
    r"^run-[A-Za-z0-9-]+-[A-Za-z0-9._-]+-\d{8}T\d{6}Z-[0-9a-f]{2}$"
)
TASK_ID_PATTERN = re.compile(r"^[A-Za-z0-9-]+$")
MODEL_SLUG_PATTERN = re.compile(r"^[a-z0-9._-]+$")

MAX_SEQUENCE = 0xFF  # two-hex sequence space: 00 .. ff (§9.2)
MAX_PATH_LENGTH = 120  # bounded path length per MVB-001 acceptance criteria

# Run directory subdirectories per harness §9.1 / MVB-001.
RUN_SUBDIRECTORIES = (
    "protocol",
    "protocol/baseline",
    "workspace",
    "workspace/read",
    "workspace/work",
    "evidence",
    "validation",
    "validation/raw",
    "review",
    "review/scoring",
    "reports",
)


@dataclass(frozen=True)
class RunDir:
    """Paths of one run directory (harness §9.1)."""

    root: Path
    run_id: str
    protocol: Path
    baseline: Path
    workspace: Path
    workspace_read: Path
    workspace_work: Path
    evidence: Path
    validation: Path
    validation_raw: Path
    review: Path
    review_scoring: Path
    reports: Path

    @property
    def manifest_path(self) -> Path:
        return self.root / "manifest.json"

    @property
    def status_path(self) -> Path:
        return self.root / "status.json"


def normalize_model_slug(model: str) -> str:
    """Normalize a model identifier into the §9.2 ``model-slug`` form.

    Lowercases, collapses whitespace to ``-``, and drops characters
    outside ``[a-z0-9._-]``.
    """
    slug = model.strip().lower()
    slug = re.sub(r"\s+", "-", slug)
    slug = re.sub(r"[^a-z0-9._-]", "", slug)
    slug = re.sub(r"-+", "-", slug).strip("-.")
    if not slug or not MODEL_SLUG_PATTERN.match(slug):
        raise ValueError(f"model identifier {model!r} does not produce a usable slug")
    return slug


def build_run_id(task_id: str, model_slug: str, timestamp: str, seq: int) -> str:
    """Assemble a run ID per §9.2."""
    if not TASK_ID_PATTERN.match(task_id):
        raise ValueError(
            f"invalid task ID {task_id!r}: expected alphanumerics and dashes"
        )
    if not MODEL_SLUG_PATTERN.match(model_slug):
        raise ValueError(f"invalid model slug {model_slug!r}")
    if not re.match(r"^\d{8}T\d{6}Z$", timestamp):
        raise ValueError(f"invalid run timestamp {timestamp!r}")
    if not 0 <= seq <= MAX_SEQUENCE:
        raise ValueError(f"sequence {seq} out of range 00..ff")
    return f"run-{task_id}-{model_slug}-{timestamp}-{seq:02x}"


def next_sequence(runs_root: Path, task_id: str, model_slug: str, timestamp: str) -> int:
    """Next two-hex sequence for repeated runs within the same second (§9.2).

    Derived from the existing run directories, so separate ``init``
    invocations in the same second receive ``-00``, ``-01``, ... with no
    wall-clock race.
    """
    prefix = f"run-{task_id}-{model_slug}-{timestamp}-"
    existing: list[int] = []
    if Path(runs_root).is_dir():
        for child in Path(runs_root).iterdir():
            if not child.is_dir() or not child.name.startswith(prefix):
                continue
            suffix = child.name[len(prefix):]
            try:
                existing.append(int(suffix, 16))
            except ValueError:
                continue
    seq = max(existing, default=-1) + 1
    if seq > MAX_SEQUENCE:
        raise RuntimeError(
            f"sequence space exhausted for {prefix} (max {MAX_SEQUENCE:02x})"
        )
    return seq


def _paths(runs_root: Path, run_id: str) -> dict[str, Path]:
    base = Path(runs_root) / run_id
    return {
        "protocol": base / "protocol",
        "baseline": base / "protocol" / "baseline",
        "workspace": base / "workspace",
        "workspace_read": base / "workspace" / "read",
        "workspace_work": base / "workspace" / "work",
        "evidence": base / "evidence",
        "validation": base / "validation",
        "validation_raw": base / "validation" / "raw",
        "review": base / "review",
        "review_scoring": base / "review" / "scoring",
        "reports": base / "reports",
    }


def _make_run_dir(runs_root: Path, run_id: str) -> RunDir:
    base = Path(runs_root) / run_id
    return RunDir(root=base, run_id=run_id, **_paths(runs_root, run_id))


def parse_run_id(run_id: str) -> dict:
    """Split a run ID into ``{task, model_slug, timestamp, seq}``.

    Task IDs follow the corpus convention of upper-case letters, digits,
    and dashes (e.g. ``NOT-02``); model slugs are normalized lowercase.
    The split takes the first ``-`` followed by a lowercase letter.
    """
    m = re.match(r"^run-(.+)-(\d{8}T\d{6}Z)-([0-9a-f]{2})$", run_id)
    if not m:
        raise ValueError(f"invalid run ID {run_id!r}")
    head, timestamp, seq = m.group(1), m.group(2), m.group(3)
    split = re.search(r"-[a-z]", head)
    if split is not None:
        task, model_slug = head[: split.start()], head[split.start() + 1:]
    else:
        task, _, model_slug = head.rpartition("-")
    if not task or not model_slug:
        raise ValueError(f"invalid run ID {run_id!r}")
    return {"task": task, "model_slug": model_slug, "timestamp": timestamp, "seq": seq}


def create_run_dir(
    task_id: str,
    model_slug: str,
    runs_root: str | Path,
    *,
    now: datetime | None = None,
) -> RunDir:
    """Create a run directory skeleton per §9.1 and return its :class:`RunDir`.

    The runs root is created when missing.  Refuses to overwrite an
    existing run directory.  Never writes outside *runs_root*.
    """
    task_id = task_id.strip()
    if not TASK_ID_PATTERN.match(task_id):
        raise ValueError(
            f"invalid task ID {task_id!r}: expected alphanumerics and dashes"
        )
    slug = normalize_model_slug(model_slug)

    runs_root = Path(runs_root)
    runs_root.mkdir(parents=True, exist_ok=True)

    timestamp = run_id_timestamp(now if now is not None else utc_now())
    seq = next_sequence(runs_root, task_id, slug, timestamp)
    run_id = build_run_id(task_id, slug, timestamp, seq)
    run_dir = _make_run_dir(runs_root, run_id)

    if len(str(run_dir.root)) > MAX_PATH_LENGTH:
        raise ValueError(
            f"run directory path exceeds {MAX_PATH_LENGTH} chars: {run_dir.root}"
        )
    if run_dir.root.exists():
        raise FileExistsError(f"run directory already exists: {run_dir.root}")

    for relpath in RUN_SUBDIRECTORIES:
        (run_dir.root / relpath).mkdir(parents=True, exist_ok=True)
    return run_dir


def load_run_dir(run_id: str, runs_root: str | Path) -> RunDir:
    """Resolve an existing run directory; raises if absent or malformed."""
    if not RUN_ID_PATTERN.match(run_id):
        raise ValueError(f"invalid run ID {run_id!r}")
    run_dir = _make_run_dir(Path(runs_root), run_id)
    if not run_dir.root.is_dir():
        raise FileNotFoundError(f"no run directory {run_id!r} under {runs_root}")
    return run_dir
