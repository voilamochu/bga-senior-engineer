---
task: migrate-notifications
version: 1.0.0
last_updated: 2026-07-30
source: docs/ai-os/bga-senior-engineer-doctrine.md
required_rules:
  - references/rules/constitution.json
  - references/rules/notifications.json
lazy_rules:
  - references/rules/migration.json
required_examples:
  - assets/examples/notification-example.json
required_checklists:
  - assets/checklists/pre-commit.json
max_tokens: 2650
---

# Extract a Centralized Notifications Class

## Prerequisites

Before proceeding, confirm the following files are loaded:
- references/rules/constitution.json
- references/rules/notifications.json
- assets/checklists/pre-commit.json

Do not continue until all files are confirmed loaded.

## Lazy-Load Rules

The following rule files are available for conditional loading. Load them only when the described situation occurs:

- references/rules/migration.json — Load when wrapping legacy notifyAllPlayers calls. Check MIGR-004.

## Workflow

### Step 1: Load the Canonical Example

Load assets/examples/notification-example.json to see the standard centralized notification pattern.

### Step 2: Create the Notifications Class

Create a centralized Notifications class following NOTF-001 and NOTF-002. The class exposes all notification types through static methods. No code outside this class may call notifyAllPlayers or notifyPlayer directly (NOTF-010).

### Step 3: Define Notification Methods

Create one static method per notification type following NOTF-003. Name each method by the event in camelCase: playerScored, cardDrawn, resourcesAwarded. Each method calls notifyAllPlayers or notifyPlayer internally.

### Step 4: Wire updateArgs

Use updateArgs for automatic i18n and player name substitution following NOTF-004. Every user-visible string must be wrapped in clienttranslate following NOTF-014.

### Step 5: Implement refreshUI and refreshHand

Implement refreshUI carrying complete public state following NOTF-005. Implement refreshHand carrying per-player hidden state following NOTF-006. Implement clearTurn for undo cleanup following NOTF-007.

### Step 6: Wrap Legacy Calls

Find every notifyAllPlayers and notifyPlayer call in the codebase. Replace each with a call to the corresponding Notifications static method.

If wrapping legacy calls, load references/rules/migration.json and check MIGR-004 for the wrapping pattern.

### Step 7: Verify Hidden Information

Ensure every notification payload is safe following NOTF-009. Card draws use the dual public/private notification pattern following NOTF-008. Spectators never receive private notifications following NOTF-013.

## Edge Cases

- Notifications currently sent from multiple locations? Wrap each call site. Every notifyAllPlayers call outside the Notifications class is a violation. See NOTF-010.
- Notification payload contains player IDs without i18n? Use updateArgs to resolve player IDs to player_name and player_id. See NOTF-004.
- Same notification sent to all players and individually? Create two methods: one public (notifyAllPlayers) and one private (notifyPlayer). See NOTF-008.
- Notification sent before mutation is complete? Reorder: complete all mutations, then send notifications. See NOTF-011.
- Notification uses delta values instead of absolute? Replace with absolute state values. See NOTF-012.

## Stop Conditions

This task is complete when:
- [ ] Centralized Notifications class exists with static methods
- [ ] One static method per notification type
- [ ] All legacy notifyAllPlayers and notifyPlayer calls are wrapped
- [ ] No notifyAllPlayers calls exist outside the Notifications class
- [ ] refreshUI, refreshHand, and clearTurn are implemented
- [ ] All notification payloads use absolute values with updateArgs i18n
- [ ] Hidden information is protected in all notification paths
- [ ] assets/checklists/pre-commit.json passes all items

## Escalation

Stop and ask the user when:

| Situation | Question to Ask |
|---|---|
| Notification type is unclear | "There is a notification with ambiguous purpose. Should I create a dedicated method or merge it with an existing one?" |
| Legacy notification carries hidden information | "A legacy notification sends data that may include private player information. Should I split it into public and private variants?" |
| Notification timing is uncertain | "A notification is sent mid-mutation rather than after all mutations. Should I reorder the code, or does the game logic depend on the current ordering?" |

## Self-Validation

Before declaring the task complete:

1. Run assets/checklists/pre-commit.json
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
