# BGA Senior Engineer — Minimum Viable Benchmark Implementation Backlog

**Repository:** `bga-senior-engineer`
**Source Codebase:** `bga-mercurio` (strictly read-only reference)
**Companion Document:** `docs/evaluation/implementation-plan.md` (v1.0)
**Version:** 1.0
**Date:** 2026-07-31
**Status:** Canonical — work items for the Minimum Viable Benchmark (MVB)

---

## Table of Contents

- [Backlog Conventions](#backlog-conventions)
- [Phase 0 — Foundations](#phase-0--foundations)
- [Phase 1 — Static Setup (P0–P1)](#phase-1--static-setup-p0p1)
- [Phase 2 — Agent Session (P2–P3)](#phase-2--agent-session-p2p3)
- [Phase 3 — Evidence (P4)](#phase-3--evidence-p4)
- [Phase 4 — Automatic Validation (P5)](#phase-4--automatic-validation-p5)
- [Phase 5 — Manual Evaluation and Scoring (P6–P7)](#phase-5--manual-evaluation-and-scoring-p6p7)
- [Phase 6 — Report and Archive (P8–P9)](#phase-6--report-and-archive-p8p9)
- [Phase 7 — Integration and Acceptance](#phase-7--integration-and-acceptance)
- [Deferred (Post-MVB)](#deferred-post-mvb)
- [Repository Safety Protocol](#repository-safety-protocol)
- [Final Verification](#final-verification)

---

## Backlog Conventions

- **ID** — `MVB-NNN`; sequential within the backlog.
- **Effort** — estimated engineer-hours.
- **Dependencies** — IDs that must be complete before this item starts.
- **Acceptance criteria** — objective, testable conditions. Every deliverable must be demonstrable by an engineer other than its author.
- **Priority** — `P0` (required for the first successful benchmark run), `P1` (required for MVB completeness), `P2` (post-MVB, listed in Deferred).
- **Phases 0–7** are MVB scope and may be executed in parallel where dependencies permit (notably: MVB-004/005/006 are independent of MVB-008/009).
- All code is stdlib-only Python 3.10+, follows the `tooling/_shared` pattern, and lives under `tooling/harness/` (new module) unless stated otherwise. The MVB introduces no specification changes; ambiguity findings are logged (implementation plan E8).

---

## Phase 0 — Foundations

### MVB-001 — Run Directory Layout and Run ID

**Title:** Implement harness run directory layout and run ID scheme

**Description:** Implement the run directory structure from harness spec §9.1 and the run ID format from §9.2 (`run-{task}-{model-slug}-{YYYYMMDDTHHMMSSZ}-{seq}`). Create the directory skeleton with all subdirectories (`protocol/`, `protocol/baseline/`, `workspace/read/`, `workspace/work/`, `evidence/`, `validation/`, `validation/raw/`, `review/`, `review/scoring/`, `reports/`). The `runs/` root MUST live outside both repositories (harness §4.1); its path is configurable via a single settings file.

**Dependencies:** None

**Effort:** 4h

**Deliverable:** `tooling/harness/runtime/run_dir.py` — `create_run_dir(task_id, model_slug, runs_root) -> RunDir` (dataclass of paths); settings module `tooling/harness/config.py`.

**Acceptance criteria:**
- Creating a run produces the full skeleton; every path exists; `workspace/work/` is writable, all other dirs created empty
- Run ID matches the §9.2 pattern; `seq` disambiguates same-second runs (seeded counter, no wall-clock race)
- Runs root defaults to `<parent-of-both-repos>/runs/` and is overridable by config; unit test asserts the skeleton
- Path lengths are bounded (≤ 120 chars for any generated path)

### MVB-002 — Manifest and Status Schemas

**Title:** Implement `manifest.json` and `status.json` schemas with validation

**Description:** Implement the run manifest schema (harness §Appendix A) and status schema (§2.0.2, §9.1) as Python dataclasses with JSON round-trip validation. Manifest supports checkpoint extension (phase timestamps) and P4 freezing (post-freeze writes rejected except errata pointer). Status transitions are constrained to the §2.0.2 status graph.

**Dependencies:** MVB-001

**Effort:** 6h

**Deliverable:** `tooling/harness/runtime/manifest.py`, `tooling/harness/runtime/status.py` + tests.

**Acceptance criteria:**
- A manifest round-trips JSON losslessly; every field of the Appendix A example is covered
- Status transitions outside the graph raise; each phase records `started_at`/`ended_at` ISO-8601 UTC
- After freeze, manifest mutation raises except for documented errata; test covers both
- Tests use only stdlib + existing `tooling/_shared`

### MVB-003 — Harness Utility Layer

**Title:** Shared harness utilities (hashing, logging, time, process runner)

**Description:** Implement the harness utility layer: SHA-256 hashing helper, deterministic logging (to run `protocol/` and stdout), ISO-8601 UTC clock, and the command wrapper that captures command, stdout, stderr, exit code, and wall time per harness §6.2 (E3).

**Dependencies:** MVB-001

**Effort:** 6h

**Deliverable:** `tooling/harness/util/` (hash.py, log.py, clock.py, proc.py) + tests.

**Acceptance criteria:**
- `run_cmd(cmd)` returns (exit_code, stdout, stderr, wall_time) and writes a log record; tested with a failing command (nonzero exit) and empty-output command
- Hash helper is verified against a known SHA-256 vector
- All timestamps in logs are ISO-8601 UTC with `Z`
- No third-party imports beyond stdlib

---

## Phase 1 — Static Setup (P0–P1)

### MVB-004 — Safety Baseline Capture and G0 Comparison

**Title:** Implement `bga-mercurio` safety baseline and verification

**Description:** Implement harness §12.2 baseline capture (`git rev-parse HEAD`, `git status --porcelain`, `git reflog -1` recorded in `safety-baseline.json`) and §12.3 verification (G0) that compares the three values and reports the exact difference on mismatch. Baseline is captured at P0 and re-verified at every checkpoint, at P5 (G0 gate) and at P9 (final verification, §13).

**Dependencies:** MVB-003

**Effort:** 4h

**Deliverable:** `tooling/harness/safety/baseline.py` + tests (tests use a scratch git repo fixture, never the reference repo).

**Acceptance criteria:**
- Baseline JSON matches §12.2 schema exactly
- Verification passes when state is unchanged; fails with a precise diff (which of the three checks diverged, and the differing value) when HEAD/status/reflog change
- `G0` result is written into `validation.json` at P5; final-verification record written at P9
- Test fixture: a temporary git repo with a commit, then a second commit, demonstrates detection

### MVB-005 — Workspace Manager

**Title:** Implement run workspace provisioning and baseline diff

**Description:** Implement harness §9.3: create run directory; copy reference material into `workspace/read/` (contents of `bga-mercurio` at pinned HEAD, `docs/`, `bga-senior-engineer-skill/`, `tooling/`, `official-docs/`, `reference-projects/`); create empty `workspace/work/`; write `workspace-baseline.diff` (empty expected); record reference HEAD in the manifest.

**Dependencies:** MVB-001, MVB-004

**Effort:** 6h

**Deliverable:** `tooling/harness/workspace/provision.py` + tests.

**Acceptance criteria:**
- `workspace/read/` is read-only (permission check test); `workspace/work/` is writable
- `workspace-baseline.diff` exists and is empty; file count of `read/bga-mercurio` matches the source checkout
- Provisioning never opens `bga-mercurio` for write; the safety baseline still matches after provisioning
- Provisioning is idempotent-safe (refuses to overwrite an existing run directory)

### MVB-006 — Environment Collector

**Title:** Implement P1 environment checks and `environment.json`

**Description:** Implement harness §4.4–4.5: check tool presence and versions (`python3 --version`, `php -v`, `node -v`, `git --version`), validator version output (`python -m tooling.validator --report human`), reference HEAD and status, OS/architecture, network policy (default `disabled`), and write `protocol/environment.json`. Missing required tools mark the run `BLOCKED` (§2.0.2).

**Dependencies:** MVB-001, MVB-004

**Effort:** 4h

**Deliverable:** `tooling/harness/environment/collect.py` + tests.

**Acceptance criteria:**
- `environment.json` contains every §4.5 field; values are captured, not hardcoded
- Missing tool → run status `BLOCKED` with a recorded reason; present-but-wrong-version tool → recorded mismatch, run proceeds flagged or blocked per config
- Network policy is read from config and recorded; default `disabled`
- Test verifies schema completeness against the §4.5 field list

### MVB-007 — Task Configuration for NOT-02

**Title:** Curated task config for NOT-02, validated against the documents

**Description:** Produce the per-task config JSON for NOT-02: ID, title, category, difficulty/effort, weight family (NOTIF: 40/10/25/15/10), success criteria, required evidence, automatic checks (from evaluation spec §3.11 and §4), G3 scenario script outline, attached-document list. Every field cites its source section ID. Add a cross-check script validating config values against the corpus and evaluation documents (task existence, weight sums, check IDs).

**Dependencies:** None (docs are frozen inputs)

**Effort:** 4h

**Deliverable:** `tooling/harness/tasks/not-02.json` + `tooling/harness/tasks/check_config.py`.

**Acceptance criteria:**
- Every config field has a `source` reference to `benchmark-task-corpus.md` / `benchmark-evaluation-spec.md` section
- Weights sum to 100; check IDs referenced exist in evaluation spec §4; criterion count matches §3.11
- Cross-check script passes on the committed config; fails if the config is edited to a non-matching weight
- Config loads via the harness config loader (MVB-001)

---

## Phase 2 — Agent Session (P2–P3)

### MVB-008 — System Prompt Asset

**Title:** Author the system prompt per harness §3.1.1

**Description:** Write the fixed system prompt text containing: agent role (BGA Senior Engineer performing a benchmark task), repository safety protocol verbatim (§12), writable/read-only boundary, evidence requirements summary, evaluation-awareness statement, and determinism policy. Archive as `tooling/harness/prompts/system-prompt.txt`.

**Dependencies:** None

**Effort:** 2h

**Deliverable:** `tooling/harness/prompts/system-prompt.txt`.

**Acceptance criteria:**
- Contains all six required elements from §3.1.1, the safety protocol text verbatim, and the §12 prohibited/permitted lists
- File is plain text, no placeholders; review by the benchmark maintainer recorded

### MVB-009 — Prompt Bundle Generator

**Title:** Implement prompt bundle assembly and hashing

**Description:** Implement harness §3.2 and Appendix B: assemble `prompt-bundle.txt` from system prompt + benchmark prompt (task config sections: ID/title/category/difficulty/effort, background/objective, expected outcomes, success criteria, required evidence, boundary, submission instruction) + attached-document list; write `protocol/prompt-bundle.txt` and its SHA-256; record the hash in the manifest. Assembly MUST be deterministic (same inputs → identical bytes).

**Dependencies:** MVB-002, MVB-003, MVB-007, MVB-008

**Effort:** 6h

**Deliverable:** `tooling/harness/prompt/bundle.py` + tests.

**Acceptance criteria:**
- Two runs with identical inputs produce byte-identical bundles and identical hashes (test)
- Bundle contains every Appendix B section in order, with task content from the NOT-02 config
- Bundle hash recorded in `manifest.json` at P2; bundle file is read-only after generation
- Attached-document list matches the config; documents themselves are not embedded

### MVB-010 — Agent Platform Adapter

**Title:** Implement the opencode session adapter (P2–P3)

**Description:** Implement the adapter that starts an opencode agent session with the prompt bundle and workspace contract (read-only `workspace/read/`, writable `workspace/work/`), supervises P3 (timeout enforcement, interruption handling per §5.2–5.3), captures the session transcript (E1), and intakes the submission manifest (`reasoning.md`, `architecture.md`, `subsystems.md`, `testing-evidence.md`, `validation-evidence.md`, `changes/`, `declaration.json`) per §3.6.

**Dependencies:** MVB-001, MVB-003, MVB-009

**Effort:** 10h (largest single adapter; includes platform log export)

**Deliverable:** `tooling/harness/agent/opencode_adapter.py`, `tooling/harness/agent/transcript.py`.

**Acceptance criteria:**
- Adapter starts a session, applies the P3 timeout from config (effort × 1.5, min 2h, max 16h per §5.2), and marks `TIMEOUT` on expiry
- Transcript export is captured and listed in the evidence set at P4 (E1)
- Submission intake validates `declaration.json` fields and verifies all five evidence documents exist (partial submissions accepted with status `partial`)
- Adapter never invokes any command inside `bga-mercurio`; a `--dry-run` mode verifies workspace boundaries
- Interruption mid-run resumes at the last checkpoint without re-running completed phases (§5.3)

### MVB-011 — Command Log Wrapper (E3)

**Title:** Wrap all harness-side commands with logging

**Description:** Wire the MVB-003 command runner into every component so all harness-side commands are recorded (command, stdout, stderr, exit code, wall time) into the run's command log, included in the evidence set as E3, per §6.1 and §6.2.4.

**Dependencies:** MVB-003

**Effort:** 4h

**Deliverable:** Integration in components (orchestrator, validation runner, etc.).

**Acceptance criteria:**
- Every command executed by the harness from P0 onward appears in the command log with all five fields
- A command that succeeds with no output still produces a record
- The command log is copied to `evidence/` at P4 and hash-listed in `evidence.json`

### MVB-012 — Checkpoint and Status Transitions

**Title:** Implement phase checkpoints and status graph

**Description:** Implement the Orchestrator's checkpoint persistence: at every phase boundary, persist `status.json` and phase timestamps in the manifest; enforce §2.0.2 status transitions; implement §5.3 interruption resume (last completed checkpoint); implement §5.1 retry rules (no retry of failed tasks within a run; no re-evaluation after verdict).

**Dependencies:** MVB-002, MVB-003

**Effort:** 6h

**Deliverable:** `tooling/harness/runtime/orchestrator.py` (core state machine) + tests.

**Acceptance criteria:**
- Each phase transition updates `status.json` + manifest timestamps; illegal transitions raise
- Simulated interruption (kill after P3 start) resumes at the last completed checkpoint on next invocation
- `TIMEOUT`, `ABORTED`, `BLOCKED`, `REJECTED`, `VERDICTED`, `ARCHIVED` reachable states covered by tests
- Completed phases are never re-executed on resume (test asserts phase records are not overwritten)

---

## Phase 3 — Evidence (P4)

### MVB-013 — Evidence Collector

**Title:** Implement evidence collection and `evidence.json`

**Description:** Implement harness §6: at every checkpoint and at P4, copy artifacts (E1 session transcript, E2 `work/` copy, E3 command log, E4 validation logs, E5 phase times, E8 diff bundle vs baseline, E9 reports, E11 environment manifest, E12 checkpoint states; E6/E7/E10 optional with recorded absence) into `evidence/`; record relative path, size, SHA-256 per artifact in `evidence.json`.

**Dependencies:** MVB-002, MVB-003, MVB-011, MVB-012

**Effort:** 8h

**Deliverable:** `tooling/harness/evidence/collect.py` + tests.

**Acceptance criteria:**
- Every required artifact type present at P4; absent optional artifacts recorded as `"absent": "reason"` (never silent)
- `evidence.json` lists every file with matching size and hash (re-verified by test)
- The diff bundle (E8) is computed against `workspace-baseline.diff` per §6.1
- Collection at checkpoint boundaries is idempotent; re-running adds no duplicate entries

### MVB-014 — Evidence Freezing

**Title:** Implement evidence immutability and root hash

**Description:** Implement harness §6.3: make `evidence/` read-only after P4, hash-verify the manifest, route post-P4 check re-runs to `evidence/reruns/` (never altering frozen artifacts), and compute the frozen-evidence root hash (Merkle root over `evidence.json`) recorded in the manifest.

**Dependencies:** MVB-013

**Effort:** 4h

**Deliverable:** `tooling/harness/evidence/freeze.py` + tests.

**Acceptance criteria:**
- After freeze, a write attempt to any frozen artifact fails (permission test)
- Root hash is deterministic and recorded in `manifest.json`; a corrupted artifact produces a verification failure
- `evidence/reruns/` writes never modify frozen files

---

## Phase 4 — Automatic Validation (P5)

### MVB-015 — Gate Runner (G0–G2)

**Title:** Implement gate execution with blocking semantics

**Description:** Implement harness §7.2: run G0 (safety comparison via MVB-004), G1 (build gates), G2 (catalog checks); apply blocking semantics — G0/G1 failure → `REJECTED`, blocking V-check failure → `REJECTED`, non-blocking failure → capped Framework Compliance; write `validation/validation.json` and raw outputs to `validation/raw/`.

**Dependencies:** MVB-004, MVB-016, MVB-017

**Effort:** 6h

**Deliverable:** `tooling/harness/validation/gates.py` + tests.

**Acceptance criteria:**
- Each gate records pass/fail per check with output artifact references
- G0 failure sets status `REJECTED`; G1 failure sets `REJECTED`; blocking G2 failure sets `REJECTED`; non-blocking failure recorded and capped
- `validation.json` schema matches §7.2 content; raw outputs present for every check
- Deterministic: identical frozen evidence → identical `validation.json`

### MVB-016 — Build Gates B1–B4

**Title:** Implement build gates (PHP syntax, JS syntax, JSON validity, artifact inventory)

**Description:** Implement the §4 catalog rows B1–B4: `php -l` over changed PHP files; `node --check` over changed JS files; JSON/JSONC parse of changed artifacts; artifact inventory diff (only declared files changed vs baseline).

**Dependencies:** MVB-003, MVB-013

**Effort:** 6h

**Deliverable:** `tooling/harness/validation/build_gates.py` + tests.

**Acceptance criteria:**
- Each check runs only against the submission's changed files (diff bundle), never against `bga-mercurio`
- B1–B3 produce 0-error pass criteria as defined; B4 detects undeclared files (fixture: added file not in `subsystems.md` inventory fails)
- Missing PHP/Node on host marks the gate `BLOCKED` with recorded environment note, not a silent pass

### MVB-017 — Catalog Check Runner and NOT-02 Checks

**Title:** Implement V1 invocation and NOT-02 task checks

**Description:** Implement G2 check execution: V1 (runtime validator per evaluation spec §4, blocking) and the NOT-02 task checks from evaluation spec §3.11: (a) single-source check — each consolidated notification type has exactly one sending method; (b) call-site count — `labOutputActivated` 4 sites, market milestone 2, synergy 2, `cardKept` 3; (c) duplication scan (non-blocking); (d) payload parity via gamelog diff harness or recorded substitution; plus basic V9 hidden-info scan (payload-key inspection, blocking). Check implementations live under `tooling/harness/validation/checks/` and are driven by the NOT-02 task config.

**Dependencies:** MVB-007, MVB-015

**Effort:** 12h

**Deliverable:** `tooling/harness/validation/checks/` (not_02.py, v1.py, v9.py) + fixture tests.

**Acceptance criteria:**
- V1 runs `python -m tooling.validator --report human` against the skill rules and records output
- Each NOT-02 check has a fixture demonstrating pass and fail; blocking classification matches §3.11
- Payload parity: if a gamelog is runnable, diff harness output is recorded; otherwise the check is recorded as substitution with the reason (evaluation spec §2.1 G3 rule)
- Zero false positives on a clean fixture (submission with all criteria satisfied passes every check)

### MVB-018 — Raw Output Retention

**Title:** Persist raw check outputs in `validation/raw/`

**Description:** Ensure every check's raw output (grep output, script stdout, validator report) is written to `validation/raw/` with a stable filename per check ID and referenced from `validation.json`. This satisfies E4 and the evaluation spec §4 "Output" column.

**Dependencies:** MVB-015, MVB-017

**Effort:** 2h

**Deliverable:** Integrated into MVB-015/017.

**Acceptance criteria:**
- One raw file per check ID, named `<check-id>.txt` (or `.json`), non-empty for every executed check
- Files listed and hash-recorded in the P4 evidence set

---

## Phase 5 — Manual Evaluation and Scoring (P6–P7)

### MVB-019 — Manual Review Kit

**Title:** Manual review templates and evaluator onboarding

**Description:** Produce the manual review kit: `manual-review.md` scaffold (findings per rubric category with evidence citations, per evaluation spec §2.6 and Appendix A), G3 scenario script for NOT-02 (payload/type/recipient parity review of the diff bundle; notification ordering review; duplicate-block audit), evidence-credibility guidance (G4: vacuous evidence, tautological tests), and a short evaluator onboarding note (rubric anchors, scoring anchors per evaluation spec §2.4). Process only — no automation.

**Dependencies:** MVB-007 (config content)

**Effort:** 6h

**Deliverable:** `tooling/harness/review/templates/manual-review.md`, `tooling/harness/review/templates/g3-not-02.md`, `tooling/harness/review/onboarding.md`.

**Acceptance criteria:**
- Template sections match evaluation spec §2.6 evidence requirements and §3.11 rubric categories (NOTIF family)
- G3 script steps are executable from frozen evidence alone (diff bundle, submission docs)
- Onboarding note includes the five rubric anchors verbatim and a scoring example

### MVB-020 — Score Calculator

**Title:** Implement weighted scoring and verdict rules

**Description:** Implement harness §7.3–7.5: compute total = Σ(category × weight)/100 from `manual-review.md` scores; apply verdict rules in order (§7.4: critical failures → INCORRECT; total < 60 → INCORRECT; category < 50 → POOR; ≥ 90 → EXCELLENT; ≥ 75 → ACCEPTABLE; else POOR); apply the 50–59 category cap (evaluation spec §2.2); implement double computation (two independent calculations; mismatch invalidates until reconciled) and write `scores.json` + `score-verification.json`.

**Dependencies:** MVB-002, MVB-007

**Effort:** 6h

**Deliverable:** `tooling/harness/scoring/calculator.py` + tests.

**Acceptance criteria:**
- Verdict rule table covered by one test per branch, including critical-failure override and category-cap cases
- Weights read from task config; sum asserted = 100
- Double computation mismatch produces a recorded reconciliation, never silent
- Arithmetic is reproducible from `manual-review.md` alone (§7.3)

### MVB-021 — Review Records Persistence

**Title:** Persist manual review and scoring records in the run layout

**Description:** Wire the review artifacts into the run: `review/manual-review.md` (editors' working file), `review/scoring/scores.json`, `review/scoring/score-verification.json`; every category score in the review must cite at least one frozen evidence artifact (§7.5).

**Dependencies:** MVB-019, MVB-020

**Effort:** 2h

**Deliverable:** `tooling/harness/scoring/persist.py` + integration test.

**Acceptance criteria:**
- Files land in the §9.1 layout paths; evidence citations are validated present in the frozen evidence set
- A category score without a citation fails validation with a precise message

---

## Phase 6 — Report and Archive (P8–P9)

### MVB-022 — Report Generator

**Title:** Implement `evaluation-report.json` and `report.md` generation

**Description:** Implement harness §8: render both reports from the same source data (manifest, environment, validation, manual review, scores); `report.md` contains all 11 required sections (§8.2); `evaluation-report.json` contains all required metadata groups (§8.3); validate the report against the section checklist before acceptance; no divergence between the two renderings.

**Dependencies:** MVB-015, MVB-020, MVB-021

**Effort:** 8h

**Deliverable:** `tooling/harness/report/generator.py` + tests.

**Acceptance criteria:**
- Both reports generated from one in-memory source; a schema test asserts field-for-field consistency
- All §8.2 sections present in `report.md`; all §8.3 field groups present in the JSON
- Deterministic: identical run data → identical report bytes

### MVB-023 — Archive Manager and Registry

**Title:** Implement archival, `index.json`, and leaderboard entry

**Description:** Implement harness §9: after P8, move the run to the archive root, write the `ARCHIVED` marker, append the run entry to `runs/index.json` (append-only; entries never modified in place — errata adds `superseded_by`), and append the normalized leaderboard entry (§7.6) under `runs/leaderboard/<version-tuple>/leaderboard.json`.

**Dependencies:** MVB-022

**Effort:** 6h

**Deliverable:** `tooling/harness/archive/manager.py` + tests.

**Acceptance criteria:**
- Archival preserves the complete run directory (hash-checked sample)
- `index.json` grows append-only; re-archiving the same run ID is rejected
- Leaderboard entry contains all §7.6 fields including version tuple; entries are grouped by tuple
- Errata path: appending `superseded_by` does not modify the original entry (test)

### MVB-024 — Final Verification Automation

**Title:** Implement the §13 final verification record

**Description:** Implement the final verification step: re-run the four §12.3 checks, verify the artifact inventory (run artifacts exist only in `runs/`), and record the four-item confirmation in `report.md` (harness §13) and in the validation dataset seed (validation plan Appendix C, `safety` entry).

**Dependencies:** MVB-004, MVB-022, MVB-023

**Effort:** 3h

**Deliverable:** `tooling/harness/safety/final_verify.py`.

**Acceptance criteria:**
- All four items recorded as pass/fail; any failure blocks `ARCHIVED`
- Artifact inventory finds nothing outside `runs/` and `bga-senior-engineer`

---

## Phase 7 — Integration and Acceptance

### MVB-025 — Harness Test Suite

**Title:** Unit and integration tests for all MVB components

**Description:** Complete the component test suites (fixtures for each module) and add an integration test that runs a **synthetic end-to-end flow** (a stub agent that writes a deterministic submission) through P0–P8 in a temporary `runs/` root. The integration test must never touch `bga-mercurio` or the real workspace (uses fixtures).

**Dependencies:** All Phase 0–6 items

**Effort:** 10h

**Deliverable:** `tooling/harness/tests/` (full suite), integration test `test_end_to_end_synthetic.py`.

**Acceptance criteria:**
- `pytest tooling/` is green (validator + shared + harness suites) on a clean environment
- Synthetic flow reaches `VERDICTED` with a known submission and expected verdict computed from a hand-calculated fixture
- Coverage of harness module lines ≥ 80% (report generated in CI-style run)

### MVB-026 — Operator Runbook

**Title:** Operator runbook for executing one benchmark task

**Description:** Write the runbook: prerequisites, config, task selection, invoking the orchestrator per phase, supervision duties, evaluator workflow (P6), failure handling (timeout/abort/blocked), and final verification. Must be sufficient for an operator to execute the acceptance run without architectural decisions.

**Dependencies:** MVB-025

**Effort:** 4h

**Deliverable:** `tooling/harness/README.md` (runbook).

**Acceptance criteria:**
- An engineer who has not read this backlog can execute one run following the runbook alone
- Every command and its expected output is documented; failure modes map to §5.1–5.3 policy

### MVB-027 — MVB Acceptance Run

**Title:** Execute the acceptance run (NOT-02, one model, end-to-end)

**Description:** Execute the MVB acceptance run: task NOT-02, one designated model, `network: disabled`, temperature 0 (recorded), P0–P9 complete. Two qualified evaluators perform P6 double-scoring; score disputes follow validation plan §6.6. Produce the run postmortem (findings, E8 ambiguity log) and seed the validation dataset (validation plan Appendix C, cycle `pilot-0`).

**Dependencies:** MVB-025, MVB-026, evaluators onboarded via MVB-019

**Effort:** 16h (includes supervision and evaluation time)

**Deliverable:** Archived run `run-NOT-02-<model>-<ts>-00`, run postmortem, validation dataset seed.

**Acceptance criteria:**
- Exit criteria E1–E7 of the implementation plan are all verified; E8–E10 recorded
- The run is archived with verdict, double-computed scores, and registry entry
- The four-item safety verification passes for `bga-mercurio`
- Postmortem documents every finding and any spec ambiguity with a proposed resolution — zero spec changes made

### MVB-028 — Operator Smoke Run

**Title:** Second run executed by an independent operator

**Description:** A second run of NOT-02 (may reuse the same model) executed by an operator other than the implementer, following the runbook alone, to validate MVB-026 and implementation plan E9. Double-scoring optional (single evaluator acceptable for the smoke run; recorded).

**Dependencies:** MVB-027

**Effort:** 8h

**Deliverable:** Archived second run + runbook feedback notes.

**Acceptance criteria:**
- Run completes P0–P9 without architectural questions (feedback log empty of design questions)
- Runbook updated with any clarifications the smoke run required

---

## Deferred (Post-MVB)

Out of MVB scope; listed for roadmap continuity. Each item is a future backlog entry when the MVB is closed.

| ID | Title | Notes |
|---|---|---|
| FUT-01 | Validation statistics tooling | IRR coefficients, difficulty indices, discrimination, coverage matrix per validation plan Appendix A; certified per validation plan §8.6 |
| FUT-02 | Full pilot program | ≥ 72 runs, stratified task selection, reference system cohort (validation plan §3) |
| FUT-03 | Remaining task configs + check catalogs | All 23 tasks; generalization of V2–V10 check implementations |
| FUT-04 | Playwright behavioral verification | Browser automation for G3 (harness spec §11.1) |
| FUT-05 | Multi-model leaderboard | Comparisons and reproducibility metrics (harness §5.6, §9.4) |
| FUT-06 | Regression re-scoring pipeline | Validation plan §8.2 historical re-evaluation |
| FUT-07 | Environment-level network enforcement | Network sandboxing beyond policy recording |

---

## Repository Safety Protocol

`bga-mercurio` is a STRICTLY READ-ONLY reference repository. It is never the subject of any write operation — by the harness, the agent, the evaluator, or any tool invoked by the pipeline.

**Prohibited:** modifying, creating, deleting, or renaming any file; staging; committing; changing refs, index, reflog, or any git metadata.

**Permitted:** read-only inspection (`git status`, `git log`, `git diff`, reading files, read-only greps and analyses). Backlog items MVB-004 and MVB-024 implement enforcement; tests use scratch git fixtures, never the reference repository.

---

## Final Verification

For the MVB implementation and acceptance run, the operator confirms and records:

1. **No files in `bga-mercurio` were modified** — `git status --porcelain` and `git diff --stat` match the baseline
2. **No files were created in `bga-mercurio`** — untracked-file set matches the baseline
3. **No git metadata changed in `bga-mercurio`** — HEAD and reflog top match the baseline
4. **All generated artifacts exist only in `bga-senior-engineer`** (code under `tooling/harness/`, documents under `docs/evaluation/`) and in the sibling `runs/` directory per harness spec §4.1

---

*End of backlog. Phase 0–7 (MVB-001 … MVB-028) constitute the Minimum Viable Benchmark; the Deferred section lists post-MVB capabilities.*
