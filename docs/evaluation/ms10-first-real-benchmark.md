# MS-10 — First Real Benchmark Run

**Date:** 2026-08-01
**Task:** NOT-02 — Consolidate Duplicated Notification Blocks (Easy / 1–2h)
**Platform:** OpenCode 1.18.10 (real, headless `opencode run --format json`)
**Designated model:** `opencode-go/deepseek-v4-pro` (OpenCode Go provider), temperature not settable via CLI — §5.5 capability recorded in session metadata (flagged for comparability)
**Run root:** `/tmp/ms10-runs` (outside both repositories, per harness §4.1; POSIX filesystem, since `/mnt/c` cannot enforce read-only modes)

---

## 1. Verdict

**REJECTED at G0 (repository safety), caused exclusively by external concurrent development of the reference repository — not by the benchmark agent.**

The run executed P0–P8 faithfully with the real OpenCode platform. The agent session completed in 11.4 minutes and produced a complete, high-quality submission (verified below). G0 correctly detected that `bga-mercurio`'s working tree at P5 differed from the P0 baseline — but the difference (newly modified `modules/js/Game.js` at 17:23:55, `mercurio.css` at 17:24:04) was introduced by an external process **after the session ended (~17:21)**, with additional external writes landing during the archive attempt itself (17:24, 17:31). Archive (P9) is blocked by FV-1 for the same cause. The frozen harness has no sanctioned §12.4 re-baseline path (`REJECTED` is terminal), so the run's recorded outcome stands.

**This is a true protocol outcome:** G0 exists precisely to reject runs whose reference state is not pristine, and the harness executed it correctly. The milestone's operative finding is that **the reference repository is not stable enough for benchmarking while under active external modification**.

---

## 2. Run Identification

| Field | Value |
|---|---|
| Run ID | `run-NOT-02-opencode-go-deepseek-v4-pro-20260801T170714Z-00` |
| Task | NOT-02 (Notification, Easy, effort 1–2h) |
| Model | `opencode-go/deepseek-v4-pro` (recorded in manifest; launch `--model`) |
| OpenCode version | 1.18.10 (`opencode --version` at P3, recorded in session metadata) |
| Prompt bundle | SHA-256 `038d8108ad19ee62540e15280415e504a6b4fd60752ffe157a0eead069071794` (65,090 bytes, immutable) |
| Skill task resolved | `migrate-notifications` |
| Reference HEAD | `25339e513cb833d262a934d6242c02ad3f3a163b` (recorded at P0) |
| P3 budget | 10,800 s (effort 1–2h × 1.5, §5.2) |
| Session duration | 682.9 s (11.4 min) — well within budget |
| Session exit | exit 0, `completed`, no restarts |
| Phases executed | P0, P1, P2, P3, P4, P5 (P6–P8 N/A for REJECTED; P8 report generated; P9 blocked) |

---

## 3. Benchmark Verdict and Score Breakdown

- **Verdict: REJECTED** (G0 blocking failure — repository safety, harness §7.2/§12.3).
- **Score breakdown: not computed.** REJECTED runs are not reviewed, not scored, and not placed on the leaderboard (harness §7.2; verified by the synthetic failing variant). The report generator confirms: "Not scored: the run was rejected at P5".
- The rejection is **not** a verdict on the submission's quality — it is a repository-integrity gate. An observational (non-recorded) assessment of the submission follows in §7.

---

## 4. Gate Results (P5)

| Gate | Result | Detail |
|---|---|---|
| G0 — Repository safety | **FAIL** (1 check) | `status_porcelain` at P5 ≠ P0 baseline: `mercurio.css` added to the modified set |
| G1 — Build gates | NOT_RUN | Short-circuited by G0 rejection (by design) |
| G2 — Catalog checks | NOT_RUN | Short-circuited by G0 rejection (by design) |
| Summary | REJECTED | blocking failures: G0 |

**G0 failure analysis (timestamps):**

| File | mtime | Relation to run |
|---|---|---|
| `modules/js/Game.js` | 16:42:46 | modified **before** run start (16:56); already in the P0 baseline |
| `modules/js/Game.js` | 17:23:55 | ~3 min **after** session end (~17:21) — external |
| `mercurio.css` | 17:24:04 | ~3 min **after** session end — external |
| `docs/testing/MERCURIO_AI_TESTING_AND_GAMEPLAY_GUIDE.md` | 17:31:27 | **during** the archive attempt — external |

The P0 baseline (17:07:27) recorded 7 pre-existing modified files. By P5 (17:27:31) there were 8 (`mercurio.css` new); by 17:31 there were 9, and the working tree hash changed again during report preparation (17:33). HEAD never moved (`25339e5` throughout). The external process writes to `bga-mercurio` repeatedly and unpredictably; the harness's G0 and FV-1 checks fired correctly on a repository that was legitimately moving under the run — the harness §12.4 concurrency case (external development).

**Agent safety verification (independent of G0):** the full session transcript (E1 raw NDJSON, 250 events, 13 bash commands, 21 edits/writes) was audited: zero git commands; every write and edit inside `workspace/work/`; all reads of `workspace/read/` were read-only (`cp`/`diff`/`grep`/`ls`); no write, chmod, or delete touched `bga-mercurio` or any read-only path. The agent is verified repository-safe.

---

## 5. Evidence Summary (P4, frozen)

Frozen at P4, root hash `f296a33ada68422089e2199a60f2e6f119021f25eb9c514999ae0efc2d5425c0`, 19 artifacts:

| ID | Status | Content |
|---|---|---|
| E1 | present | `transcript.txt` (opencode export) + `raw-response.txt` (650 KB NDJSON event stream) |
| E2 | present | `work/` copy: 11 files (submission + staged copies) |
| E3 | present | `command.log` — 3 harness commands (version, run, export) |
| E4 | absent (recorded) | validation logs — N/A (P5 rejected at G0 before check execution) |
| E5 | present | phase times (P0–P4) |
| E6/E7 | absent (recorded) | token/cost metrics not extracted (opencode export carries usage; not surfaced — NIT-4 class) |
| E8 | present | `e8-diff-bundle` — `Game.diff` + `Game.php` (modified file) |
| E9 | absent (recorded) | interim reports — P4-time snapshot, correct |
| E10 | absent (recorded) | no browser automation used |
| E11 | present | `environment.json` — python3 3.13.5, php 8.x, node v24.14.0, git, **opencode 1.18.10** (G3 from MS-10B operational) |
| E12 | present | manifest.json + status.json |

**Transcript fidelity finding:** `opencode export` output was truncated at ~65 KB (JSON cut mid-structure, exit 0) — an opencode 1.18.10 export limitation; the full session content is preserved in E1 `raw-response.txt` (complete NDJSON). Recorded for the runbook.

---

## 6. Archive Verification (P9)

`archive` **blocked**: final verification FV-1 FAIL — "git status --porcelain tracked changes differ from the P0 baseline: added ' M docs/testing/MERCURIO_AI_TESTING_AND_GAMEPLAY_GUIDE.md'; added ' M mercurio.css'". The run remains `REJECTED`, unarchived, with no registry/leaderboard entry — the harness's sanctioned behavior when the reference repository has moved (spec §12.4 / R2.6 NIT-6). Verification of the FV-1 failure mechanism is exact: the P0 baseline (17:07:27) and the P5/P9 states differ only in externally added modifications; HEAD and reflog are unchanged; the run's workspace was provisioned at 17:07:27 (before the post-session external writes) and is unaffected.

**§12.4 applicability:** the spec's concurrency provision (operator records external change with timestamps; workspace unaffected; re-baseline flagged `baseline_amended`, excluded from leaderboard) is **not executable with the frozen harness**: `REJECTED` is terminal for gates, and no CLI exists to amend the run's baseline or set `manifest.baseline_amended` (the field exists in the schema). This is a v1.1 harness capability gap, recorded as a recommendation.

---

## 7. Observational Assessment of the Submission (non-recorded; REJECTED runs are not scored)

Provided for the postmortem only — the harness recorded no scores. Verification performed directly on the frozen submission (`changes/Game.php`, `Game.diff`, and the five evidence documents):

- **Consolidation complete and structurally correct.** Four private helpers introduced: `_sendLabOutputActivated` (4 call sites: stPlayerTurn, actBuyResource, applyBeam, applyTap), `_sendMarketMilestoneNotifications` (3 call sites: actBuyResource, actSellResource, **plus a third instance the agent discovered in `resolveContractFreeSell` that the corpus did not list**), `_sendSynergyMilestoneNotifications` (2 call sites: applyBeam, applyTap), `_sendCardKeptNotification` (3 paths in actChooseKeep). `grep` audit: only 1 remaining `notifyAllPlayers('labOutputActivated'` and 1 `'cardKept'` — inside the helpers themselves. Success criteria 1–4 are met by construction.
- **Diff scale:** 218 lines removed, 119 added (−99 net lines in the modified file: 13,544 → 13,445).
- **Payload parity:** helpers reproduce the original payloads; the agent handled the observed message-variant difference ("regenerated" vs "activated") with a parameter rather than a separate block. The `cardKept` conditional keys (e.g., `discardedCards` presence) are preserved via optional parameters. A byte-level parity check (criterion 5) would require the V-check harness under a re-run.
- **Evidence documents:** all five present and substantive (reasoning: grep-based discovery method with line numbers; validation evidence: per-criterion grep counts and scan commands). Quality exceeds the stub baseline.
- **Declaration schema deviation:** `declaration.json` used `completion_status`/`self_reported_time_minutes` instead of the §3.6 required `status`/`self_reported_time`, so the intake recorded `partial` with two issues despite all seven items being present. The agent's self-assessment (45 min, all 6 criteria PASS) matches the observable submission. Recommendation: the system prompt should name the exact declaration fields (runbook/skill-adjacent note — the skill is frozen, so this goes to the prompt wording review).

---

## 8. Issues Encountered

| # | Issue | Impact | Resolution / Note |
|---|---|---|---|
| 1 | **External concurrent modification of `bga-mercurio` during the run** | G0 REJECTED; archive blocked (FV-1) | Documented with timestamps (§4); §12.4 not executable with frozen harness; see recommendations |
| 2 | `/mnt/c` (drvfs) cannot enforce read-only chmod — initial run root failed `verify_boundaries` | P3 could not start at the default sibling runs root | Relocated to `/tmp/ms10-runs` (POSIX FS; spec §4.1-compliant; matches the milestone plan's `/tmp/mvb-runs` acceptance pattern); initial run dir removed |
| 3 | `opencode export` truncates at ~64 KB (exit 0) | E1 transcript JSON truncated | Full stream preserved in `raw-response.txt`; recorded for runbook |
| 4 | `declaration.json` schema deviation (`completion_status` vs `status`) | Intake `partial` | Prompt-wording recommendation (§7) |
| 5 | Agent staged copies in `/tmp` (outside the workspace) | Minor §3.4 deviation (no artifact leaked; nothing remained) | `external_directory` policy allowed the read but the bash staging left no residue; observed for the permission-policy follow-up |

---

## 9. Observations

### Skill (BGA Senior Engineer, `migrate-notifications`)

- The skill's inlined prompt+artifacts bundle (65 KB) worked as the sole context: the agent followed the task decomposition precisely without any additional instruction (spec §3.1 constraint honored).
- The bundle's line-number references in the corpus (e.g., "line 161", "lines 2640-2661") were stale relative to the pinned reference HEAD — the agent re-located all sites by grep and succeeded (it also found a third market-milestone instance the corpus missed). Recommendation: corpus/skill references should be validated against the pinned reference HEAD, or phrased location-agnostically.
- The skill did not pin the exact `declaration.json` field names — the only schema deviation of the run.

### Harness

- MS-10B integration items worked in production on the first real run: permission policy (G1) — agent read `workspace/read/`, wrote `workspace/work/`, never touched `bga-mercurio`; `--dir` (G7) — session rooted in `work/`; environment gate (G3) — opencode 1.18.10 recorded; capability record (G6) — temperature fact in session metadata; export-after-timeout path (G5) not exercised (session completed normally).
- G0 and FV-1 performed exactly as specified and caught real drift; the short-circuit of G1/G2 after a G0 failure is correct and by design.
- **Gap:** §12.4 re-baselining is unimplemented (no pause/amend/re-gate path; `baseline_amended` flag inert). Any benchmark against a repository under concurrent development will reject.
- **Gap (v1.1):** runs root on non-POSIX filesystems (`/mnt/c`) fails boundary verification; document the POSIX requirement in the runbook.
- The harness's own operations were flawless across all executed stages (P0–P5, P8; P9 correctly blocked).

### Model (`opencode-go/deepseek-v4-pro`)

- Completed the task in 11.4 min with correct structure, discovered an undocumented third duplication instance, self-verified with grep counts, and produced complete evidence documents. No temperature control available via opencode CLI (§5.5 flagged).

---

## 10. Recommendations Before Benchmarking Additional Models

1. **Stabilize the reference repository first.** The single blocking issue for this run was external concurrent development of `bga-mercurio` (three write bursts during the ~75-minute window: 16:42, 17:23–17:24, 17:31). Options: (a) schedule benchmark windows when no other process writes to the repo; (b) run against a detached, immutable snapshot checkout used exclusively for benchmarking (workspace copy is already a snapshot; G0 would then verify the snapshot — a spec §12.4-adjacent protocol decision to be made deliberately); (c) implement the §12.4 amendment path in the harness (v1.1: pause + operator-recorded amendment + `baseline_amended` flag + leaderboard exclusion), which the frozen harness cannot do today.
2. **Implement §12.4 in v1.1** (small, well-scoped: baseline amendment CLI + manifest flag + re-gate path). Until then, `REJECTED`-on-drift is the correct but lossy outcome.
3. **Re-run NOT-02** once the reference is stable — the agent's submission here was structurally complete and would be worth a clean gate run.
4. **Fix the opencode export truncation note** in the runbook (E1 dual-capture: `raw-response.txt` is the authoritative full stream).
5. **Prompt wording review** (skill is frozen — schedule as a skill revision): pin exact `declaration.json` field names; make corpus line references location-agnostic.
6. **Runbook items from this run:** POSIX runs root required; `/tmp/mvb-runs` pattern validated; model designation via transient `settings.json` (removed after the run; model recorded in manifest/session metadata).

---

## 11. Repository Verification

1. **`bga-mercurio` remained read-only — with respect to this work.** HEAD unchanged (`25339e5`) before, during, and after the run; no commit, no staging, no file created or modified by any MS-10 operation (all commands read-only: `git status/log/rev-parse/diff`, `stat`). The working tree's pre-existing modified files (7 at P0 baseline) grew to 9 **exclusively through external processes** (timestamps 16:42:46, 17:23:55, 17:24:04, 17:31:27 — all outside the benchmark session window and none attributable to harness or agent commands, as verified from the E3 command log and E1 transcript).
2. **No benchmark logic changed** — zero tracked modifications in `bga-senior-engineer` at run time and at completion (working tree clean).
3. **No harness code changed** — none of the harness modules were edited during MS-10 (verified clean `git status`).
4. **No skill files changed** — `bga-senior-engineer-skill/` untouched.
5. **All generated artifacts exist only inside the benchmark runs directory** (`/tmp/ms10-runs/run-NOT-02-opencode-go-deepseek-v4-pro-20260801T170714Z-00`). A transient operator `settings.json` (model designation) was created outside the runs root and removed after the run; the model is recorded in the run's manifest and session metadata. The temporary session console log under the runs root was removed. The abandoned first attempt (run root on `/mnt/c`) was deleted before any session ran.

**Deliverable:** this report — `docs/evaluation/ms10-first-real-benchmark.md`.
