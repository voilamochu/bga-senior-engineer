# BGA Senior Engineer — Benchmark Harness Specification

**Repository:** `bga-senior-engineer`
**Source Codebase:** `bga-mercurio` (strictly read-only reference)
**Depends On:** `docs/evaluation/benchmark-task-corpus.md` (v1.0), `docs/evaluation/benchmark-evaluation-spec.md` (v1.0)
**Version:** 1.0
**Date:** 2026-07-31
**Status:** Canonical — authoritative execution protocol for every benchmark run

---

## Table of Contents

- [1. Purpose](#1-purpose)
- [2. Evaluation Lifecycle](#2-evaluation-lifecycle)
- [3. Agent Execution Contract](#3-agent-execution-contract)
- [4. Environment Specification](#4-environment-specification)
- [5. Determinism](#5-determinism)
- [6. Evidence Collection](#6-evidence-collection)
- [7. Scoring Pipeline](#7-scoring-pipeline)
- [8. Report Specification](#8-report-specification)
- [9. Result Archive](#9-result-archive)
- [10. Versioning](#10-versioning)
- [11. Future Automation](#11-future-automation)
- [12. Repository Safety Protocol](#12-repository-safety-protocol)
- [13. Final Verification](#13-final-verification)
- [Appendix A: Run Manifest Schema](#appendix-a-run-manifest-schema)
- [Appendix B: Prompt Bundle Template](#appendix-b-prompt-bundle-template)
- [Appendix C: Phase Checklists](#appendix-c-phase-checklists)

---

## 1. Purpose

This document defines the **execution protocol** for Mercurio benchmark runs.

The three benchmark documents divide responsibility as follows:

| Document | Answers |
|---|---|
| `benchmark-task-corpus.md` | WHAT tasks exist |
| `benchmark-evaluation-spec.md` | HOW task outputs are scored |
| **`benchmark-harness-spec.md`** (this document) | HOW benchmark runs are executed |

This specification ensures benchmark results are **reproducible across different AI systems, models, and future benchmark sessions**. It is implementation-independent: it specifies the protocol, not the tooling. Where a tool is referenced (validators, checkers), its behavior — not its implementation — is normative.

### 1.1 Scope

The protocol governs:

- Environment preparation and validation
- Agent session initialization and task dispatch
- Evidence collection and freezing
- Automatic and manual evaluation
- Score calculation and verdict assignment
- Report generation and archival
- Versioning and longitudinal comparison

### 1.2 Non-Goals

- Defining tasks (corpus) or scoring semantics (evaluation spec)
- Implementing automation (Section 11 specifies automation; it does not implement it)
- Modifying `bga-mercurio` in any way (Section 12)

### 1.3 Normative Language

- **MUST** — mandatory protocol step; deviation invalidates the run
- **SHOULD** — recommended; deviation must be recorded in the run report
- **MAY** — optional, at the operator's discretion

---

## 2. Evaluation Lifecycle

A benchmark run progresses through ten phases. Each phase has defined inputs, outputs, and completion conditions. Every phase boundary is a **checkpoint**: state is persisted to the run directory, and an interruption (Section 5.3) resumes at the last completed checkpoint.

```
P0 Repository Preparation
   ↓
P1 Environment Validation
   ↓
P2 Agent Initialization
   ↓
P3 Task Execution
   ↓
P4 Evidence Collection
   ↓
P5 Automatic Validation        (Gates G0–G2)
   ↓
P6 Manual Evaluation           (Gates G3–G4)
   ↓
P7 Score Calculation           (Gate G5)
   ↓
P8 Report Generation
   ↓
P9 Result Archival
```

### P0 — Repository Preparation

| Attribute | Specification |
|---|---|
| Inputs | `bga-mercurio` checkout, `bga-senior-engineer` checkout |
| Actions | Record safety baseline (Section 12.2); create run directory (Section 9.3); record corpus/evaluation/harness versions |
| Output | Safety baseline snapshot, run manifest (initialized) |
| Completion | Baseline matches archive baseline; run directory writable |

### P1 — Environment Validation

| Attribute | Specification |
|---|---|
| Inputs | Run manifest, host environment |
| Actions | Execute environment checks (Section 4.4); record tool versions; verify `bga-mercurio` clean read-only state |
| Output | `environment.json` manifest (Section 4.5) |
| Completion | All required tools present at required versions; any tool mismatch recorded and either resolved or the run marked `BLOCKED` |

### P2 — Agent Initialization

| Attribute | Specification |
|---|---|
| Inputs | Task selection (Section 2.0.1), prompt bundle (Section 3.2), agent configuration |
| Actions | Build prompt bundle; compute content hash; record agent identity and inference settings; start session |
| Output | Immutable prompt bundle + bundle hash in run manifest |
| Completion | Agent session established and ready |

### P3 — Task Execution

| Attribute | Specification |
|---|---|
| Inputs | Prompt bundle, workspace, read-only references |
| Actions | Agent performs the task under the Execution Contract (Section 3); supervision monitors timeouts (Section 5.2) and interruptions (Section 5.3) |
| Output | Agent artifacts in workspace: changed files, evidence documents, test outputs |
| Completion | Agent declares completion, or the phase ends by timeout/abort |

### P4 — Evidence Collection

| Attribute | Specification |
|---|---|
| Inputs | Workspace state, session artifacts |
| Actions | Freeze all evidence (Section 6): copy to run evidence directory, compute hashes, record execution time and token/cost usage |
| Output | Frozen evidence set with manifest `evidence.json` |
| Completion | Every artifact listed in the evidence manifest exists and is hash-verified |

### P5 — Automatic Validation

| Attribute | Specification |
|---|---|
| Inputs | Frozen evidence, evaluation spec Section 4 catalog |
| Actions | Execute gates G0–G2 (Section 7.2); record per-check results |
| Output | `validation.json` with per-check pass/fail/blocking classification |
| Completion | All blocking checks executed; blocking failures reject the run (verdict `INCORRECT` with reason, or `REJECTED` for G0/G1) |

### P6 — Manual Evaluation

| Attribute | Specification |
|---|---|
| Inputs | Frozen evidence, validation results, evaluation spec task section |
| Actions | Behavioral verification (G3) and evidence review (G4) per the task rubric |
| Output | `manual-review.md` with per-category findings and category scores |
| Completion | Every rubric category has a recorded score with cited evidence |

### P7 — Score Calculation

| Attribute | Specification |
|---|---|
| Inputs | Manual review scores, task weight family (evaluation spec Section 2.5) |
| Actions | Apply weights; compute total; apply verdict rules (Section 7.4); apply critical failure conditions (C1–C9) |
| Output | `scores.json` and verdict |
| Completion | Verdict assigned and recorded |

### P8 — Report Generation

| Attribute | Specification |
|---|---|
| Inputs | Run manifest, validation, manual review, scores |
| Actions | Assemble canonical report per Section 8 |
| Output | `evaluation-report.json` + `report.md` |
| Completion | Report validated against the report schema (all required sections present) |

### P9 — Result Archival

| Attribute | Specification |
|---|---|
| Inputs | Complete run directory |
| Actions | Move run directory to the archive (Section 9); update archive registry; (optionally) update leaderboard |
| Output | Archived run, registry entry |
| Completion | Registry entry points to hash-verified artifacts |

#### 2.0.1 Task Selection

Task selection is a session-planning decision made by the operator:

1. Select tasks from the corpus (corpus Task Selection Guide).
2. Each task executes as its own run. A multi-task session produces one run per task.
3. Task dependency notes: tasks that modify the same subsystem (e.g., ARC-01 then NOT-01) MAY run sequentially against the same workspace; each run still snapshots its own start state (Section 3.4). Sequencing must be recorded in each run's manifest.

#### 2.0.2 Run Statuses

| Status | Meaning |
|---|---|
| `INITIALIZING` | P0–P1 in progress |
| `READY` | P1 complete, awaiting P2 |
| `RUNNING` | P2–P3 in progress |
| `COMPLETED` | P3 finished normally |
| `TIMEOUT` | P3 ended by phase timeout |
| `ABORTED` | P3 ended by interruption policy |
| `BLOCKED` | Environment or safety failure prevents execution |
| `REJECTED` | Failed G0/G1 (safety or build) |
| `VERDICTED` | P7 complete (verdict assigned) |
| `ARCHIVED` | P9 complete |

---

## 3. Agent Execution Contract

### 3.1 Session Composition

Each run's agent session receives exactly the following inputs, in this order:

1. **System prompt** (fixed, per Section 3.1.1)
2. **Benchmark prompt** (per task, Section 3.2)
3. **Attached documents** (per task, Section 3.3)
4. **Environment** (repositories and directories, Section 3.4)

The session MUST NOT receive any additional instruction, hint, or correction during P3 unless the interruption policy (Section 5.3) is invoked.

#### 3.1.1 System Prompt

The system prompt is the same for all runs and MUST contain:

- Agent role: BGA Senior Engineer performing a benchmark task
- The repository safety protocol verbatim (Section 12)
- The writable/read-only boundary (Section 3.4)
- The evidence requirements summary (Section 6.1)
- A statement that the agent's output is being evaluated and that all claims must be supported by evidence
- The determinism policy applicable to the agent (Section 5.5)

The system prompt text is archived verbatim in the run directory (`protocol/system-prompt.txt`) and hashed into the prompt bundle hash.

### 3.2 Benchmark Prompt

The benchmark prompt is generated per run from the corpus task entry and the evaluation spec task section. It MUST contain:

| Component | Source |
|---|---|
| Task ID, title, category, difficulty, effort estimate | Corpus task entry |
| Background and objective | Corpus task entry |
| Expected outcomes | Evaluation spec task section |
| Success criteria | Evaluation spec task section |
| Required evidence | Evaluation spec task section |
| Writable and read-only boundary | Section 3.4 of this document |
| Submission instruction (what to return) | Section 3.5 |

The assembled prompt bundle (system prompt + benchmark prompt + attached document list) is written to `protocol/prompt-bundle.txt`, and its SHA-256 content hash is recorded in the run manifest. The bundle is immutable from P2 onward.

### 3.3 Attached Documents

The agent receives the following documents as context:

| Document | Delivery |
|---|---|
| `benchmark-evaluation-spec.md` | Always attached |
| Runtime specification rule files relevant to the task (`bga-senior-engineer-skill/rules/*.json`) | Per task's "Primary rules" |
| Engineering standards relevant to the task | Per task's "Key standards" |
| `benchmark-task-corpus.md` | Always attached |
| Relevant official BGA documentation (`official-docs/`) | Operator discretion, recorded in manifest |

The attached document list — not the documents' contents — is recorded in the run manifest. Documents are read-only references for the agent (Section 3.4).

### 3.4 Repository and Directory Contract

| Path | Role | Agent permissions |
|---|---|---|
| `bga-mercurio/` | Reference codebase | **READ ONLY** — no modification, creation, deletion, staging, committing, or git metadata change under any circumstances |
| `bga-senior-engineer/docs/` | Specification documents | **READ ONLY** |
| `bga-senior-engineer/bga-senior-engineer-skill/` | Rules, checklists, prompts | **READ ONLY** |
| `bga-senior-engineer/tooling/` | Validators and checkers | **READ ONLY** (run, never modify) |
| `bga-senior-engineer/official-docs/` | Official BGA docs | **READ ONLY** |
| Workspace (`runs/<run-id>/workspace/`) | Agent work area | **READ ONLY** |
| Workspace `work/` directory | Agent-generated artifacts | **WRITABLE** |
| Evidence staging (`runs/<run-id>/evidence/`) | Frozen artifacts | Written by harness only, after P4 |
| Outside the two repositories | — | Forbidden for agent operations |

The workspace layout is defined in Section 9.3. The harness copies the task's target repository material into the workspace read-only area at P0 so the agent's working copy is fully contained inside the run directory. The agent MUST work against the workspace copy, not directly against `bga-mercurio`.

### 3.5 Allowed and Prohibited Operations

**Allowed:**

- Reading any file in any attached repository or document
- Running build, analysis, and test commands against the workspace copy
- Running the automatic validation commands (Section 4 catalog)
- Writing files inside the workspace `work/` directory
- Using network access ONLY if the run manifest declares `network: enabled` (default: disabled) and only for declared purposes (e.g., dependency resolution); all network activity is logged

**Prohibited (each is an automatic critical failure — C9):**

- Any write, create, delete, or rename operation inside `bga-mercurio`
- Any git operation in `bga-mercurio` (including `git add`, `git commit`, `git reset`, `git stash`, `git checkout` writes, index or ref updates)
- Writing to any read-only path listed in Section 3.4
- Executing arbitrary commands that modify environment state (package manager global installs, system config) unless declared in the run manifest
- Deleting or altering previously frozen evidence
- Modifying the prompt bundle or any attached document during the run

### 3.6 Submission Instruction

The agent MUST conclude P3 by producing a **submission manifest** in `work/` containing:

| Item | Required |
|---|---|
| `reasoning.md` | Reasoning evidence (evaluation spec Section 2.6) |
| `architecture.md` | Architecture explanation |
| `subsystems.md` | Modified subsystems inventory |
| `testing-evidence.md` | Testing evidence |
| `validation-evidence.md` | Validation evidence |
| `changes/` | The complete set of changed files (diff bundle) |
| `declaration.json` | Task ID, completion status, self-reported time, list of artifacts |

If the agent cannot complete, it MUST submit a partial submission with `declaration.json` status `partial` and a reason. Partial submissions proceed through evaluation; they are scored on the submitted work.

---

## 4. Environment Specification

### 4.1 Repository Layout

The run environment contains exactly two repositories:

```
<root>/
├── bga-mercurio/                     # READ-ONLY reference (never touched)
├── bga-senior-engineer/              # Specifications, skill, tooling, docs
│   ├── docs/
│   │   ├── evaluation/               # corpus + evaluation spec + harness spec
│   │   ├── standards/                # engineering standards
│   │   └── foundation/               # reference analyses
│   ├── bga-senior-engineer-skill/    # runtime rules, prompts, checklists
│   ├── tooling/                      # validator and shared infrastructure
│   └── official-docs/                # official BGA framework documentation
└── runs/                             # benchmark runs (Section 9)
```

`runs/` is created by the harness. It never exists inside either repository.

### 4.2 Required Tools

| Tool | Requirement | Used for |
|---|---|---|
| Python | 3.10+ (exact version recorded) | Runtime validator (V1), evidence hashing, report assembly |
| PHP | PHP 8.1+ (exact version recorded) | Build gate B1 (`php -l`), framework-adjacent checks |
| Node.js | 18+ LTS (exact version recorded) | Build gate B2 (`node --check`), JS analysis |
| git | Any modern version | Safety baseline (G0), diff extraction |
| Standard shell utilities | POSIX | Command execution, artifact collection |

No additional packages are required. The runtime validator is stdlib-only by design. Any additional tool used in a specific run MUST be recorded in the environment manifest with version.

### 4.3 Version Pinning

| Component | Pin source |
|---|---|
| Corpus version | `benchmark-task-corpus.md` header |
| Evaluation version | `benchmark-evaluation-spec.md` header |
| Harness version | This document's header |
| Runtime specification | `runtime-v1.1` (frozen), per skill manifest |
| Validator | `tooling/validator` release (v1.0.0); its own version output is recorded in the environment manifest |
| Reference codebase | `bga-mercurio` HEAD commit hash (recorded at P0) |

Pins are recorded in the run manifest. Runs with different pins are not directly comparable (Section 10).

### 4.4 Environment Checks

At P1, the harness executes and records:

1. Tool presence and versions (`python3 --version`, `php -v`, `node -v`, `git --version`)
2. Validator version: `python -m tooling.validator --report human` output recorded
3. `bga-mercurio` safety baseline (Section 12.2)
4. Workspace creation and write-permission verification
5. Reference-codebase reachability (read access to `bga-mercurio` sources)

### 4.5 Environment Manifest

P1 produces `protocol/environment.json` containing:

| Field | Description |
|---|---|
| `tools` | Name, version, path per required tool |
| `validator_version` | Validator version output |
| `reference_head` | `bga-mercurio` HEAD commit at P0 |
| `reference_status` | `git status --porcelain` output at P0 |
| `os` | OS and architecture |
| `network` | Enabled/disabled |
| `dependencies` | Any non-default packages installed |

---

## 5. Determinism

### 5.1 Retries

| Scope | Policy |
|---|---|
| Environment check failures (P1) | MAY be retried after fixing the environment; each retry is recorded |
| Automatic validation tooling errors | MAY be re-run once if the failure is identified as tooling malfunction (not a submission defect); re-runs recorded in `validation.json` |
| Task execution (P3) | NO automatic retries of a failed/partial task within the same run; a new run (new run ID) is required for re-attempts |
| Evaluation (P5–P7) | NO retries after a verdict is recorded; score recalculation is only permitted for arithmetic error, with the correction recorded |

A retry NEVER discards evidence from the first attempt; all attempts are preserved (Section 6.3).

### 5.2 Timeout Policy

| Phase | Budget |
|---|---|
| P1 Environment validation | 15 minutes |
| P2 Agent initialization | 15 minutes |
| P3 Task execution | Corpus effort estimate × 1.5, rounded up to the next hour, with a minimum of 2 hours and maximum of 16 hours |
| P4 Evidence collection | 30 minutes |
| P5 Automatic validation | 30 minutes |
| P6 Manual evaluation | Effort estimate × 1.0 (recorded per run) |
| P7–P9 | 30 minutes each |

A phase timeout ends the phase; P3 timeout marks the run `TIMEOUT`, retains all evidence, and proceeds to evaluation of the partial submission. Time budgets are recorded in the run manifest and MUST NOT be extended mid-run.

### 5.3 Interruption Policy

- **User-initiated interruption** MAY occur at any time; the run is marked `ABORTED` and archived with all evidence.
- **Infrastructure interruption** (power, network, tool failure): the run resumes at the last completed checkpoint; completed phases are never re-executed. If the failure is within P3, the phase restarts from the beginning (agent session is not resumable mid-task), and the restart is recorded.
- An interruption NEVER deletes evidence and NEVER re-runs a completed checkpoint.

### 5.4 Randomness Policy

- The harness and evaluation logic MUST be deterministic: no sampling, shuffling, or randomized selection anywhere in the protocol. Where a run needs a unique identifier or a random seed (e.g., task spot-check selection), a seeded PRNG is used and the seed recorded in the run manifest.
- Agent-side randomness is governed by Section 5.5.
- Game RNG determinism (seeded `bga_rand`, replay determinism) is a task property evaluated by the task rubric, not a harness concern.

### 5.5 Temperature and Inference Guidance

| Setting | Guidance |
|---|---|
| Temperature | 0 (or the platform's lowest supported value). If the platform does not support temperature control, the fact is recorded in the manifest; the run is still valid but flagged for comparability |
| Sampling | NO sampling or cherry-picking of agent outputs. The single produced output is the evaluated artifact |
| Output selection | The agent's final submission manifest is evaluated; no other output variant may be substituted |

### 5.6 Reproducibility Requirements

A run is reproducible when:

1. The prompt bundle hash is identical for equivalent runs
2. The environment manifest (tools, versions, reference HEAD, network policy) is recorded
3. All evaluation decisions cite frozen evidence (hash-verified)
4. The scoring arithmetic is deterministic (Section 7.5)

The harness MUST NOT promise byte-identical outputs across different models or sessions — that is not a protocol property. Instead, the archive registry tracks a **reproducibility metric**: for pairs of runs with identical prompt bundle hash and identical environment pins, the fraction that reach the same verdict class. This metric is maintained longitudinally in the archive registry (Section 9.5).

---

## 6. Evidence Collection

### 6.1 Evidence Set

Every artifact produced by or about the run is collected and frozen. Artifact types:

| ID | Artifact | Capture mechanism | Required |
|---|---|---|---|
| E1 | Session transcript (agent turns, tool calls) | Platform log export, per-turn timestamp | Yes |
| E2 | Agent output artifacts | Copy of `work/` at P4 | Yes |
| E3 | Command log (command, stdout, stderr, exit code, wall time) | Per-command wrapper logging | Yes |
| E4 | Validation logs (catalog checks B1–B4, V1–V10) | `validation.json` + raw outputs | Yes |
| E5 | Execution time per phase | Phase timestamps | Yes |
| E6 | Token usage (input/output, per phase when available) | Platform metrics; MAY be absent | Optional |
| E7 | Cost (currency, per run when available) | Platform metrics; MAY be absent | Optional |
| E8 | Changed files (diff bundle vs workspace baseline) | `git diff` in workspace copy | Yes |
| E9 | Generated reports (interim and final) | Copies of all report drafts | Yes |
| E10 | Browser/automation artifacts (screenshots, traces, console logs) | Playwright or equivalent output | When used |
| E11 | Environment manifest | `environment.json` | Yes |
| E12 | Checkpoint states | Run state files at each phase boundary | Yes |

### 6.2 Collection Rules

1. Collection happens at P4 and at every phase boundary (checkpoint).
2. Each artifact is stored in the run evidence directory with a relative path, size, and SHA-256 hash recorded in `evidence.json`.
3. Absent optional artifacts (E6, E7, E10) are recorded as `"absent": "reason"` — omission is a recorded fact, never silent.
4. stdout and stderr are captured for every command, including commands that succeed with no output.
5. Timestamps are recorded in ISO-8601 UTC across all artifacts.

### 6.3 Freezing

Freezing makes evidence immutable:

1. After P4, the evidence directory is made read-only.
2. The evidence manifest is hash-verified: every listed artifact must match its recorded hash.
3. Any subsequent evidence requirement (e.g., re-run of a check during P5) appends to a separate `evidence/reruns/` directory that never alters frozen artifacts.
4. The frozen evidence root hash (Merkle root over `evidence.json`) is recorded in the run manifest.

---

## 7. Scoring Pipeline

### 7.1 Pipeline Overview

```
Automatic validation (G0–G2)          ← P5
        ↓
Manual review (G3–G4)                 ← P6
        ↓
Weighted scoring (G5)                 ← P7
        ↓
Final verdict                         ← P7
        ↓
Leaderboard entry                     ← P9
```

### 7.2 Gate Execution

| Gate | Executed in | Content | Rejection rule |
|---|---|---|---|
| G0 | P5 | Repository safety (Section 12.3) | Any violation → `REJECTED` |
| G1 | P5 | Build gates B1–B3 | Any failure → `REJECTED` |
| G2 | P5 | Validation catalog checks V1–V10 per task | Blocking failure → `REJECTED`; non-blocking failure → capped Framework Compliance |
| G3 | P6 | Behavioral verification | Findings feed rubric |
| G4 | P6 | Evidence review | Findings feed rubric |
| G5 | P7 | Weighted scoring | Verdict rules below |

### 7.3 Weighted Scoring

1. Manual review produces a 0–100 score per rubric category (Correctness, Architecture, Framework Compliance, Maintainability, Testing).
2. The task's weight family (evaluation spec Section 2.5) maps categories to weights (sum = 100).
3. Total = Σ (category score × weight) / 100.
4. Scores and totals are computed by arithmetic on the recorded numbers; the computation is reproducible from `manual-review.md` alone.

### 7.4 Verdict Rules

Applied in order (evaluation spec Section 2.2):

1. Any critical failure condition C1–C9 → verdict `INCORRECT`, reason recorded
2. Total < 60 → `INCORRECT`
3. Any category < 50 → `POOR` (with note)
4. Total ≥ 90 → `EXCELLENT`
5. Total ≥ 75 → `ACCEPTABLE`
6. Otherwise → `POOR`

G0/G1 rejections produce `REJECTED` (not a scoring verdict) and are not leaderboard entries.

### 7.5 Score Integrity

- Two independent computations of total and verdict are performed; a mismatch invalidates the run until reconciled and the correction recorded.
- Category scores in the manual review MUST cite at least one frozen evidence artifact per category.
- A verdict change after archival requires a documented errata (Section 8.4), never an in-place edit.

### 7.6 Leaderboard Entry

After P9, a normalized leaderboard entry is appended to the archive registry:

| Field | Value |
|---|---|
| Run ID, model, model version | From manifest |
| Task ID, difficulty | From manifest |
| Category scores and total | From scores.json |
| Verdict | From scores.json |
| Version tuple (C, E, H) | From manifest |
| Reproducibility notes | Optional |

Entries are comparable only within identical version tuples (Section 10). Difficulty-normalized comparisons (e.g., Excellent-rate by difficulty band) are computed by the operator, not encoded in the protocol.

---

## 8. Report Specification

### 8.1 Report Set

Each run produces two canonical reports:

1. **`evaluation-report.json`** — machine-readable, schema-defined (Appendix A extension)
2. **`report.md`** — human-readable rendering of the same content

Both are generated at P8 from the same source data; they never diverge.

### 8.2 Required Sections (`report.md`)

| # | Section | Content |
|---|---|---|
| 1 | Header | Run ID, model, task ID, versions (C/E/H), reference HEAD, dates |
| 2 | Run Status | Final status (`VERDICTED`/`REJECTED`/`ABORTED`/...) |
| 3 | Environment Summary | Tool versions, network policy, deviations |
| 4 | Gate Results | G0–G5 with pass/fail and artifact references |
| 5 | Evidence Index | Evidence manifest summary (E1–E12) with hash roots |
| 6 | Category Scores | Per-category score + weight + cited evidence |
| 7 | Total and Verdict | Arithmetic, verdict, applicable critical failures |
| 8 | Manual Review Notes | Behavioral findings, evidence-quality findings |
| 9 | Common Failure Mode Notes | Which corpus common failure modes were observed |
| 10 | Safety Verification | Section 13 checklist results |
| 11 | Errata History | Any corrections (initially empty) |

### 8.3 Required Metadata (`evaluation-report.json`)

| Field group | Fields |
|---|---|
| Identity | `run_id`, `model`, `model_version`, `task_id`, `task_difficulty` |
| Versions | `corpus_version`, `evaluation_version`, `harness_version`, `runtime_version`, `validator_version`, `reference_head` |
| Timing | `started_at`, `ended_at`, `phase_times` (ISO-8601 UTC) |
| Usage | `token_usage` (optional), `cost` (optional) |
| Evidence | `evidence_manifest_hash`, `evidence_root_hash` |
| Scores | `category_scores`, `weights`, `total` |
| Verdict | `verdict`, `critical_failures[]`, `rejection_reason` |
| Attribution | `evaluator_id`, `operator_id`, `manual_review_file` |

### 8.4 Errata

Corrections to an archived report MUST:

1. Be appended to the report's Errata History (never edit prior sections)
2. Reference the frozen evidence that justifies the correction
3. Trigger re-verification of the affected gate(s)
4. Update the archive registry with the errata pointer

---

## 9. Result Archive

### 9.1 Layout

```
runs/
├── index.json                          # Archive registry (Section 9.5)
├── <run-id>/
│   ├── manifest.json                   # Run manifest (Appendix A)
│   ├── status.json                     # Current status, checkpoint index
│   ├── protocol/                       # P0–P2 artifacts
│   │   ├── environment.json
│   │   ├── prompt-bundle.txt
│   │   ├── prompt-bundle.sha256
│   │   ├── system-prompt.txt
│   │   └── baseline/
│   │       ├── safety-baseline.json
│   │       └── workspace-baseline.diff
│   ├── workspace/                      # P3 agent environment (copied at P0)
│   │   ├── read/                       # read-only reference material
│   │   └── work/                       # agent-writable (frozen at P4)
│   ├── evidence/                       # P4 frozen artifacts (read-only)
│   │   ├── evidence.json               # artifact index with hashes
│   │   └── ...                         # E1–E12 artifacts
│   ├── validation/                     # P5 outputs
│   │   ├── validation.json
│   │   └── raw/                        # raw check outputs
│   ├── review/                         # P6 outputs
│   │   ├── manual-review.md
│   │   └── scoring/
│   │       ├── scores.json
│   │       └── score-verification.json # double-computation record
│   ├── reports/                        # P8 outputs
│   │   ├── evaluation-report.json
│   │   └── report.md
│   └── ARCHIVED                        # marker written at P9
└── leaderboard/
    └── <version-tuple>/
        └── leaderboard.json            # normalized entries (Section 7.6)
```

### 9.2 Run ID

`run-{task}-{model-slug}-{YYYYMMDDTHHMMSSZ}-{seq}` where:

- `task` = corpus task ID (e.g., `ARC-01`)
- `model-slug` = normalized model identifier
- `{YYYYMMDDTHHMMSSZ}` = P0 start time UTC
- `seq` = two-hex sequence distinguishing repeated runs within the same second

Example: `run-ARC-01-gpt5-20260731T120000Z-00`

### 9.3 Run Directory Initialization

At P0, the harness:

1. Creates `<run-id>/` per the layout above
2. Creates the workspace and copies read-only reference material (a fresh checkout of `bga-mercurio` contents at the pinned HEAD) into `workspace/read/`
3. Records `workspace-baseline.diff` (empty expected)
4. Initializes `manifest.json` and `status.json`

### 9.4 Multi-Model and Longitudinal Support

| Requirement | Mechanism |
|---|---|
| Multiple models | One run directory per model; model recorded in run ID and manifest |
| Multiple versions of a model | `model_version` in manifest; runs distinguished in run ID sequence |
| Repeated runs | New run IDs; prior runs never overwritten |
| Longitudinal comparison | Archive registry aggregates leaderboard entries by version tuple and model; operators query the registry for trend analysis |

### 9.5 Archive Registry

`runs/index.json` contains one entry per archived run:

```json
{
  "run_id": "...",
  "task_id": "ARC-01",
  "model": "...",
  "model_version": "...",
  "version_tuple": {"corpus": "1.0", "evaluation": "1.0", "harness": "1.0"},
  "status": "ARCHIVED",
  "verdict": "ACCEPTABLE",
  "total": 81.5,
  "category_scores": {...},
  "evidence_root_hash": "...",
  "archived_at": "ISO-8601 UTC"
}
```

Registry updates are append-only; entries are never modified in place (errata adds a `superseded_by` pointer).

---

## 10. Versioning

### 10.1 Version Axes

| Axis | Artifact | Bumped when |
|---|---|---|
| **C** Corpus | `benchmark-task-corpus.md` | Task set, task objectives, or task criteria change |
| **E** Evaluation | `benchmark-evaluation-spec.md` | Scoring semantics, rubric weights, critical failure conditions, or validation catalog change |
| **H** Harness | this document | Execution protocol, environment, determinism, archive, or report format change |

Each document carries a semantic version (MAJOR.MINOR). A MAJOR bump means runs are not directly comparable across the bump.

### 10.2 Compatibility Rules

| Combination | Comparable? | Rule |
|---|---|---|
| Same C, E, H | Yes | Direct leaderboard comparison |
| Same C and E, different H (patch/minor) | Conditionally | Comparable if the harness change is protocol-neutral; declared in the H release notes |
| Same C, different E | No | Re-evaluation with E' required for comparison |
| Different C | No | Different tasks; never compared |

Every report MUST record its full version tuple. A harness MUST NOT combine documents of incompatible versions without recording the mismatch and excluding the run from comparison.

### 10.3 Version Interaction

- E depends on C: every task ID referenced by E must exist in C at the same version tuple.
- H depends on E and C: H references gates, checks, and rubric mechanics by ID from E; a reference to a missing ID is a version conflict and invalidates the run.
- The runtime specification (`v1.1`, frozen) is a separate axis with its own versioning; harness rule checks reference it via the validator (V1) whose version is recorded per run.

---

## 11. Future Automation

This section identifies automation boundaries. Nothing here is implemented; the protocol is the specification for future tooling.

### 11.1 Automation-Suitable Steps

| Step | Automation readiness | Notes |
|---|---|---|
| P0 repository preparation and baseline | Fully automatable | Scripted checkout, snapshot, hashing |
| P1 environment validation | Fully automatable | Tool-version checks, manifest generation |
| P2 prompt bundle assembly | Fully automatable | Template + task entry → bundle + hash; MUST be deterministic |
| P3 task execution | Automatable via agent platform | Requires the execution contract to be mechanically enforced (writable/read-only, network policy, timeouts) |
| P4 evidence collection | Fully automatable | Command logging, artifact copy, hashing, freeze |
| P5 automatic validation | Fully automatable | Gate runner executing catalog checks with blocking semantics |
| P6 manual evaluation | Partially automatable | G3 behavioral scripts can be pre-built per task; rubric judgment and evidence credibility remain human |
| P7 score calculation | Fully automatable once category scores exist | Arithmetic, verdict rules, double-computation check |
| P8 report generation | Fully automatable | Template rendering from structured data |
| P9 archival and registry | Fully automatable | Append-only registry updates, leaderboard entries |

### 11.2 Human-Required Steps

| Step | Why human |
|---|---|
| Task selection and session composition | Judgment about sequencing, dependencies, operator intent |
| G3 behavioral verification judgment | Interpreting observed behavior against subjective criteria |
| G4 evidence credibility | Detecting vacuous evidence, tautological tests, fabricated claims |
| Category score assignment | Rubric judgment; requires domain expertise |
| Verdict sign-off | Final accountability for the run |
| Errata decisions | Determining whether a correction is justified |

### 11.3 Automation Design Constraints

Future automation MUST:

1. Preserve the checkpoint/immutability model (phases and frozen evidence are never re-executed or modified)
2. Record every automated action in the evidence set (E3 command log)
3. Enforce the blocking semantics of catalog checks (Section 7.2)
4. Never write outside the run directory and archive
5. Be versioned with the harness and validated against this specification before use

---

## 12. Repository Safety Protocol

### 12.1 The Invariant

`bga-mercurio` is a STRICTLY READ-ONLY reference repository. It is never the subject of any write operation — by the harness, the agent, the evaluator, or any tool invoked by the protocol.

**Prohibited:** modifying, creating, deleting, or renaming any file; staging; committing; changing refs, index, reflog, or any git metadata.

**Permitted:** read-only inspection (`git status`, `git log`, `git diff`, reading files, read-only greps and analyses).

### 12.2 Safety Baseline (P0)

The harness records:

```json
{
  "head": "<HEAD commit hash>",
  "status_porcelain": "<git status --porcelain output>",
  "reflog_top": "<git reflog -1 output>",
  "recorded_at": "<ISO-8601 UTC>"
}
```

### 12.3 Safety Verification (G0, P5)

The harness re-runs the same three commands and compares:

1. `git rev-parse HEAD` — MUST equal baseline `head`
2. `git status --porcelain` — MUST be identical to baseline `status_porcelain`
3. `git reflog -1` — MUST equal baseline `reflog_top`

Any difference is a critical failure (C9) → verdict `REJECTED`. Verification is repeated at P9 (final verification, Section 13).

### 12.4 Concurrency Note

If another process legitimately modifies `bga-mercurio` during a run (external development), the run MUST be paused and its G0 re-baselined ONLY if the operator records the external change with timestamps and the run's workspace was not affected. A re-baselined run is flagged `baseline_amended` in the manifest and excluded from leaderboard comparison unless the amendment is declared protocol-neutral.

---

## 13. Final Verification

For every run, at P9, the operator confirms and records in the report:

1. **No files in `bga-mercurio` were modified** — `git status --porcelain` matches baseline
2. **No files were created in `bga-mercurio`** — untracked-file set matches baseline
3. **No git metadata changed in `bga-mercurio`** — HEAD and reflog top match baseline
4. **All generated artifacts exist only inside `bga-senior-engineer`** (under `runs/`) and inside the run's workspace — verified by artifact inventory

These four items are the mandatory final section of every `report.md`.

---

## Appendix A: Run Manifest Schema

`manifest.json` — created at P0, extended at each checkpoint, frozen at P4, immutable thereafter (errata only):

```json
{
  "schema": "benchmark-harness-manifest/1.0",
  "run_id": "run-ARC-01-<model>-<ts>-<seq>",
  "task": {"id": "ARC-01", "difficulty": "hard"},
  "versions": {
    "corpus": "1.0",
    "evaluation": "1.0",
    "harness": "1.0",
    "runtime": "v1.1",
    "validator": "1.0.0",
    "reference_head": "<sha>"
  },
  "model": {"id": "...", "version": "...", "temperature": 0, "temperature_controlled": true},
  "network": "disabled",
  "baseline_amended": false,
  "prompt_bundle_sha256": "...",
  "evidence_root_hash": "...",
  "phases": {
    "p0": {"started_at": "...", "ended_at": "..."},
    "p1": {...}, "p2": {...}, "p3": {...}, "p4": {...},
    "p5": {...}, "p6": {...}, "p7": {...}, "p8": {...}, "p9": {...}
  },
  "timeouts": {"p3_budget_seconds": 14400},
  "submission_status": "complete",
  "rebaseline": null
}
```

## Appendix B: Prompt Bundle Template

The benchmark prompt is assembled as:

```text
# BGA Senior Engineer — Benchmark Task

## Repository Safety (MANDATORY)
<system prompt safety section, verbatim>

## Task
<corpus task: ID, category, difficulty, effort, background, objective>

## Expected Outcomes
<evaluation spec task section>

## Success Criteria
<evaluation spec task section, all criteria verbatim>

## Required Evidence
<evaluation spec task section>

## Environment
- Reference codebase: <workspace>/read/ (READ ONLY)
- Working directory: <workspace>/work/ (WRITABLE)
- Prohibited: any write to bga-mercurio or any read-only path

## Submission
Produce in <workspace>/work/:
- reasoning.md, architecture.md, subsystems.md
- testing-evidence.md, validation-evidence.md
- changes/ (diff bundle)
- declaration.json

## Attached Documents
<list: evaluation spec, corpus, task rules, task standards>
```

## Appendix C: Phase Checklists

**P0:** safety baseline recorded ▢ run dir created ▢ workspace provisioned ▢ versions pinned

**P1:** all tools present ▢ versions recorded ▢ validator run recorded ▢ network policy set

**P2:** prompt bundle written and hashed ▢ system prompt archived ▢ session started

**P3:** supervision active ▢ timeouts armed ▢ submission manifest received or timeout recorded

**P4:** all E1–E12 collected ▢ evidence.json hash-verified ▢ evidence frozen (read-only)

**P5:** G0–G2 executed ▢ validation.json complete ▢ blocking failures recorded

**P6:** behavioral scenarios per task executed ▢ rubric categories scored with citations

**P7:** weights applied ▢ double computation matched ▢ verdict assigned

**P8:** report.json + report.md generated ▢ schema-validated

**P9:** archive moved ▢ registry appended ▢ final verification (Section 13) recorded

---

*End of harness specification. Version 1.0 is the authoritative execution protocol for all future benchmark runs.*
