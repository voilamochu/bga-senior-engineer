# BGA Senior Engineer — Minimum Viable Benchmark Implementation Plan

**Repository:** `bga-senior-engineer`
**Source Codebase:** `bga-mercurio` (strictly read-only reference)
**Depends On:** `docs/evaluation/benchmark-task-corpus.md` (v1.0), `docs/evaluation/benchmark-evaluation-spec.md` (v1.0), `docs/evaluation/benchmark-harness-spec.md` (v1.0), `docs/evaluation/benchmark-validation-plan.md` (v1.0), Runtime Specification v1.1, Validator Specification v1.0.0
**Version:** 1.0
**Date:** 2026-07-31
**Status:** Canonical — implementation roadmap for the Minimum Viable Benchmark (MVB)

---

## Table of Contents

- [1. Goals](#1-goals)
- [2. Architecture](#2-architecture)
- [3. Execution Flow](#3-execution-flow)
- [4. Existing Components](#4-existing-components)
- [5. Risks](#5-risks)
- [6. Exit Criteria](#6-exit-criteria)
- [7. Repository Safety Protocol](#7-repository-safety-protocol)
- [8. Final Verification](#8-final-verification)
- [Appendix A: Data Contracts](#appendix-a-data-contracts)

---

## 1. Goals

### 1.1 MVB Definition

The Minimum Viable Benchmark (MVB) is the smallest executable implementation of the benchmark specifications that can run **one benchmark task end-to-end**:

- Provision a run environment (P0–P1)
- Execute one agent session against one corpus task under the Execution Contract (P2–P3)
- Freeze complete evidence (P4)
- Run gates G0–G2 with blocking semantics (P5)
- Produce a manual review record (P6)
- Compute scores and assign a verdict (P7)
- Generate and archive reports (P8–P9)

The MVB is **not** a production benchmark. It proves the pipeline works, exposes design gaps, and produces the first real run data for validation (per `benchmark-validation-plan.md` §3). Its single objective is a **successful end-to-end run of one task** against the existing specifications, with no specification changes.

### 1.2 Scope Decisions (binding for MVB)

| Decision | MVB choice | Rationale |
|---|---|---|
| First task | `NOT-02` (Consolidate Duplicated Notification Blocks) | Easy difficulty; dominated by mechanical checks (single-source script, call-site counts, payload parity); light manual review; no BGA Studio simulation required; independent of other tasks (no workspace sequencing) |
| Agent platform | `opencode` (local CLI) | Available in this environment; session transcript (E1) is exportable; temperature and inference settings recorded per harness spec §5.5 |
| Run directory | Sibling directory `runs/` **outside both repositories** | Mandated by harness spec §4.1: "`runs/` is created by the harness. It never exists inside either repository." |
| Manual evaluation (G3–G4) | Manual, supported by review templates | Per harness spec §11.2, manual evaluation is a human-required step; MVB provides the kit, not automation |
| Behavioral simulation | Manual scenario execution + documented substitution | Evaluation spec §2.1 G3 permits recorded substitution when the environment cannot fully simulate BGA |
| Double-scoring | 2 evaluators for the MVB acceptance run | Validation plan §6.2 requires 100% double-scoring in calibration cycles; the acceptance run doubles as the first pilot data point |
| Validation statistics tooling | **Deferred** (post-MVB) | IRR coefficients, difficulty metrics, coverage matrix are validation-plan outputs, not required to execute a run |
| Playwright/behavioral scripts | **Deferred** (post-MVB) | Catalog rows marked "non-blocking (strong evidence when run)" do not gate the MVB |
| Leaderboard | Single entry append only | Minimal registry per harness spec §9.5; no comparison UI |

### 1.3 MVP vs Future Capabilities

| Capability | MVB | Post-MVB |
|---|---|---|
| One task end-to-end | Yes | — |
| All 23 tasks, all check catalogs | — | Yes (pilot per validation plan §3) |
| Multi-model runs and leaderboard comparison | One model, one entry | Yes |
| Automated behavioral verification (Playwright) | No | Yes (harness spec §11) |
| Validation statistics (IRR, difficulty, coverage) | No | Yes (validation plan §2–§7) |
| Full pilot program (≥ 72 runs) | — | Yes (validation plan §3.2) |
| Re-scoring / regression validation | No | Yes (validation plan §8) |
| Reusable check catalog across tasks | First-task checks only | Generalize to V2–V10 catalog |

---

## 2. Architecture

### 2.1 Component Overview

```
                        ┌──────────────────────────────┐
                        │      Run Orchestrator        │
                        │  phase controller, check-    │
                        │  points, timeouts, status    │
                        └──────────────┬───────────────┘
        ┌───────────┬───────────┬──────┴──────┬─────────────┬────────────┐
        ▼           ▼           ▼             ▼             ▼            ▼
 ┌────────────┐┌──────────┐┌───────────┐┌────────────┐┌───────────┐┌───────────┐
 │ Workspace  ││ Prompt   ││Environment││ Evidence   ││ Validation││ Archive   │
 │ Manager    ││ Bundle   ││ Collector ││ Collector  ││ Runner    ││ Manager   │
 └────────────┘│ Generator │└───────────┘└────────────┘└───────────┘└───────────┘
               └──────────┘        ┌─────────────────┬──────────────┘
                                   ▼                 ▼
                            ┌──────────────┐  ┌──────────────┐
                            │Score         │  │ Report       │
                            │Calculator    │  │ Generator    │
                            └──────────────┘  └──────────────┘
        ┌────────────────────────────────────────────────────────────┐
        │  Manual Review Kit (process + templates, not automation)    │
        │  evaluator onboarding, manual-review.md, G3 scenario script │
        └────────────────────────────────────────────────────────────┘
```

All components are **stdlib-only Python 3.10+** following the existing `tooling/_shared` pattern (pure functions, deterministic, no external dependencies). Components are invoked by the Run Orchestrator, one per phase; each is independently testable.

### 2.2 Component Responsibilities and Interfaces

| Component | Responsibility | Primary inputs | Primary outputs |
|---|---|---|---|
| **Run Orchestrator** | Drive the phase state machine (P0–P9); persist checkpoints; enforce timeouts (§5.2) and status transitions (§2.0.2); invoke phase components in order | Operator command `run --task NOT-02 --model <id> --out <runs-dir>` | `status.json` updates, per-phase records |
| **Workspace Manager** | Create run directory per §9.3; copy reference material (read-only `bga-mercurio` contents at pinned HEAD, specs, skill, tooling, official-docs) into `workspace/read/`; create `workspace/work/`; record `workspace-baseline.diff` | Run ID, reference repo path, pinned HEAD | Run directory layout, baseline diff |
| **Prompt Bundle Generator** | Assemble system prompt + benchmark prompt + attached-document list per §3.2 and Appendix B; write `protocol/prompt-bundle.txt`; compute and record SHA-256 | Task entry (from task config, MVB-007), system prompt asset, document list | `prompt-bundle.txt`, bundle hash in manifest |
| **Environment Collector** | Execute P1 environment checks (§4.4); record tool versions, validator output, reference HEAD/status, OS, network policy; write `protocol/environment.json` (§4.5) | Host environment, reference repo | `environment.json` |
| **Evidence Collector** | At every checkpoint and at P4: copy artifacts (E1–E12) to `evidence/`; compute size + SHA-256 per artifact; write `evidence.json`; freeze (read-only, hash-verify, Merkle root per §6.3) | Run directory, session transcript, command log, `work/` contents | `evidence.json`, frozen evidence root hash |
| **Validation Runner** | Execute gates G0–G2 (§7.2): safety comparison, build gates B1–B4, catalog checks (V1 + per-task checks); apply blocking/non-blocking semantics; write `validation/validation.json` and raw outputs | Frozen evidence, task config, reference safety baseline | `validation.json` |
| **Score Calculator** | Apply weight family (§2.5), compute total, apply verdict rules (§2.2–2.3, §7.4); perform double computation (§7.5); write `scores.json` + `score-verification.json` | `manual-review.md` category scores, task weight family | `scores.json`, verdict |
| **Report Generator** | Render `evaluation-report.json` + `report.md` from the same source data (§8); validate required sections | Manifest, environment, validation, review, scores | Both reports |
| **Archive Manager** | Move run to archive; append to `index.json` (append-only, §9.5); append leaderboard entry (§7.6); write `ARCHIVED` marker | Completed run directory | Archived run, registry entry |
| **Manual Review Kit** | Templates and instructions for G3–G4: `manual-review.md` scaffold, evidence-citation rules (eval spec §2.6, Appendix A), G3 scenario script for the task, evaluator onboarding note | Task rubric section | `review/manual-review.md`, findings |

### 2.3 Data Contracts

All inter-component data flows through files whose schemas are defined by the harness spec:

- `manifest.json` — harness spec Appendix A (extended at each checkpoint, frozen at P4)
- `status.json`, `environment.json`, `evidence.json`, `validation.json`, `manual-review.md`, `scores.json`, `score-verification.json`, `evaluation-report.json`, `report.md`, `index.json`, `leaderboard.json` — harness spec §7–§9
- Task config (new): per-task curated JSON (ID, title, category, difficulty, effort, success criteria, check list, weight family, evidence requirements) extracted once from the corpus/evaluation docs with source section references. The MVB uses curated config (rather than parsing Markdown at runtime) because the corpus/evaluation docs are normative prose; the config is validated against the docs in MVB-007's acceptance criteria.

The MVB implements these contracts as Python dataclasses + JSON round-trip validation, with one schema test per file (pattern: `tooling/validator/tests/`).

---

## 3. Execution Flow

The MVB executes the harness-spec lifecycle (§2) unchanged. This section binds each phase to a component and a specification reference; it does not restate the specifications.

| Phase | Actions | Component | Spec reference |
|---|---|---|---|
| **P0** | Record safety baseline (§12.2); create run dir; pin versions (C/E/H, runtime, validator, reference HEAD) | Orchestrator, Workspace Manager | Harness §2, §12.2 |
| **P1** | Environment checks; write `environment.json` | Environment Collector | Harness §4.4–4.5 |
| **P2** | Build + hash prompt bundle; record agent identity/inference settings; start session | Prompt Bundle Generator, agent adapter | Harness §3.2, §2 |
| **P3** | Agent executes NOT-02 against `workspace/read/` (read-only reference) writing into `workspace/work/`; supervision, timeouts, interruption policy; submission manifest intake (§3.6) | Orchestrator (supervision), agent adapter | Harness §3, §5.2–5.3 |
| **P4** | Freeze E1–E12; hash-verify; compute evidence root hash | Evidence Collector | Harness §6 |
| **P5** | G0 safety re-verify; G1 build gates; G2 catalog checks (V1 + NOT-02 checks: single-source per type, call-site counts, payload parity, duplication scan) | Validation Runner | Harness §7.2; Eval §4; Eval §3.11 |
| **P6** | G3 behavioral verification (manual scenario script — payload/type/recipient parity review of the diff bundle, gamelog diff if runnable, else recorded substitution); G4 evidence review | Manual Review Kit, evaluators | Eval §2.1, §3.11 |
| **P7** | Weighted scoring (family NOTIF: 40/10/25/15/10), verdict rules, double computation | Score Calculator | Eval §2.2–2.5; Harness §7.3–7.5 |
| **P8** | Render both reports from the same data; schema-validate | Report Generator | Harness §8 |
| **P9** | Archive; registry append; leaderboard entry; final verification (§13) | Archive Manager | Harness §7.6, §9 |

**Task selection note.** The corpus Task Selection Guide (corpus Appendix) lists NOT-02 for Junior; it is chosen for the MVB because pipeline validation — not difficulty — is the objective. Later pilot tasks follow the validation-plan stratification (§3.3).

**Checkpoint rule.** Every phase boundary persists state (harness spec §2); an interruption resumes at the last completed checkpoint; completed phases are never re-executed (§5.3).

**Determinism.** No randomization anywhere in the pipeline; unique IDs and any sampling use a seeded PRNG with the seed recorded (harness §5.4). The acceptance run records temperature and model version (harness §5.5).

---

## 4. Existing Components

### 4.1 Reusable as-is

| Component | Location | Used for |
|---|---|---|
| Runtime validator (V1) | `tooling/validator/` (v1.0.0, stdlib-only, CLI + human/JSON/CI outputs) | V1 check in G2 (`python -m tooling.validator --report human`), rule-compliance evidence |
| Shared infrastructure | `tooling/_shared/` (loader, markdown, registry, schema, types) | Pattern and library base for harness components |
| Validator test suite | `tooling/validator/tests/` | Pattern for component tests (pytest, stdlib-only fixtures) |
| Runtime Specification v1.1 | `bga-senior-engineer-skill/rules/*.json` (12 files, 185 rules) | Attached documents (§3.3), rule checks |
| Checklists | `bga-senior-engineer-skill/checklists/*.json` | Non-blocking V3 compliance mapping |
| Prompts | `bga-senior-engineer-skill/prompts/` (13 task prompts) | Reference material for system/benchmark prompt drafting |
| Engineering standards | `docs/standards/*` (11 docs) | Attached documents (§3.3) |
| Official BGA docs | `official-docs/` | Attached documents (§3.3, operator discretion) |
| Canonical reference patterns | `reference-projects/` (Agricola, Ark Nova, Arnak, Earth) | Reference material |
| Specifications (normative) | `docs/evaluation/*` (4 documents) | The contract the MVB implements; no changes allowed |
| Validator architecture doc | `tooling/validator/architecture.md` | Component-design reference (stateless, deterministic, CI-native) |

### 4.2 Must Be Implemented

| Component | Notes |
|---|---|
| Run Orchestrator | New; the phase state machine does not exist |
| Workspace Manager | New; run layout and reference copying do not exist |
| Prompt Bundle Generator | New; Appendix B assembly logic |
| Agent platform adapter | New; opencode session start, transcript export (E1), submission intake |
| Environment Collector | New; §4.4–4.5 implementation |
| Command log wrapper (E3) | New; per-command record of command/stdout/stderr/exit code/wall time |
| Evidence Collector + freezer | New; hashing, immutability, Merkle root |
| Validation Runner + gate executor | New; G0–G2 orchestration; B1–B4; per-task check scripts for NOT-02 (single-source check, call-site count, duplication scan, payload parity where runnable); V9 hidden-info scan (basic payload-key scan) |
| Safety baseline tooling | New; §12.2 capture + G0 comparison |
| Score Calculator | New; weights, verdict rules, double computation |
| Report Generator | New; §8 rendering from single source data |
| Archive Manager + registry | New; append-only registry, leaderboard entry |
| Manual Review Kit | New; templates and evaluator onboarding per eval spec Appendix A |
| Task config for NOT-02 | New; curated per-task JSON validated against corpus/eval docs |
| Operator runbook | New; step-by-step operator instructions |
| Validation statistics tooling | **Not MVB** (validation plan §8.6; post-MVB backlog) |

---

## 5. Risks

| # | Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|---|
| R1 | Agent modifies `bga-mercurio` (critical failure C9) | Medium | Rejects run; corrupts reference | Workspace copy isolation (§3.4), read-only reference material, G0 re-verify at every checkpoint (not only P5/P9), agent system prompt contains the safety protocol verbatim |
| R2 | Agent exceeds time budget or stalls | Medium | Run TIMEOUT; partial submission | Harness §5.2 budgets enforced by Orchestrator; partial submissions proceed to evaluation (§3.6); interruption policy §5.3 |
| R3 | Evidence gaps (missing transcript, unlogged commands) | Medium | Fails §6 evidence requirements; scores capped | Command wrapper for all harness-side commands (E3); transcript export verified at P4 against a checklist; evidence completeness gate in P4 |
| R4 | G3 behavioral verification infeasible without BGA Studio | High | G3 findings limited | Evaluation spec §2.1 permits recorded substitution (static verification against gamelog schema/diff bundle); substitution MUST be recorded in the report; NOT-02 chosen partly for this |
| R5 | Payload-parity check not runnable (no live gamelog) | High | One check non-blocking or substituted | Recorded substitution per R4; parity reviewed manually against diff bundle in G3 |
| R6 | Evaluator unavailability / disagreement | Medium | No verdict; IRR failure (validation plan §6.4) | MVB acceptance run double-scored by 2 qualified evaluators; onboarding via Manual Review Kit; disputes adjudicated per validation plan §6.6 |
| R7 | Component bugs pollute run state | Medium | Invalid run; lost evidence | Checkpoint + immutability model (harness §5.3); component unit tests before integration (MVB-025); evidence never deleted |
| R8 | Model output variance (non-determinism) | Medium | Verdict instability across re-runs | Temperature 0 recorded (§5.5); single submission evaluated; reproducibility metric is a longitudinal property (harness §5.6), not an MVB gate |
| R9 | Path-length / filesystem constraints (WSL, long paths) | Low–Medium | Tooling failures | Run IDs and paths kept short (§9.2); file ops via stdlib `pathlib`; environment checks verify writable run dir (P1) |
| R10 | Spec ambiguity discovered mid-implementation | Medium | Architectural decisions deferred to implementer | MVB contract: no spec changes; ambiguities are logged as findings (RFA list for the validation plan's next cycle), never resolved ad hoc |
| R11 | Agent uses network despite policy | Low | Policy violation | `network: disabled` default in manifest; environment-level enforcement recorded; session log reviewed at P4 |
| R12 | Curated task config diverges from corpus/eval docs | Medium | Wrong checks/weights applied | MVB-007 acceptance: config fields cite source section IDs; cross-check script validates IDs and weights against the documents |

---

## 6. Exit Criteria

The MVB is complete when ALL of the following hold:

| # | Criterion | Verification |
|---|---|---|
| E1 | Acceptance run completed | One NOT-02 run executed through P0–P9; final status `ARCHIVED`; verdict assigned by Score Calculator with double computation matching |
| E2 | All gates executed | G0–G2 results recorded in `validation.json` with blocking semantics applied; G3–G4 recorded in `manual-review.md` with evidence citations; G5 in `scores.json` |
| E3 | Evidence complete and frozen | All required E1–E12 artifacts present, hash-verified, evidence read-only; evidence root hash recorded in manifest |
| E4 | Reports produced and validated | `evaluation-report.json` + `report.md` generated from the same data; required sections present (§8.2–8.3) |
| E5 | Archive integrity | Run archived under `runs/<run-id>/`; `index.json` appended; leaderboard entry appended with version tuple |
| E6 | Safety verified | All four §12 items pass for `bga-mercurio` at P9: no modified files, no created files, no git metadata changes; artifact inventory shows run artifacts only in `runs/` |
| E7 | Backlog closed | All MVB backlog items (Phase 0–7) implemented with acceptance criteria met; test suite green (`pytest tooling/` + harness tests) |
| E8 | Findings logged | Any spec ambiguity discovered is recorded in the run postmortem as a finding with proposed resolution for the next validation cycle — the MVB made zero spec changes |
| E9 | Runbook validated | An operator other than the implementer executes a second (smoke) run following the runbook without architectural questions; that run may reuse the same task and model |
| E10 | First pilot data point | The acceptance run's scoring pair and evaluation data are recorded in the validation dataset seed (validation plan Appendix C, cycle `pilot-0`) |

E1–E7 are mandatory for MVB completion; E8–E10 are recorded as MVB closing artifacts. A verdict of any class (including `INCORRECT`/`REJECTED`) for the acceptance run does not fail E1 — the pipeline, not the system, is under test; a `REJECTED` run is only acceptable if the rejection is a G0/G1 gate behaving correctly and the cause is reported in the postmortem.

---

## 7. Repository Safety Protocol

`bga-mercurio` is a STRICTLY READ-ONLY reference repository. It is never the subject of any write operation — by the harness, the agent, the evaluator, or any tool invoked by the pipeline. The MVB enforces this through:

- Workspace copy isolation (agents work against `workspace/read/`, never the reference repo)
- Read-only reference material provisioning (Workspace Manager)
- G0 safety comparison at every checkpoint (Validation Runner)
- Final verification at P9 (Section 8)

**Prohibited:** modifying, creating, deleting, or renaming any file; staging; committing; changing refs, index, reflog, or any git metadata.

**Permitted:** read-only inspection (`git status`, `git log`, `git diff`, reading files, read-only greps and analyses).

## 8. Final Verification

For the MVB implementation and the acceptance run, the operator confirms and records:

1. **No files in `bga-mercurio` were modified** — `git status --porcelain` and `git diff --stat` match the baseline
2. **No files were created in `bga-mercurio`** — untracked-file set matches the baseline
3. **No git metadata changed in `bga-mercurio`** — HEAD and reflog top match the baseline
4. **All generated artifacts exist only in `bga-senior-engineer`** (implementation code under `tooling/harness/`, documents under `docs/evaluation/`) and in the sibling `runs/` directory per harness spec §4.1

These four items are recorded in the acceptance-run report (harness spec §13).

---

## Appendix A: Data Contracts

The MVB implements the following file contracts; each is defined in the harness spec and implemented as a validated schema in MVB-002:

| File | Location | Defined in |
|---|---|---|
| `manifest.json` | `<run-id>/manifest.json` | Harness §Appendix A |
| `status.json` | `<run-id>/status.json` | Harness §2.0.2, §9.1 |
| `environment.json` | `<run-id>/protocol/environment.json` | Harness §4.5 |
| `prompt-bundle.txt` + `.sha256` | `<run-id>/protocol/` | Harness §3.2, Appendix B |
| `safety-baseline.json` | `<run-id>/protocol/baseline/` | Harness §12.2 |
| `evidence.json` | `<run-id>/evidence/evidence.json` | Harness §6 |
| `validation.json` | `<run-id>/validation/validation.json` | Harness §7.2 |
| `manual-review.md` | `<run-id>/review/manual-review.md` | Eval §2.6, Appendix A |
| `scores.json` / `score-verification.json` | `<run-id>/review/scoring/` | Harness §7.3–7.5 |
| `evaluation-report.json` / `report.md` | `<run-id>/reports/` | Harness §8 |
| `index.json` / `leaderboard.json` | `runs/` / `runs/leaderboard/<tuple>/` | Harness §9.5, §7.6 |
| Task config | `tooling/harness/tasks/not-02.json` | New (validated against corpus/eval docs) |

---

*End of implementation plan. Version 1.0 is the roadmap for the Minimum Viable Benchmark; the companion backlog is `docs/evaluation/implementation-backlog.md`.*
