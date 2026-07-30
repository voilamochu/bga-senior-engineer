---
task: review-notifications
version: 1.0.0
last_updated: 2026-07-30
source: docs/ai-os/bga-senior-engineer-doctrine.md
required_rules:
  - rules/constitution.json
  - rules/notifications.json
lazy_rules:
  - rules/synchronization.json
required_examples:
  - examples/notification-example.json
required_checklists:
  - checklists/pre-review.json
max_tokens: 2650
---

# Audit the Notification System

## Prerequisites

Before proceeding, confirm the following files are loaded:
- rules/constitution.json
- rules/notifications.json
- checklists/pre-review.json

Do not continue until all files are confirmed loaded.

## Lazy-Load Rules

The following rule files are available for conditional loading. Load them only when the described situation occurs:

- rules/synchronization.json — Load when reviewing spectator or reconnect notification paths. Check SYNC-002 through SYNC-005.

## Workflow

### Step 1: Load the Canonical Example

Load examples/notification-example.json to see the standard notification pattern. Review the annotations.

### Step 2: Verify Centralized Class

All notifications must be sent through a single centralized Notifications class following NOTF-001. No notifyAllPlayers or notifyPlayer calls may exist outside this class following NOTF-010.

### Step 3: Verify One Method Per Type

Each notification type must have exactly one static method following NOTF-003. Method names describe the event in camelCase. No duplicate methods for the same notification type.

### Step 4: Check updateArgs Usage

Verify every notification uses updateArgs for automatic i18n and player name substitution following NOTF-004. All user-visible strings must be wrapped in clienttranslate following NOTF-014.

### Step 5: Verify refreshUI and refreshHand

Confirm refreshUI carries complete public state following NOTF-005. Confirm refreshHand carries per-player hidden state following NOTF-006. Confirm clearTurn resets client UI after undo following NOTF-007.

### Step 6: Check Hidden Information

Verify no notification payload leaks hidden information following NOTF-009. Card draws use the dual public/private pattern following NOTF-008. Spectator filtering is covered by SYNC-005.

If reviewing spectator or reconnect paths, load rules/synchronization.json and check SYNC-002 through SYNC-005.

### Step 7: Verify Timing

All notifications are sent after all mutations are complete and before the action returns the transition following NOTF-011.

### Step 8: Check Absolute Values

Every notification payload carries absolute state values, never deltas following NOTF-012. The client sets state from the payload without computing diffs.

## Edge Cases

- Notification called outside the Notifications class? Wrap all external notifyAllPlayers calls into the centralized class. See NOTF-010.
- Notification payload contains raw player IDs without i18n? Use updateArgs to resolve player references. See NOTF-004.
- Same notification visible to all players but contains per-player data? Split into public and private variants. See NOTF-008.
- Notification sent during mutation? Reorder: validate all, mutate all, notify all. See NOTF-011.
- Spectator receives private notification? Filter spectator notifications. See SYNC-005.
- Notification order matters after undo? Ensure clearTurn precedes refreshUI precedes refreshHand. See SYNC-006 through SYNC-008.

## Stop Conditions

This task is complete when:
- [ ] All notifications go through a centralized Notifications class
- [ ] One static method per notification type with camelCase naming
- [ ] updateArgs is used for i18n on all notifications
- [ ] refreshUI, refreshHand, and clearTurn are correctly implemented
- [ ] No hidden information leaks in any notification payload
- [ ] All notifications carry absolute values, not deltas
- [ ] Notifications are sent after mutations and before transition
- [ ] checklists/pre-review.json passes all items

## Escalation

Stop and ask the user when:

| Situation | Question to Ask |
|---|---|
| Notification carries sensitive game data | "A notification payload includes information that may reveal game state to opponents. Should I split it into public and private notifications?" |
| Notification timing is ambiguous | "A notification must be sent at a specific point in the action pipeline, but the current code sends it earlier. Should I reorder?" |
| Spectator visibility is undefined | "Some notifications may contain data that spectators should not see. Should I filter them, or is this data safe for all viewers?" |

## Self-Validation

Before declaring the task complete:

1. Run checklists/pre-review.json
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
