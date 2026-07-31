# BGA Senior Engineer — Implementation Milestones (MVB)

**Repository:** `bga-senior-engineer`
**Source Codebase:** `bga-mercurio` (strictly read-only reference)
**Depends On:** `docs/evaluation/implementation-plan.md` (v1.0), `docs/evaluation/implementation-backlog.md` (v1.0)
**Version:** 1.0
**Date:** 2026-07-31
**Status:** Canonical — milestone plan for the Minimum Viable Benchmark implementation

---

## Table of Contents

- [1. Purpose](#1-purpose)
- [2. Reorganization Map](#2-reorganization-map)
- [3. Milestone Definitions](#3-milestone-definitions)
  - [MS-01 Foundation](#ms-01-foundation)
  - [MS-02 Workspace](#ms-02-workspace)
  - [MS-03 Prompt Generation](#ms-03-prompt-generation)
  - [MS-04 Agent Execution](#ms-04-agent-execution)
  - [MS-05 Evidence Pipeline](#ms-05-evidence-pipeline)
  - [MS-06 Automatic Validation](#ms-06-automatic-validation)
  - [MS-07 Manual Evaluation](#ms-07-manual-evaluation)
  - [MS-08 Reporting and Archive](#ms-08-reporting-and-archive)
  - [MS-09 Synthetic End-to-End](#ms-09-synthetic-end-to-end)
  - [MS-10 First Real Benchmark Run](#ms-10-first-real-benchmark-run)
- [4. Milestone Dependency Graph](#4-milestone-dependency-graph)
- [5. Critical Path](#5-critical-path)
- [6. Parallel Work Opportunities](#6-parallel-work-opportunities)
- [7. Review Gates](#7-review-gates)
- [8. Effort Summary](#8-effort-summary)
- [9. Repository Safety Protocol](#9-repository-safety-protocol)
- [10. Final Verification](#10-final-verification)

---

## 1. Purpose

This document reorganizes the 28 MVB backlog items (MVB-001 … MVB-028) from pipeline-phase order into **ten implementation milestones** (MS-01 … MS-10). It is an implementation planning exercise only:

- It does NOT create or modify benchmark specifications
- It does NOT change the architecture defined in `implementation-plan.md`
- It does NOT change work-item content, dependencies-within-milestone, or acceptance criteria defined in `implementation-backlog.md`
- It ONLY resequences work into vertical slices, each of which is independently executable, reviewable, and mergeable, and each of which ends with an objective acceptance test

### 1.1 Milestone Design Principles

Every milestone MUST:

- have a clear objective (the capability that becomes available)
- implement a coherent vertical slice (one pipeline capability, runnable end-to-end at its own level)
- produce a runnable artifact (a CLI command, a validated schema set, a rendered report)
- include an objective acceptance test (an executable command with deterministic pass conditions)
- be reviewable in isolation (a reviewer needs only the milestone's diff)
- be mergeable independently (each milestone merges to `main` on its own)
- be achievable in approximately one engineering sprint (≤ 40 engineer-hours including review)

### 1.2 CLI Surface

Milestones are exposed through one deterministic CLI. Each milestone adds subcommands to `python -m tooling.harness`:

| Milestone | Subcommand added | Phase covered |
|---|---|---|
| MS-01 | `init` | P0 (directory + manifest) |
| MS-02 | `prepare` | P0–P1 (workspace, safety baseline, environment) |
| MS-03 | `prompt` | P2 (bundle generation) |
| MS-04 | `session` | P2–P3 (agent execution, supervision) |
| MS-05 | `collect` | P4 (evidence) |
| MS-06 | `gates` | P5 (G0–G2) |
| MS-07 | `review`, `score` | P6–P7 (scaffolds + scoring) |
| MS-08 | `report`, `archive` | P8–P9 |
| MS-09 | `synthetic` | All phases (deterministic fixture run) |
| MS-10 | `run` | All phases (operator one-shot for a real task) |

---

## 2. Reorganization Map

Backlog items retain their IDs, descriptions, and internal acceptance criteria; only their execution grouping changes. Two items move across backlog phases:

- **MVB-007** (task config) moves from the backlog's Static-Setup phase into MS-03 — it is the prompt-generation input, and the milestone ordering requires task config before bundle assembly.
- **MVB-011** (command log wrapper) moves from the backlog's Agent-Session phase into MS-04 — its consumer is the session supervision machinery and its evidence (E3) is captured from P3 onward.

| Milestone | Backlog items | Sprint effort |
|---|---|---|
| MS-01 Foundation | MVB-001, MVB-002, MVB-003 | 16h |
| MS-02 Workspace | MVB-004, MVB-005, MVB-006 | 14h |
| MS-03 Prompt Generation | MVB-007, MVB-008, MVB-009 | 12h |
| MS-04 Agent Execution | MVB-010, MVB-011, MVB-012 | 20h |
| MS-05 Evidence Pipeline | MVB-013, MVB-014 | 12h |
| MS-06 Automatic Validation | MVB-015, MVB-016, MVB-017, MVB-018 | 26h |
| MS-07 Manual Evaluation | MVB-019, MVB-020, MVB-021 | 14h |
| MS-08 Reporting and Archive | MVB-022, MVB-023, MVB-024 | 17h |
| MS-09 Synthetic End-to-End | MVB-025 | 10h |
| MS-10 First Real Benchmark Run | MVB-026, MVB-027, MVB-028 | 28h |
| **Total** | **MVB-001 … MVB-028** | **169h** |

---

## 3. Milestone Definitions

---

### MS-01 Foundation

**Title:** Run directory, schemas, and utilities

**Goal:** The harness can initialize a valid, spec-compliant run directory with an empty manifest and status file, and provides the shared utilities every later milestone builds on. This is the first working slice: `init` produces the §9.1 layout deterministically.

**Included Backlog Items**

- MVB-001 — Run Directory Layout and Run ID
- MVB-002 — Manifest and Status Schemas
- MVB-003 — Harness Utility Layer

**Deliverables**

- `tooling/harness/runtime/run_dir.py` (run skeleton, run ID)
- `tooling/harness/runtime/manifest.py`, `tooling/harness/runtime/status.py` (validated dataclasses)
- `tooling/harness/util/` (hash, log, clock, proc)
- `tooling/harness/config.py` (settings, runs root)
- CLI: `python -m tooling.harness init <task> <model> [--runs-root PATH]`

**Dependencies**

- None (first milestone)

**Acceptance Test**

```
python -m tooling.harness init NOT-02 demo-model --runs-root /tmp/mvb-runs
```

Pass conditions:

1. Exit code 0; the full §9.1 skeleton exists under `/tmp/mvb-runs/run-NOT-02-demo-model-<ts>-00/` with every subdirectory present
2. `manifest.json` parses and round-trips; `status.json` reads `INITIALIZING`
3. Run ID matches the §9.2 pattern; a second `init` in the same second yields `-01`
4. `pytest tooling/harness/tests/test_run_dir.py tooling/harness/tests/test_manifest.py tooling/harness/tests/test_status.py tooling/harness/tests/test_util.py` — all green

**Exit Criteria**

- All MVB-001/002/003 acceptance criteria pass
- No component imports a third-party library
- `init` never touches a path outside the runs root

---

### MS-02 Workspace

**Title:** Safety baseline, workspace provisioning, and environment collection

**Goal:** The harness can prepare a run: capture the `bga-mercurio` safety baseline, provision the isolated read-only workspace, and record the environment manifest. This completes the static P0–P1 slice with no execution machinery.

**Included Backlog Items**

- MVB-004 — Safety Baseline Capture and G0 Comparison
- MVB-005 — Workspace Manager
- MVB-006 — Environment Collector

**Deliverables**

- `tooling/harness/safety/baseline.py` (baseline + G0 comparison)
- `tooling/harness/workspace/provision.py`
- `tooling/harness/environment/collect.py`
- CLI: `python -m tooling.harness prepare <run-id> --reference PATH --runs-root PATH`

**Dependencies**

- MS-01

**Acceptance Test**

```
python -m tooling.harness init NOT-02 demo-model --runs-root /tmp/mvb-runs
python -m tooling.harness prepare run-NOT-02-demo-model-<ts>-00 \
  --reference /path/to/bga-mercurio
```

Pass conditions:

1. `protocol/baseline/safety-baseline.json` contains the §12.2 fields; `protocol/environment.json` contains all §4.5 fields
2. `workspace/read/bga-mercurio` file count equals the reference checkout; `workspace/read/` is read-only, `workspace/work/` is writable
3. `workspace-baseline.diff` exists and is empty
4. Safety baseline of the reference repo is unchanged after `prepare` (G0 comparison passes)
5. `prepare` against a reference path that was modified between baseline and check fails with a precise diff

**Exit Criteria**

- All MVB-004/005/006 acceptance criteria pass
- Workspace provisioning is verified to never open the reference repo for write (test assertion)
- Missing required tool (e.g., `php`) → `prepare` records the mismatch and marks the run `BLOCKED`

---

### MS-03 Prompt Generation

**Title:** Task configuration, system prompt, and prompt bundle

**Goal:** The harness can produce the immutable, hashed prompt bundle for NOT-02 from the task config and system prompt — the complete P2 input generation slice. Bundle assembly is deterministic: identical inputs produce identical bytes.

**Included Backlog Items**

- MVB-007 — Task Configuration for NOT-02 (moved forward from backlog Static-Setup phase)
- MVB-008 — System Prompt Asset
- MVB-009 — Prompt Bundle Generator

**Deliverables**

- `tooling/harness/tasks/not-02.json` + `tooling/harness/tasks/check_config.py`
- `tooling/harness/prompts/system-prompt.txt`
- `tooling/harness/prompt/bundle.py`
- CLI: `python -m tooling.harness prompt <run-id>`

**Dependencies**

- MS-01, MS-02

**Acceptance Test**

```
python -m tooling.harness prompt run-NOT-02-demo-model-<ts>-00
```

Pass conditions:

1. `protocol/prompt-bundle.txt` contains every Appendix B section in order, with NOT-02 content from the task config
2. `protocol/prompt-bundle.sha256` matches `sha256sum` of the bundle; the hash equals the value in `manifest.json`
3. Running `prompt` twice against two freshly initialized runs with identical inputs produces byte-identical bundles
4. `python tooling/harness/tasks/check_config.py not-02` exits 0 (weights sum to 100, all check IDs exist in the evaluation spec §4, criterion count matches §3.11)
5. The bundle file is read-only after generation

**Exit Criteria**

- All MVB-007/008/009 acceptance criteria pass
- Config cross-check passes; config fields cite source section IDs
- No network access used at any point

---

### MS-04 Agent Execution

**Title:** Agent adapter, supervision, and session machinery

**Goal:** The harness can execute the agent session under the Execution Contract: start an opencode session against the prepared workspace with the prompt bundle, supervise timeouts and interruptions, log every harness-side command, persist checkpoints, and intake the submission manifest. This is the first milestone with execution machinery — the highest-risk external integration.

**Included Backlog Items**

- MVB-010 — Agent Platform Adapter (opencode)
- MVB-011 — Command Log Wrapper (E3) (moved from backlog Agent-Session phase)
- MVB-012 — Checkpoint and Status Transitions

**Deliverables**

- `tooling/harness/agent/opencode_adapter.py`, `tooling/harness/agent/transcript.py`
- Command-log integration across components
- `tooling/harness/runtime/orchestrator.py` (phase state machine)
- CLI: `python -m tooling.harness session <run-id> [--timeout-min MIN] [--dry-run]`

**Dependencies**

- MS-03

**Acceptance Test**

```
python -m tooling.harness session run-NOT-02-demo-model-<ts>-00 --dry-run
```

Pass conditions:

1. `--dry-run` verifies workspace boundaries and prints the exact prompt bundle hash without starting a platform session; exit 0
2. With a stub adapter fixture (test-only), a session completes: transcript exported (E1), submission manifest validated, `declaration.json` fields checked, missing evidence documents reported
3. A forced timeout (fixture session sleeping past `--timeout-min 1`) marks the run `TIMEOUT`, retains partial `work/` content, and transitions status exactly per §2.0.2
4. Interruption resume: after a simulated kill at mid-P3, re-invocation resumes at the last checkpoint without re-executing P0–P2 (phase records unchanged)
5. Every command run by the harness in the fixture session appears in the command log with command, stdout, stderr, exit code, wall time

**Exit Criteria**

- All MVB-010/011/012 acceptance criteria pass
- The adapter never invokes a command inside the reference repo (test assertion; also covered in MS-09)
- Network policy `disabled` is recorded in the manifest and honored by the adapter (no external calls in `--dry-run` or fixture mode)

---

### MS-05 Evidence Pipeline

**Title:** Evidence collection and freezing

**Goal:** The harness can freeze a complete, hash-verified evidence set for a run: collect E1–E12, write `evidence.json`, make the evidence immutable, and record the frozen root hash. This is the determinism and auditability core of the benchmark.

**Included Backlog Items**

- MVB-013 — Evidence Collector
- MVB-014 — Evidence Freezing

**Deliverables**

- `tooling/harness/evidence/collect.py`, `tooling/harness/evidence/freeze.py`
- CLI: `python -m tooling.harness collect <run-id> [--freeze]`

**Dependencies**

- MS-04

**Acceptance Test**

```
python -m tooling.harness collect run-NOT-02-demo-model-<ts>-00 --freeze
```

Pass conditions (run against the MS-04 fixture session's run directory):

1. `evidence/evidence.json` lists every artifact with matching size and SHA-256; the diff bundle (E8) is present and computed against the workspace baseline
2. Every required artifact type (E1, E2, E3, E4, E5, E8, E9, E11, E12) is present; absent optional artifacts (E6/E7/E10) are recorded as `"absent": "reason"`
3. After `--freeze`, a write to any frozen artifact fails; `evidence/reruns/` accepts new files without altering frozen ones
4. Corrupting one frozen artifact and re-verifying produces a verification failure; the root hash in `manifest.json` is deterministic across two identical runs
5. Re-running `collect` is idempotent — no duplicate entries

**Exit Criteria**

- All MVB-013/014 acceptance criteria pass
- Freezing is enforced at the filesystem level, not by convention
- The evidence root hash is recorded in the manifest at P4

---

### MS-06 Automatic Validation

**Title:** Gates G0–G2 and the NOT-02 check catalog

**Goal:** The harness can execute automatic validation against frozen evidence: safety comparison (G0), build gates (G1), runtime validator and task checks (G2), with blocking semantics and raw output retention. This completes the P5 slice — the first fully mechanical evaluation stage.

**Included Backlog Items**

- MVB-015 — Gate Runner (G0–G2)
- MVB-016 — Build Gates B1–B4
- MVB-017 — Catalog Check Runner and NOT-02 Checks
- MVB-018 — Raw Output Retention

**Deliverables**

- `tooling/harness/validation/gates.py`
- `tooling/harness/validation/build_gates.py`
- `tooling/harness/validation/checks/` (`v1.py`, `not_02.py`, `v9.py`)
- CLI: `python -m tooling.harness gates <run-id>`

**Dependencies**

- MS-04, MS-05

**Acceptance Test**

```
python -m tooling.harness gates run-NOT-02-demo-model-<ts>-00
```

Pass conditions:

1. `validation/validation.json` records every gate with pass/fail and artifact references; raw outputs exist in `validation/raw/` (one file per check ID)
2. Fixture A (submission satisfying all NOT-02 criteria): every gate passes; V1 invokes the runtime validator and its output is recorded
3. Fixture B (duplicated notification block reintroduced): the single-source check fails and the run is marked `REJECTED`; G0/G1 still recorded
4. Fixture C (untracked file added outside the declared inventory): B4 fails
5. G0 failure (reference repo modified between baseline and gate): run `REJECTED` with the precise divergence reported
6. Identical frozen evidence produces byte-identical `validation.json`

**Exit Criteria**

- All MVB-015/016/017/018 acceptance criteria pass
- Blocking vs non-blocking classification matches evaluation spec §3.11 and §4 exactly
- All checks execute only against the submission's diff bundle and frozen evidence — never against the reference repo

---

### MS-07 Manual Evaluation

**Title:** Review kit, scoring calculator, and review records

**Goal:** The harness can support the manual stage: reviewers work from templates (G3 scenario script, rubric findings with evidence citations), category scores are persisted, and the Score Calculator computes totals, applies verdict rules, and double-verifies the arithmetic. This completes the P6–P7 slice.

**Included Backlog Items**

- MVB-019 — Manual Review Kit
- MVB-020 — Score Calculator
- MVB-021 — Review Records Persistence

**Deliverables**

- `tooling/harness/review/templates/manual-review.md`, `g3-not-02.md`, `onboarding.md`
- `tooling/harness/scoring/calculator.py`, `tooling/harness/scoring/persist.py`
- CLI: `python -m tooling.harness review <run-id> --scaffold`, `python -m tooling.harness score <run-id> --scores JSON`

**Dependencies**

- MS-03 (task config content), MS-06

**Acceptance Test**

```
python -m tooling.harness score run-NOT-02-demo-model-<ts>-00 --scores '{"Correctness":80,"Architecture":90,"Framework_Compliance":85,"Maintainability":70,"Testing":75}'
```

Pass conditions:

1. For the example scores (family NOTIF 40/10/25/15/10): total = 80.0×0.40 + 90.0×0.10 + 85.0×0.25 + 70.0×0.15 + 75.0×0.10 = **80.25** → verdict `ACCEPTABLE`; `scores.json` + `score-verification.json` match
2. Verdict-rule branches covered by fixture scores: total ≥ 90 → `EXCELLENT`; category < 50 → `POOR` (regardless of total); total < 60 → `INCORRECT`; category 50–59 cap → total capped at 85; critical-failure flag → `INCORRECT`
3. `review` scaffolds `manual-review.md` with all five rubric categories and evidence-citation fields; a category score without a citation fails `score` with a precise message
4. A deliberately wrong double-computation fixture produces a recorded reconciliation, never a silent result
5. The G3 scenario script is executable from the frozen diff bundle alone (steps verified by a reviewer during the milestone review)

**Exit Criteria**

- All MVB-019/020/021 acceptance criteria pass
- Score arithmetic reproducible from `manual-review.md` alone
- Review templates cite evaluation spec §2.6 evidence requirements and the NOTIF family rubric

---

### MS-08 Reporting and Archive

**Title:** Report generation, archival, and final verification

**Goal:** The harness can produce both canonical reports from a single source of truth, archive the run, append registry and leaderboard entries, and execute the four-item final verification. This completes the P8–P9 slice — the pipeline is now capable of producing an archived, reported run from any prior stage's data.

**Included Backlog Items**

- MVB-022 — Report Generator
- MVB-023 — Archive Manager and Registry
- MVB-024 — Final Verification Automation

**Deliverables**

- `tooling/harness/report/generator.py`
- `tooling/harness/archive/manager.py`
- `tooling/harness/safety/final_verify.py`
- CLI: `python -m tooling.harness report <run-id>`, `python -m tooling.harness archive <run-id>`

**Dependencies**

- MS-06, MS-07

**Acceptance Test**

```
python -m tooling.harness report run-NOT-02-demo-model-<ts>-00
python -m tooling.harness archive run-NOT-02-demo-model-<ts>-00
```

Pass conditions (run against the MS-06/MS-07 fixture data):

1. `reports/report.md` contains all 11 §8.2 sections; `evaluation-report.json` contains all §8.3 field groups; both derive from one in-memory source (no divergence)
2. Identical run data produces byte-identical report pairs
3. After `archive`: the complete run directory is present under the archive root with the `ARCHIVED` marker; `index.json` gained exactly one append-only entry; a second `archive` of the same run ID is rejected
4. `leaderboard/<tuple>/leaderboard.json` contains the entry with all §7.6 fields
5. Final verification records the four §13 items as pass; a fixture run with a modified reference repo fails verification and blocks `ARCHIVED`

**Exit Criteria**

- All MVB-022/023/024 acceptance criteria pass
- Registry is append-only (test); errata path adds `superseded_by` without in-place edits
- Final-verification failure blocks archival with no partial state

---

### MS-09 Synthetic End-to-End

**Title:** Deterministic pipeline validation (no LLM)

**Goal:** The entire benchmark pipeline — P0 through P9 — executes end-to-end against a **deterministic fixture submission** written by a stub agent, producing deterministic evidence, deterministic scores, and deterministic reports. This milestone is mandatory before any real AI execution: it proves the pipeline independently of model behavior.

**Included Backlog Items**

- MVB-025 — Harness Test Suite (including the synthetic end-to-end integration test)

**Deliverables**

- `tooling/harness/tests/test_end_to_end_synthetic.py` (stub agent + fixture submission satisfying NOT-02 criteria, fixture pair also covering a failing-submission case)
- CLI: `python -m tooling.harness synthetic [--task NOT-02] [--runs-root PATH]` (one-shot deterministic run)
- Full `pytest tooling/` suite

**Dependencies**

- MS-01 … MS-08

**Acceptance Test**

```
python -m tooling.harness synthetic --runs-root /tmp/mvb-runs
pytest tooling/ --cov=tooling.harness --cov-report=term
```

Pass conditions:

1. The synthetic run reaches `ARCHIVED` with a verdict computed from the fixture's known scores — no LLM participates (asserted: no adapter invocation in the run manifest; transcript artifact E1 is the stub transcript)
2. Two identical synthetic runs produce byte-identical evidence, scores, and reports (determinism check)
3. A second fixture submission that violates a NOT-02 criterion produces a `REJECTED` run through G1/G2, with the failing check identified in `validation.json`
4. Every gate (G0–G2) executes and is recorded; manual-review stage consumes the scaffolded template; final verification passes
5. `pytest tooling/` is green; harness module line coverage ≥ 80%
6. The synthetic run leaves no artifacts outside `/tmp/mvb-runs` and `bga-senior-engineer`

**Exit Criteria**

- All MVB-025 acceptance criteria pass
- Determinism demonstrated on two identical runs (byte-identical outputs)
- The pipeline's full data path is proven: prompt bundle → evidence → gates → scores → reports → registry
- The synthetic fixture run is recorded as the `pilot-0` validation-dataset seed (implementation plan E10)

---

### MS-10 First Real Benchmark Run

**Title:** Runbook, acceptance run, and operator smoke run

**Goal:** The first real benchmark execution: NOT-02 run with a real model via the opencode adapter, double-scored by two qualified evaluators, archived, and verified. The operator runbook is validated by a second, independent operator run. This completes the MVB (implementation plan exit criteria E1–E10).

**Included Backlog Items**

- MVB-026 — Operator Runbook
- MVB-027 — MVB Acceptance Run
- MVB-028 — Operator Smoke Run

**Deliverables**

- `tooling/harness/README.md` (runbook)
- Archived run `run-NOT-02-<model>-<ts>-00` with postmortem
- Archived smoke run + runbook feedback notes
- CLI: `python -m tooling.harness run --task NOT-02 --model <id> [--runs-root PATH]` (operator one-shot)

**Dependencies**

- MS-09 (synthetic pipeline review gate R2 must have passed)

**Acceptance Test**

```
python -m tooling.harness run --task NOT-02 --model <designated-model> --runs-root /tmp/mvb-runs
```

Pass conditions:

1. The run reaches `ARCHIVED` with a verdict assigned by the Score Calculator; double computation (two evaluators) matched or reconciled per validation plan §6.6
2. All gates executed and recorded; `manual-review.md` cites frozen evidence for every category score
3. The four-item final verification passes for `bga-mercurio`; the artifact inventory shows run artifacts only under the runs root and `bga-senior-engineer`
4. `runs/index.json` and the leaderboard entry contain the run; postmortem documents every finding; zero specification changes made
5. A second operator executes the smoke run following `tooling/harness/README.md` alone and completes P0–P9 without architectural questions; runbook updated with any clarifications
6. Implementation-plan exit criteria E1–E7 verified at the Acceptance Review gate; E8–E10 recorded

**Exit Criteria**

- All MVB-026/027/028 acceptance criteria pass
- Implementation plan §6 exit criteria E1–E10 all verified
- MVB declared complete at the Acceptance Review (R3); post-MVB backlog (FUT-01 … FUT-07) becomes the next planning input

---

## 4. Milestone Dependency Graph

The graph is intentionally almost entirely linear: each milestone builds on the previous slice and each can be reviewed and merged independently.

```
MS-01 Foundation
  │
  ▼
MS-02 Workspace ──────────────┐
  │                            │
  ▼                            │
MS-03 Prompt Generation        │
  │                            │
  ▼                            │
MS-04 Agent Execution          │
  │                            │
  ▼                            │
MS-05 Evidence Pipeline        │
  │                            │
  ▼                            │
MS-06 Automatic Validation ────┤
  │                            │
  ▼                            │
MS-07 Manual Evaluation ───────┤   (uses MS-03 task config content)
  │                            │
  ▼                            │
MS-08 Reporting and Archive    │
  │                            │
  ▼                            │
MS-09 Synthetic End-to-End     │
  │                            │
  ▼                            │
MS-10 First Real Benchmark Run │
```

Edge notes:

- **MS-07 → MS-03 (soft dependency):** MS-07's review kit and Score Calculator consume the NOT-02 task config (MVB-007). In practice MS-03 completes before MS-07 starts (both are earlier on the critical path), so no schedule impact.
- **MS-10 → MS-09 (hard gate):** the real run MUST NOT start before the synthetic milestone passes review gate R2.
- **MS-02 and MS-03 are not on each other's critical path** in the strict sense (MS-03 depends only on MS-01 for bundle determinism), but MS-03 runs `prepare`d runs in its acceptance test, so the ordering above is retained.

---

## 5. Critical Path

### 5.1 Longest Dependency Chain

The critical path is the full linear chain, because every milestone's acceptance test consumes the previous milestone's run directory format:

```
MS-01 → MS-02 → MS-03 → MS-04 → MS-05 → MS-06 → MS-07 → MS-08 → MS-09 → MS-10
```

Critical-path effort: **169h** (sum of all milestones). At one 40h sprint per milestone with review overhead, the nominal duration is ~10 sprints; parallel opportunities (Section 6) can compress MS-02/MS-03 and MS-05/MS-06/MS-07 windows.

### 5.2 Highest-Risk Milestones

| Milestone | Risk | Why | Mitigation (in-place) |
|---|---|---|---|
| **MS-04 Agent Execution** | External integration | The only milestone depending on a third-party platform (opencode): session control, transcript export, timeout behavior cannot be fully unit-tested | `--dry-run` and stub-adapter fixtures in the acceptance test; adapter isolated behind a narrow interface so MS-05+ never depends on platform behavior |
| **MS-06 Automatic Validation** | Correctness of mechanical checks | NOT-02 checks must be right the first time: false positives/negatives invalidate runs; payload-parity check may be infeasible without a live gamelog | Fixture pairs (pass/fail per check) in acceptance test; recorded substitution path for parity (evaluation spec §2.1) |
| **MS-10 First Real Benchmark Run** | People + external factors | Real model behavior, evaluator availability, double-scoring disputes, long wall-clock (effort × 1.5 budget) | MS-09 gate mandatory; double-scoring + adjudication per validation plan §6.6; timeouts and partial-submission path already implemented in MS-04 |

### 5.3 Milestones That Unlock the Most Subsequent Work

| Milestone | Unlocks |
|---|---|
| **MS-04 Agent Execution** | Everything after it: evidence, validation, scoring, and reporting all consume session artifacts; it is the last milestone that no later milestone can absorb |
| **MS-09 Synthetic End-to-End** | The real run; also the first end-to-end proof that the architecture in the implementation plan is correct — the cheapest point to find cross-component contract bugs |
| **MS-01 Foundation** | All other milestones (the run directory and schemas are consumed by every later slice) |

---

## 6. Parallel Work Opportunities

All parallel work is within-milestone or between non-adjacent milestones; the milestone ordering of Section 3 is retained.

| Parallel set | Content | Constraint |
|---|---|---|
| MS-02 ↔ MS-03 (partial) | MVB-007 (task config) and MVB-008 (system prompt) have no dependencies (backlog: "None") and can be authored during MS-02 | MS-009 bundle assembly still waits for MS-01/02 acceptance |
| MVB-004/005/006 within MS-02 | Safety baseline, workspace provisioning, environment collection are independent of each other | All depend only on MS-01 utilities |
| MS-05 ↔ MS-06 (partial) | MVB-015 (gate runner) can be drafted in parallel with MS-05; its acceptance only needs MS-05 evidence | Fixture evidence can be produced by MS-04's fixture session |
| MS-06 ↔ MS-07 (partial) | MVB-019 (review kit templates) depends only on MVB-007 content and can be drafted during MS-06 | Score Calculator (MVB-020) still waits for MS-06 |
| MVB-019 onboarding → MS-10 | Evaluator onboarding using the review kit can run in parallel with MS-08/MS-09 | Kit exists by end of MS-07 |
| MS-08 ↔ runbook drafting | MVB-026 (runbook) can be drafted during MS-08 against the MS-09 CLI surface | Final runbook lands in MS-10 |
| MVB-025 tests during MS-06–08 | Unit tests for later components can be written as their interfaces stabilize | Full suite gates at MS-09 |

**Concurrency rule:** parallel work never merges out of milestone order. A PR containing items from two milestones is split; each milestone's acceptance test is run on its own merge.

---

## 7. Review Gates

Review gates are checkpoints, not implementation work. Each gate is a meeting + a written record appended to the milestone's merge; no gate may be skipped, and no gate may add backlog items without reopening this plan.

### R1 — Architecture Review (after MS-03)

**Input:** MS-01/02/03 merged; static skeleton complete (`init`, `prepare`, `prompt`, schemas, task config).

**Reviews:**

- Module layout and CLI surface against implementation plan §2
- Data contracts (Appendix A) against the harness spec file schemas
- Task config completeness for NOT-02
- Repository-safety enforcement points (baseline capture, workspace isolation)

**Decision rule:** approve, or reject with a written list of required changes to the implementation plan (architecture changes only; no benchmark-spec changes). Execution machinery (MS-04) does not start until approved.

### R2 — Synthetic Pipeline Review (after MS-09)

**Input:** MS-09 merge; deterministic synthetic run artifacts.

**Reviews:**

- Two identical synthetic runs: byte-identical evidence, scores, reports (determinism evidence)
- Gate behavior on the failing fixture (rejection path through G1/G2)
- Evidence completeness and freezing enforcement
- Final verification and registry entries
- Test suite results and coverage

**Decision rule:** approve → real run (MS-10) may start. Reject → written defect list against the affected milestone(s); MS-10 is blocked until a re-run of the synthetic milestone passes.

### R3 — Acceptance Review (after MS-10)

**Input:** acceptance run archive, postmortem, smoke run, runbook feedback.

**Reviews:**

- Implementation-plan exit criteria E1–E10 (all must be verified)
- The four-item final verification records for both runs
- Validation-dataset seed (`pilot-0`) per validation plan Appendix C
- Findings/ambiguity log (E8): zero spec changes made; proposed resolutions recorded

**Decision rule:** approve → MVB declared complete; post-MVB backlog (FUT-01 … FUT-07) becomes the next planning input. Reject → written remediation plan; the affected milestone is re-opened.

---

## 8. Effort Summary

| Milestone | Items | Effort | Cumulative |
|---|---|---|---|
| MS-01 | 3 | 16h | 16h |
| MS-02 | 3 | 14h | 30h |
| MS-03 | 3 | 12h | 42h |
| MS-04 | 3 | 20h | 62h |
| MS-05 | 2 | 12h | 74h |
| MS-06 | 4 | 26h | 100h |
| MS-07 | 3 | 14h | 114h |
| MS-08 | 3 | 17h | 131h |
| MS-09 | 1 | 10h | 141h |
| MS-10 | 3 | 28h | 169h |
| Gates R1–R3 | — | 6h | 175h |

---

## 9. Repository Safety Protocol

`bga-mercurio` is a STRICTLY READ-ONLY reference repository. It is never the subject of any write operation — by the harness, the agent, the evaluator, or any tool invoked by the pipeline. Milestone work uses only read-only inspection of the reference repo; all implementation artifacts live in `bga-senior-engineer` (code under `tooling/harness/`, documents under `docs/evaluation/`), and run artifacts live in the sibling `runs/` directory per harness spec §4.1.

**Prohibited:** modifying, creating, deleting, or renaming any file; staging; committing; changing refs, index, reflog, or any git metadata.

**Permitted:** read-only inspection (`git status`, `git log`, `git diff`, reading files, read-only greps and analyses).

---

## 10. Final Verification

For this milestone-planning phase, the operator confirms and records:

1. **No files in `bga-mercurio` were modified** — `git status --porcelain` and `git diff --stat` match the baseline recorded in previous phases
2. **No files were created in `bga-mercurio`** — untracked-file set matches the baseline
3. **No git metadata changed in `bga-mercurio`** — HEAD and reflog top match the baseline
4. **All generated artifacts exist only inside `bga-senior-engineer`** — the single artifact of this phase is `docs/evaluation/implementation-milestones.md`

---

*End of milestone plan. MS-01 … MS-10 reorganize MVB-001 … MVB-028 without changing their content; each milestone is independently executable, reviewable, and mergeable, and ends with an objective acceptance test.*
