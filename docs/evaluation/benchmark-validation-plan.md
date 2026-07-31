# BGA Senior Engineer — Benchmark Validation Plan

**Repository:** `bga-senior-engineer`
**Source Codebase:** `bga-mercurio` (strictly read-only reference)
**Depends On:** `docs/evaluation/benchmark-task-corpus.md` (v1.0), `docs/evaluation/benchmark-evaluation-spec.md` (v1.0), `docs/evaluation/benchmark-harness-spec.md` (v1.0), Runtime Specification v1.1 (frozen), Validator Specification (tooling/validator v1.0.0)
**Version:** 1.0
**Date:** 2026-07-31
**Status:** Canonical — authoritative methodology for validating and maintaining the benchmark

---

## Table of Contents

- [1. Purpose](#1-purpose)
- [2. Validation Objectives](#2-validation-objectives)
- [3. Pilot Benchmark Program](#3-pilot-benchmark-program)
- [4. Difficulty Calibration](#4-difficulty-calibration)
- [5. Rubric Calibration](#5-rubric-calibration)
- [6. Inter-Rater Reliability](#6-inter-rater-reliability)
- [7. Coverage Validation](#7-coverage-validation)
- [8. Regression Validation](#8-regression-validation)
- [9. Benchmark Maintenance](#9-benchmark-maintenance)
- [10. Success Criteria](#10-success-criteria)
- [11. Repository Safety Protocol](#11-repository-safety-protocol)
- [12. Final Verification](#12-final-verification)
- [Appendix A: Calibration Statistics](#appendix-a-calibration-statistics)
- [Appendix B: Validation Roles](#appendix-b-validation-roles)
- [Appendix C: Validation Dataset Schema](#appendix-c-validation-dataset-schema)

---

## 1. Purpose

This document defines how the **benchmark itself** — the corpus, the evaluation specification, and the harness — is validated and calibrated before it is relied upon for long-term model comparisons.

The four benchmark documents divide responsibility as follows:

| Document | Answers |
|---|---|
| `benchmark-task-corpus.md` | WHAT tasks exist |
| `benchmark-evaluation-spec.md` | HOW task outputs are scored |
| `benchmark-harness-spec.md` | HOW benchmark runs are executed |
| **`benchmark-validation-plan.md`** (this document) | HOW the benchmark is validated and maintained |

### 1.1 Validating the Benchmark vs Validating AI Systems

The distinction is the central invariant of this document.

| Question | Benchmark run (harness spec) | Benchmark validation (this document) |
|---|---|---|
| **Subject under test** | An AI system performing a corpus task | The benchmark: corpus, rubrics, thresholds, harness |
| **What is measured** | Task output quality against a rubric | Whether the rubric, thresholds, and corpus behave as intended |
| **Evidence** | Per-run frozen evidence (E1–E12) | Meta-evidence: score distributions, agreement coefficients, calibration statistics |
| **Artifact** | `evaluation-report.json` + `report.md` per run | Validation report per pilot/calibration cycle (Section 3.7) |
| **Failure meaning** | A system failed the task | The benchmark failed to measure what it claims, or to do so reliably |
| **When performed** | Continuously, per run | Before release, on revision, and on a review cadence |

A benchmark run produces data **about a system**. A validation activity consumes many runs and produces conclusions **about the benchmark**. Data from routine runs is reused as validation evidence; validation conclusions are never treated as scores of any system.

### 1.2 Scope and Non-Goals

**In scope:**

- Pilot evaluation program design (Section 3)
- Empirical difficulty calibration (Section 4)
- Rubric boundary validation (Section 5)
- Inter-rater reliability (Section 6)
- Coverage and redundancy analysis (Section 7)
- Regression validation on revision (Section 8)
- Maintenance processes and release cadence (Section 9)
- Objective maturity criteria (Section 10)

**Out of scope:**

- Defining tasks (corpus), scoring semantics (evaluation spec), or execution protocol (harness spec)
- Evaluating any specific AI system (benchmark runs)
- Modifying `bga-mercurio` in any way (Section 11)
- Implementing validation tooling (this document specifies behavior, not implementation)

### 1.3 Normative Language

- **MUST** — mandatory validation procedure; omission means the benchmark is not validated for long-term use
- **SHOULD** — recommended; deviation must be recorded in the validation report
- **MAY** — optional, at the benchmark maintainer's discretion

### 1.4 Inputs

| Input | Role |
|---|---|
| `benchmark-task-corpus.md` v1.0 | Task set (23 tasks, 10 categories), difficulty labels, effort estimates |
| `benchmark-evaluation-spec.md` v1.0 | Verdict rules, weight families, rubrics, critical failure conditions C1–C9, validation catalog (B1–B4, V1–V10) |
| `benchmark-harness-spec.md` v1.0 | Run protocol, gates G0–G5, version axes (C/E/H), evidence collection, archive |
| Runtime Specification v1.1 | Frozen rule set (12 rule files, 185 rules) referenced by task rubrics |
| Validator Specification | `tooling/validator` v1.0.0 — the mechanical rule-compliance instrument |

---

## 2. Validation Objectives

The benchmark is validated against five measurable objectives. Each objective has an operational definition, a metric, and a validation instrument. Objectives are evaluated in every calibration cycle (Section 3.7) and reported with pass/fail per objective.

### 2.1 Representative Task Coverage (O1)

**Definition:** The corpus exercises the range of engineering skills the benchmark claims to measure: server architecture, client architecture, framework migration, debugging, synchronization, state machine, persistence, code review, and testing — at senior-engineer difficulty.

**Metrics:**

- **Category coverage index** — fraction of the corpus coverage areas (corpus Appendix: 9 areas) with at least one task: target 9/9.
- **Standard coverage index** — fraction of engineering standards (`docs/standards/*`, 11 documents) and runtime rule files (12) referenced by at least one task's rubric: target 100% of rule files, ≥ 80% of standards documents.
- **Skill-band coverage** — each skill level band in the corpus Task Selection Guide (Junior / Mid / Senior / Staff) has at least one task.

**Instrument:** Coverage matrix (Section 7.1). Evaluated in each validation cycle.

### 2.2 Reliable Scoring (O2)

**Definition:** The same submission, scored by different qualified evaluators, receives scores and a verdict that agree within tolerances; scoring decisions cite verifiable evidence.

**Metrics:**

- Verdict inter-rater agreement: Cohen's κ ≥ 0.70 (Section 6.4)
- Exact verdict agreement ≥ 80%
- Per-category agreement: weighted κ ≥ 0.60
- Score reproducibility: total-score difference between independent evaluators ≤ 8 points in ≥ 90% of double-scored runs

**Instrument:** Double-scoring protocol (Section 6.2), archive cross-check via score-verification records (harness spec Section 7.5).

### 2.3 Appropriate Difficulty Distribution (O3)

**Definition:** Empirical task difficulty matches the published difficulty labels, is spread across the corpus rather than degenerate, and discriminates between systems of different capability.

**Metrics:**

- Label-match rate: fraction of tasks whose empirical difficulty band (Section 4.3) matches the published label: target ≥ 85% after calibration
- Band balance: after calibration, each difficulty band (Easy / Medium / Hard) contains at least 15% of tasks; a band with no tasks is a distribution defect (Section 4.5)
- Discrimination: task-score correlation with total score (point-biserial) ≥ 0.30 for ≥ 80% of tasks (Appendix A.2)
- Effort accuracy: measured wall time within effort estimate × 1.5 (the harness P3 budget) for ≥ 90% of pilot runs

**Instrument:** Difficulty calibration statistics (Section 4), computed from the pilot dataset.

### 2.4 Consistent Evaluator Behavior (O4)

**Definition:** Evaluators apply the rubric uniformly across tasks, across evaluators, and across time; no systematic evaluator bias (severity/leniency) or rubric drift.

**Metrics:**

- Evaluator bias: per-evaluator mean score deviation from the panel mean ≤ 5 points, with no evaluator consistently ≥ 5 points off in the same direction
- Drift: reference-submission rescoring (Section 6.6) shows score change ≤ 5 points per evaluator across consecutive cycles
- Rating-pattern agreement: verdict boundary behavior (Section 5.2) does not differ significantly between evaluators

**Instrument:** Bias-and-drift monitoring (Section 6.5), boundary analysis (Section 5.2).

### 2.5 Longitudinal Stability (O5)

**Definition:** Scores for the same system and task remain stable across benchmark versions and across time, so that leaderboard movement reflects system change rather than benchmark change.

**Metrics:**

- Re-evaluation drift: for the held-out reference system, mean per-task score change across a version migration ≤ 5 points when no task intent changed (Section 8.4)
- Comparability: runs are comparable only within identical version tuples (C, E, H) per harness spec Section 10.2; compatibility expectations are enforced by the regression protocol (Section 8)
- Reproducibility: same-version, same-task re-runs of the reference system reach the same verdict in ≥ 80% of pairs (harness spec Section 5.6)

**Instrument:** Regression validation (Section 8), reference system cohort (Section 3.4).

---

## 3. Pilot Benchmark Program

The pilot is the first full validation cycle. It produces the empirical data required for difficulty calibration (Section 4), rubric calibration (Section 5), reliability estimation (Section 6), and coverage analysis (Section 7). A pilot run is executed exactly like a production run under the harness spec; validation adds meta-analysis on top of the run data.

### 3.1 Pilot Cohort

| Element | Requirement |
|---|---|
| AI systems | ≥ 3 distinct systems, drawn from different model families/architectures; identity recorded per run |
| Reference system | One system designated the **reference system**; re-run in every calibration cycle to measure drift (Sections 2.5, 8.4) |
| Human baseline (optional) | One human senior BGA engineer completes a 3-task subset (one per difficulty band) to provide criterion grounding for score calibration; recorded as `human-baseline` runs |

System selection MUST be recorded with model, version, and temperature settings per harness spec Appendix A. No pilot system's results are used to judge the systems; they are used to judge the benchmark.

### 3.2 Sample Size

The minimum pilot matrix is **3 systems × 12 tasks × 2 runs = 72 runs**, with the reference system adding 2 extra runs per task for stability estimation.

| Dimension | Minimum | Rationale |
|---|---|---|
| Systems | 3 | Enough to observe between-system variance (discrimination) without implying model comparisons |
| Tasks | 12 (≥ 50% of corpus) | Enough per stratum for stable statistics while keeping the pilot executable |
| Runs per task per system | 2 | Estimates within-system variance; more runs are added where statistics are unstable |
| Reference-system runs | 4 per task | Feeds drift and reproducibility metrics |
| Total | 72+ | — |

Sample size MAY be increased; it MUST NOT be decreased below these minima for the pilot to count as a calibration cycle.

### 3.3 Task Selection Strategy

Tasks are selected deterministically (seeded PRNG per harness spec Section 5.4; seed recorded in the validation report):

1. **Stratification by category.** Every one of the 10 corpus categories (Architecture, Migration, Debugging, Notification, Synchronization, Client, State Machine, Persistence, Code Review, Testing) MUST have ≥ 1 task in the pilot.
2. **Stratification by difficulty.** Quotas per band: Easy ≥ 2, Medium ≥ 5, Hard ≥ 3.
3. **Redundancy probes.** Include task pairs suspected of overlap (e.g., ARC-01 with NOT-01, ARC-02 with CLI-02, DBG-01 with NOT-02) to generate data for redundancy analysis (Section 7.3).
4. **Skill-band coverage.** The selected set MUST span all four skill bands of the corpus Task Selection Guide.
5. **Sequencing.** Tasks that share subsystems (harness spec Section 2.0.1) MAY run sequentially against one workspace; sequencing is recorded per run manifest.

### 3.4 Evidence Collected

Per run: the full frozen evidence set E1–E12 from harness spec Section 6.1, including the submission manifest, validation logs, manual review, and scores.

Per pilot (meta-evidence, stored in the validation dataset, Appendix C):

- Per-task score distributions across systems and runs (mean, median, SD, min, max, pass rate)
- Per-task difficulty statistics (Appendix A)
- Per-category and per-weight-family score profiles
- Verdict distributions and critical-failure incidence per task
- Double-scoring records and agreement coefficients (Section 6)
- Evaluator notes: rubric ambiguities encountered, missing criteria, unanswerable questions
- Coverage matrix (Section 7.1) with gap flags
- Per-run wall time vs effort estimate

### 3.5 Expected Outputs

The pilot MUST produce a **pilot validation report** containing:

1. Objective status table (Section 2) — pass/fail per objective with data
2. Difficulty calibration results (Section 4.4) — label-match table, proposed reclassifications
3. Rubric calibration findings (Section 5.4) — boundary analysis, proposed refinements
4. Inter-rater reliability results (Section 6.4) — coefficients, adjudication log summary
5. Coverage validation results (Section 7.5) — coverage matrix, redundancy findings, proposed additions/retirements
6. Evaluator feedback digest (rubric ambiguities, per-task)
7. Recommendations: benchmark revisions proposed, each mapped to the version axis it would change (C/E/H)
8. Certification recommendation against Section 10 success criteria

### 3.6 Pilot Run Statuses

Pilot runs use the harness spec statuses (Section 2.0.2). Runs that are `REJECTED` (G0/G1) or `BLOCKED` are excluded from difficulty statistics but MUST be reported; a high rejection rate (≥ 10% of pilot runs) is itself a finding against objective O2 (harness/instruction clarity).

### 3.7 Calibration Cycle Definition

A **calibration cycle** is: a pilot (Section 3) OR a revision-triggered validation (Section 8) OR a scheduled re-validation (Section 10.4). Every cycle produces a validation report with the Section 3.5 contents and updates the validation dataset (Appendix C).

---

## 4. Difficulty Calibration

Difficulty labels (Easy / Medium / Hard) and effort estimates in the corpus are hypotheses until confirmed empirically. This section defines how they are validated and adjusted.

### 4.1 Empirical Difficulty Metrics

Per task, computed over the pilot dataset (systems × runs):

| Statistic | Definition | Appendix |
|---|---|---|
| Difficulty index `p` | Mean total score / 100 | A.1 |
| Pass rate | Fraction of runs with verdict ≥ Acceptable | A.1 |
| Effort ratio | Mean P3 wall time / corpus effort estimate | A.3 |
| Discrimination `d` | Point-biserial correlation of task score with total | A.2 |

### 4.2 Empirical Difficulty Bands

Provisional band thresholds (calibrated on the pilot, revisable only through a calibration cycle):

| Band | Difficulty index `p` | Pass rate |
|---|---|---|
| Easy | ≥ 0.70 | ≥ 0.80 |
| Medium | 0.45 – 0.70 | 0.40 – 0.80 |
| Hard | < 0.45 | < 0.40 |

Bands are applied to the score distribution; a task's empirical band is the band containing its `p`.

### 4.3 Label Validation

A task's label is **confirmed** if its empirical band equals its published label, or if it falls within one band of it with a documented reason (e.g., small sample). A task is **mislabeled** if:

- Its `p` is more than one band away from its label, OR
- Its effort ratio is outside 0.7–1.5 while its label band contradicts the measured time, OR
- Its discrimination `d` is negative (higher-capability systems score lower) — a signal the task measures something other than intended

### 4.4 Adjustment Procedure

1. The validation report lists each task as **confirmed**, **reclassify**, or **investigate**, with statistics.
2. **Reclassify** — the maintainer changes the difficulty label or effort estimate in the corpus. This is a corpus change: version axis C bumps (harness spec Section 10.1) and triggers regression validation (Section 8).
3. **Investigate** — the maintainer determines whether the task, its rubric, or its label is at fault (Section 5 findings feed this). Resolution is one of: reclassify, revise the task or rubric (C or E bump), or retire (Section 9.2).
4. No label adjustment MAY be made on fewer than 6 runs per task; under-powered statistics are reported as "insufficient data".
5. Reclassification of a task automatically re-runs its label through the label-match target (≥ 85%, Section 2.3) in the next cycle.

### 4.5 Distribution Calibration

Beyond per-task labels, the pilot MUST verify band balance (Section 2.3). If a band is empty or underweighted after calibration, the maintainer proposes new tasks (Section 9.1) or reclassification to restore balance before the next calibration cycle.

---

## 5. Rubric Calibration

Rubric calibration validates that the scoring machinery in the evaluation spec — category anchors, weight families, verdict boundaries — produces scores that are meaningful, discriminating, and stable.

### 5.1 Anchor Validation

The five rubric anchors (100 / 75 / 50 / 25 / 0, evaluation spec Section 2.4) are validated by **anchor calibration submissions**: for each weight family, the maintainer curates exemplar submissions (real pilot submissions or constructed goldens) representing each anchor level, with written justification. Procedure:

1. ≥ 2 independent evaluators score each exemplar (Section 6).
2. An anchor is **confirmed** if evaluator scores fall within ±12 points of the anchor value and within one anchor band of each other.
3. Anchors that fail are revised in the evaluation spec (E bump) with the calibration data cited.

Anchor exemplars become part of the validation dataset (Appendix C) and are reused for evaluator onboarding and drift checks (Section 6.6).

### 5.2 Boundary Validation

The verdict boundaries (Excellent ≥ 90, Acceptable ≥ 75, Poor ≥ 60, Incorrect < 60; plus the 50–59 category cap and category < 50 rule, evaluation spec Section 2.2–2.4) are validated on the pilot score distribution:

- **Boundary density:** fraction of runs within ±2 points of each boundary. Density ≥ 20% of the distribution at any boundary flags boundary instability (many verdicts hinge on 2 points) — the boundary or the scoring resolution may need adjustment or the rubric anchors need tightening.
- **Verdict-relevance check:** correlation between boundary-adjacent score differences and observable evidence differences (evaluators' written findings) — boundary splits MUST be traceable to evidence, not noise.
- **Category-cap check:** whether the 50–59 category-cap rule (evaluation spec Section 2.2) triggers in plausible cases, and whether capped and uncapped verdicts differ as intended.

### 5.3 Weight Validation

Per weight family, over tasks in that family:

- Compute per-category contribution to total variance. A category with near-zero variance contribution (all submissions score the same) is either trivially easy for the category or mis-weighted; the maintainer investigates.
- Correlate each category score with the total. A category with correlation ≈ 0 while the task discriminates is likely mis-weighted or mis-scored.
- Weight adjustments are E bumps, require justification in the validation report, and trigger regression validation (Section 8).

### 5.4 Rubric Refinement Rules

- Any refinement MUST cite validation data (score distributions, evaluator notes, IRR results).
- Refinements that change scoring semantics, weights, boundaries, or critical failure conditions are **E-axis** changes (harness spec Section 10.1).
- Refinements MUST NOT be applied to archived runs; they apply through re-scoring per Section 8.
- Evaluator feedback (Section 3.4) on rubric ambiguity is a first-class input; a criterion that ≥ 2 evaluators independently flagged as ambiguous in the same cycle MUST be reworded or have a clarifying note added.

---

## 6. Inter-Rater Reliability

IRR measures whether the manual gates (G3–G5) produce consistent results. It is the primary instrument for objectives O2 and O4.

### 6.1 Evaluator Qualification

| Requirement | Specification |
|---|---|
| Domain expertise | BGA framework experience; completed the onboarding rubric (anchor exemplars, Section 5.1) |
| Blind scoring | Evaluators score independently without seeing other evaluators' scores |
| Isolation | Evaluators work from the same frozen evidence only; no cross-talk during scoring |
| Qualification check | Onboarding score on anchor exemplars within ±12 points of panel-consensus anchors |

### 6.2 Double-Scoring Protocol

- In every calibration cycle, **100% of pilot runs** are double-scored.
- In production (post-certification), a rolling **≥ 25% sample** of runs per task is double-scored; the sample is drawn deterministically (seeded, recorded) across tasks, systems, and evaluators.
- Double-scoring is recorded per run in the validation dataset (Appendix C) as a scoring pair.

### 6.3 Agreement Statistics

| Statistic | Applies to | Threshold |
|---|---|---|
| Cohen's κ | Verdicts (nominal) | ≥ 0.70 |
| Exact agreement | Verdicts | ≥ 80% |
| Weighted κ (quadratic) | Per-category scores | ≥ 0.60 |
| Score difference | Total score | ≤ 8 points in ≥ 90% of pairs |

Statistics are computed per task and pooled. Statistics per task with < 6 pairs are reported as provisional.

### 6.4 Threshold Interpretation

| Outcome | Action |
|---|---|
| All thresholds met | IRR confirmed for the cycle; production sampling continues |
| Verdict κ ≥ 0.70 but score-difference threshold missed | Boundary or anchor instability suspected → Section 5.2 analysis; evaluator recalibration |
| Verdict κ < 0.70 | IRR FAILED. Mandatory: rubric ambiguity review of the affected tasks, evaluator recalibration, and a re-validation on a fresh sample before the cycle's conclusions are accepted |
| Per-category κ < 0.60 | Category anchors need refinement (Section 5.1) or the category is not scorable at the required resolution → E-axis change |

### 6.5 Bias and Drift Monitoring

- **Bias:** per cycle, each evaluator's mean deviation from the panel mean per task. |Deviation| > 5 points in the same direction for ≥ 3 tasks flags evaluator bias; the evaluator recalibrates and the affected runs MAY be re-scored by another pair.
- **Drift:** each cycle, evaluators rescore 2–3 anchor exemplars blind to prior scores. Per-evaluator deviation from their own prior cycle scores > 5 points flags drift; a stable evaluator's scores are usable, a drifting evaluator's scoring weight is reviewed.

### 6.6 Disagreement Resolution

1. A scoring pair whose verdicts disagree, or whose total scores differ > 8 points, is **disputed** and recorded as such.
2. The two evaluators re-review the frozen evidence independently and MAY adjust scores; the revision and reason are recorded. A reversal of the initial score is recorded as a two-stage disagreement.
3. If disagreement persists, a third evaluator (or the benchmark maintainer) adjudicates. The adjudication MUST be written: evidence cited, rubric interpretation applied, final scores.
4. Adjudicated runs carry the adjudicator's scores; the pair's original scores remain in the dataset as disagreement data.
5. Persistent disagreement on a task (≥ 30% of its double-scored runs) triggers a rubric ambiguity review of that task (Section 5.4) — the task's rubric is implicated, not the evaluators.

---

## 7. Coverage Validation

Coverage validation identifies what the corpus measures well, what it misses, and where it wastes measurement capacity.

### 7.1 Coverage Matrix

The central artifact: a matrix of **corpus categories × engineering skills**, where a cell is marked when a task exercises the skill. Skills are drawn from:

- The 9 corpus coverage areas (corpus Appendix)
- The 11 engineering standards documents (`docs/standards/*`)
- The 12 runtime rule files and their rule groups
- The 4 skill-level bands (corpus Task Selection Guide)

The matrix is maintained in the validation dataset (Appendix C) and regenerated on every C-axis change.

### 7.2 Missing Benchmark Areas

A gap exists when:

- A corpus coverage area, standards document, or rule file has no task referencing it, or
- A skill is exercised only by Easy tasks while senior-level measurement requires a Hard task in that skill, or
- A cross-cutting concern (e.g., reconnect handling, hidden-information protection, framework migration) appears only as a secondary criterion and never as a primary objective.

Gap findings produce new-task proposals (Section 9.1) with the gap mapped to the task's objective and rubric. Gaps that cannot be filled because no authentic Mercurio material exists are documented as **accepted gaps** with a reason.

### 7.3 Redundant Tasks and Overlapping Evaluations

| Analysis | Method | Finding → action |
|---|---|---|
| Subsystem overlap | Tasks sharing affected subsystems (e.g., multiple tasks on `Game.php` notification sites) | Assess whether objectives remain distinct; if two tasks measure the same skill with the same evidence, one is a candidate for retirement (Section 9.2) |
| Criterion overlap | Tasks whose success criteria measure the same property (e.g., payload parity in ARC-01, NOT-01, NOT-02) | Confirm the criteria serve different objectives; redundant criteria are candidates for trimming |
| Empirical redundancy | Score correlation between task pairs ≥ 0.80 across systems (tasks that rank systems identically) | Investigate: tasks may be interchangeable; retire or differentiate one |
| Evaluator-confusion overlap | Evaluator notes flagging two tasks as "the same work" (e.g., sequential runs of ARC-01 then NOT-01 in one workspace) | Sequence-separation guidance in the harness spec or task revision |

### 7.4 Underrepresented Engineering Skills

Beyond the matrix, the maintainer validates that the corpus measures the skills named in the benchmark's claim: senior BGA engineering including debugging, architecture, migration, synchronization, security review, and testing. Specific checks:

- Each corpus category has ≥ 2 tasks OR a documented reason (categories with 1 task are acceptable only with a justification in the corpus).
- Each weight family is exercised by ≥ 2 tasks (families with a single task cannot validate their weights — Section 5.3).
- Debugging and synchronization (the benchmark's hardest-to-measure skills) have at least one Hard task each.

### 7.5 Coverage Report

Every cycle produces the coverage section of the validation report: matrix status, gap list, redundancy findings, and per-flag actions (propose / revise / retire / accept). Coverage conclusions are C-axis inputs (Sections 8 and 9).

---

## 8. Regression Validation

Regression validation ensures that benchmark revisions do not silently change what is measured. It defines when historical re-evaluation happens and what compatibility expectations hold across versions.

### 8.1 Revision Triggers

| Trigger | Axis (harness spec §10.1) | Regression validation required |
|---|---|---|
| Task added, retired, or objectives/criteria changed | C | Yes |
| Rubric weights, boundaries, anchors, or critical conditions changed | E | Yes |
| Evaluation catalog checks changed | E | Yes |
| Harness protocol, environment, or determinism policy changed | H | Yes, if non-trivial |
| Reclassification / effort re-estimate (Section 4.4) | C | Yes |
| New validation tooling | — | Tooling is certified against this plan before use (Section 8.6) |

### 8.2 Historical Re-Evaluation

When a revision is released, archived runs are affected as follows:

| Situation | Policy |
|---|---|
| C unchanged, E changed | Affected tasks' archived runs are **re-scored** under E' by the double-scoring protocol (Section 6.2) from the same frozen evidence. Re-scoring is a new evaluation record (new scores, new citations); archived verdicts are never edited in place (harness spec Section 8.4) |
| C changed (task-level) | Runs of the changed task are re-run under C' with the reference system (Section 3.1) and, where feasible, one additional system; old runs are flagged `superseded_task` in the registry |
| C changed (corpus-wide) | Full pilot-scale re-validation per Section 3 before the new C is released for comparison |
| H changed | Comparability depends on the protocol-neutrality declaration (harness spec Section 10.2); runs affected by non-neutral H changes are re-executed or excluded |
| No change to a task | Its runs are untouched |

Re-evaluation scope MAY be limited to a stratified sample (by task, system, verdict) when the run count is large; the sample MUST cover all affected tasks and all verdict classes, and the sampling design MUST be recorded. Re-scored runs are appended to the validation dataset and the archive registry (harness spec Section 9.5) with a `rescored_under` pointer.

### 8.3 Compatibility Expectations

| Version combination | Compatibility (harness spec §10.2) | Regression expectation |
|---|---|---|
| Identical (C, E, H) | Direct comparison | Scores for the same system/task are stable within run-to-run variance |
| Same C, E; H patch/minor, protocol-neutral | Conditional comparison | No measurable effect on scores: verified by a reference-system before/after run pair differing ≤ 5 points |
| Same C; different E | No direct comparison without re-scoring | Re-scored runs (Section 8.2) restore comparability where intended |
| Different C | Never compared | Re-run under C' required |

The leaderboard (harness spec Section 7.6) MUST NOT mix version tuples except through the explicit mechanisms above.

### 8.4 Reference-System Drift

Each regression validation MUST include reference-system runs:

- Before/after a revision: the reference system completes ≥ 2 runs per affected task. Mean per-task score change ≤ 5 points is **revision-neutral**. A larger change is a finding: the revision altered measurement — either the revision intent (task changed) or unintended drift, which MUST be explained in the validation report before the release proceeds.
- Across cycles: the cumulative reference-system score series is the benchmark's stability record (objective O5).

### 8.5 Version Migration Checklist

Every released version migration MUST record:

1. Version tuple before/after and the diff of the documents (C/E/H)
2. The revision-trigger matrix (Section 8.1) and the re-evaluation actually performed
3. Reference-system drift results (Section 8.4)
4. Re-scored run list with `rescored_under` pointers
5. Compatibility statement: which historical runs remain directly comparable, which are re-scored, which are excluded
6. Changelog entries (Section 9.5)

### 8.6 Validation Tooling Certification

Tooling used to compute validation statistics (agreement coefficients, difficulty metrics, coverage matrix) MUST be:

- Versioned and deterministic (seeded PRNG only, harness spec Section 5.4)
- Verified against the statistics definitions in Appendix A by a known-answer test before each cycle
- Recorded (name, version, run output) in the validation dataset

Statistics computed by hand or by unrecorded scripts are not admissible as validation evidence.

---

## 9. Benchmark Maintenance

### 9.1 Adding Tasks

A new task enters the corpus through a proposal that MUST include:

1. **Representativeness argument** — authentic BGA work pattern, mapped to a corpus coverage area and engineering standards
2. **Independence statement** — does not duplicate an existing task's objective (Section 7.3 checks apply)
3. **Scorability** — objective success criteria with at least one mechanical check where possible (evaluation spec Section 4)
4. **Difficulty hypothesis** — label and effort estimate with reasoning
5. **Evidence requirements** — consistent with evaluation spec Section 2.6

Process: proposal → maintainer review against Sections 7.2–7.4 → pilot the task with the reference system and one other system (≥ 6 runs total) → difficulty calibration (Section 4) → corpus merge as a C-minor (or C-major if it changes task-set semantics) with regression validation (Section 8).

A task MUST NOT be added without pilot data. A task MUST NOT be added to fix a leaderboard outcome; the benchmark is validated against the corpus's skill claims, not against system performance.

### 9.2 Retiring Tasks

A task is a retirement candidate when:

- **Empirically redundant** (Section 7.3): near-identical ranking of systems as another task
- **Degenerate measurement**: `p` < 0.10 or > 0.95 (no information), or discrimination `d` ≤ 0 with sufficient data
- **Obsolete**: the engineering practice it measures no longer reflects modern BGA development (e.g., a framework pattern the platform has fully removed)
- **Leak risk**: task content leaks reference-solution hints through evaluation materials (detected by repeated near-perfect scores by systems without Mercurio exposure — investigated before concluding)

Process: retirement is proposed in the validation report → deprecation in the corpus (C-minor; the task is marked `deprecated`, its runs are flagged, and it is excluded from new runs) → removal at the next C-major. Deprecated tasks' historical runs stay in the archive for continuity analysis.

### 9.3 Updating Rubrics

Rubric updates follow Section 5.4: data-cited, E-axis, regression-validated. Standing review cadence: each calibration cycle reviews (a) ambiguous-criterion flags (Section 5.4), (b) IRR-failing categories (Section 6.4), (c) weight-correlation findings (Section 5.3).

### 9.4 Version Migration

Version semantics follow harness spec Section 10.1 (axes C, E, H; semantic versioning; MAJOR = not directly comparable). This document adds:

- **C-major** requires a full calibration cycle (Section 3) before release and a documented compatibility statement (Section 8.5)
- **C-minor / E-minor / H-minor** require regression validation (Section 8) and a compatibility statement
- **Patch** (clarification-only) requires only the diff record and changelog entry

### 9.5 Release Cadence

| Release type | Cadence | Contents |
|---|---|---|
| Validation cycle | At least annually; triggered additionally by any revision | Pilot or regression validation per Section 3.7; validation report published |
| Corpus/evaluation/harness minor | At most quarterly, batched | Task additions/retirements (C), rubric refinements (E), protocol clarifications (H) |
| Patch | As needed | Clarifications, typographical and reference fixes that do not change semantics |
| C/E/H major | Only when a validation cycle demonstrates the need | Re-set of tasks, scoring semantics, or protocol; always preceded by a full pilot |

Version bumps are recorded per document header; the version interaction rules (harness spec Section 10.3) are enforced: every E reference must exist in C, every H reference in E.

### 9.6 Ownership

- **Benchmark maintainer** — owns the corpus, rubrics, versioning, and release process; adjudicates disputes (Section 6.6)
- **Evaluation panel** — qualified evaluators per Section 6.1
- **Validation operator** — runs calibration cycles, maintains the validation dataset (Appendix C), operates tooling (Section 8.6)
- All roles are recorded in validation reports; no single individual MAY be both the sole evaluator of a run and the adjudicator for it.

---

## 10. Success Criteria

The benchmark is **mature enough for long-term use** when all of the following hold in the most recent calibration cycle, verified by an independent audit of the validation dataset:

| # | Criterion | Requirement |
|---|---|---|
| S1 | Reliability (O2) | Verdict κ ≥ 0.70, exact verdict agreement ≥ 80%, weighted κ ≥ 0.60 per category, score difference ≤ 8 points in ≥ 90% of pairs |
| S2 | Difficulty calibration (O3) | ≥ 85% of tasks label-matched to empirical bands; all three bands populated; ≥ 80% of tasks discriminate (d ≥ 0.30) |
| S3 | Coverage (O1) | Category coverage 9/9; all 12 runtime rule files referenced; no open un-accepted gap in a senior-level skill |
| S4 | Evaluator consistency (O4) | No evaluator bias flags in the cycle; drift ≤ 5 points per evaluator on anchor rescoring |
| S5 | Stability (O5) | Reference-system drift ≤ 5 points across the cycle's revision tests; reproducibility ≥ 80% same-verdict on re-runs |
| S6 | Redundancy | No task pair with empirical redundancy ≥ 0.80 left unaddressed (retired or differentiated) |
| S7 | Pilot integrity | ≥ 72-run pilot completed; rejection rate < 10%; all statistics computed by certified tooling (Section 8.6) |
| S8 | Evidence | Validation dataset complete per Appendix C; every conclusion in the validation report cites dataset entries |

**Certification decision.** When S1–S8 all pass, the maintainer issues a certification record in the validation dataset: the benchmark version tuple (C, E, H) is certified for long-term comparison. Certification expires on the next revision that requires regression validation (Section 8.1) or after 12 months without a validation cycle.

**Use of criteria.** Before certification, benchmark runs MAY still be executed (e.g., pilot and calibration runs), but their leaderboard entries are provisional: they are flagged `pre-certification` and MUST NOT be cited for long-term model comparisons. Post-certification, a new calibration cycle is scheduled per Section 9.5 and the criteria re-evaluated; a failure of S1 or S5 in any later cycle suspends certification for the affected tasks until resolved.

---

## 11. Repository Safety Protocol

`bga-mercurio` is a STRICTLY READ-ONLY reference repository. It is never the subject of any write operation — by the validation process, the harness, the agent, the evaluator, or any tool invoked by the protocol.

**Prohibited:** modifying, creating, deleting, or renaming any file; staging; committing; changing refs, index, reflog, or any git metadata.

**Permitted:** read-only inspection (`git status`, `git log`, `git diff`, reading files, read-only greps and analyses).

Validation activity produces artifacts only inside `bga-senior-engineer` (validation reports, datasets) and inside run directories under `runs/` (harness spec Section 9). Validation never touches the reference codebase.

---

## 12. Final Verification

For every validation activity (pilot, calibration cycle, revision release), the operator confirms and records in the validation report:

1. **No files in `bga-mercurio` were modified** — `git status --porcelain` and `git diff --stat` match the recorded baseline
2. **No files were created in `bga-mercurio`** — untracked-file set matches the baseline
3. **No git metadata changed in `bga-mercurio`** — HEAD (`git rev-parse HEAD`) and reflog top (`git reflog -1`) match the baseline
4. **All generated artifacts exist only in `bga-senior-engineer`** — under `docs/` (specifications and reports) and `runs/` (run directories); verified by artifact inventory

These four items are the mandatory final section of every validation report.

---

## Appendix A: Calibration Statistics

### A.1 Difficulty Index and Pass Rate

Per task, over `N` runs (systems × repetitions), with `s_i` the total score of run `i`:

- Difficulty index `p = (Σ s_i) / (100 × N)`
- Pass rate `= (# runs with verdict ≥ Acceptable) / N`

`p` is inverted relative to engineering convention deliberately: higher `p` = easier task (higher scores).

### A.2 Discrimination

Point-biserial correlation between task score `s` and the system-level mean score over the same cycle's task set (using only tasks run by ≥ 3 systems):

`d = corr(s_i, m_sys(i))`

where `m_sys` is the system's mean over common tasks. Threshold: `d ≥ 0.30` for ≥ 80% of tasks (Section 2.3). Negative `d` triggers investigation (Section 4.3).

### A.3 Effort Ratio

`e = mean P3 wall time / corpus effort estimate`. Harness budget is effort × 1.5 (harness spec Section 5.2). Bands: `e ≤ 0.7` over-estimate, `0.7–1.5` accurate, `> 1.5` under-estimate.

### A.4 Agreement Coefficients

- Cohen's κ on verdicts, computed per task and pooled.
- Exact agreement = fraction of pairs with identical verdicts.
- Quadratic weighted κ per rubric category (scores 0–100 mapped to five anchor bands).
- Score difference = |total_A − total_B| per pair.

### A.5 Redundancy

Empirical redundancy between tasks X and Y = Pearson correlation of per-system mean scores across the systems that ran both tasks, with N ≥ 3 systems. ≥ 0.80 triggers investigation (Section 7.3).

### A.6 Boundary Density

For each verdict boundary `b`, boundary density = fraction of runs with total score within ±2 points of `b`. ≥ 20% at any boundary triggers boundary validation (Section 5.2).

---

## Appendix B: Validation Roles

| Role | Responsibilities |
|---|---|
| Benchmark maintainer | Corpus/rubric ownership, versioning, release decisions, adjudication (Section 6.6), certification (Section 10) |
| Evaluation panel | Blind scoring of runs (G3–G5) under Section 6.1; anchor rescoring for drift |
| Validation operator | Calibration cycle execution, statistics computation, dataset maintenance, tooling operation (Section 8.6) |
| Independent auditor | Verifies the certification criteria (Section 10) against the validation dataset; audits the repository safety verification (Section 12) |

---

## Appendix C: Validation Dataset Schema

The validation dataset is a versioned, append-only collection (one entry per calibration cycle), stored under `docs/evaluation/` as `validation-dataset/` and referenced by every validation report. Schema:

| Entry | Contents |
|---|---|
| `cycle` | Cycle ID, type (pilot / regression / scheduled), version tuple (C, E, H) before and after, dates |
| `runs` | Run IDs, statuses, verdicts, totals, category scores, per-system and per-task grouping |
| `stats` | Per-task and pooled statistics per Appendix A (p, pass rate, e, d, κ, exact agreement, boundary density, redundancy pairs) |
| `scoring_pairs` | Double-scoring pairs, agreements, disputes, adjudications (Section 6) |
| `evaluators` | Evaluator IDs, onboarding qualification, bias and drift records (Section 6.5) |
| `coverage` | Coverage matrix (Section 7.1), gap list, redundancy findings (Section 7.3) |
| `anchors` | Anchor exemplar IDs and calibration scores (Section 5.1) |
| `revisions` | Proposal, decision, and axis for every task/rubric/weight change; `rescored_under` pointers (Section 8.2) |
| `tooling` | Statistics-tooling name, version, known-answer verification output (Section 8.6) |
| `certification` | S1–S8 results, certification record or suspension (Section 10) |
| `safety` | Repository safety verification records (Section 12) |

Entries are immutable after a cycle closes; corrections are appended as errata entries pointing at the corrected entry.

---

*End of validation plan. Version 1.0 is the authoritative methodology for validating and maintaining the benchmark over time.*
