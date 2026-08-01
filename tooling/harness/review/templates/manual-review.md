# Manual Review — {{TASK_TITLE}}

Run: {{RUN_ID}}  |  Task: {{TASK_ID}} ({{DIFFICULTY}})  |  Model: {{MODEL}}

| Field | Value |
|---|---|
| Run ID | {{RUN_ID}} |
| Task | {{TASK_ID}} — {{TASK_TITLE}} |
| Difficulty / Effort | {{DIFFICULTY}} |
| Model | {{MODEL}} |
| Corpus version | {{CORPUS_VERSION}} |
| Evaluation version | {{EVALUATION_VERSION}} |
| Harness version | {{HARNESS_VERSION}} |
| Runtime version | {{RUNTIME_VERSION}} |
| Validator version | {{VALIDATOR_VERSION}} |
| Reference HEAD | {{REFERENCE_HEAD}} |
| Rubric family | {{RUBRIC_FAMILY}} (evaluation spec §2.5) |
| Reviewer |  |
| Review date |  |

## Rubric

Category weights (evaluation spec §2.5 family {{RUBRIC_FAMILY}}, sum = 100):

{{WEIGHTS_ROW}}

Scoring anchors (evaluation spec §2.4, all categories):

| Score | Anchor description |
|---|---|
| 100 | All success criteria satisfied; no regressions; edge cases explicitly handled; evidence complete |
| 75 | Primary criteria satisfied; minor gaps with documented justification |
| 50 | Majority satisfied; one or more observable deviations or missing evidence items |
| 25 | Substantial deviation from the objective; core behavior incorrect or evidence absent |
| 0 | Not attempted, or the category is wholly failed |

## Evidence References

Frozen evidence (hash-verified at P4; root hash {{EVIDENCE_ROOT_HASH}}):

{{EVIDENCE_INDEX}}

## Automatic Validation Results

P5 gates (validation/validation.json):

{{VALIDATION_SUMMARY}}

## Category Scores

Score each category 0-100 per the §2.4 anchors. Cite at least one frozen
evidence artifact per category — paths relative to the run root, e.g.
`evidence/e1-transcript.txt` — separated by ';'. Enter `yes` in the
Critical failure column when the category exhibits a C1-C9 condition
(evaluation spec §2.3).

| Category | Score (0-100) | Evidence citations | Reviewer comments | Deductions | Uncertainty | Critical failure |
|---|---|---|---|---|---|---|
| Correctness |  |  |  |  |  | no |
| Architecture |  |  |  |  |  | no |
| Framework Compliance |  |  |  |  |  | no |
| Maintainability |  |  |  |  |  | no |
| Testing |  |  |  |  |  | no |

## Critical Failures Observed (C1-C9)

List any critical failure condition codes from evaluation spec §2.3,
one per line (e.g. `- C4 hidden information leak`); write `none` when no
critical failure was observed.

- none

## G3 Findings — Behavioral Verification

Execute the G3 scenario script (`g3-not-02.md`) against the frozen diff
bundle and record per-step findings here: payload/type/recipient parity,
notification ordering, duplicate-block audit, and (when a gamelog is
available) gamelog diff.

- 

## G4 Findings — Evidence Review

Assess the required evidence (evaluation spec §2.6): reasoning,
architecture explanation, modified subsystems inventory, testing
evidence, validation evidence.  Record credibility findings (vacuous
evidence, tautological tests, fabricated claims) and how they affected
the category scores.

- 

## Required Review Checklist

- [ ] Every rubric category has a score (0-100) entered
- [ ] Every category score cites at least one frozen evidence artifact
- [ ] G3 scenario script executed (g3-not-02.md) and findings recorded
- [ ] G4 evidence review completed (evidence credibility, evaluation spec §2.6)
- [ ] Deductions and uncertainty notes recorded where applicable
- [ ] Critical failure conditions reviewed (C1-C9, evaluation spec §2.3)

## Reviewer Instructions

1. Work from the frozen evidence in `evidence/` and the submission's
   diff bundle (`evidence/e8-diff-bundle/`) only — never the reference
   repository.
2. Score every category against the §2.4 anchors; a category without a
   score or without evidence citations will be rejected by the `score`
   command with a precise message.
3. Record deductions (what was subtracted and why) and uncertainty notes
   (where the evidence is ambiguous) per category.
4. A critical failure (C1-C9) in any category makes the run `INCORRECT`
   regardless of the total (harness spec §7.4 rule 1).
5. Do not modify this file after `score` completes the review; the
   recorded hashes pin the reviewed state (harness spec §7.5).
