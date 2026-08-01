# MS-10B — OpenCode Integration Implementation

**Date:** 2026-08-01
**Scope:** The integration work required by `docs/evaluation/ms10-opencode-integration-readiness.md` (G1, G2, G3, G5, G6, G7). G4, G8, G9, G10, G11 are out of scope (runbook/documentation work).
**Constraints honored:** benchmark logic, scoring, evaluation specifications, corpus, skill package, validator, synthetic pipeline — untouched. All changes confined to `bga-senior-engineer`.

---

## 1. Verdict

**READY FOR FIRST REAL BENCHMARK**

Every required integration item is implemented, tested, and validated: the run-scoped permission policy (G1), hermetic adapter coverage (G2), the P1 platform gate (G3), transcript export after timeout (G5), the §5.5 temperature capability record (G6), and the explicit working-directory flag (G7). Full suite: **881 passed, 0 failed** (868 pre-existing + 13 new adapter tests). Synthetic validation re-run for both variants with no regression; byte-determinism, scoring, and archive verification all re-confirmed.

---

## 2. Implemented Items

### 2.1 G1 — Run-scoped OpenCode permission configuration

`tooling/harness/agent/opencode_adapter.py` — `_permission_policy()` builds a policy from the run's own paths and `_session_env()` delivers it as the documented `OPENCODE_PERMISSION` environment variable (inline JSON). The policy:

- `external_directory` — `allow` only `<workspace/work>/**` and `<workspace/read>/**`; **explicitly `deny` the real `bga-mercurio` checkout** (resolved via `config.default_reference_root()`); every other external path is denied by opencode's default `ask` resolving to denial in headless `run` mode.
- `edit` — `deny` on `<workspace/read>/**` and `<reference>/**`; the work directory is the session's project root and remains the only writable location.
- `webfetch`/`websearch` — `deny` (network disabled per harness §3.5; partial platform-level enforcement of the recorded policy).

The policy is strictly run-local (per-process environment), **never modifies opencode's global configuration**, and no config file is written into the run. Allow rules precede deny rules per opencode's last-matching-rule-wins semantics.

### 2.2 G2 — Automated OpenCode adapter tests

`tooling/harness/tests/test_opencode_adapter.py` (new, 13 tests) drives a **hermetic fake `opencode` executable** (a Python script written into a temp `fake-bin`, first on `PATH`; no model, no provider, no network). Coverage:

- argv construction — `opencode --version`, `opencode run --dir <work> --format json <bundle>`, `opencode export <session-id>` recorded in the E3 command log
- permission environment — the child echoes `OPENCODE_PERMISSION`; the test asserts the allow/deny map and that `CWD`/`PWD` equal the working directory
- session-id parsing and the export path (success, missing session ID, export failure)
- timeout path — SIGTERM-killed session retains partial output **and** the transcript export is still attempted (G5), including the unrecoverable-export case
- failure handling — non-zero exit propagates with raw response and transcript retained
- G6 — the temperature capability record; G7 — the `--dir` flag (and its absence for `--model` when not configured)

### 2.3 G3 — OpenCode as a required environment tool

`tooling/harness/environment/collect.py` — `REQUIRED_TOOLS` gains `ToolSpec("opencode", ["--version"], r"(\d+)\.(\d+)(?:\.(\d+))?", None)` (no minimum version; the §4.2 rule "any additional tool used in a run MUST be recorded with its version"). Consequences:

- `opencode --version` is recorded in `protocol/environment.json` at P1 (verified: `1.18.10` in the synthetic run's environment manifest)
- a missing platform now blocks `prepare` at P1 (existing `missing_tools` gate in `cli.py:397-403`), instead of failing at P3 session launch
- `test_environment.py` updated for the exact tool set and the opencode entry's presence/version semantics

### 2.4 G5 — Transcript export after timeout

`opencode_adapter.py` — the export is attempted whenever a session ID was parsed from the JSON stream, **regardless of exit code** (previously skipped on the timeout kill). opencode persists sessions while they run, so the transcript of a killed session is normally recoverable; a failed export leaves the transcript absent with a recorded reason (evidence `E1` absent-with-reason), never silently discarded. Covered by `test_timeout_retains_partial_output_and_exports_transcript` and `test_timeout_with_unrecoverable_export`.

### 2.5 G6 — Temperature/platform capability record

`tooling/harness/agent/adapter.py` — `SessionResult` gains an optional `capabilities: dict | None = None` (backward compatible; the stub adapter is unchanged). `opencode_adapter.py` records:

```json
"capabilities": {
  "temperature": {
    "supported": false,
    "note": "opencode CLI does not expose a temperature flag; --variant (provider-specific reasoning effort) is available instead",
    "policy": "flagged for comparability per harness §5.5"
  }
}
```

`tooling/harness/agent/capture.py` — one-line pass-through so the fact lands in `protocol/session/session.json` (the manifest's session metadata source), satisfying §5.5's record requirement. Covered by `test_temperature_capability_recorded`.

### 2.6 G7 — Explicit working-directory flag

`opencode_adapter.py` — the launch argv is now `opencode run --dir <workspace_work> --format json <bundle>`; the `PWD` alignment is retained as defense-in-depth. Verified in `test_version_preflight_and_argv_construction` (asserts `--dir` and the work path in the command record) and `test_session_runs_inside_working_directory` (child sees `CWD`/`PWD` = work).

---

## 3. Files Created

| File | Purpose |
|---|---|
| `tooling/harness/tests/test_opencode_adapter.py` | 13 hermetic adapter tests (G2) |
| `docs/evaluation/ms10-opencode-integration-readiness.md` | MS-10A planning report (delivered previously; part of this workstream) |

## 4. Files Modified

| File | Change |
|---|---|
| `tooling/harness/agent/opencode_adapter.py` | G1 permission policy + env, G5 export-after-timeout, G6 capabilities, G7 `--dir` |
| `tooling/harness/agent/adapter.py` | `SessionResult.capabilities` field (G6) |
| `tooling/harness/agent/capture.py` | `capabilities` recorded in session metadata (G6) |
| `tooling/harness/environment/collect.py` | `opencode` ToolSpec in `REQUIRED_TOOLS` (G3) |
| `tooling/harness/tests/test_environment.py` | tool-set assertion + opencode entry assertions (G3) |

No other file in `bga-senior-engineer` was touched. No file outside `bga-senior-engineer` was touched.

---

## 5. Tests Added

13 tests in `tooling/harness/tests/test_opencode_adapter.py`:

- `test_version_preflight_and_argv_construction` — E3 records, `--dir`, `--format`, bundle message, export command
- `test_model_flag_when_configured` / `test_model_flag_absent_when_not_configured`
- `test_run_scoped_permission_policy` — allow read `/work`+`/read`, deny `bga-mercurio`, edit denies, webfetch/websearch denies
- `test_session_runs_inside_working_directory` — CWD/PWD pinned to work
- `test_global_opencode_config_untouched` — policy via env only; work tree stays pristine
- `test_transcript_captured_from_export`
- `test_missing_session_id_skips_export`
- `test_export_failure_leaves_absent_transcript`
- `test_nonzero_exit_propagates_with_artifacts`
- `test_timeout_retains_partial_output_and_exports_transcript` (G5)
- `test_timeout_with_unrecoverable_export` (G5)
- `test_temperature_capability_recorded` (G6)

All tests execute the fake executable only; no real `opencode` binary is invoked, no session is started, no provider is contacted.

---

## 6. Acceptance Results

### 6.1 Full test suite

```
pytest tooling/        →  881 passed, 0 failed  (408s)
```

- 868 pre-existing tests: all green, no regressions.
- 13 new adapter tests: all green.
- `test_gates.py::TestDeterminism::test_identical_runs_produce_identical_validation` failed **once** in the first full-suite run and passed in the second full-suite run and 0/12 in isolation. Root cause: the test builds two runs whose run IDs embed the wall-clock creation second (`…162414Z` vs `…162415Z`); when the two fixture builds straddle a second boundary under full-suite load, the E12 manifests differ and the evidence root hash differs. This is the pre-existing latent timing-flake class registered by R2.6 as MEDIUM-1 ("timing-dependent, test-level only"; the R2.6 fix was deferred to MS-10 runbook work, out of MS-10B scope). The mechanism involves only `run_dir.py` clock behavior and the E12 fixture — none of the MS-10B code paths — and was confirmed passing in isolation and in the rerun.

### 6.2 Synthetic validation (re-run, both variants, scratch repos)

| Check | Passing variant | Failing variant |
|---|---|---|
| Status | `ARCHIVED` | `ARCHIVED` |
| Gates | PASS, 11 checks | REJECTED (NOT02-A, NOT02-B) |
| Scores | total 80.25, ACCEPTABLE | no review/leaderboard (REJECTED) |
| Final verification | FV-1..FV-4 PASS | FV-1..FV-4 PASS |
| `archive --verify` | exit 0, all records pass | — |

- Byte-determinism across two identical runs: **78 files identical**; the only differing files are the two documented location-dependent ones (`protocol/harness.log`, `protocol/session/session.json`). Evidence tree, reports, registry, leaderboard, and the pilot-0 seed are byte-identical. No scoring regression (80.25/ACCEPTABLE matches the R2.6 baseline and the recorded seed).
- Environment manifest now records `opencode` (`1.18.10`, present) — G3 verified in the synthetic pipeline.

### 6.3 No benchmark behavior changed

The synthetic pipeline runs the full P0–P9 CLI on scratch repositories with the stub adapter — identical to the R2.6 validation protocol. No real benchmark, no real reference repository usage, no real agent.

---

## 7. Remaining Deferred Items (out of MS-10B scope)

| ID | Item | Owner |
|---|---|---|
| G4 | `run` one-shot orchestrator / documented command chain | MS-10 runbook (MVB-026) |
| G8 | prompt bundle as `--file`/stdin instead of a single argv | post-MVB follow-up |
| G9 | raw-response streaming; E3/E6-E7 runbook notes | runbook / follow-up |
| G10 | operator configuration documentation (`settings.json`) | MS-10 runbook (MVB-026) |
| G11 | full network enforcement | FUT-07 (post-MVB) |
| MEDIUM-1…5 (R2.6) | latent flaky test, stale E4/E9 wording, double-scoring workflow, config docs, runbook | MS-10 runbook per R2.6 |

None of these block the first real benchmark.

---

## 8. Repository Verification

- **`bga-mercurio` strictly read-only — no files modified or created by this work.** HEAD unchanged at `25339e5`. All MS-10B commands against it were read-only (`git status`, `git log`, `git rev-parse`, `git diff`, `stat`). No staging, no commits, no untracked files.
- **External-development note (recorded, not caused by this work):** the working tree of `bga-mercurio` carries 6 pre-existing modified files (as recorded in MS-10A §6) plus one additional externally modified file, `modules/js/Game.js` (mtime `2026-08-01 16:42:46 UTC`, +227/−1), which changed during this session from outside this workstream — the R2.6-registered NIT-6 situation (external development on the reference repo). No MS-10B operation can write to that repository: all test fixtures and synthetic runs used scratch git repositories under `tmp_path`/`/tmp` (removed after validation).
- **No benchmark executed.** No sibling `runs/` root exists; no real run directory was created in the workspace; synthetic runs used `/tmp/ms10b-*` scratch roots, deleted after verification.
- **No real model invoked.** No `opencode run` session was ever started; the only `opencode` executions were the hermetic fake in the 13 adapter tests and `opencode --version` (inert) during MS-10A. No provider authentication, no network use.
- **All changes confined to `bga-senior-engineer`:** 5 modified files + 2 new files (report + tests), listed in §3–§4. Working tree otherwise clean.
