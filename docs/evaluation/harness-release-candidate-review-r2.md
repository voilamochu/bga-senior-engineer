# R2.6 — Harness Release Candidate Re-Review

**Date:** 2026-08-01
**Scope:** The entire benchmark harness as it exists today (MS-01 … MS-09 complete, MS-09.5 resolution of BLOCKER-1 in the working tree, MS-10 not started).
**Method:** Fresh review. Every R2.5 verification was repeated: full test suite with coverage, both synthetic variants, byte-determinism comparison, blocked-archive CLI demonstration, artifact inspection (prompt bundle, review package, reports, evidence catalog, archive, registry, leaderboard, dataset seed), CLI help, documentation cross-check against the five evaluation documents, and repository-safety verification. No implementation changes were made during this review.

---

## 1. Executive Summary

The single BLOCKER from R2.5 — the missing MVB-024 §13 final verification — is **resolved and verified**: the four items execute at P9 immediately before `ARCHIVED`, any failure blocks archival and leaves the run in its pre-archive state, `report.md` carries the completed §13 section, the validation-dataset seed records the results, and the record is persisted through the sanctioned manifest errata channel. The resolution is exercised by 19 new tests and re-confirmed end-to-end during this review, including a fresh blocked-archive demonstration.

The suite is green (868 passed, 0 failed; 96% coverage vs the ≥ 80% requirement), both synthetic variants reach `ARCHIVED`, byte-determinism holds across identical runs (78 files, only the two documented location-dependent files differ), and `archive --verify` passes including the new final-verification record check.

All previously reported HIGH/MEDIUM/LOW/NIT findings were re-evaluated from scratch. No new BLOCKER exists. Five MEDIUM issues from R2.5 remain (all pre-existing, none MS-09.5 scope, none preventing MS-10): a latent flaky acceptance test, the stale E4/E9 wording in report §5 and the review kit, the missing two-evaluator scoring workflow, undocumented operator configuration, and the absent runbook (scheduled as the MS-10 deliverable itself).

**Verdict: READY FOR MS-10 WITH MINOR ISSUES.**

---

## 2. Verification Matrix

| # | Verification (fresh, this session) | Result |
|---|---|---|
| 1 | `pytest tooling/` full suite | 868 passed, 0 failed (373s) |
| 2 | Harness line coverage | 96% (9,338 lines, 393 missed) — ≥ 80% requirement met |
| 3 | `synthetic` passing variant | `ARCHIVED`; verdict ACCEPTABLE (80.25); G0–G2 PASS; FV-1..FV-4 PASS; `archive --verify` exit 0 |
| 4 | `synthetic` failing variant | `ARCHIVED`; REJECTED at P5; blocking NOT02-A/NOT02-B; no review/leaderboard; final verification still PASS |
| 5 | Byte-determinism (two identical runs) | 78 files compared: only `protocol/harness.log` and `protocol/session/session.json` differ (documented runs-root paths); evidence, reports, final-verification record, registry, leaderboard, seed byte-identical |
| 6 | Blocked archive (fresh CLI demo) | Tampered tracked file → `archive` exit 1, "final verification failed … FV-1 FAIL"; run stays `VERDICTED`, no marker, no registry entry; after restoring the reference → exit 0, `ARCHIVED`, §13 PASS in report, `archive --verify` exit 0 |
| 7 | §13 record (passing run) | `validation/final-verification.json`: schema `benchmark-harness-final-verification/1.0`, all four items PASS, deterministic |
| 8 | §13 record (failing run) | Present and passed; report §10 renders the four items |
| 9 | Report §10 | "Final verification (§13): PASS" with FV-1..FV-4 lines; "outside the MS-08 scope" remnant gone (grep over `tooling/harness/` clean) |
| 10 | Manifest errata | "P9 final verification: passed=True (FV-1=PASS, FV-2=PASS, FV-3=PASS, FV-4=PASS) record sha256=… reference_head=…" recorded post-freeze; P9 report regeneration recorded as a second "P8 reports generated" entry |
| 11 | Validation dataset | `safety.final_verification` records the four items (pass/fail per item) |
| 12 | Previously flaky test (MEDIUM-1) | Passed 10/10 in isolation this session and in the full suite; code unchanged (see register) |
| 13 | Evidence catalog / environment / scoring | Unchanged from R2.5: E1–E12 catalog with hashes, §4.5 environment fields, §7.4 verdict order + §2.2 caps, double computation matched |
| 14 | Registry / leaderboard | Append-only; VERDICTED entry with verdict/total; REJECTED excluded from leaderboard |
| 15 | Platform | opencode 1.18.10 installed; adapter flags (`run --format json <msg>`, `export <id>`) valid |
| 16 | Reference safety | Every gate/provision/verification uses read-only git commands on scratch repos; `bga-mercurio` untouched (see §7) |

---

## 3. Updated Finding Register

### 3.1 BLOCKER-1 — §13 final verification (MVB-024): **RESOLVED**

Verified against all five documents, not just code presence:

- **Harness spec §13** — the four items are implemented as FV-1 (no modified files: `git status --porcelain` tracked lines vs P0 baseline), FV-2 (no created files: untracked `??` lines vs baseline), FV-3 (no git metadata changes: HEAD + reflog top vs baseline), FV-4 (artifact inventory: canonical §9.1 layout, no caches/temp). The four items are the mandatory final section of `report.md` (§10, populated from the recorded results at P9).
- **Implementation backlog MVB-024** — deliverable `tooling/harness/safety/final_verify.py` exists; "all four items recorded as pass/fail; any failure blocks ARCHIVED" holds (BLOCKED verdicts on missing baseline/repository, never silent); the four-item confirmation is recorded in the report and in the validation-dataset seed `safety` entry.
- **Implementation plan** — P9 row ("Archive; registry append; leaderboard entry; final verification (§13)") and exit criterion E6 satisfied.
- **Milestones** — MS-08 acceptance #5 (fixture run with modified reference blocks ARCHIVED) covered by integration tests; MS-09 acceptance #4 exercised by the synthetic runner's `_verify_run`; MS-10 acceptance #3 now satisfiable.
- Behavioral evidence: fresh blocked-archive demo (matrix row 6), 19 dedicated tests, deterministic record, verify_archive record check, REJECTED-run archival with verification, retry-after-restore path.

Minor notes (non-blocking, recorded for the record): failed attempts persist a FAILED record while the (unregenerated) report still says "not yet performed" — acceptable since the report is a P8-time snapshot; a blocked item records `reference_head=None` in the errata message.

### 3.2 R2.5 finding statuses

| ID | R2.5 severity | Status now | Evidence |
|---|---|---|---|
| BLOCKER-1 | BLOCKER | **RESOLVED** | §3.1 above |
| HIGH-1 | HIGH | **RESOLVED** | Commit `7010770` lands the entire harness, the R2.5 review, and the pilot-0 seed. Follow-up: the MS-09.5 resolution itself (final_verify.py + 9 modified files) is not yet committed; commit before the MS-10 baseline freeze (recommendation, not a defect) |
| MEDIUM-1 | MEDIUM | **STILL PRESENT (latent)** | `test_two_fresh_runs_produce_byte_identical_bundles` code unchanged: run discovery via `next()` over unordered `iterdir()` can select an already-prompted run when two `init` calls straddle a second boundary. Passed 10/10 in isolation and in the full suite this session; failed ~50% in R2.5 sessions. Timing-dependent, test-level only; the harness's own `next_sequence` is correct |
| MEDIUM-2 | MEDIUM | **STILL PRESENT** | Fresh archived run's report §5 still renders "E4: absent (automatic validation (P5) has not run)" and "E9: absent (reports (P8) have not run)" while §4 shows 11 checks passing and §10 shows the §13 verification passing; `manual-review.md` carries the same stale wording; `evidence/reruns/e4/` never surfaced. Not in MS-09.5 scope |
| MEDIUM-3 | MEDIUM | **STILL PRESENT** | `score` records exactly one reviewer and refuses a second pass (`--recompute` is arithmetic-error only); validation-plan §6.2 requires two-evaluator double-scoring (100% of pilot runs) and MS-10 acceptance #1 requires reconciliation per §6.6; the seed's `scoring_pairs` is populated from the arithmetic double-computation, not inter-rater pairs. Workflow decision belongs to the MS-10 runbook |
| MEDIUM-4 | MEDIUM | **STILL PRESENT** | `settings.json` options documented only in `config.py`; the milestones-documented acceptance command `pytest tooling/ --cov=tooling.harness --cov-report=term` still fails out of the box (`import pytest_cov` fails; the suite runs via `coverage run -m pytest`) |
| MEDIUM-5 | MEDIUM | **STILL PRESENT** | `tooling/harness/README.md` (runbook) absent; it is the MS-10/MVB-026 deliverable, so absence is scheduled, not defective |
| LOW-1 | LOW | STILL PRESENT | G3 script hardcoded to NOT-02 in `review/kit.py` |
| LOW-2 | LOW | STILL PRESENT | Report §2 shows pre-archive status (VERDICTED/REJECTED) in archived runs; the P9-regenerated report still renders the pre-archive status (report is a P8-time snapshot by design) |
| LOW-3 | LOW | STILL PRESENT | Report §1 "Ended" is the P7 verdict time |
| LOW-4 | LOW | STILL PRESENT | No operator `status` command |
| LOW-5 | LOW | STILL PRESENT | `init` accepts unknown task IDs; failure surfaces at `prompt` |
| NIT-1 | NIT | STILL PRESENT | Double "Repository Safety" heading in the materialized prompt (lines 117–118 of the bundle) |
| NIT-2 | NIT | STILL PRESENT | Attached-document paths not anchored to `workspace/read/` |
| NIT-3 | NIT | STILL PRESENT | `evidence/reruns/e4/` absent from every index/report reference (tied to MEDIUM-2) |
| NIT-4 | NIT | STILL PRESENT (documented) | `session.json`/`harness.log` embed runs-root paths; explicitly covered by a determinism test asserting the documented exception |
| NIT-5 | NIT | STILL PRESENT | Checkpoint index lacks p6/p8 checkpoints (p8 absence still unacknowledged by the report data note, which mentions only p6) |

### 3.3 New observations from the MS-09.5 resolution (all NIT-class)

- **NIT-6** — `archive` (P9) now depends on the original reference checkout: a run whose reference repo has legitimately moved on (external development) blocks archival until re-baselined per §12.4. Correct behavior; needs a runbook note in MS-10.
- **NIT-7** — the synthetic runner's `_verify_run` reads `report.md` before checking its existence (a missing report raises instead of appending a divergence). Cosmetic; unreachable in the synthetic flow.
- **NIT-8** — errata history now carries two "P8 reports generated" entries (P8 and the P9 regeneration); `verify_archive` correctly compares against the last. Consistent with the append-only model, but §11 of the report filters both, so readers see neither — acceptable.

---

## 4. Comparison with R2.5

| Dimension | R2.5 | R2.6 |
|---|---|---|
| Verdict | NOT READY FOR MS-10 | READY FOR MS-10 WITH MINOR ISSUES |
| Blocking defects | 1 (missing §13 final verification) | 0 |
| Final verification (§13) | Absent; report §10 contained a written out-of-scope disclaimer | Implemented, recorded, reported, seed-integrated, archive-blocking; disclaimer gone |
| Test suite | 848–849 passed, 1 flaky | 868 passed, 0 failed (19 new tests) |
| Coverage | 96% | 96% |
| Determinism | evidence/scores/reports byte-identical | unchanged, now including the final-verification record and regenerated report |
| Baseline | harness uncommitted | harness committed (`7010770`); MS-09.5 deltas uncommitted |
| Everything else | MEDIUM-1…5, LOW-1…5, NIT-1…5 as registered | unchanged (all still present, none blocking) |

No R2.5 finding regressed; nothing previously resolved became unresolved; no new MEDIUM+ findings introduced by the resolution.

---

## 5. Release Readiness Assessment

**MS-10 prerequisite (final verification, E6): met.** The four-item §13 check executes at P9, blocks `ARCHIVED` on failure, is recorded in the manifest errata, the report's mandatory final section, the archive verification, and the validation dataset.

**MS-10 acceptance walkthrough against the current state:**

1. Run reaches ARCHIVED with a Score-Calculator verdict — pipeline proven by both synthetic variants; double-evaluator reconciliation remains a runbook-defined process (MEDIUM-3).
2. Gates executed and recorded; manual-review.md citations enforced by `score` — unchanged, green.
3. Four-item final verification + artifact inventory — **now satisfied** (the R2.5 blocker).
4. Registry/leaderboard + postmortem — registry/leaderboard verified; postmortem is an MS-10 artifact.
5. Second-operator runbook execution — runbook is the MS-10 deliverable (MEDIUM-5, scheduled).
6. E1–E7 — E6 now satisfied; E7 green suite (modulo the latent flake, MEDIUM-1).

**What would make the remaining minor issues disappear:** commit the MS-09.5 delta (follow-up to HIGH-1); fix the test's run-discovery bug (5-minute change, MEDIUM-1); re-word the E4/E9 absence reasons in the report and review kit (MEDIUM-2); document `settings.json` and swap the acceptance command to `coverage run -m pytest` or install `pytest-cov` (MEDIUM-4); settle the double-scoring workflow in the runbook (MEDIUM-3). None of these block the first real benchmark; all are either MS-10-scoped deliverables or small, safe fixes.

---

## 6. Recommendation

Proceed to MS-10. Before the acceptance run:

1. Commit the MS-09.5 resolution so the R2.6-approved baseline is reproducible (mirrors the R2.5 HIGH-1 rationale).
2. Fold MEDIUM-1, MEDIUM-2, MEDIUM-3, and MEDIUM-4 into the MS-10 runbook work (they are runbook/process items or small fixes; the runbook is being written in MS-10 anyway).
3. Do not expand scope: no architecture changes, no feature work beyond the MS-10 milestones.

---

## 7. Repository Verification

- **bga-mercurio remained completely read-only.** Unchanged: same HEAD (`25339e5`), same 6 pre-existing working-tree entries as before this session. Every harness operation in this review used scratch git repositories (`/var/tmp/r26-*`, pytest `tmp_path` under `/var/tmp/pytest-of-mOCHU`).
- **No files were modified outside bga-senior-engineer.** All runs, demos, and test executions wrote only to `/var/tmp` scratch roots; the sole tracked-file artifact touched by tooling (`.coverage`) was restored immediately after the coverage run.
- **Only the review document was created.** This review created exactly one file: `docs/evaluation/harness-release-candidate-review-r2.md`. The working tree's MS-09.5 modifications (final_verify.py + 9 files) pre-date this review and were left untouched.

---

## 8. Final Verdict

**READY FOR MS-10 WITH MINOR ISSUES**
