# Evaluator Onboarding — BGA Senior Engineer Benchmark (NOT-02)

This note prepares a qualified evaluator to perform the manual review
(G3-G4) and scoring (G5) of a NOT-02 run.  The full authority is
`docs/evaluation/benchmark-evaluation-spec.md` (§2, §3.11) and
`docs/evaluation/benchmark-harness-spec.md` (§7).

## Review Workflow

1. Verify the run's frozen evidence is complete (`evidence/evidence.json`,
   hash-verified at P4) and the automatic validation results are recorded
   (`validation/validation.json`, P5).
2. Run `python -m tooling.harness review <run-id>` to inspect the
   scaffolded review state.
3. Execute the G3 scenario script (`g3-not-02.md`) against the frozen
   diff bundle and record findings in `manual-review.md`.
4. Complete the G4 evidence review (evaluation spec §2.6) and record
   credibility findings.
5. Score the five rubric categories in `manual-review.md` with evidence
   citations, then run
   `python -m tooling.harness score <run-id> --scores <json> [--reviewer <name>]`.

## Rubric Categories (evaluation spec §2.4)

| Category | What it measures |
|---|---|
| Correctness | Observable behavior matches the task objective; existing behavior preserved; edge cases handled |
| Architecture | Boundaries, ownership, dependency direction, absence of duplication, testability |
| Framework Compliance | Conformance to the Runtime Specification v1.1 rules and official BGA framework contracts |
| Maintainability | Readability, naming, size discipline, documentation, absence of dead code |
| Testing | Existence, meaningfulness, and reproducibility of tests for the changed behavior |

## Scoring Anchors (evaluation spec §2.4, all categories, verbatim)

| Score | Anchor description |
|---|---|
| 100 | All success criteria satisfied; no regressions; edge cases explicitly handled; evidence complete |
| 75 | Primary criteria satisfied; minor gaps with documented justification |
| 50 | Majority satisfied; one or more observable deviations or missing evidence items |
| 25 | Substantial deviation from the objective; core behavior incorrect or evidence absent |
| 0 | Not attempted, or the category is wholly failed |

## NOT-02 Weight Family (evaluation spec §2.5, family NOTIF)

| Category | Weight |
|---|---|
| Correctness | 40 |
| Architecture | 10 |
| Framework Compliance | 25 |
| Maintainability | 15 |
| Testing | 10 |

## Scoring Example

Scores `Correctness 80, Architecture 90, Framework Compliance 85,
Maintainability 70, Testing 75` (weights 40/10/25/15/10):

    total = 80×0.40 + 90×0.10 + 85×0.25 + 70×0.15 + 75×0.10
          = 32.0 + 9.0 + 21.25 + 10.5 + 7.5
          = 80.25

Verdict rules (harness spec §7.4): no critical failure; total ≥ 75 →
`ACCEPTABLE`.

## Evidence Standard (evaluation spec §2.6)

Every category score must cite at least one frozen evidence artifact
(harness spec §7.5).  Evidence credibility is assessed in G4: a
submission whose claims cannot be verified from evidence scores 25 or
below in Testing and Maintainability regardless of code quality.
