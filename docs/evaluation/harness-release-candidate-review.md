# R2.5 — Harness Release Candidate Review

**Date:** 2026-08-01
**Scope:** The benchmark harness exactly as it exists today (MS-01 … MS-09 accepted and frozen; no MS-10 work performed).
**Method:** Static review of all 45 harness modules plus the four evaluation specifications; dynamic verification via `pytest` (full suite + coverage), two `synthetic` runs (passing and failing variants), a byte-determinism comparison of two identical synthetic runs, CLI dry-run/help inspection, and direct inspection of every generated artifact (prompt bundle, review package, reports, registry, leaderboard, evidence catalog, status/manifest).

This document is the R2.5 release-candidate gate before MS-10. No implementation changes were made during this review.

---

## 1. Summary of Verifications Performed

| Verification | Result |
|---|---|
| `pytest tooling/` (full suite) | 848–849 passed, 1 flaky failure (see MEDIUM-1) |
| Harness line coverage | 96% (8,990 lines, 381 missed) — exceeds the ≥ 80% MS-09 requirement |
| `synthetic` passing variant | Reaches `ARCHIVED`; verdict ACCEPTABLE, total 80.25; G0–G2 all PASS |
| `synthetic` failing variant | `REJECTED` at P5; blocking failures NOT02-A/NOT02-B identified in `validation.json`; no review scaffolded (correct) |
| Determinism (two identical runs) | Evidence, scores, reports, validation, review records byte-identical. Only `protocol/harness.log` and `protocol/session/session.json` differ (embedded runs-root paths) — see NIT-2 |
| Reference-repo safety | Every gate, provision, and baseline operation uses read-only commands; synthetic runs use a scratch reference; `bga-mercurio` untouched |
| Registry / leaderboard | `index.json` append-only with one entry; leaderboard entry present for VERDICTED, absent for REJECTED (correct) |
| Report validation | All 11 §8.2 sections and 8 §8.3 metadata groups present; validation passes on both variants |
| Scoring | §7.4 verdict order, §2.2 weak-band cap (50–59 → 85), non-blocking FC cap, and double computation all behave per spec |
| Environment | python 3.13.5, php 8.4.23, node v24.14.0, git 2.47.3 — all ≥ §4.2 minimums; `environment.json` carries §4.5 fields |
| Platform readiness | `opencode` 1.18.10 installed; `opencode run --format json <message>` and `opencode export <session-id>` flags match the adapter's usage |

---

## 2. Findings

### 2.1 Prompt Bundle (protocol/prompt-bundle.txt, protocol/prompt-bundle.json)

The bundle is well-structured and easy to inspect: clear `# ===` section separators, deterministic section order (SYSTEM PROMPT → BENCHMARK PROMPT → SKILL ARTIFACTS), immutable after P2 (0444 permissions, SHA-256 recorded in the manifest and in `prompt-bundle.sha256`). `prompt-bundle.json` is a machine-readable artifact index with per-file sizes and hashes. The bundle for NOT-02 is 1,313 lines — all of it task-relevant (system prompt, materialized benchmark prompt, skill prompt, mandatory rules, checklists, examples, references).

Findings:

- **NIT-1 — Double safety heading.** The BENCHMARK PROMPT's `## Repository Safety (MANDATORY)` heading is immediately followed by the safety section's own `## Repository Safety Protocol (MANDATORY)` heading, producing a section whose content starts with a same-level heading. Harmless but looks like a rendering mistake on first read. The duplicate content itself is per spec (Appendix B mandates the safety section; the system prompt also carries it).
- **NIT-2 (bundle-adjacent) — Attached-document paths are not anchored.** The `## Attached Documents` list gives repo-relative paths (`docs/evaluation/…`, `bga-senior-engineer-skill/rules/…`) without stating they resolve under `workspace/read/`. A capable agent will figure this out from the system prompt, but spelling it out would remove ambiguity.
- Nothing is missing from the bundle. The attached documents' *contents* are intentionally not embedded (spec §3.3 records the list, documents are read-only references); `opencode` sessions can read them.

### 2.2 Review Package (review/manual-review.md, review/onboarding.md, review/g3-not-02.md, review/review.json)

The package is complete and usable: rubric (family + weights, sum-checked against the pinned evaluation spec), frozen-evidence index with hash root, P5 validation summary, a five-category scoring table with citation/deduction/uncertainty/critical-failure columns, G3 script, G4 instructions, checklist, and reviewer instructions. The `review <run-id>` resume view (categories missing scores/citations) and `score`'s precise rejection messages make completion self-checking. Citations are validated against the frozen evidence catalog by `score`. Reviewer workload (G3 four steps + G4 + five category scores) is reasonable for NOT-02.

Findings:

- **LOW-1 — G3 script is hardcoded to NOT-02.** `review/kit.py` always renders `g3-not-02.md` regardless of task. Harmless while NOT-02 is the only skill-supported task; a different task would receive the wrong scenario script. Defer to v1.1.
- **MEDIUM-3 (first half) — No two-evaluator scoring path.** `score` records exactly one reviewer and then refuses a second pass (`--recompute` is restricted to arithmetic-error correction). The validation plan (§6.2) requires 100% double-scoring of pilot runs by two independent evaluators, and MS-10 acceptance condition 1 requires "double computation (two evaluators) matched or reconciled per validation plan §6.6". Today the second evaluator's scores cannot be recorded by the CLI; double-scoring must happen out-of-band. See also the dataset finding below.

### 2.3 Reports (reports/report.md, reports/evaluation-report.json)

`report.md` is pleasant to read: eleven numbered sections, deterministic, no wall-clock surprises. `evaluation-report.json` carries all §8.3 groups plus gates/review/errata extras. The two renderings derive from one in-memory `ReportData`, so they cannot diverge. Verification (section/group checklists) runs at generation time and again at archive verification.

Findings:

- **MEDIUM-2 — Evidence index contradicts Gate Results.** The frozen evidence catalog is captured at P4, before P5/P8 run, so its E4/E9 entries say `absent` with reasons "automatic validation (P5) has not run" and "reports (P8) have not run". The P8 report re-prints those P4-time reasons verbatim in §5, so every archived report says E4 and E9 are absent and "P5 has not run" while §4 shows all 11 checks passing. The P5 artifacts are appended to `evidence/reruns/e4/` with a hashed catalog, but neither the report nor the review kit references `evidence/reruns/`. For a first real benchmark this will confuse reviewers and could be misread as missing evidence. Required-before-MS-10 (see Recommendations): render the reruns presence (or at least re-word the absence reason as "recorded at P4 freeze; P5 artifacts in evidence/reruns/e4/").
- **LOW-2 — §2 "Run Status" shows the pre-archive status.** Reports are generated at P8, so an archived run's report says `VERDICTED`/`REJECTED` while `status.json` says `ARCHIVED`. §8.2 calls for the final status. Cosmetic; the report is a P8-time snapshot.
- **LOW-3 — §1 "Ended" uses the P7 `updated_at`.** Same root cause; the report's end time is the verdict time, not the P8/P9 time.

### 2.4 Archive (run directory, index.json, leaderboard)

The archive is the run directory itself plus `index.json` and `leaderboard/<tuple>/leaderboard.json`. Layout matches §9.1 exactly (verified against a synthetic run, including `protocol/baseline/workspace-baseline.diff`). Retrieval is easy: one directory per run, registry entry with verdict/total/hash, `archive --verify` re-checks marker, registry, leaderboard, evidence hashes, reports, and packaging. Registry and leaderboard are append-only and deterministic; REJECTED runs are correctly excluded from the leaderboard.

Findings:

- **BLOCKER-1 — The §13 final verification is not implemented.** Details in §2.6. This is the archive-phase defect that blocks MS-10.
- **NIT-3 — `evidence/reruns/e4/` is not part of the evidence index.** It is recorded only in `reruns/catalog.json`. Fine mechanically (frozen evidence is never altered), but it is the only place P5 validation lives in the evidence tree, and nothing in the review kit or report points to it.

### 2.5 CLI

The command sequence `init → prepare → prompt → session → collect --freeze → gates → review --scaffold → score → report → archive` mirrors the P0–P9 lifecycle, and every command enforces its predecessor with a precise error ("run 'prompt' first", "complete P4 with 'collect --freeze' first", etc.). Names are consistent verb forms; `run_id` positional and `--runs-root` option are uniform; help text is informative. Exit codes are sensible (e.g., `gates` returns 1 for BLOCKED, 0 for PASS/REJECTED). The `review <run-id>` resume view and `session --dry-run` are genuinely useful.

Findings:

- **LOW-4 — No operator `status` command.** To learn where a run stands, an operator must read `status.json` or run `review <run-id>` (which only works after scaffolding). Every other stage has a command; a `status` command is a natural addition for supervision duties (MS-10 runbook will need it).
- **LOW-5 — `init` does not validate the task ID.** `init NOT-01 demo-model` succeeds and the failure surfaces only at `prompt` ("no skill task associated with benchmark task"). An early corpus/skill check would catch typos at the cheapest point. (The error message itself is clear.)

### 2.6 Documentation

The four evaluation specifications are coherent and the implementation tracks them closely. The milestones document is the de-facto implementation doc; module docstrings are thorough. Findings:

- **BLOCKER-1 — Final verification (MVB-024) is missing.** The milestones document puts MVB-024 in MS-08 with deliverable `tooling/harness/safety/final_verify.py` and acceptance condition "Final verification records the four §13 items as pass; a fixture run with a modified reference repo fails verification and blocks `ARCHIVED`". MS-09 acceptance condition 4 requires "final verification passes"; implementation-plan exit criterion E6 requires the four §12 items to pass at P9; harness spec §13 makes the four-item confirmation the mandatory final section of every `report.md`. None of this exists:
  - `grep` for `final_verify|final_verification` finds nothing in `tooling/`.
  - `archive` (P9) never re-checks `bga-mercurio` and never records the four items.
  - `report.md` §10 explicitly states "Final §13 verification at P9 is outside the MS-08 scope (recorded by the MVB-024 final-verification work item)" — contradicting MS-08's own scope, which includes MVB-024.
  - The pilot-0 `safety` entry records only the synthetic G0 note, not the four-item confirmation (MVB-024 was specified to update it).
  
  This is the single largest discrepancy between the "MS-01…MS-09 complete and accepted" claim and the repository's actual state. It is a spec-required safety check on the reference repository at the last possible moment, it is required by MS-08/MS-09/MS-10 acceptance and by E6, and it is cheap (backlog estimate: 3h). See Recommendations.

- **HIGH-1 — The harness is not committed.** `git log -- tooling/harness` is empty; the entire MS-01…MS-09 implementation and `docs/evaluation/validation-dataset/pilot-0.json` are untracked files. The milestone plan's R2 gate is defined as "MS-09 merge", and MS-10 needs a reproducible, diffable baseline (the runbook references "runbook feedback notes" and postmortems against the implementation). The working tree functions, but the release candidate has no recorded baseline.
- **MEDIUM-4 — Operator configuration is undocumented.** `settings.json` (network policy, platform, model, runs_root, block_on_tool_version_mismatch) is described only in `config.py` docstrings; no doc mentions it. The MS-09 acceptance command `pytest tooling/ --cov=tooling.harness --cov-report=term` requires `pytest-cov`, which is not installed (the suite must be run via `coverage run -m pytest …`). A new operator would not know the config exists, and the documented acceptance command fails out of the box.
- **MEDIUM-5 — No runbook yet.** `tooling/harness/README.md` (operator runbook) is an explicit MS-10 deliverable (MVB-026), so its absence is scheduled, not a defect — but it means "execute a benchmark from documentation alone" is not possible today (see §4).

### 2.7 Operational Readiness

**Could someone who did not build this harness execute a benchmark today?**

Not from documentation alone. The pipeline works end to end (proven twice by the synthetic runs and by direct CLI inspection), and the CLI help is good, but:

1. There is no runbook or README anywhere in the repository that names the ten commands, the `--runs-root`/`--reference`/`--skill` defaults, the settings file, or the supervision duties (all scheduled for MS-10's MVB-026, but absent today).
2. The §13 final verification (BLOCKER-1) is a documented P9 duty that the harness does not perform — an operator following the spec would look for it in the report and not find it.
3. The configuration surface (`settings.json`) exists only in source.

With the implementer's knowledge, the sequence works cleanly. Without it, an engineer could still piece the pipeline together from the spec + milestones + `--help`, but not confidently, and they could not satisfy E6.

---

## 3. Finding Register

| ID | Severity | Area | Finding |
|---|---|---|---|
| BLOCKER-1 | BLOCKER | Archive / docs | §13 four-item final verification (MVB-024) not implemented; MS-08/09/10 acceptance and E6 require it |
| HIGH-1 | HIGH | Repository | Harness entirely uncommitted (0 commits); no R2/MS-10 baseline |
| MEDIUM-1 | MEDIUM | Tests | `test_two_fresh_runs_produce_byte_identical_bundles` is flaky (~50% in isolation) — `next()` over unordered `iterdir()` may pick the already-prompted run |
| MEDIUM-2 | MEDIUM | Reports | §5 Evidence Index prints P4-time "absent" reasons ("P5 has not run") contradicting §4; `evidence/reruns/e4/` never surfaced |
| MEDIUM-3 | MEDIUM | Review / dataset | No CLI path for a second evaluator's scores; validation-dataset `scoring_pairs` populated from the arithmetic double-computation, not inter-rater pairs (validation plan §6.2/§6.6) |
| MEDIUM-4 | MEDIUM | Docs / config | `settings.json` undocumented; MS-09 acceptance command needs uninstalled `pytest-cov` |
| MEDIUM-5 | MEDIUM | Docs | No runbook/README (scheduled for MS-10/MVB-026, but blocks documentation-only operation today) |
| LOW-1 | LOW | Review | G3 script hardcoded to NOT-02 in `kit.py` |
| LOW-2 | LOW | Reports | §2 shows pre-archive status in archived runs |
| LOW-3 | LOW | Reports | §1 "Ended" is the P7 verdict time |
| LOW-4 | LOW | CLI | No `status` command for operator inspection |
| LOW-5 | LOW | CLI | `init` accepts unknown task IDs; failure surfaces late at `prompt` |
| NIT-1 | NIT | Prompt bundle | Double "Repository Safety" heading in the materialized prompt |
| NIT-2 | NIT | Prompt bundle | Attached-document paths not anchored to `workspace/read/` |
| NIT-3 | NIT | Archive | `evidence/reruns/e4/` absent from every index/report reference |
| NIT-4 | NIT | Determinism | `session.json` + `harness.log` embed the runs-root path (byte-determinism holds only for identical roots; evidence/scores/reports unaffected) |
| NIT-5 | NIT | Status model | Checkpoint index lacks p6/p8 checkpoints (report data notes p6 only) |

---

## 4. Recommendations

### Required before MS-10

1. **Implement MVB-024 (BLOCKER-1).** Add `tooling/harness/safety/final_verify.py` per the backlog: re-run the three §12.3 checks against `bga-mercurio` plus the artifact-inventory check, record the four items pass/fail, block `ARCHIVED` on any failure, render the four-item confirmation as the §13 section of `report.md`, and record it in the validation-dataset `safety` entry. Backlog effort estimate is 3h. This closes MS-08 acceptance #5, MS-09 acceptance #4, MS-10 acceptance #3, and E6.
2. **Commit the harness (HIGH-1).** Land `tooling/harness/` and `docs/evaluation/validation-dataset/pilot-0.json` so the R2-approved baseline is reproducible and MS-10 is diffable.
3. **Fix the flaky test (MEDIUM-1).** In `test_prompt_cli.py::test_two_fresh_runs_produce_byte_identical_bundles`, select the newest run directory instead of `next()` over unordered `iterdir()` (the harness's own `next_sequence` is correct; the test's discovery is not). The MS-09 "suite is green" claim is not trustworthy while this fails intermittently.
4. **Fix the evidence-index contradiction (MEDIUM-2).** In the report (and review kit), either surface `evidence/reruns/e4/` (and e9 if added) or re-word the absence reasons to "recorded absent at P4 freeze; see evidence/reruns/e4/" so an archived report never claims "P5 has not run" when P5 ran. Small change in `report/data.py` / `review/kit.py`.
5. **Document the operator configuration (MEDIUM-4).** One page (or a section of the MS-10 runbook) covering `settings.json` keys, and either install `pytest-cov` or replace the acceptance command with `coverage run -m pytest …` in the milestones document.

### Safe to defer to v1.1 (or to the MS-10 runbook)

- MEDIUM-3 — Define the double-scoring workflow: either document an out-of-band second-evaluator pass recorded in the validation dataset (per validation plan §6.6), or add a paired `score` path. Must be settled before the MS-10 acceptance run's double-scoring, but it is a process/runbook decision, not a pipeline defect.
- MEDIUM-5 — Runbook is the MS-10 deliverable; nothing to add here beyond making sure it covers the items above.
- LOW-1 … LOW-5 and NIT-1 … NIT-5 — all safe to defer; none affect the first real benchmark's correctness. LOW-4 (status command) and LOW-5 (init task validation) are worth folding into the MS-10 runbook work if the operator reports friction.

### Explicitly out of scope / not recommended

- No architecture changes, no feature expansion, no redesign of the phase model or the evidence freeze design. The E4/E9-at-P4-absent + reruns design is spec-conformant; only the report's stale wording needs fixing.

---

## 5. Final Verdict

**NOT READY FOR MS-10**

Justification:

1. **BLOCKER-1** — The four-item final verification (harness spec §13, MVB-024) is not implemented anywhere in the harness, despite being an explicit MS-08 deliverable (`tooling/harness/safety/final_verify.py`), an MS-08/MS-09/MS-10 acceptance condition, and implementation-plan exit criterion E6. The final report even contains a written acknowledgment that the check was not performed ("outside the MS-08 scope"). The reference repository's integrity is therefore not mechanically verified at P9, and MS-10's acceptance conditions cannot be met as written.
2. **HIGH-1** — The entire harness is uncommitted; the "MS-09 merge" that R2 is defined on does not exist in version control.

Every other verified property supports readiness: the full pipeline executes end-to-end deterministically (passing and failing variants), evidence/scoring/reporting/archiving are hash-consistent and spec-conformant, coverage is 96% against the 80% requirement, and the reviewer-facing artifacts (review kit, reports, registry, leaderboard) are complete and clear once MEDIUM-2's wording is corrected.

Closing the two blocking items (BLOCKER-1 and HIGH-1) plus the four small required items (MEDIUM-1, MEDIUM-2, MEDIUM-4, and the runbook-scoped double-scoring decision) makes the harness ready for the first real benchmark. The single lowest-risk path to MS-10: implement MVB-024 (3h per backlog), commit the harness, fix the flaky test, and re-run this review's verification checklist.

---

## 6. Repository Verification

- **No files were modified outside bga-senior-engineer.** All harness operations during this review ran against scratch locations (`/var/tmp/…`): the synthetic runs, determinism comparison, and pytest runs (pytest `tmp_path` under `/var/tmp/pytest-of-mOCHU`). The only repository file touched by tooling was `.coverage` (a tracked artifact overwritten by the coverage run); it was restored with `git checkout`. `git status` shows only the pre-existing untracked entries (`tooling/harness/`, `docs/evaluation/validation-dataset/`).
- **bga-mercurio remained completely read-only.** This review never opened `bga-mercurio` for any operation; every harness execution used scratch git repositories (conftest fixtures, `synthetic-reference/`). Its pre-existing working-tree state (uncommitted changes from other development) is unchanged.
- **No implementation changes were made.** This review is analysis only; the sole deliverable is this document. No code was written, and no defect was fixed in place — including BLOCKER-1, which is recorded here for resolution before MS-10.
