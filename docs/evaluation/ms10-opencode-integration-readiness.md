# MS-10A — OpenCode Integration Readiness

**Date:** 2026-08-01
**Status:** Planning and integration analysis only — no harness modification, no benchmark execution, no agent invocation.
**Scope:** Exact integration design between OpenCode, the Benchmark Harness, and the BGA Senior Engineer Skill for the first real benchmark run (MS-10 / MVB-027, task NOT-02).
**Predecessor:** R2.6 gate verdict — READY FOR MS-10 WITH MINOR ISSUES (docs/evaluation/harness-release-candidate-review-r2.md).
**Baseline:** Commit `a71ea5a` (MS-09.5 complete, Harness v1.0 candidate). Working tree clean.

---

## 1. Summary

The harness is structurally complete: the OpenCode adapter implements the full launch/export/capture contract, and the R2.6 gate verified the CLI surface and flags against the installed opencode 1.18.10. What has never happened is a **real end-to-end session**: the adapter has zero automated coverage, and one integration detail — the headless permission environment of `opencode run` — is very likely to prevent the agent from reading the reference material (`workspace/read/`) on the first real run.

Twelve investigation questions are answered in §2; the integration map is §3; eleven gaps with severity, estimates, and solutions are §4. Required-before-first-run work totals ≈ 6.5–8h, all confined to the adapter/config/test layer inside `bga-senior-engineer`. The benchmark logic, the harness pipeline (P0–P9), and the skill are untouched.

**Final recommendation: READY AFTER MINOR INTEGRATION** (justification in §5).

---

## 2. Investigation — How the Integration Works Today

### 2.1 How the harness launches OpenCode

`OpenCodeAdapter.launch` (tooling/harness/agent/opencode_adapter.py:61) is invoked by `run_session` (tooling/harness/agent/runtime.py:97) via the `session` CLI subcommand (tooling/harness/cli.py:540). The launch sequence is:

1. `opencode --version` preflight through the run's `CommandLog` (opencode_adapter.py:65–68) — records `platform_version`.
2. `opencode run --format json <bundle-text>` executed as a subprocess with `cwd = runs/<id>/workspace/work` and a corrected `PWD` env (opencode_adapter.py:70–80; util/proc.py:83–90).
3. Session ID parsed from the first JSON event of the NDJSON stream (opencode_adapter.py:40–53).
4. `opencode export <session-id>` for the transcript (opencode_adapter.py:86–90).

Every one of these commands is executed through `CommandLog.run` → `run_cmd`, so each produces an E3 record in `protocol/command.log` (command, stdout, stderr, exit code, wall time) (util/proc.py:48–120).

Pre-flight boundary verification runs before launch: `workspace/read/` read-only, `workspace/work/` writable, prompt bundle present (runtime.py:66–94, `verify_boundaries`; CLI `session --dry-run` reuses it, cli.py:565–578).

### 2.2 Which OpenCode command line is used

```
opencode run --format json <message>        # message = the entire prompt bundle text
opencode run --format json --model <provider/model> <message>   # when settings "model" is set
opencode --version                          # preflight
opencode export <session-id>                # transcript
```

- `--format json` emits NDJSON events on stdout (session ID lives in the first event).
- `--model` comes from the optional settings `model` key (config.py:102–110, `launch_model`).
- No `--auto`, no `--agent`, no `--dir`, no temperature/variant flags are passed today.
- Verified against installed opencode 1.18.10 (`opencode run --help`, `opencode export --help`) and the opencode CLI docs: all flags used are valid.

### 2.3 How the prompt bundle is supplied

The bundle is built at P2 and written to `protocol/prompt-bundle.txt`, made read-only and hashed into the manifest (prompt/bundle.py:140–157; cli.py:429–519). Its three sections are SYSTEM PROMPT, BENCHMARK PROMPT, SKILL ARTIFACTS (bundle.py:20–26, 108–129). At session time the adapter reads the file and passes it as the **single `message` argv** to `opencode run` (opencode_adapter.py:63, 73).

Sizing: the NOT-02 skill artifact set is ~80KB (6 files) plus the two prompt sections — a total bundle of ≈ 85–90KB, comfortably inside Linux `ARG_MAX` (~2MB) and opencode's message limits. Feasible; see G8 for robustness caveats.

### 2.4 How the skill is supplied

The skill is **inlined as text** into the bundle, not installed as an opencode skill/agent:

- P2 loads and validates the frozen skill package (`load_skill`, skill/loader.py:214–245) and resolves the benchmark task NOT-02 → skill task `migrate-notifications` (loader.py:427–429; cli.py:522–537).
- The task's artifact bundle — prompt, mandatory rules, lazy-rule declarations, checklists, examples, references, phase groups — is rendered verbatim into the SKILL ARTIFACTS section (bundle.py:41–97).
- The skill package is also copied into `workspace/read/bga-senior-engineer-skill/` at P0 (workspace/provision.py:54–64, 134–138), so the agent can read the raw files.

This matches the harness spec §3.1 session composition (system prompt + benchmark prompt + attached documents) and §3.3; the bundle is the complete, immutable context.

### 2.5 How OpenCode receives the writable workspace

- The session subprocess runs with `cwd = runs/<id>/workspace/work` and `env["PWD"]` aligned to it (opencode_adapter.py:33–37, 75–80). `work/` is the only writable path; it starts empty and the agent's submission is written there (spec §3.4, §3.6).
- The project root opencode resolves from the launch directory is therefore `workspace/work`, so in-editor edits inside `work/` fall under opencode's permissive defaults — **but every other directory the agent must read (`workspace/read/`, the docs, the skill) is outside that project root** (see G1).

### 2.6 How completion is detected

Process-level: exit code 0 of `opencode run` ⇒ session completed; non-zero ⇒ `ABORTED`; harness timeout kill (−1) ⇒ `TIMEOUT` (runtime.py:174–196; adapter.py:17–18, 57–58). Submission-level: the agent "declares completion" by producing the §3.6 submission manifest in `work/` (intake, §2.11), with `declaration.json` `complete`/`partial` (§5.1 no-retry, partial submission path is exercised by the failing synthetic variant).

### 2.7 How stdout/stderr are captured

`run_cmd` pipes and captures both streams for every harness-side command (util/proc.py:83–107). For the session: `raw_response = stdout + stderr` concatenated (opencode_adapter.py:81) persisted as `protocol/session/raw-response.txt`; stderr is normally minimal since `--print-logs` is not used (capture.py:84–88). The full E3 record (stdout, stderr, exit code, wall time) is appended to `protocol/command.log`.

### 2.8 How session transcripts are captured

`opencode export <session-id>` stdout is persisted as `protocol/session/transcript.txt` (opencode_adapter.py:84–92; capture.py:78–82) and becomes evidence **E1** (`e1-transcript.txt`, evidence/collect.py:138–151). Caveats: export is skipped on timeout (G5), and E6/E7 (token/cost) data present in the export JSON is not extracted as separate evidence (NIT, recorded in G9).

### 2.9 How timeout handling works

`run_cmd` uses `subprocess.Popen.communicate(timeout=…)`: on expiry it sends SIGTERM, allows a 5s grace for the platform to flush, then SIGKILL; the partial stdout/stderr are retained and the exit code is −1 (util/proc.py:92–103; `TIMEOUT_EXIT_CODE`). The runtime maps −1 to `TIMEOUT` status, records the reason, and proceeds to evidence/evaluation of the partial submission (runtime.py:174–181). The P3 budget is `effort × 1.5` rounded up to the hour, min 2h, max 16h (runtime.py:50–63), or the operator `--timeout-min` override; it is derived from the corpus task effort at session start (cli.py:592–602).

### 2.10 How retry handling works

Per spec §5.1: **no automatic P3 retries** — a failed session ends `ABORTED` with evidence retained; a new run (new run ID) is required (runtime.py:182–189). Launch/platform failures end `BLOCKED` with a recorded reason and the run may be re-attempted after the cause is fixed (runtime.py:164–171; cli.py:300–306). An interrupted mid-session run (status `RUNNING`) resumes P3 from the start with a recorded restart (runtime.py:124–130; §5.3). Environment failures are retryable at P1 (BLOCKED → re-`prepare`). Gate tooling malfunctions are re-runnable. All attempts preserve evidence (spec §5.1, §6.3).

### 2.11 How the produced submission is discovered

At session end, `capture_intake` scans `workspace/work` for the five evidence documents, `changes/`, and `declaration.json`; it validates the declaration and records found/missing/status in `protocol/session/intake.json` (intake.py:33–80; capture.py:121–129), and the manifest records `submission_status`. At P4 the entire `work/` tree is copied to evidence **E2** (evidence/collect.py:158–161). The submission is the only evaluated artifact (spec §5.5).

### 2.12 Does the current harness already support everything needed?

Structure: **yes** — every launch/export/capture/timeout/intake path exists, is spec-traced, and the R2.6 gate ran 868 green tests with both synthetic variants `ARCHIVED`.

Reality: **not quite** — the OpenCode adapter has never run a real session and has **no automated test** (`test_stub_adapter.py` exists; no `test_opencode_adapter.py`). R2.6 row 15 validated flags against `--help` output only. One concrete, likely-fatal-at-runtime issue exists (G1, headless permissions), plus smaller fidelity/conformance gaps (G3, G5, G6) and one missing MS-10 deliverable (G4, the `run` one-shot).

---

## 3. Integration Map (OpenCode ↔ Harness ↔ Skill)

```
operator:  python -m tooling.harness session <run-id> [--platform opencode] [--timeout-min N]
             │
harness P2:  protocol/prompt-bundle.txt          ← skill artifacts (SKILL ARTIFACTS §)
             │                                      inlined verbatim (bundle.py:41–97)
             │
harness P3:  OpenCodeAdapter.launch
             ├─ opencode --version                      → session.json platform_version
             ├─ opencode run --format json <bundle>     → NDJSON stdout (raw response + session id)
             │      cwd = runs/<id>/workspace/work      → writable work/, read-only read/
             │      env PWD aligned; no --dir, no --auto, no permission config  [G1, G7]
             ├─ timeout ⇒ SIGTERM→SIGKILL, exit −1      → TIMEOUT (partial work retained)
             └─ opencode export <session-id>            → transcript.txt → E1          [G5]
             │
harness P3.5: intake of work/ (5 docs + changes/ + declaration.json) → intake.json  [§2.11]
harness P4:  E1 transcript, E2 work/ copy, E3 command.log, E11 environment.json      [G3]
harness P5–P9: gates, review, scoring, reports, archive (unchanged; MS-10 runbook)
```

---

## 4. Gap Analysis

### G1 — No run-scoped OpenCode permission configuration (**HIGH**, ~3h)

- **Description.** The session launches with opencode's default permissions (opencode docs, /docs/permissions/): most tools default `allow`, but **`external_directory` defaults to `ask`**. In headless `opencode run` there is no UI to answer the prompt, so the request is denied. The agent's project root is `workspace/work` (§2.5), so **everything the agent must read — `workspace/read/bga-mercurio/`, `docs/`, the skill, `tooling/` — is external**, and would be blocked on the first real run. The repo-root `opencode.json` (which grants external_directory reads of `bga-mercurio`) is **not** picked up: config discovery walks up from the launch directory (`work/` → `workspace/` → `runs/` → `CascadeProjects/`), and `bga-senior-engineer/` is not an ancestor. The behavior must be confirmed in a smoke session, but the risk is material.
- **Severity.** HIGH — would invalidate or abort the acceptance run (agent cannot access reference material; or, with a wrongly broad config, the read-only boundary is unenforced).
- **Estimate.** ~3h.
- **Recommended solution.** In `OpenCodeAdapter.launch`, set the documented `OPENCODE_PERMISSION` env var (inline JSON, no file write into the agent's workspace) — or `OPENCODE_CONFIG_CONTENT` — with a run-scoped policy: `external_directory` `allow` for `workspace/read/**` (and `workspace/work/**`), `deny` for `bga-mercurio/**` and any harness paths outside the run; `edit` `allow` under `work/**` only, `deny` elsewhere; `webfetch`/`websearch` `deny` (enforces the recorded `network: disabled` at the platform level, closing part of FUT-07); `bash` git-write commands on `bga-mercurio` denied. Derive the patterns from `SessionConfig.workspace_read`/`workspace_work` so they are run-relative. Verify with a stub-LLM smoke session before MVB-027.

### G2 — OpenCode adapter has no automated coverage; first real session unexercised (**MEDIUM**, ~3h)

- **Description.** No test file exercises `OpenCodeAdapter` (argv construction, session-id parsing, export invocation, timeout retention, permission env). R2.6 verified flags via `--help` only; the entire P2/P3 opencode path is covered only by the stub adapter.
- **Severity.** MEDIUM — no known defect, but the riskiest untested path in the pipeline is the one the acceptance run depends on (mitigated by G1's smoke requirement).
- **Estimate.** ~3h.
- **Recommended solution.** Hermetic test: install a fake `opencode` shell script earlier in PATH (or an env override for the binary path) returning canned NDJSON for `run` and JSON for `export`, then assert `SessionResult` fields, E3 records, timeout behavior (−1 exit), and that the permission env was applied. No network, no LLM, deterministic.

### G3 — `opencode` absent from the P1 environment gates and `environment.json` (**MEDIUM**, ~1h)

- **Description.** Harness spec §4.2/§4.5: tools used in a run MUST be recorded in the environment manifest. `opencode --version` is captured at session time and recorded in `session.json` only (opencode_adapter.py:65–68); a missing/broken opencode surfaces as `BLOCKED` at P3 instead of a P1 gate (environment/collect.py:49–54 lists only python3, php, node, git).
- **Severity.** MEDIUM — conformance gap (§4.5 "additional tool … MUST be recorded") and a P3-stage surprise for operators.
- **Estimate.** ~1h.
- **Recommended solution.** Add an `opencode` ToolSpec (version `--version`, no min version) to `REQUIRED_TOOLS` so it lands in `environment.json` and a missing platform blocks at `prepare`, not at session start. Optional: gate on presence only (version is informative).

### G4 — MS-10 `run` one-shot orchestrator command missing (**MEDIUM**, ~2–4h)

- **Description.** The MS-10 milestone plan lists the deliverable `python -m tooling.harness run --task NOT-02 --model <id> [--runs-root PATH]` (implementation-milestones.md:527). The CLI has nine stage commands (`init`, `prepare`, `prompt`, `session`, `collect`, `gates`, `review`, `score`, `report`, `archive`) but no `run` orchestrator (cli.py:78–273). MVB-026 (runbook) must document the sequence; P6/P7 are human-in-the-loop so a one-shot cannot run P0–P9 unattended.
- **Severity.** MEDIUM — missing documented deliverable; does not block the pipeline (commands can be chained), but MVB-028's second-operator acceptance (#5) needs a single documented path.
- **Estimate.** ~2h for a runbook-only sequence; ~4h to add a `run` subcommand that chains init→prepare→prompt→session and prints the next human step.
- **Recommended solution.** Fold into the MS-10 runbook work (MVB-026): document the exact 9-command sequence with expected outputs and failure mappings (§5.1–5.3), and add the `run` wrapper for P0–P3 with a clear "stop for P6/P7 evaluation" contract. No change to benchmark logic.

### G5 — Transcript export skipped on timeout (**MEDIUM**, ~1h)

- **Description.** `opencode export` runs only when `exit_code >= 0` (opencode_adapter.py:84–85); on timeout (exit −1) the transcript is skipped even though opencode persists the session DB and export would succeed after the kill. A TIMEOUT run — which the spec explicitly routes into partial-submission evaluation — would lack E1 (evidence/collect.py:138–151 records `absent` with a reason).
- **Severity.** MEDIUM — evidence fidelity for the TIMEOUT path (one of MS-04's explicit design goals, spec §5.2/§6.1).
- **Estimate.** ~1h.
- **Recommended solution.** Attempt export whenever a session ID was parsed, independent of the exit code; on export failure, keep the current recorded-absent behavior. Covered by the G2 hermetic test.

### G6 — Temperature/section-5.5 fact not recorded (**MEDIUM**, ~0.5h)

- **Description.** Spec §5.5 requires temperature 0 or "the platform's lowest supported value", with the fact recorded when unsupported. The opencode CLI exposes `--variant` (reasoning effort) but **no temperature flag**; the adapter records only `launch_model` (opencode_adapter.py:96–108).
- **Severity.** MEDIUM (spec-mandated record) / LOW (run still valid, flagged for comparability).
- **Estimate.** ~0.5h.
- **Recommended solution.** Record in `session.json`/manifest: `temperature: {"supported": false, "note": "opencode CLI does not expose temperature; --variant available"}` and use the §5.5 flagged-for-comparability status in the runbook.

### G7 — PWD-assumption vs the explicit `--dir` flag (**LOW**, ~0.5h)

- **Description.** The adapter aligns `PWD` env to the launch directory based on an implementation-time assumption that opencode prefers `PWD` over the process cwd (opencode_adapter.py:13–16, 33–37). opencode's documented, version-stable mechanism is the `run --dir` flag (CLI docs).
- **Severity.** LOW — both cwd and PWD are currently consistent, so behavior is correct today; robustness against opencode version changes.
- **Estimate.** ~0.5h.
- **Recommended solution.** Pass `--dir <workspace_work>` explicitly (and keep the PWD alignment as defense-in-depth). Verify in the G2 test.

### G8 — Prompt bundle delivered as a single argv message (**LOW**, ~1h if changed)

- **Description.** The ~86KB bundle is one argv element (opencode_adapter.py:73). Fine on Linux (ARG_MAX ≈ 2MB) but brittle as skill artifacts grow and on other platforms; also places the full bundle in the process environment surface.
- **Severity.** LOW — works today.
- **Recommended solution.** Keep as-is for MS-10 (documented), or switch to `opencode run --file <bundle-path>` / stdin delivery in a post-MVB follow-up. No benchmark-logic impact.

### G9 — E3 scope and raw-response memory (NIT, ~0.5h)

- **Description.** (a) E3 captures harness-side commands only; agent-side tool calls live in E1 (transcript), not E3 — correct per the spec's "per-command wrapper logging" (the wrapper is harness-side) but should be stated in the runbook. (b) The full JSON event stream is buffered in memory for the session duration (raw_response, util/proc.py:83–107); for a 16h session this can grow large. (c) E6/E7 token/cost data present in the export JSON is not extracted into separate evidence (optional artifacts, currently `absent`).
- **Severity.** NIT — no correctness impact.
- **Estimated.** ~0.5h for (b) streaming to a file; (a) and (c) are runbook notes.
- **Recommended solution.** Runbook notes; stream stdout to the raw-response file in a follow-up.

### G10 — Operator configuration undocumented; no settings file (**LOW**, ~1h)

- **Description.** No `tooling/harness/settings.json` exists; `platform`, `model`, `network`, `runs_root`, and `block_on_tool_version_mismatch` are read from an optional settings file only (config.py:72–110, 118–141). R2.6 MEDIUM-4 (documented configuration) is still open.
- **Severity.** LOW — MS-10-scoped documentation work.
- **Estimated.** ~1h.
- **Recommended solution.** MS-10 runbook section documenting `settings.json` keys and defaults; optionally add env-var fallbacks for `model`/`platform`.

### G11 — Network policy recorded but not platform-enforced (LOW, part of G1)

- **Description.** `network: disabled` is recorded in `environment.json`/manifest but not enforced (known FUT-07, acknowledged in R2.6).
- **Severity.** LOW — accepted gap; the §3.5 policy relies on the prompt.
- **Estimated.** 0h beyond G1.
- **Recommended solution.** G1's run-scoped permission config denies `webfetch`/`websearch`, giving partial platform-level enforcement at no extra cost.

### Pre-existing (not MS-10A scope, per R2.6)

- MEDIUM-1 (latent flaky test), MEDIUM-2 (stale E4/E9 wording), MEDIUM-3 (double-scoring workflow), MEDIUM-4 (config docs — see G10), MEDIUM-5 (runbook — the MS-10 deliverable), LOW-1…5, NIT-1…8: all registered in the R2.6 finding register; none block launch integration; MEDIUM-1/2/3/4 are folded into MS-10 runbook work per R2.6's recommendation. P2's 15-minute budget is recorded but not separately enforced (a single subprocess spans init+execution; the P3 budget governs) — documented behavior, runbook note.

---

## 5. Final Recommendation

### READY AFTER MINOR INTEGRATION

Required before the first real run (acceptance-run blocking):

| # | Item | Effort |
|---|---|---|
| G1 | Run-scoped opencode permission config (`OPENCODE_PERMISSION`/`OPENCODE_CONFIG_CONTENT`) + smoke verification | ~3h |
| G2 | Hermetic adapter test (fake `opencode` shim) | ~3h |
| G5 | Export transcript on timeout | ~1h |
| G3, G6, G7 | opencode in P1 gates/environment.json; temperature fact; `--dir` | ~2h |
| **Subtotal** | | **~7–9h** |

Deferrable to MS-10 runbook work (MVB-026): G4 (`run` one-shot / documented command chain), G8, G9, G10, G11, and the R2.6 MEDIUM-1…4 items.

**Why not "READY TO EXECUTE":** G1 is unverified and likely-fatal at runtime (headless permission denial would block the agent's access to `workspace/read/` on the very first session), and no real opencode session has ever been exercised. Executing the acceptance run as-is risks a wasted 16h+ session.

**Why not "ADDITIONAL IMPLEMENTATION REQUIRED":** the harness is structurally complete (R2.6: READY FOR MS-10 WITH MINOR ISSUES; 868 tests, both synthetic variants `ARCHIVED`, byte-determinism proven). Every gap is a small, isolated adapter/config/test addition inside `bga-senior-engineer`; none touches the benchmark logic, the pipeline, or the skill; total effort is under one day of engineering before the acceptance run, with the runbook being an MS-10 deliverable anyway.

**Constraints honored:** harness implementation frozen (no changes made in this milestone); no benchmark executed; no real agent invoked; all work above (when MS-10B proceeds) stays inside `bga-senior-engineer`.

---

## 6. Repository Verification

- **`bga-mercurio` remained read-only.** HEAD unchanged at `25339e5` (docs: m15 investigation reconciliation). Working tree contains exactly the same 6 pre-existing modified files recorded in R2.6 §7 (`automation/state.md`, `data/contracts.json`, `modules/php/Game.php`, `modules/php/data/contracts.php`, `ops/09-historical-records/qualifications/07-operational-readiness-review.md`, `states.inc.php`) — verified by name and by working-tree hash; no new files, no commits, no git metadata changes.
- **No benchmark execution occurred.** No runs root exists at the sibling `runs/` location (§4.1); no run directories or `/var/tmp` run artifacts were created; the only non-read-only repository activity in this session is the report file itself.
- **No benchmark results were generated.** No evidence, validation, scores, reports, registry, or leaderboard artifacts were produced or modified.
- **No real agent was invoked.** No `opencode run` session was started (only `opencode --version` and `opencode run --help`, which are inert). No LLM call, no provider authentication, no network use. All commands executed were read-only inspection (git status/log/rev-parse, ls, file reads, skill-file sizing via Python imports).
- **Deliverable.** This report is the only file created: `docs/evaluation/ms10-opencode-integration-readiness.md`.
