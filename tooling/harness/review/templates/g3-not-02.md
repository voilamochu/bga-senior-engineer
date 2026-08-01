# G3 Scenario Script — NOT-02 (Consolidate Duplicated Notification Blocks)

Run: {{RUN_ID}}

Execute every step against the **frozen diff bundle** (`evidence/e8-diff-bundle/`)
and the frozen evidence in `evidence/` — never against the reference
repository.  Record the result of each step in the G3 Findings section
of `manual-review.md`.  Where the environment cannot fully simulate BGA,
record the substitution per evaluation spec §2.1.

Evidence inputs:

- `evidence/e8-diff-bundle/modules/php/Game.php` — the submission's changed Game.php
- `evidence/e2/subsystems.md` — the modified subsystems inventory
- `evidence/e1-transcript.txt` — the session transcript
- `evidence/e8-diff-bundle/*gamelog*` — a gamelog, when present

## Step 1 — Single-source verification (payload/type/recipient parity)

1. In the diff bundle's `modules/php/Game.php`, locate the consolidation
   helpers for `labOutputActivated`, the market milestone switch, the
   synergy milestone switch, and `cardKept`.
2. Verify each consolidated notification type is sent from exactly one
   method (corpus NOT-02 evaluation criteria).
3. For each helper, compare the payload against the reference behavior
   described in the corpus task entry: identical payload keys, message
   strings, recipients (`notifyAllPlayers` vs `notifyPlayer`), and
   `_private` handling.
4. Record: per-type parity PASS/FAIL with the payload keys compared.

## Step 2 — Notification ordering review

1. In each changed handler, verify state persistence precedes
   notification dispatch and all notifications are dispatched before
   `nextState()` (NOTF-011; DBG-01 context).
2. Record: per-handler ordering PASS/FAIL.

## Step 3 — Duplicate-block audit

1. Scan the changed Game.php for any remaining duplicated notification
   construction blocks (identical payload construction in more than one
   place).
2. Record: audit PASS/FAIL with the locations checked.

## Step 4 — Gamelog diff (when a gamelog is available)

1. If `evidence/e8-diff-bundle/` contains a gamelog, diff the
   pre-consolidation and post-consolidation notification sequences for
   type, payload, and recipient parity.
2. If no gamelog is available, record the substitution (evaluation spec
   §2.1) — parity was reviewed statically in Steps 1-3.

## Recording

For every step, enter the finding in the G3 Findings section of
`manual-review.md` with the evidence paths examined.
