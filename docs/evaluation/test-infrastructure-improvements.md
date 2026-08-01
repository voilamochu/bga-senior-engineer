# Test Infrastructure Improvements — pytest Temporary-Directory Retention

**Date:** 2026-08-01
**Scope:** pytest configuration, temporary-directory handling, and fixture hierarchy of the benchmark harness test suite (MS-09.5 follow-up, engineering quality only — no benchmark behavior, scoring, reports, validators, or user-facing behavior changed).

---

## 1. Root Cause Analysis

### 1.1 Why `/tmp/pytest-of-<user>` accumulated historical runs

The suite had **no pytest configuration at all** (`inifile: None` — no `pytest.ini`, no `pyproject.toml`, no `setup.cfg` anywhere in the repository), so pytest's stock defaults applied:

- **Default retention:** `tmp_path_retention_policy = all`, `tmp_path_retention_count = 3` — pytest keeps the **3 most recent sessions** under `<tempdir>/pytest-of-<user>/` regardless of whether the tests passed, and cleans older directories **only when the next session starts**.
- **Numbering never resets:** session directories are numbered `pytest-N` where N grows monotonically (`pytest-3413` observed), so a machine with many interrupted sessions looks like it retains hundreds of runs even though the directory count is capped at 3.
- **Interrupted sessions skip cleanup:** the pruning registered by `make_numbered_dir_with_cleanup` runs at session finish. Sessions killed mid-run (timeouts, SIGKILL, full disk) never prune, so their directories accumulate until a later successful session cleans them — and if the next session is also interrupted, the pile grows unbounded.
- **Read-only remnants defeat plain `rmtree`:** the harness freezes evidence trees and provisions workspaces with restrictive modes (files `0444`, directories `0555`, harness spec §6.3, §9.3). pytest's per-test teardown and session-basetemp removal use `shutil.rmtree(..., ignore_errors=True)`, which **silently leaves those frozen subtrees behind**. Under default retention the remnants survive inside the 3 retained sessions; with heavy integration sessions this reached ~28 MB per subset session and hundreds of MB per full session.
- **Full-disk incident:** the R2.5 sessions exhausted a 6.8 GB tmpfs `/tmp` (retained sessions + 2.1 GB of external debug scratch). With a full disk, even pytest's own pruning cannot always complete, compounding the pile.

### 1.2 Why failed cleanup was expected (and acceptable)

Under the default policy, "cleanup" only means "drop sessions older than 3" at the *next* session start. A fully-passing session leaves its directories behind by design. Nothing in the suite cleans up after itself at session level, and the read-only trees guarantee partial deletion even when pytest does attempt removal.

### 1.3 Fixture duplication

- **`git_repo` (function scope):** every test ran `git init -b main` + `git add` + `git commit` (3 subprocesses ≈ 16 ms) — measured 6.1× slower than copying a prebuilt repository. Used by 43 test files (~290 references).
- **`senior_root` (function scope):** recreated 5 directories with one file each per test; content is immutable (provisioning only ever copies from it).
- **Run/workspace/evidence fixtures** (`build_frozen_run`, `make_gated_run`, `reviewed_run`, `make_p4_run`, …): intentionally function-scoped — they produce *mutable* per-test state (run IDs, evidence catalogs with timestamps, frozen trees) and are the determinism tests' subject; sharing them would weaken the guarantees they verify.

---

## 2. Changes Made

### 2.1 `pytest.ini` (new, repository root)

```ini
[pytest]
tmp_path_retention_policy = failed
tmp_path_retention_count = 2
```

**Retained policy (documented in the file):**
- `failed` — per-test temporary directories are removed as each test passes; the whole session basetemp is removed when the session succeeds. Directories are retained **only when tests fail**, preserving the failed test's artifacts for debugging.
- `2` — at most the 2 most recent sessions are ever retained under the pytest temp root; anything older is pruned.

This uses pytest's own, version-native mechanisms (no behavior hacks); it is fully overridable on the command line or via `PYTEST_DEBUG_TEMPROOT`.

### 2.2 `conftest.py` (new, repository root)

Three session hooks complement pytest's policy:

1. **`pytest_sessionstart` — crash recovery pruning.** Sessions killed mid-run skip pytest's session-finish cleanup; this hook prunes the pytest temp root at the *start* of the next session, keeping the newest 2 sessions and removing everything older plus `garbage-*` leftovers. Lock files (`<dir>/.lock`) younger than pytest's own 3-day `LOCK_TIMEOUT` are respected — directories that look actively used by another pytest process are never touched.
2. **`pytest_sessionfinish` — read-only-aware completion.** After pytest's own cleanup ran, the session basetemp remainder (the harness's frozen `0444`/`0555` trees that pytest's `rmtree(ignore_errors=True)` cannot delete) is force-removed via `_force_rmtree` (chmod dirs/files owner-writable, then `rmtree`) when the session passed; the dead `pytest-current` symlink is removed; stragglers beyond the policy are pruned.
3. **`pytest_terminal_summary` — cleanup verification report.** Reports: stale sessions removed at start, whether the current session basetemp was removed, retained sessions, and a **working-tree check** (repository `git status --porcelain` compared before/after the session) proving no test leaked files — orphan workspaces, benchmark runs, or scratch output — into the repository.

`--basetemp` is honored: when given, pytest owns that directory and all hooks stand aside.

### 2.3 `tooling/harness/tests/conftest.py` — fixture hierarchy

- **`_git_repo_template` (new, session scope, immutable):** a pristine one-commit scratch repository created once per session.
- **`git_repo` (function scope, now a copy):** a deep copy of the session template per test. Byte-identical to a freshly initialized repository (same git environment, same single commit), so every test behaves exactly as before — including the ~15 tests that mutate their copy (tracked-file edits, new files, extra commits); the template stays pristine. Determinism tests are unaffected (they compare two runs created *within one test* from the same per-test copy, exactly as before).
- **`senior_root` (session scope):** immutable reference-material stand-in; provisioning only ever copies from it, so it is created once per session instead of once per test.
- Everything else remains function-scoped: benchmark runs, workspaces, and evidence trees are **mutable per-test state** and must be recreated per test. No shared mutable state was introduced.

---

## 3. Fixture Hierarchy Diagram

```
session  _git_repo_template ──┐  (immutable, one git init+commit per session)
function git_repo ────────────┴──┐  (deep copy per test; tests may mutate the copy)
function tmp_path ───────────────┤  (per-test; removed on pass by policy "failed")
session  senior_root ────────────┤  (immutable material stand-in)
function _scratch_material ──────┤  (per-test monkeypatch → senior_root, read-only)
function _full_flow / make_gated_run / reviewed_run / build_frozen_run / make_p4_run
         └── create run dir + manifest + status + protocol + evidence (mutable,
             per-test by necessity; subjects of the determinism tests)
session  tmp_path_factory ───────┴── session basetemp (removed when session passes)
```

---

## 4. Before / After

Measured on the same machine, same suite state (868 tests).

| Metric | Before (default pytest) | After (`failed` / 2 + hooks) |
|---|---|---|
| Retained temp after a passing subset run (58 heavy tests) | **28 MB** (`/tmp/pytest-of-mOCHU/pytest-0`) | **0 B** (root directory removed) |
| Retained sessions after a passing run | 3 (default `count=3`, policy `all`) | 0 (session basetemp removed; stragglers pruned to ≤ 2) |
| Full suite duration | 373.6 s (R2.6 baseline) | **369.4 s** |
| Full suite result | 868 passed | **868 passed** |
| Harness coverage | 96 % | **96 %** (9,344 lines) |
| Failed-test debugging | kept (all sessions) | kept — failed tests retain their session (≤ 2 sessions total); verified: failed test's `debug-artifact.txt` survives |
| Interrupted-session accumulation | unbounded until a successful session | pruned at next session start (keeps newest 2); verified with 9 simulated crashed sessions → 7 removed, 2 retained |
| Fresh-lock protection | n/a | verified: a session dir with a fresh `.lock` is never touched |
| Per-test scratch repo creation | 15.7 ms (3 git subprocesses) | **2.6 ms** (copytree) — 6.1× faster, ~250 calls per suite |
| `git init`/`git commit` subprocesses per suite | ~750–870 | ~3 (template only) |

Estimated steady-state `/tmp` usage: from hundreds of MB (3 × ~100–400 MB sessions plus read-only remnants) to **~0 after passing runs**, capped at 2 sessions (≈ tens of MB) after failing runs.

---

## 5. Cleanup Behavior

| Event | Behavior |
|---|---|
| Test passes | pytest removes its `tmp_path` (read-only remnants left by pytest's plain `rmtree` are reclaimed at session end) |
| Test fails | Its directory is retained intact for debugging |
| Session passes | Session basetemp force-removed (including frozen `0444`/`0555` evidence trees); dead `pytest-current` symlink removed; older sessions pruned to ≤ 2 |
| Session fails | Session basetemp retained (debugging); older sessions pruned to ≤ 2 |
| Session killed mid-run | Its directories remain; the *next* session start prunes to the newest 2 (lock-aware) |
| `--basetemp` given | All hooks stand aside; pytest owns the directory |

Session-end verification (printed in the terminal summary): stale sessions removed, session basetemp removed/retained, retained sessions, and the repository working-tree diff proving no test left files behind.

---

## 6. Verification

- Full suite: **868 passed, 0 failed** (unchanged).
- Coverage: **96 %** (unchanged).
- Determinism: all determinism tests pass (byte-identical run pairs, synthetic e2e determinism); the fixture changes produce byte-identical repositories per test.
- Cleanup verification section appears on every run; working tree unchanged by tests.
- Retention scenarios exercised: passing session (0 retained), failing session (retained with artifacts), 9 simulated crashed sessions (pruned to 2), fresh-lock protection (untouched), read-only remnant cleanup (frozen evidence trees removed).
- `bga-mercurio` remained completely read-only throughout; no files outside `bga-senior-engineer` were modified.

---

## 7. Remaining Opportunities

- **Per-test read-only remnants during the session.** pytest's per-test teardown cannot remove the harness's frozen trees (`rmtree(ignore_errors=True)`), so remnants accumulate *during* a session and are reclaimed only at session end. A pytest plugin could delete per-test dirs with the read-only-aware path, but that duplicates pytest internals; current behavior is bounded and correct.
- **`.pytest_cache`** in the repository is gitignored and tiny; if desired, `-p no:cacheprovider` could be added to `pytest.ini`.
- **A session-scoped "frozen run template"** (deep-copy of a gated run with regenerated volatile bits) could save the per-test gate executions (validator/php/node subprocesses ≈ 1–2 s each), but it would require regenerating evidence timestamps and would weaken the generation-determinism tests; deliberately not done.
- **`synthetic` scratch reference reuse** is already handled inside the synthetic runner (reuses an existing `synthetic-reference/` per runs root); left untouched per scope.
