"""CLI for the benchmark harness (MS-01: ``init``, MS-02: ``prepare``).

Each milestone adds subcommands to ``python -m tooling.harness``; MS-01
implements ``init`` (P0 run directory + manifest + status) and MS-02
implements ``prepare`` (P0–P1: safety baseline, workspace provisioning,
environment collection).
"""

from __future__ import annotations

import argparse
import json
import sys
from pathlib import Path

from tooling.harness.agent.adapter import AdapterError, create_adapter
from tooling.harness.agent.runtime import (
    SessionRuntimeError,
    p3_budget_seconds,
    run_session,
    verify_boundaries,
)
from tooling.harness.util.proc import CommandLog
from tooling.harness.config import (
    block_on_tool_version_mismatch,
    default_evaluation_docs,
    default_reference_root,
    default_skill_root,
    default_system_prompt_path,
    execution_platform,
    launch_model,
    load_settings,
    network_policy,
    resolve_runs_root,
)
from tooling.harness.environment.collect import (
    collect_environment,
    mismatched_tools,
    missing_tools,
    save_environment,
)
from tooling.harness.evidence.collect import EvidenceError, collect_evidence, load_evidence_catalog
from tooling.harness.evidence.freeze import FreezeError, freeze_evidence
from tooling.harness.prompt.bundle import (
    BundleError,
    build_prompt_bundle,
    bundle_artifact_json,
    bundle_metadata,
    artifact_hashes,
    save_bundle_metadata_json,
    write_bundle,
)
from tooling.harness.prompt.materialize import (
    TaskSectionError,
    default_attached_documents,
    extract_safety_section,
    materialize_benchmark_prompt,
    parse_corpus_task,
    parse_eval_task,
    primary_rule_files,
)
from tooling.harness.runtime import run_dir
from tooling.harness.runtime.manifest import RunManifest, new_run_manifest
from tooling.harness.runtime.run_dir import load_run_dir
from tooling.harness.runtime.status import RunStatus
from tooling.harness.safety.baseline import (
    BaselineError,
    capture_baseline,
    save_baseline,
    verify_baseline,
)
from tooling.harness.skill.loader import SkillError, load_skill
from tooling.harness.util.clock import format_iso, utc_now
from tooling.harness.util.log import harness_log
from tooling.harness.workspace.provision import ProvisionError, provision


def build_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(
        prog="tooling.harness",
        description="BGA Senior Engineer benchmark harness",
    )
    subparsers = parser.add_subparsers(dest="command", required=True)

    init = subparsers.add_parser("init", help="initialize a run directory (P0)")
    init.add_argument("task", help="corpus task ID (e.g. NOT-02)")
    init.add_argument("model", help="model identifier (e.g. demo-model)")
    init.add_argument(
        "--runs-root",
        help="runs root directory (default: config or sibling runs/ directory)",
    )

    prepare = subparsers.add_parser(
        "prepare", help="prepare a run: safety baseline, workspace, environment (P0-P1)"
    )
    prepare.add_argument("run_id", help="run ID created by init")
    prepare.add_argument(
        "--reference",
        help="path to the bga-mercurio reference repository "
        "(default: sibling bga-mercurio checkout)",
    )
    prepare.add_argument(
        "--runs-root",
        help="runs root directory (default: config or sibling runs/ directory)",
    )

    prompt = subparsers.add_parser(
        "prompt", help="generate the prompt bundle for a run (P2)"
    )
    prompt.add_argument("run_id", help="run ID created by init")
    prompt.add_argument(
        "--skill",
        help="path to the skill package (default: bga-senior-engineer-skill/)",
    )
    prompt.add_argument(
        "--runs-root",
        help="runs root directory (default: config or sibling runs/ directory)",
    )

    session = subparsers.add_parser(
        "session", help="execute the agent session for a run (P2-P3)"
    )
    session.add_argument("run_id", help="run ID created by init")
    session.add_argument(
        "--platform",
        choices=["opencode", "stub"],
        help="execution platform (default: settings or 'opencode')",
    )
    session.add_argument(
        "--timeout-min",
        type=float,
        help="P3 time budget in minutes (overrides the §5.2 effort formula)",
    )
    session.add_argument(
        "--dry-run",
        action="store_true",
        help="verify workspace boundaries and print the bundle hash without "
        "starting a session",
    )
    session.add_argument(
        "--runs-root",
        help="runs root directory (default: config or sibling runs/ directory)",
    )

    collect = subparsers.add_parser(
        "collect", help="collect and freeze the run's evidence (P4)"
    )
    collect.add_argument("run_id", help="run ID created by init")
    collect.add_argument(
        "--freeze",
        action="store_true",
        help="freeze the evidence set after collection (P4 completion)",
    )
    collect.add_argument(
        "--runs-root",
        help="runs root directory (default: config or sibling runs/ directory)",
    )

    gates = subparsers.add_parser(
        "gates", help="run the automatic validation gates G0-G2 (P5)"
    )
    gates.add_argument("run_id", help="run ID created by init")
    gates.add_argument(
        "--reference",
        help="path to the bga-mercurio reference repository "
        "(default: sibling bga-mercurio checkout)",
    )
    gates.add_argument(
        "--runs-root",
        help="runs root directory (default: config or sibling runs/ directory)",
    )

    review = subparsers.add_parser(
        "review", help="assemble or inspect the manual review package (P6)"
    )
    review.add_argument("run_id", help="run ID created by init")
    review.add_argument(
        "--scaffold",
        action="store_true",
        help="assemble the review package (manual-review.md, g3 script, "
        "onboarding, review.json); without this flag the current review "
        "state is printed (resume view)",
    )
    review.add_argument(
        "--runs-root",
        help="runs root directory (default: config or sibling runs/ directory)",
    )

    score = subparsers.add_parser(
        "score", help="record category scores and compute the verdict (P7)"
    )
    score.add_argument("run_id", help="run ID created by init")
    score.add_argument(
        "--scores",
        metavar="JSON",
        help="category scores as JSON: plain {\"Correctness\": 80, ...} or "
        "full records {\"Correctness\": {\"score\": 80, \"evidence\": [...], ...}}; "
        "when omitted the scores are read from manual-review.md",
    )
    score.add_argument(
        "--reviewer",
        help="reviewer identity recorded in the manifest errata "
        "(default: manual-review.md Reviewer field, else 'anonymous')",
    )
    score.add_argument(
        "--recompute",
        action="store_true",
        help="recalculate a completed review (harness §5.1 arithmetic-error "
        "correction, recorded)",
    )
    score.add_argument(
        "--runs-root",
        help="runs root directory (default: config or sibling runs/ directory)",
    )

    report = subparsers.add_parser(
        "report", help="generate the canonical reports (P8)"
    )
    report.add_argument("run_id", help="run ID created by init")
    report.add_argument(
        "--runs-root",
        help="runs root directory (default: config or sibling runs/ directory)",
    )

    archive = subparsers.add_parser(
        "archive", help="archive a completed run: marker, registry, leaderboard (P9)"
    )
    archive.add_argument("run_id", help="run ID created by init")
    archive.add_argument(
        "--reference",
        help="path to the bga-mercurio reference repository "
        "(default: sibling bga-mercurio checkout)",
    )
    archive.add_argument(
        "--verify",
        action="store_true",
        help="verify an archived run (marker, registry, leaderboard, "
        "evidence hashes, reports, packaged contents)",
    )
    archive.add_argument(
        "--runs-root",
        help="runs root directory (default: config or sibling runs/ directory)",
    )

    synthetic = subparsers.add_parser(
        "synthetic",
        help="run the full pipeline deterministically with a synthetic "
        "submission (no LLM, MS-09)",
    )
    synthetic.add_argument(
        "--task",
        default="NOT-02",
        help="corpus task ID (default: NOT-02)",
    )
    synthetic.add_argument(
        "--runs-root",
        help="runs root directory (default: config or sibling runs/ directory)",
    )
    synthetic.add_argument(
        "--variant",
        choices=["passing", "failing"],
        default="passing",
        help="fixture submission: 'passing' satisfies every NOT-02 "
        "criterion; 'failing' reintroduces a duplicated notification "
        "block (REJECTED at P5)",
    )
    synthetic.add_argument(
        "--dataset",
        metavar="PATH",
        help="pilot-0 validation-dataset seed path (default: "
        "docs/evaluation/validation-dataset/pilot-0.json)",
    )
    return parser


def cmd_init(args: argparse.Namespace) -> int:
    runs_root = resolve_runs_root(args.runs_root)
    now = utc_now()
    run = run_dir.create_run_dir(args.task, args.model, runs_root, now=now)
    log = harness_log(run.root)

    manifest = new_run_manifest(
        run.run_id,
        args.task,
        model_id=args.model,
        started_at=format_iso(now),
    )
    manifest.save(run.manifest_path)

    status = RunStatus(run_id=run.run_id, updated_at=format_iso(now))
    status.record_checkpoint("p0", at=format_iso(now))
    status.save(run.status_path)

    log.info(f"run directory initialized: {run.run_id}")
    print(f"Run initialized: {run.run_id}")
    print(f"Run directory: {run.root}")
    return 0


def _block_run(status: RunStatus, reason: str, run) -> int:
    """Transition the run to BLOCKED with a recorded reason and report it."""
    status.transition("BLOCKED")
    status.blocked_reason = reason
    status.save(run.status_path)
    print(f"error: run blocked: {reason}", file=sys.stderr)
    return 1


def cmd_prepare(args: argparse.Namespace, *, material_roots: dict | None = None) -> int:
    """P0–P1: safety baseline, workspace provisioning, environment collection.

    On success the run transitions INITIALIZING -> READY (checkpoint
    p1).  On failure it transitions to BLOCKED with a recorded reason;
    a BLOCKED run may be re-prepared after the cause is fixed
    (harness §5.1 retry policy).  The reference repository is only
    ever opened read-only.
    """
    runs_root = resolve_runs_root(args.runs_root)
    reference = Path(args.reference) if args.reference else default_reference_root()
    run = load_run_dir(args.run_id, runs_root)
    log = harness_log(run.root)
    manifest = RunManifest.load(run.manifest_path)
    status = RunStatus.load(run.status_path)

    if status.status == "READY":
        print(f"error: run {args.run_id} is already prepared (status READY)", file=sys.stderr)
        return 1
    if status.status not in ("INITIALIZING", "BLOCKED"):
        print(
            f"error: cannot prepare run {args.run_id} in status {status.status}",
            file=sys.stderr,
        )
        return 1
    if not reference.is_dir():
        print(f"error: reference path is not a directory: {reference}", file=sys.stderr)
        return 1
    if not (reference / ".git").is_dir():
        print(f"error: reference path is not a git repository: {reference}", file=sys.stderr)
        return 1

    baseline_path = run.baseline / "safety-baseline.json"
    if status.status == "INITIALIZING" and baseline_path.exists():
        print(
            f"error: {baseline_path} already exists; refusing to overwrite a "
            "partially prepared run. Remove the file to re-prepare.",
            file=sys.stderr,
        )
        return 1

    started_at = format_iso(utc_now())
    log.info(f"preparing run {run.run_id} (reference: {reference})")

    # 1. Safety baseline (§12.2)
    baseline = capture_baseline(reference)
    save_baseline(baseline, baseline_path)
    log.info(f"safety baseline recorded: {baseline['head']}")

    # 2. Workspace provisioning (§9.3)
    result = provision(reference, run, manifest, material_roots=material_roots)
    verb = "skipped" if result.skipped else "copied"
    log.info(
        f"workspace provisioned ({verb}): {result.files_copied} files at "
        f"{result.reference_head}"
    )

    # 3. Environment collection (§4.4–4.5)
    policy = network_policy()
    environment = collect_environment(reference, network=policy)
    save_environment(environment, run.protocol / "environment.json")
    manifest.update(
        network=policy,
        versions={**manifest.versions, "reference_head": result.reference_head},
    )
    manifest.save(run.manifest_path)
    log.info(f"environment recorded (network: {policy})")

    # 4. Workspace write-permission verification (§4.4 item 4)
    probe = run.workspace_work / ".harness-write-probe"
    try:
        probe.write_text("probe", encoding="utf-8")
        probe.unlink()
    except OSError as exc:
        return _block_run(status, f"workspace work/ is not writable: {exc}", run)
    log.info("workspace work/ write-permission verified")

    # 5. G0 safety re-verification (§12.3) — proves provisioning never
    #    modified the reference repository.
    verification = verify_baseline(reference, baseline)
    if not verification.passed:
        reason = verification.describe()
        log.error(reason)
        return _block_run(status, reason, run)
    log.info("G0 safety verification passed")

    # 6. Tool gates (§4.2): missing required tool always blocks; a
    #    present-but-wrong-version tool blocks only per config.
    missing = missing_tools(environment)
    mismatch_block = block_on_tool_version_mismatch()
    mismatched = mismatched_tools(environment)
    if missing:
        return _block_run(
            status, f"missing required tools: {', '.join(missing)}", run
        )
    if mismatched and mismatch_block:
        return _block_run(
            status,
            f"tool version below §4.2 minimum (blocked by config): "
            f"{', '.join(mismatched)}",
            run,
        )

    # Success: complete P0 and P1 in the manifest and move to READY.
    ended_at = format_iso(utc_now())
    manifest.end_phase("p0", at=ended_at)
    manifest.start_phase("p1", at=started_at)
    manifest.end_phase("p1", at=ended_at)
    manifest.save(run.manifest_path)
    status.transition("READY", checkpoint="p1", at=ended_at)
    status.save(run.status_path)

    log.info(f"run {run.run_id} is READY")
    print(f"Run prepared: {run.run_id}")
    print(f"Reference HEAD: {baseline['head']}")
    print(f"Workspace files: {result.files_copied}")
    print(f"Status: READY")
    return 0


def cmd_prompt(args: argparse.Namespace) -> int:
    """P2: build, hash, and persist the prompt bundle for a run.

    Loads the skill package, resolves the run's benchmark task to its
    skill task, materializes the benchmark prompt from the pinned
    corpus/evaluation documents, assembles the bundle, and records the
    bundle hash plus prompt metadata in the manifest.  A run is
    prompted at most once; the bundle is immutable afterwards.
    """
    runs_root = resolve_runs_root(args.runs_root)
    skill_root = Path(args.skill) if args.skill else default_skill_root()
    run = load_run_dir(args.run_id, runs_root)
    log = harness_log(run.root)
    manifest = RunManifest.load(run.manifest_path)
    status = RunStatus.load(run.status_path)

    if status.status not in ("INITIALIZING", "READY"):
        print(
            f"error: cannot generate prompt for run {args.run_id} in status "
            f"{status.status}",
            file=sys.stderr,
        )
        return 1
    if manifest.prompt_bundle_sha256 is not None:
        print(
            f"error: run {args.run_id} already has a prompt bundle "
            f"({manifest.prompt_bundle_sha256}); bundles are immutable.",
            file=sys.stderr,
        )
        return 1

    started_at = format_iso(utc_now())
    log.info(f"generating prompt bundle for run {run.run_id}")

    package = load_skill(skill_root)
    # resolve_benchmark_task returns the raw task entry; resolve the
    # artifact bundle from the index task ID.
    skill_task_id = _skill_task_id(package, manifest.task["id"])
    artifacts = package.collect_artifacts(skill_task_id)
    log.info(
        f"resolved skill task {skill_task_id} for benchmark task {manifest.task['id']}"
    )

    docs = default_evaluation_docs()
    corpus_task = parse_corpus_task(docs["corpus"], manifest.task["id"])
    eval_task = parse_eval_task(docs["evaluation"], manifest.task["id"])
    attached = default_attached_documents(
        eval_task, skill_rule_files=primary_rule_files(eval_task["primary_rules"])
    )
    system_prompt = default_system_prompt_path().read_text(encoding="utf-8")
    benchmark_prompt = materialize_benchmark_prompt(
        corpus_task,
        eval_task,
        safety_section=extract_safety_section(system_prompt),
        attached_documents=attached,
    )
    bundle = build_prompt_bundle(
        system_prompt=system_prompt,
        benchmark_prompt=benchmark_prompt,
        package=package,
        artifacts=artifacts,
    )
    bundle_hash = write_bundle(run, bundle, system_prompt=system_prompt)
    save_bundle_metadata_json(run, bundle_artifact_json(package, artifacts))
    log.info(f"prompt bundle written and hashed: {bundle_hash}")

    hashes = artifact_hashes(package, artifacts)
    metadata = bundle_metadata(
        skill_task_id=skill_task_id,
        package=package,
        artifacts=artifacts,
        bundle_sha256=bundle_hash,
        attached_documents=attached,
        hashes=hashes,
    )
    ended_at = format_iso(utc_now())
    manifest.update(
        prompt_bundle_sha256=bundle_hash,
        task={"id": corpus_task["id"], "difficulty": corpus_task["difficulty"]},
        prompt=metadata,
    )
    manifest.start_phase("p2", at=started_at)
    manifest.end_phase("p2", at=ended_at)
    manifest.save(run.manifest_path)

    log.info(f"run {run.run_id} prompt bundle complete")
    print(f"Prompt bundle generated: {run.run_id}")
    print(f"Skill task: {skill_task_id}")
    print(f"Bundle SHA-256: {bundle_hash}")
    print(f"Bundle file: {run.root / 'protocol' / 'prompt-bundle.txt'}")
    return 0


def _skill_task_id(package, benchmark_task_id: str) -> str:
    """Resolve a benchmark task to its skill index task ID."""
    from tooling.harness.skill.loader import (
        BENCHMARK_TASK_TO_SKILL_TASK,
    )

    if benchmark_task_id in package.tasks:
        return benchmark_task_id
    skill_task_id = BENCHMARK_TASK_TO_SKILL_TASK.get(benchmark_task_id)
    if skill_task_id is None:
        from tooling.harness.skill.loader import TaskNotFoundError

        raise TaskNotFoundError(
            f"no skill task associated with benchmark task {benchmark_task_id!r}"
        )
    return skill_task_id


def cmd_session(args: argparse.Namespace) -> int:
    """P2–P3: launch, supervise, and capture one agent session.

    ``--dry-run`` verifies workspace boundaries and prints the exact
    prompt bundle hash without starting a session.  Otherwise the
    session executes with the §5.2 P3 budget (or ``--timeout-min``),
    raw artifacts are captured, the submission is intaken (§3.6), and
    the run status moves RUNNING -> COMPLETED / TIMEOUT / ABORTED /
    BLOCKED.  A run left RUNNING by an interruption resumes P3
    (§5.3) with a recorded restart.
    """
    runs_root = resolve_runs_root(args.runs_root)
    run = load_run_dir(args.run_id, runs_root)
    log = harness_log(run.root)
    manifest = RunManifest.load(run.manifest_path)
    status = RunStatus.load(run.status_path)

    if status.status not in ("READY", "RUNNING"):
        print(
            f"error: cannot run a session for {args.run_id} in status "
            f"{status.status} (expected READY or RUNNING for a resume)",
            file=sys.stderr,
        )
        return 1

    if args.dry_run:
        failures = verify_boundaries(run)
        bundle_hash = manifest.prompt_bundle_sha256
        if failures:
            for failure in failures:
                print(f"error: {failure}", file=sys.stderr)
            return 1
        if not bundle_hash:
            print("error: run has no prompt bundle; run 'prompt' first", file=sys.stderr)
            return 1
        print(f"Session dry run: workspace boundaries OK")
        print(f"Prompt bundle SHA-256: {bundle_hash}")
        print(f"Platform: {args.platform or execution_platform()}")
        return 0

    if not manifest.prompt_bundle_sha256:
        print(
            f"error: run {args.run_id} has no prompt bundle; run 'prompt' first",
            file=sys.stderr,
        )
        return 1

    platform = args.platform or execution_platform()
    model_override = launch_model()
    settings = load_settings()

    # §5.2 P3 budget: effort formula, or the operator override.
    if args.timeout_min is not None:
        timeout_seconds = p3_budget_seconds("", override_minutes=args.timeout_min)
    else:
        docs = default_evaluation_docs()
        try:
            effort = parse_corpus_task(docs["corpus"], manifest.task["id"])["effort"]
            timeout_seconds = p3_budget_seconds(effort)
        except Exception:
            log.warning("cannot derive effort from corpus; using minimum P3 budget")
            timeout_seconds = 2 * 60 * 60
    log.info(f"P3 time budget: {timeout_seconds}s")

    adapter = create_adapter(platform)
    command_log = CommandLog(run.root / "protocol" / "command.log")
    try:
        outcome = run_session(
            run,
            manifest,
            status,
            adapter=adapter,
            timeout_seconds=timeout_seconds,
            log=log,
            command_log=command_log,
            launch_model=model_override,
            docs_paths=default_evaluation_docs(),
        )
    except SessionRuntimeError as exc:
        print(f"error: {exc}", file=sys.stderr)
        return 1
    except (AdapterError, OSError) as exc:
        print(f"error: {exc}", file=sys.stderr)
        return 1

    print(f"Session finished: {run.run_id}")
    print(f"Run status: {outcome['status']}")
    print(f"Exit status: {outcome['exit_status']}")
    if outcome.get("reason"):
        print(f"Reason: {outcome['reason']}")
    print(f"Submission: {outcome['intake']['status']} "
          f"(missing: {', '.join(outcome['intake']['missing']) or 'none'})")
    print(f"Session metadata: {run.root / 'protocol' / 'session' / 'session.json'}")
    return 0


def cmd_collect(args: argparse.Namespace) -> int:
    """P4: collect (and optionally freeze) the run's evidence set.

    Collection copies E1–E12 into ``evidence/`` and writes the
    ``evidence.json`` catalog with per-artifact size and SHA-256.
    ``--freeze`` completes P4: hash-verifies, makes the evidence tree
    read-only at the filesystem level, records the frozen root hash in
    the manifest, and freezes the manifest itself (§6.3).
    """
    runs_root = resolve_runs_root(args.runs_root)
    run = load_run_dir(args.run_id, runs_root)
    log = harness_log(run.root)
    manifest = RunManifest.load(run.manifest_path)
    status = RunStatus.load(run.status_path)

    if status.status not in ("COMPLETED", "TIMEOUT", "ABORTED"):
        print(
            f"error: cannot collect evidence for {args.run_id} in status "
            f"{status.status} (P3 must have ended)",
            file=sys.stderr,
        )
        return 1
    if manifest.frozen:
        print(
            f"error: run {args.run_id} manifest is frozen at P4; evidence "
            "cannot be re-collected",
            file=sys.stderr,
        )
        return 1
    if load_evidence_catalog(run).get("frozen") if (run.root / "evidence" / "evidence.json").is_file() else False:
        print(f"error: run {args.run_id} evidence is already frozen", file=sys.stderr)
        return 1

    started_at = format_iso(utc_now())
    if "p4" not in manifest.phases or manifest.phases["p4"].started_at is None:
        manifest.start_phase("p4", at=started_at)

    log.info(f"collecting evidence for run {run.run_id}")
    catalog = collect_evidence(run, manifest, status)
    from tooling.harness.evidence.freeze import record_collected_evidence

    record_collected_evidence(run, manifest, catalog, frozen=False, frozen_at=None)
    manifest.save(run.manifest_path)
    status.record_checkpoint("p4", at=format_iso(utc_now()))
    status.save(run.status_path)

    present = [t for t, e in catalog["types"].items() if e["status"] == "present"]
    absent = [t for t, e in catalog["types"].items() if e["status"] == "absent"]
    print(f"Evidence collected: {run.run_id}")
    print(f"Present: {', '.join(present)}")
    print(f"Absent (recorded): {', '.join(absent)}")

    if args.freeze:
        result = freeze_evidence(run, manifest)
        manifest.save(run.manifest_path)
        log.info(f"evidence frozen: root hash {result['root_hash']}")
        print(f"Evidence frozen: {run.run_id}")
        print(f"Root hash: {result['root_hash']}")
        print(f"Artifact count: {result['artifact_count']}")
    print(f"Evidence catalog: {run.root / 'evidence' / 'evidence.json'}")
    return 0


def cmd_gates(args: argparse.Namespace) -> int:
    """P5: run the automatic validation gates G0-G2 against frozen evidence.

    Requires a frozen run (``collect --freeze`` at P4).  On completion
    ``validation/validation.json`` and ``validation/raw/<check-id>.txt``
    are written, the validation artifacts are appended to
    ``evidence/reruns/e4/`` (§6.3), and the run is marked ``REJECTED``
    when a blocking check fails.  Exit code 0 when validation ran to
    completion (PASS or REJECTED); exit 1 when a gate was BLOCKED
    (validation incomplete; fix the environment and re-run) or the
    preconditions are not met.
    """
    runs_root = resolve_runs_root(args.runs_root)
    reference = Path(args.reference) if args.reference else default_reference_root()
    run = load_run_dir(args.run_id, runs_root)
    log = harness_log(run.root)
    manifest = RunManifest.load(run.manifest_path)
    status = RunStatus.load(run.status_path)

    if status.status not in ("COMPLETED", "TIMEOUT"):
        print(
            f"error: cannot run gates for {args.run_id} in status "
            f"{status.status} (P3 must have ended; a REJECTED run is terminal)",
            file=sys.stderr,
        )
        return 1

    from tooling.harness.validation.gates import ValidationError, run_gates

    command_log = CommandLog(run.root / "protocol" / "command.log")
    try:
        outcome = run_gates(
            run,
            manifest,
            status,
            reference_root=reference,
            command_log=command_log,
            log=log,
        )
    except ValidationError as exc:
        print(f"error: {exc}", file=sys.stderr)
        return 1
    except (BaselineError, ValueError, OSError, FileNotFoundError) as exc:
        print(f"error: {exc}", file=sys.stderr)
        return 1

    summary = outcome["summary"]
    for gate_id in ("G0", "G1", "G2"):
        gate = outcome["gates"][gate_id]
        print(
            f"{gate_id} ({gate['name']}): {gate['verdict']} "
            f"({len(gate['checks'])} checks)"
        )
    if summary["verdict"] == "REJECTED":
        print(
            "Summary: REJECTED (blocking failures: "
            f"{', '.join(summary['blocking_failures'])})"
        )
    elif summary["verdict"] == "BLOCKED":
        print(
            "Summary: BLOCKED (blocked checks: "
            f"{', '.join(summary['blocked_checks'])}); validation incomplete"
        )
    else:
        print(
            f"Summary: PASS ({summary['executed_check_count']} checks executed, "
            "0 blocking failures)"
        )
        if summary["non_blocking_findings"]:
            print(
                "Non-blocking findings: "
                + ", ".join(summary["non_blocking_findings"])
            )
    for substitution in summary["substitutions"]:
        print(
            f"Substitution: {substitution['check']}: "
            f"{substitution['reason']}"
        )
    print(f"Validation: {outcome['validation_path']}")
    print(f"Run status: {RunStatus.load(run.status_path).status}")
    return outcome["exit_code"]


def cmd_review(args: argparse.Namespace) -> int:
    """P6: assemble the manual review package (--scaffold) or print the
    current review state (resume view)."""
    runs_root = resolve_runs_root(args.runs_root)
    run = load_run_dir(args.run_id, runs_root)
    log = harness_log(run.root)
    manifest = RunManifest.load(run.manifest_path)
    status = RunStatus.load(run.status_path)

    from tooling.harness.review.state import ReviewState, review_json_path

    if args.scaffold:
        from tooling.harness.review.kit import ReviewKitError, scaffold_review

        try:
            outcome = scaffold_review(run, manifest, status)
        except ReviewKitError as exc:
            print(f"error: {exc}", file=sys.stderr)
            return 1
        log.info(f"review package assembled for {run.run_id}")
        print(f"Review package assembled: {run.run_id}")
        print(f"Rubric: family {outcome['rubric']['family']} "
              f"(weights sum {outcome['rubric']['weights_sum']})")
        for name in outcome["files"]:
            print(f"  review/{name}")
        return 0

    review_path = review_json_path(run)
    if not review_path.is_file():
        print(
            f"error: no review package for {run.run_id}; run "
            "'review --scaffold' first",
            file=sys.stderr,
        )
        return 1
    state = ReviewState.load(review_path)
    print(f"Review state: {run.run_id}")
    print(f"  status: {state.status}")
    print(f"  review version: {state.review_version}")
    print(f"  rubric: family {state.rubric['family']} (weights "
          f"{state.rubric['weights']})" if state.rubric else "  rubric: unset")
    print(f"  reviewer: {state.reviewer or 'not recorded'}")
    if state.status == "COMPLETED":
        print(f"  verdict: {state.verdict} (total {state.total})")
        print(f"  category scores: {state.category_scores}")
        print(f"  critical failures: {state.critical_failures}")
        print(f"  artifact hashes: {len(state.artifact_hashes)} pinned")
    else:
        from tooling.harness.review.parser import parse_category_table

        md_path = run.review / "manual-review.md"
        records = parse_category_table(md_path.read_text(encoding="utf-8")) if md_path.is_file() else []
        unscored = [r.category for r in records if r.score is None]
        uncited = [r.category for r in records if not r.evidence]
        print(f"  categories missing scores: {', '.join(unscored) or 'none'}")
        print(f"  categories missing citations: {', '.join(uncited) or 'none'}")
        print(f"  working file: {md_path}")
        print("  resume: complete manual-review.md, then run 'score'")
    return 0


def cmd_score(args: argparse.Namespace) -> int:
    """P7: validate, compute, and record the review's category scores."""
    runs_root = resolve_runs_root(args.runs_root)
    run = load_run_dir(args.run_id, runs_root)
    log = harness_log(run.root)
    manifest = RunManifest.load(run.manifest_path)
    status = RunStatus.load(run.status_path)

    from tooling.harness.scoring.runner import ScoringError, run_scoring

    scores_json = None
    if args.scores is not None:
        try:
            scores_json = json.loads(args.scores)
        except json.JSONDecodeError as exc:
            print(f"error: --scores is not valid JSON: {exc}", file=sys.stderr)
            return 1
        if not isinstance(scores_json, dict):
            print("error: --scores must be a JSON object", file=sys.stderr)
            return 1

    try:
        outcome = run_scoring(
            run,
            manifest,
            status,
            scores_json=scores_json,
            reviewer=args.reviewer,
            recompute=args.recompute,
        )
    except ScoringError as exc:
        print(f"error: {exc}", file=sys.stderr)
        return 1

    scores = outcome["scores"]
    print(f"Scores recorded: {run.run_id}")
    print(f"  rubric: family {scores['rubric']['family']} "
          f"(weights sum {scores['rubric']['weights_sum']})")
    for category, record in scores["category_scores"].items():
        print(f"  {category}: {record['score']}")
    print(f"  total: {scores['arithmetic']['total']} "
          f"(capped {scores['arithmetic']['capped_total']})")
    if scores["arithmetic"]["cap_reasons"]:
        print(f"  caps: {scores['arithmetic']['cap_reasons']}")
    print(f"  critical failures: {scores['critical_failures']}")
    print(f"  verdict: {scores['verdict']}")
    print(f"  scores: {run.root / 'review' / 'scoring' / 'scores.json'}")
    print(f"  verification: {run.root / 'review' / 'scoring' / 'score-verification.json'}")
    print(f"  run status: {RunStatus.load(run.status_path).status}")
    return 0


def cmd_report(args: argparse.Namespace) -> int:
    """P8: generate and validate the canonical reports from recorded data."""
    runs_root = resolve_runs_root(args.runs_root)
    run = load_run_dir(args.run_id, runs_root)
    log = harness_log(run.root)
    manifest = RunManifest.load(run.manifest_path)
    status = RunStatus.load(run.status_path)

    from tooling.harness.report.generator import ReportError, generate_reports

    try:
        outcome = generate_reports(run, manifest, status)
    except ReportError as exc:
        print(f"error: {exc}", file=sys.stderr)
        return 1
    log.info(f"reports generated for {run.run_id}")
    print(f"Reports generated: {run.run_id}")
    print(f"  evaluation-report.json: {outcome['json_path']} "
          f"(sha256 {outcome['json_sha256']})")
    print(f"  report.md: {outcome['md_path']} (sha256 {outcome['md_sha256']})")
    print(f"  sections: {len(outcome['sections'])} of §8.2 present")
    return 0


def cmd_archive(args: argparse.Namespace) -> int:
    """P9: archive a completed run or verify an archived run.

    Archiving runs the §13 final verification (MVB-024) first: the four
    items are recorded and any failure blocks ``ARCHIVED``, leaving the
    run in its pre-archive status.  ``--verify`` re-checks an archived
    run (marker, registry, leaderboard, final-verification record,
    evidence hashes, reports, packaged contents).
    """
    runs_root = resolve_runs_root(args.runs_root)
    reference = Path(args.reference) if args.reference else default_reference_root()
    run = load_run_dir(args.run_id, runs_root)
    log = harness_log(run.root)
    manifest = RunManifest.load(run.manifest_path)
    status = RunStatus.load(run.status_path)

    from tooling.harness.archive.manager import ArchiveError, archive_run, verify_archive

    if args.verify:
        result = verify_archive(run, manifest, status, runs_root=runs_root)
        if result["passed"]:
            print(f"Archive verified: {run.run_id}")
            print("  marker, registry entry, leaderboard entry, final-"
                  "verification record, evidence hashes, reports, and packaged "
                  "contents all pass")
            return 0
        print(f"Archive verification FAILED: {run.run_id}", file=sys.stderr)
        for divergence in result["divergences"]:
            print(f"  - {divergence}", file=sys.stderr)
        return 1

    try:
        outcome = archive_run(
            run, manifest, status, runs_root=runs_root, reference_root=reference
        )
    except ArchiveError as exc:
        print(f"error: {exc}", file=sys.stderr)
        return 1
    log.info(f"run {run.run_id} archived")
    print(f"Run archived: {run.run_id}")
    print(f"  marker: {outcome['marker_path']}")
    print(f"  registry: {runs_root / 'index.json'} "
          f"(verdict {outcome['registry_entry'].get('verdict')})")
    if outcome["leaderboard_entry"]:
        from tooling.harness.archive.manager import leaderboard_path

        entry = outcome["leaderboard_entry"]
        print(f"  leaderboard: {leaderboard_path(runs_root, entry['version_tuple'])}")
    else:
        print("  leaderboard: none (REJECTED runs are not leaderboard entries)")
    print(f"  run status: {RunStatus.load(run.status_path).status}")
    return 0


def cmd_synthetic(args: argparse.Namespace) -> int:
    """MS-09: execute the full pipeline deterministically (no LLM).

    Runs P0-P9 against a deterministic fixture submission with the stub
    adapter, a scratch reference repository, and pinned clocks; verifies
    every stage's artifact; and records the ``pilot-0`` validation-dataset
    seed for the passing variant.
    """
    from tooling.harness.synthetic.runner import SyntheticError, run_synthetic

    runs_root = resolve_runs_root(args.runs_root)
    try:
        outcome = run_synthetic(
            task=args.task,
            runs_root=runs_root,
            variant=args.variant,
            dataset_path=args.dataset,
        )
    except SyntheticError as exc:
        print(f"error: {exc}", file=sys.stderr)
        return 1

    print(f"Synthetic benchmark complete: {outcome['run_id']}")
    print(f"  variant: {outcome['variant']}")
    print(f"  status: {outcome['status']}")
    print(f"  runs root: {outcome['runs_root']}")
    print(f"  reference repo: {outcome['reference']}")
    print(f"  verification: {len(outcome['verification']['divergences'])} divergences")
    if outcome.get("seed"):
        print(f"  pilot-0 seed: {outcome['seed']}")
    print("  artifact inventory:")
    for relpath in _artifact_inventory(outcome["run_root"]):
        print(f"    {relpath}")
    return 0


def _artifact_inventory(run_root: str) -> list[str]:
    from tooling.harness.archive.packaging import EXPECTED_TOP_LEVEL

    root = Path(run_root)
    inventory: list[str] = []
    for name in EXPECTED_TOP_LEVEL:
        path = root / name
        if path.is_file():
            inventory.append(f"{name}")
        elif path.is_dir():
            for child in sorted(path.rglob("*")):
                if child.is_file():
                    inventory.append(f"{child.relative_to(root)}")
    return inventory


def main(argv: list[str] | None = None) -> int:
    parser = build_parser()
    args = parser.parse_args(argv)
    if args.command == "init":
        try:
            return cmd_init(args)
        except (ValueError, FileExistsError, OSError, RuntimeError) as exc:
            print(f"error: {exc}", file=sys.stderr)
            return 1
    if args.command == "prepare":
        try:
            return cmd_prepare(args)
        except (ValueError, FileExistsError, OSError, RuntimeError,
                BaselineError, ProvisionError, FileNotFoundError) as exc:
            print(f"error: {exc}", file=sys.stderr)
            return 1
    if args.command == "prompt":
        try:
            return cmd_prompt(args)
        except (ValueError, OSError, RuntimeError, FileNotFoundError,
                SkillError, BundleError, TaskSectionError) as exc:
            print(f"error: {exc}", file=sys.stderr)
            return 1
    if args.command == "session":
        try:
            return cmd_session(args)
        except (ValueError, OSError, RuntimeError, FileNotFoundError,
                AdapterError, SessionRuntimeError) as exc:
            print(f"error: {exc}", file=sys.stderr)
            return 1
    if args.command == "collect":
        try:
            return cmd_collect(args)
        except (ValueError, OSError, RuntimeError, FileNotFoundError,
                EvidenceError, FreezeError) as exc:
            print(f"error: {exc}", file=sys.stderr)
            return 1
    if args.command == "gates":
        return cmd_gates(args)
    if args.command == "review":
        return cmd_review(args)
    if args.command == "score":
        return cmd_score(args)
    if args.command == "report":
        return cmd_report(args)
    if args.command == "archive":
        return cmd_archive(args)
    if args.command == "synthetic":
        return cmd_synthetic(args)
    parser.error(f"unknown command: {args.command}")
    return 2


if __name__ == "__main__":
    sys.exit(main())
