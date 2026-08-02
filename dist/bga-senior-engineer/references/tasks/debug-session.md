---
task: debug-session
version: 1.0.0
last_updated: 2026-07-30
source: docs/ai-os/bga-senior-engineer-doctrine.md
required_rules:
  - references/rules/constitution.json
lazy_rules:
  - references/rules/actions.json
  - references/rules/notifications.json
  - references/rules/state-machine.json
  - references/rules/architecture.json
  - references/rules/persistence.json
required_checklists:
  - assets/checklists/pre-review.json
max_tokens: 1810
---

# Systematic Debugging Workflow

## Prerequisites

Before proceeding, confirm the following files are loaded:
- references/rules/constitution.json
- assets/checklists/pre-review.json

Do not continue until all files are confirmed loaded.

## Lazy-Load Rules

The following rule files are available for conditional loading. Load them only when the described situation occurs:

- references/rules/actions.json — Load when debugging action handler behavior. Check ACTN-001 through ACTN-013.
- references/rules/notifications.json — Load when debugging notification delivery or payload. Check NOTF-001 through NOTF-014.
- references/rules/state-machine.json — Load when debugging state transitions or zombie mode. Check STAT-001 through STAT-016.
- references/rules/architecture.json — Load when debugging component boundaries or ownership. Check ARCH-001 through ARCH-022.
- references/rules/persistence.json — Load when debugging database queries or globals. Check PERS-001 through PERS-014.

## Workflow

### Step 1: Identify the Layer

Determine which layer contains the bug. Inspect the error location: server logic, notification delivery, or client rendering.

If the bug is in an action handler, load references/rules/actions.json. If the bug involves notifications, load references/rules/notifications.json. If the bug is in state transitions, load references/rules/state-machine.json. For component ownership issues, load references/rules/architecture.json. For database issues, load references/rules/persistence.json.

### Step 2: Reproduce Deterministically

Find a seed that reproduces the bug. Record the exact action sequence. Follow CORE-013: correctness first — reproduce before fixing.

### Step 3: Trace the Data Flow

Trace the data path: DB → Manager → Action → Notification → Client Handler. Identify which step produces the wrong value.

Compare the actual value against the expected value at each step. The step where the values diverge is the owning layer of the bug.

### Step 4: Fix at the Owning Layer

Apply the fix at the layer that owns the bug. Never patch a downstream layer to work around an upstream defect. Follow ARCH-015.

If the fix touches a new concern, load the relevant lazy rule file and verify compliance.

### Step 5: Verify Undo and Replay

Check that the mutation is undoable (CORE-006). Verify that notifications carry absolute values (CORE-007). Confirm zombie handling for the affected state.

If undo or replay logic is involved, load references/rules/undo-replay.json and check UNDO-001 through UNDO-005.

### Step 6: Add a Regression Test

Add a test that fails without the fix. Follow TEST-002 for Manager tests or TEST-007 for seeded RNG tests.

## Edge Cases

- Bug only occurs in production but not in studio? Check framework differences in timing, session handling, or error reporting. The root cause is likely a race condition or uninitialized state on reconnect.
- Bug manifests only during replay? Notification handlers may be executing domain logic instead of rendering payloads. See UNDO-008.
- Bug affects one player but not others? Check per-player state handling: private notifications, per-player args, and refreshHand implementation.
- Bug disappears when you add logging? This indicates a race condition or timing-dependent issue. Add assertions instead of logging to narrow the condition.
- Bug involves incorrect score? Scores are incremental (PERS-011). Check that every scoring event adds or subtracts correctly and the initial value is seeded properly.

## Stop Conditions

This task is complete when:
- [ ] The owning layer of the bug is identified
- [ ] The bug is reproduced deterministically with a recorded seed
- [ ] The fix is applied at the owning layer with no downstream patches
- [ ] Undo, replay, and zombie paths are verified for the affected code
- [ ] A regression test is added that fails without the fix
- [ ] assets/checklists/pre-review.json passes all items

## Escalation

Stop and ask the user when:

| Situation | Question to Ask |
|---|---|
| Bug occurs across multiple layers | "The error trace crosses Action, Manager, and Notification layers. Which layer should I treat as the root cause?" |
| Bug cannot be reproduced deterministically | "I cannot find a seed that reproduces this bug consistently. Do you have a specific game replay or log I can inspect?" |
| Fix would change visible game behavior | "The correct fix changes how the game displays during this scenario. Is the current or proposed behavior correct?" |
| Performance issue masquerading as a bug | "The symptom is a timeout, but the root cause is an N+1 query pattern. Should I fix the performance issue or treat this as a separate task?" |

## Self-Validation

Before declaring the task complete:

1. Run assets/checklists/pre-review.json
   - Load the checklist file if not already loaded
   - Verify every item passes
   - If any item fails, fix the violation and re-run

2. Verify each stop condition is met
   - Re-read the Stop Conditions section
   - Confirm every condition passes

3. If any lazy rules were loaded:
   - Verify the triggered concerns are correctly addressed
   - Confirm no rule violations were introduced

4. Re-read the modified files
   - Verify no debugging artifacts remain
   - Verify naming is consistent with codebase conventions

Do not declare the task complete until all validation steps pass.
